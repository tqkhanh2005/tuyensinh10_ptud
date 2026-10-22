<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../includes/db.php';
$directory = $argv[1] ?? '';
if (!$directory || !is_dir($directory)) throw new RuntimeException('Supply an existing backup directory outside the web root.');
$directory = realpath($directory);
$root = realpath(dirname(__DIR__));
if (str_starts_with(strtolower(str_replace('\\', '/', $directory)) . '/', strtolower(str_replace('\\', '/', $root)) . '/')) {
    throw new RuntimeException('Database backups must be stored outside this web project.');
}
$pdo = db();
$name = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) throw new RuntimeException('Invalid schema name');
$label = $argv[2] ?? 'before_cleanup';
if (!in_array($label, ['before_cleanup', 'after_hcm_import', 'before_workflow_checks', 'before_teacher_workflow', 'after_teacher_workflow', 'after_period_configuration', 'after_exam_organization', 'after_exam_grading', 'after_admission_selection', 'after_enrollment_confirmation'], true)) throw new RuntimeException('Unsupported backup label');
$file = $directory . '/' . $name . '_' . $label . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.sql';
$env = getenv();
$env['MYSQL_PWD'] = getenv('DB_PASS') ?: '';
$command = ['C:/xampp/mysql/bin/mysqldump.exe', '--host=' . (getenv('DB_HOST') ?: '127.0.0.1'), '--port=' . (getenv('DB_PORT') ?: '3306'), '--user=' . (getenv('DB_USER') ?: 'root'), '--single-transaction', '--routines', '--triggers', '--events', '--hex-blob', '--default-character-set=utf8mb4', '--result-file=' . $file, $name];
$process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
if (!is_resource($process)) throw new RuntimeException('Could not start database backup.');
fclose($pipes[0]);
$output = stream_get_contents($pipes[1]);
fclose($pipes[1]);
$error = stream_get_contents($pipes[2]);
fclose($pipes[2]);
$status = proc_close($process);
if ($status !== 0 || !is_file($file) || filesize($file) === 0) throw new RuntimeException('Backup failed: ' . $error);
$counts = [];
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) $counts[$table] = (int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
$metadata = ['database' => $name, 'created_at' => date(DATE_ATOM), 'file' => $file, 'bytes' => filesize($file), 'sha256' => hash_file('sha256', $file), 'table_counts' => $counts];
file_put_contents($file . '.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
