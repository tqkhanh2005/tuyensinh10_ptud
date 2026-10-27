<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(2);
// In-memory session: controller tests must not write application session files.
session_set_save_handler(new class implements SessionHandlerInterface {
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }
    public function read(string $id): string { return ''; }
    public function write(string $id, string $data): bool { return true; }
    public function destroy(string $id): bool { return true; }
    public function gc(int $max_lifetime): int { return 0; }
}, true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/quota_workflow_service.php';
$checks = 0;
$assert = static function(bool $ok, string $label) use (&$checks): void {
    if (!$ok) throw new RuntimeException($label);
    echo 'PASS ', ++$checks, ': ', $label, PHP_EOL;
};
$reject = static function(callable $op, string $label) use ($assert): void {
    $caught = false;
    try { $op(); } catch (InvalidArgumentException | RuntimeException $e) { $caught = true; }
    $assert($caught, $label);
};
foreach (['0', '-1', '1.5', '2e2', 'abc', '4294967296', [], null] as $bad) $assert(!quota_positive_integer($bad), 'Reject non-positive, malformed or overflowing quota');
$assert(quota_positive_integer('4294967295') && quota_positive_integer('000120'), 'Accept unsigned INT limit and leading zeros');
$pdo = db();
// All mutations use connection-local temporary tables that shadow real tables.
$tables = [
    'admission_periods' => 'id INT PRIMARY KEY,name VARCHAR(150),academic_year VARCHAR(20),status VARCHAR(20)',
    'schools' => 'id INT PRIMARY KEY,school_code VARCHAR(50),name VARCHAR(150),level VARCHAR(10),active INT,admission_area VARCHAR(100),district VARCHAR(100)',
    'admission_quotas' => 'id INT AUTO_INCREMENT PRIMARY KEY,admission_period_id INT,school_id INT,quota INT UNSIGNED,status VARCHAR(20),published_at DATETIME NULL,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY(admission_period_id,school_id)',
    'exam_subjects' => 'id INT AUTO_INCREMENT PRIMARY KEY,admission_period_id INT,exam_date DATE NULL,start_time TIME NULL,status VARCHAR(20)',
    'admission_period_configs' => 'admission_period_id INT PRIMARY KEY,settings LONGTEXT,revision INT DEFAULT 1',
];
try {
    foreach ($tables as $name => $ddl) $pdo->exec("CREATE TEMPORARY TABLE $name ($ddl) ENGINE=InnoDB");
    $pdo->exec("INSERT INTO admission_periods VALUES (1,'Kỳ nháp TP.HCM','2026-2027','draft'),(2,'Kỳ công bố TP.HCM','2026-2027','published'),(3,'Kỳ khác','2025-2026','published')");
    $pdo->exec("INSERT INTO schools VALUES (1,'HCM_A','THPT kiểm thử A','THPT',1,'A','TP.HCM'),(2,'HCM_B','THPT kiểm thử B','THPT',1,'B','TP.HCM'),(3,'HCM_C','THCS kiểm thử','THCS',1,'A','TP.HCM'),(4,'HCM_D','THPT ngưng hoạt động','THPT',0,'A','TP.HCM')");
    $rows = quota_rows($pdo, 1);
    $assert(count($rows) === 2, 'Only active THPT schools are editable');
    $invalid = quota_prepare_changes($rows, [1 => '-1', 2 => '1.5']);
    $assert(count($invalid['errors']) === 2, 'Collect every invalid row');
    $before = quota_snapshot($rows);
    quota_save_all($pdo, 1, [1 => 100, 2 => 200], null, $before);
    $rows = quota_rows($pdo, 1);
    $assert(count(array_filter($rows, static fn($r) => $r['quota_status'] === 'set')) === 2, 'Save all rows and set statuses');
    $prepared = quota_prepare_changes($rows, [1 => '110', 2 => '200']);
    $assert(count($prepared['changes']) === 1 && $prepared['replaced'] === 1, 'Confirmation counts only changed schools and previous quotas');
    $assert(count(quota_prepare_changes($rows, [1 => '', 2 => '0'])['errors']) === 2, 'Clearing existing quota and zero both fail');
    $saved = quota_snapshot($rows);
    $reject(fn() => quota_save_all($pdo, 1, [1 => 300, 2 => 0]), 'Mixed batch rolls back earlier valid writes');
    $assert(quota_snapshot(quota_rows($pdo, 1)) === $saved, 'No partial changes after failed batch');
    $reject(fn() => quota_save_all($pdo, 1, [1 => 300, 3 => 50]), 'THCS cannot receive quota');
    $assert(quota_snapshot(quota_rows($pdo, 1)) === $saved, 'Invalid school batch is atomic');
    $reject(fn() => quota_save_all($pdo, 1, [1 => 300], null, $before), 'Stale snapshot rejected');
    $reject(fn() => quota_save_all($pdo, 2, [1 => 300]), 'Published period is immutable');
    $imported = quota_import_table([['Mã trường','Chỉ tiêu'],['HCM_A','123'],['HCM_B','234']], $rows);
    $assert($imported === [1 => '123', 2 => '234'], 'Excel mapping uses school codes');
    foreach ([
        [['Mã trường','Tên trường','Chỉ tiêu'],['HCM_A','A','120']],
        [['Mã trường','Chỉ tiêu'],['HCM_A','120'],['HCM_A','130']],
        [['Mã trường','Chỉ tiêu'],['UNKNOWN','120']],
        [['Mã trường','Chỉ tiêu'],['HCM_A','120'],['HCM_B','-2']],
        [['Mã trường','Chỉ tiêu']],
    ] as $table) $reject(fn() => quota_import_table($table, $rows), 'Invalid Excel rejected without partial mapping');
    $assert(quota_snapshot(quota_rows($pdo, 1)) === $saved, 'Excel parsing never writes quotas');
    $path = tempnam(sys_get_temp_dir(), 'quota_test_');
    try {
        file_put_contents($path, excel_xlsx_bytes(['Mã trường','Chỉ tiêu'], [['HCM_A',150]]));
        $assert(quota_import_table(excel_parse_xlsx($path), $rows) === [1 => '150'], 'Real XLSX export/parse round trip');
    } finally { unlink($path); }
    $pdo->exec("INSERT INTO exam_subjects(admission_period_id,exam_date,start_time,status) VALUES (1,'2000-01-01','08:00:00','scheduled')");
    $reject(fn() => quota_save_all($pdo, 1, [1 => 500]), 'Past exam start blocks saves');
    $assert(quota_snapshot(quota_rows($pdo, 1)) === $saved, 'Exam lock preserves quotas');
    $pdo->exec("UPDATE exam_subjects SET exam_date='2099-01-01'");
    $assert(quota_edit_block_reason($pdo, 1) === '', 'Future exam allows draft quota setup');
    $pdo->exec("UPDATE exam_subjects SET status='completed'");
    $reject(fn() => quota_save_all($pdo, 1, [1 => 500]), 'Completed exam blocks edits even with future date');
    $config = $pdo->prepare('INSERT INTO admission_period_configs(admission_period_id,settings,revision) VALUES (?,?,1)');
    $config->execute([2, json_encode(['mode' => 'official'])]);
    $config->execute([3, json_encode(['mode' => 'simulation'])]);
    $pdo->exec("INSERT INTO admission_quotas(admission_period_id,school_id,quota,status,published_at) VALUES (2,1,333,'set',CURRENT_TIMESTAMP),(2,2,444,'set',NULL),(3,1,999,'set',CURRENT_TIMESTAMP)");
    $public = quota_public_rows($pdo, 2);
    $assert(count($public) === 1 && (int)$public[0]['quota'] === 333, 'Public view excludes unpublished rows and other periods');
    $assert(quota_public_rows($pdo, 3) === [] && quota_public_rows($pdo, 1) === [], 'Simulation and draft quotas never appear publicly');
    $assert(count(quota_public_rows($pdo, 0)) === 1, 'Default view selects latest official period without summing periods');
    $assert(count(quota_public_rows($pdo, 2, 'HCM_A')) === 1, 'Public search works with native prepared statements');
    require_once __DIR__ . '/../includes/auth.php';
    $actor = $pdo->query('SELECT u.id,u.username,u.full_name,r.code FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE u.active=1 AND r.active=1 AND r.code IN ("admin","so_gdtd") ORDER BY u.id LIMIT 1')->fetch();
    if (!$actor) throw new RuntimeException('An active administration account is required to verify the page.');
    $_SESSION['auth'] = ['user_id' => (int)$actor['id'], 'username' => $actor['username'], 'full_name' => $actor['full_name'], 'roles' => [$actor['code']]];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    $pdo->exec('DELETE FROM exam_subjects');
    $render = static function(string $action, array $values, string $token = '') use ($pdo): string {
        $_GET = ['period_id' => 1];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/quotas/index.php?period_id=1';
        $_POST = ['csrf_token' => $_SESSION['csrf_token'], 'action' => $action, 'quota' => $values,
            'snapshot' => quota_snapshot(quota_rows($pdo, 1)), 'confirmation_token' => $token];
        ob_start();
        try { include __DIR__ . '/../quotas/index.php'; return (string)ob_get_contents(); }
        finally { ob_end_clean(); }
    };
    $html = $render('preview', [1 => '130', 2 => '240']);
    $assert(str_contains($html, 'id="quota-confirm"') && str_contains($html, 'Tổng số trường sẽ được cập nhật: <strong>2</strong>'), 'Page renders exact confirmation counts');
    $assert(quota_snapshot(quota_rows($pdo, 1)) === $saved, 'Opening confirmation performs no writes');
    $token = $_SESSION['quota_confirmation_1']['token'];
    $html = $render('confirm', [1 => '131', 2 => '240'], $token);
    $assert(str_contains($html, 'Xác nhận đã hết hạn hoặc dữ liệu đã đổi'), 'Page rejects data changed after confirmation');
    $assert(quota_snapshot(quota_rows($pdo, 1)) === $saved, 'Rejected confirmation preserves database');
    $html = $render('preview', [1 => '-1', 2 => '1.5']);
    $assert(str_contains($html, 'id="quota-error-1"') && str_contains($html, 'id="quota-error-2"') && str_contains($html, 'value="-1"') && str_contains($html, 'value="1.5"'), 'Page marks every invalid row and preserves entered values');
    $html = $render('import', [1 => '130', 2 => '240']);
    $assert(str_contains($html, 'Không thể tải file') && str_contains($html, 'value="130"') && str_contains($html, 'value="240"'), 'Failed upload preserves manual input');
    echo "PASS: $checks quota workflow checks. No real application rows modified.\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach (array_reverse(array_keys($tables)) as $name) $pdo->exec("DROP TEMPORARY TABLE IF EXISTS $name");
}
