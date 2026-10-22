<?php
declare(strict_types=1);

// Always restore a disposable database; never run fixture suites on the operator's database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/test_data_cleanup_service.php';
require_once __DIR__ . '/../includes/handover_service.php';
$codeFingerprint=handover_code_hash();
$options = getopt('', ['backup:', 'report-dir:', 'suite:']);
$backup = realpath($options['backup'] ?? '') ?: '';
$directory = realpath($options['report-dir'] ?? '') ?: '';
$root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
if (!$backup || !is_dir($directory) || str_starts_with(strtolower(str_replace('\\', '/', $directory) . '/'), strtolower($root . '/'))) {
    throw new RuntimeException('Supply a backup and a private report directory outside the web root.');
}
$metadata = json_decode((string)file_get_contents($backup . '.json'), true, 512, JSON_THROW_ON_ERROR);
if (empty($metadata['restore_verified']) || !hash_equals($metadata['sha256'], hash_file('sha256', $backup))) {
    throw new RuntimeException('A verified backup is required.');
}
$pdo = db();
$before = cleanup_snapshot($pdo);
if ($metadata['database'] !== $before['database'] || $metadata['fingerprints'] !== cleanup_fingerprints($before)) {
    throw new RuntimeException('Backup no longer matches current application data.');
}
$suites = ['B', 'C', 'D', 'E', 'F', 'G1', 'G2', 'H', 'I', 'School', 'Period', 'Organization', 'Grading', 'Selection', 'Enrollment', 'Handover'];
if (isset($options['suite'])) {
    if (!in_array($options['suite'], $suites, true)) throw new RuntimeException('Unknown suite.');
    $suites = [$options['suite']];
}
$environment = getenv();
$environment['MYSQL_PWD'] = getenv('DB_PASS') ?: '';
$report = ['started_at' => date(DATE_ATOM), 'application_database' => $before['database'], 'suites' => []];
$failed = false;
foreach ($suites as $suite) {
    $clone = 'ts10_workflow_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
    $created = false;
    try {
        $pdo->exec("CREATE DATABASE `$clone` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $created = true;
        $command = ['C:/xampp/mysql/bin/mysql.exe', '--host=' . (getenv('DB_HOST') ?: '127.0.0.1'), '--port=' . (getenv('DB_PORT') ?: '3306'), '--user=' . (getenv('DB_USER') ?: 'root'), '--default-character-set=utf8mb4', $clone];
        $restoreLog = $directory . '/restore_' . $suite . '.log';
        $process = proc_open($command, [0 => ['file', $backup, 'r'], 1 => ['file', $restoreLog, 'w'], 2 => ['redirect', 1]], $pipes, null, $environment);
        if (!is_resource($process) || proc_close($process) !== 0) throw new RuntimeException('Restore failed.');
        $childEnvironment = $environment;
        $childEnvironment['DB_NAME'] = $clone;
        $log = $directory . '/workflow_' . $suite . '.log';
        $process = proc_open([PHP_BINARY, '-d', 'output_buffering=65536', __DIR__ . '/batch' . $suite . '_runtime.php'], [0 => ['pipe', 'r'], 1 => ['file', $log, 'w'], 2 => ['redirect', 1]], $pipes, dirname(__DIR__), $childEnvironment);
        if (!is_resource($process)) throw new RuntimeException('Could not launch workflow checks.');
        fclose($pipes[0]);
        $code = proc_close($process);
        $output = (string)file_get_contents($log);
        preg_match_all('/^(?:PASS|FAIL|BLOCKED) TEST [0-9]+:[^\r\n]*/m', $output, $matches);
        $issues = array_values(array_filter($matches[0], static fn($line) => preg_match('/^(FAIL|BLOCKED)/', $line)));
        $passed = count(array_filter($matches[0], static fn($line) => str_starts_with($line, 'PASS')));
        $ok = $code === 0 && !$issues && $passed > 0;
        $report['suites'][$suite] = ['passed' => $passed, 'issues' => $issues, 'exit_code' => $code, 'ok' => $ok, 'log' => $log];
        echo $suite, ': ', $ok ? 'PASS' : 'NEEDS ATTENTION', ' (', $passed, ' checks)', PHP_EOL;
        foreach ($issues as $issue) echo $issue, PHP_EOL;
        if (!$ok) $failed = true;
    } finally {
        if ($created && preg_match('/^ts10_workflow_[0-9]{14}_[a-f0-9]{8}$/', $clone)) $pdo->exec("DROP DATABASE `$clone`");
    }
}
$unchanged = cleanup_fingerprints(cleanup_snapshot($pdo)) === cleanup_fingerprints($before);
$report['application_data_unchanged'] = $unchanged;
$report['code_fingerprint']=$codeFingerprint;
$report['database_fingerprints']=cleanup_fingerprints($before);
$report['code_unchanged']=hash_equals($codeFingerprint,handover_code_hash());
$report['completed_at'] = date(DATE_ATOM);
file_put_contents($directory . '/workflow_report' . (isset($options['suite']) ? '_' . $options['suite'] : '') . '.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
if (!$unchanged) throw new RuntimeException('Application data changed during verification.');
if(!$report['code_unchanged'])throw new RuntimeException('Code changed during verification; rerun with the final sources.');
echo 'PASS: every application row remains unchanged; disposable databases removed.', PHP_EOL;
exit($failed ? 1 : 0);
