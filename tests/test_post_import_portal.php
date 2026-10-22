<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$options = getopt('', ['base-url:', 'session-dir:', 'report:']);
$base = rtrim($options['base-url'] ?? '', '/');
if (!preg_match('#^http://(?:127\.0\.0\.1|localhost)(?::[0-9]+)?(?:/[a-zA-Z0-9_-]+)?$#', $base)) throw new RuntimeException('Only a local application URL is accepted.');
$sessions = realpath($options['session-dir'] ?? '') ?: '';
if (!is_dir($sessions)) throw new RuntimeException('A dedicated session directory is required.');
ini_set('session.save_path', $sessions);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/test_data_cleanup_service.php';
require_once __DIR__ . '/../includes/admission_readiness_service.php';
require_once __DIR__ . '/../includes/school_admission_service.php';
require_once __DIR__ . '/../includes/request_path_policy.php';
require_once __DIR__ . '/../includes/handover_service.php';
$pdo = db(); $before = cleanup_snapshot($pdo); $checks = []; $sessionFiles = [];
$assert = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) throw new RuntimeException('FAIL: ' . $label);
    $checks[] = $label; echo 'PASS: ', $label, PHP_EOL;
};
$makeSession = static function (array $user) use ($pdo, $sessions, &$sessionFiles): string {
    $statement = $pdo->prepare('SELECT r.code FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.active=1 ORDER BY r.id');
    $statement->execute([$user['id']]); $roles = $statement->fetchAll(PDO::FETCH_COLUMN);
    if (!$roles) throw new RuntimeException('No active roles.');
    $id = bin2hex(random_bytes(20));
    $data = ['auth'=>['user_id'=>(int)$user['id'],'id'=>(int)$user['id'],'username'=>$user['username'],'full_name'=>$user['full_name'],'roles'=>$roles,'role'=>$roles[0]],'csrf_token'=>bin2hex(random_bytes(32))];
    $serialized = ''; foreach ($data as $key=>$value) $serialized .= $key . '|' . serialize($value);
    $file = $sessions . '/sess_' . $id; file_put_contents($file, $serialized); $sessionFiles[] = $file;
    return $id;
};
$request = static function (string $path, ?string $session = null) use ($base): array {
    $curl = curl_init($base . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false]);
    if ($session) curl_setopt($curl, CURLOPT_COOKIE, 'PHPSESSID=' . $session);
    $raw = curl_exec($curl);
    if ($raw === false) throw new RuntimeException(curl_error($curl));
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE); $size = (int)curl_getinfo($curl, CURLINFO_HEADER_SIZE); curl_close($curl);
    return ['status'=>$status,'headers'=>substr($raw,0,$size),'body'=>substr($raw,$size)];
};
try {
    $admin = $pdo->query('SELECT u.* FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE u.active=1 AND r.code IN ("admin","so_gdtd") ORDER BY u.id LIMIT 1')->fetch();
    $student = $pdo->query('SELECT u.*,c.full_name AS candidate_name,c.national_id FROM users u JOIN candidates c ON c.user_id=u.id WHERE u.active=1 ORDER BY c.id LIMIT 1')->fetch();
    if (!$admin || !$student) throw new RuntimeException('Existing admin and student accounts required.');
    $adminSession = $makeSession($admin); $studentSession = $makeSession($student);
    $readiness = admission_readiness($pdo);
    $assert(count($readiness['steps']) === 8, 'Setup indicators cover eight workflow stages');
    if (!$readiness['period']) $assert(!$readiness['steps'][3]['available'] && !$readiness['steps'][4]['available'] && !$readiness['steps'][7]['available'], 'No period, quota or publication is invented');
    $schools = school_list($pdo, 'THCS_NGUYEN_DU', 'THCS');
    $assert(count($schools) === 1 && $schools[0]['school_code'] === 'THCS_NGUYEN_DU', 'School search works with native prepared statements');
    $dashboard = $request('/admin/index.php', $adminSession);
    $assert($dashboard['status'] === 200 && str_contains($dashboard['body'],'Tiến độ kỳ thi Tuyển sinh 10'), 'Administrator dashboard loads');
    $assert(!str_contains($dashboard['body'], 'Đang mở cho thí sinh và trường THCS cập nhật nguyện vọng.'), 'Dashboard does not falsely announce registration is open');
    foreach (['/candidates/index.php','/schools/index.php?q=THCS_NGUYEN_DU','/staff/index.php','/admission/index.php','/admission/calculate.php','/publication/index.php','/councils/index.php','/exam/organization.php','/exam/grading.php','/appeals/update_result.php','/exam/scores.php','/statistics/index.php','/admitted/index.php','/admin/audit.php','/adjustments/index.php'] as $path) {
        $page = $request($path, $adminSession);
        $assert($page['status'] === 200 && !preg_match('/(?:Fatal error|Warning:|Uncaught PDOException)/', $page['body']), 'Admin page without runtime errors: ' . $path);
    }
    foreach (['/admin/index.php','/candidates/index.php','/schools/index.php','/staff/index.php','/admission/create.php','/admission/calculate.php','/admission/export.php','/exam/organization.php','/exam/grading.php','/exam/roster.php','/publication/index.php','/admitted/index.php','/admin/audit.php','/adjustments/index.php'] as $path) {
        $page = $request($path, $studentSession);
        $assert($page['status'] === 403, 'Student cannot access management: ' . $path);
    }
    foreach(['/results/exam.php','/appeals/register.php','/appeals/track.php','/admission/results.php','/enrollment/confirm.php'] as $path){$page=$request($path,$studentSession);$assert($page['status']===200 && !preg_match('/Warning:|Fatal error/',$page['body']),'Student exam/appeal page without runtime errors: '.$path);}
    $assert($request('/enrollment/receipt.php',$studentSession)['status']===404,'No receipt is invented without a confirmation');
    $teacher=$pdo->query('SELECT u.* FROM users u JOIN staff s ON s.user_id=u.id JOIN schools sc ON sc.id=s.school_id WHERE u.active=1 AND s.active=1 AND sc.active=1 ORDER BY u.id LIMIT 1')->fetch();
    if(!$teacher)throw new RuntimeException('Existing linked teacher required.');$teacherSession=$makeSession($teacher);
    foreach(['/teacher/index.php','/teacher/statistics.php','/teacher/admitted.php'] as $path){$page=$request($path,$teacherSession);$assert($page['status']===200 && !preg_match('/Warning:|Fatal error/',$page['body']),'Teacher page without runtime errors: '.$path);}
    foreach(['/admin/audit.php','/statistics/index.php','/adjustments/index.php'] as $path)$assert($request($path,$teacherSession)['status']===403,'Teacher cannot read global management data: '.$path);
    $anonymous = $request('/admin/index.php');
    $assert($anonymous['status'] === 302 && str_contains($anonymous['headers'],'/auth/login'), 'Anonymous visitor is redirected to login');
    foreach (['/','/ket-qua.php','/auth/login.php'] as $path) {
        $page = $request($path);
        $assert($page['status'] === 200 && !str_contains($page['body'],'Fatal error'), 'Public page loads: ' . $path);
    }
    foreach (['/database/backup_before_test_cleanup.sql','/database/cleanup_batch_test_data.php','/tests/batchB_runtime.php','/scratch/ensure_schools.php','/includes/db.php','/data/import/schools_hcm.json','/.git/config','/outputs/01a13af4/support/build.mjs','/package.json','/docs/project_context.md'] as $path) {
        $assert(request_path_is_private($path), 'Path policy rejects ' . $path);
        $response = $request($path);
        $assert(in_array($response['status'],[403,404],true), 'HTTP blocks private path: ' . $path);
    }
    foreach (['/DATABASE/a.sql','/%64atabase/a.sql','/database%2fsecret.csv','/tests\\batchB_runtime.php','/public/../database/a.sql'] as $path) $assert(request_path_is_private($path), 'Alternate spelling is blocked: ' . $path);
    $css = $request('/public/portal-responsive.css');
    $assert($css['status'] === 200 && str_contains($css['headers'],'text/css'), 'Public stylesheet remains accessible');
    $assert(cleanup_fingerprints(cleanup_snapshot($pdo)) === cleanup_fingerprints($before), 'All live database rows remain unchanged');
    if (isset($options['report'])) file_put_contents($options['report'],json_encode(['checks_passed'=>count($checks),'checks'=>$checks,'base_url'=>$base,'code_fingerprint'=>handover_code_hash(),'database_fingerprints'=>cleanup_fingerprints($before)],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    echo 'COMPLETE: ', count($checks), ' checks passed.', PHP_EOL;
} finally {
    foreach ($sessionFiles as $file) if (is_file($file)) unlink($file);
}
