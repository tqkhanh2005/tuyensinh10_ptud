<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/school_admission_service.php';

function delete_check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

// All tables are connection-local shadows. No application records are deleted.
$pdo = db();
$references = [
    'admission_quotas' => 'school_id', 'admission_results' => 'school_id',
    'aspirations' => 'school_id', 'candidates' => 'middle_school_id',
    'enrollment_confirmations' => 'school_id', 'exam_sites' => 'school_id', 'staff' => 'school_id',
];
$created = [];
try {
    $ddl = $pdo->query('SHOW CREATE TABLE schools')->fetch(PDO::FETCH_NUM)[1];
    $pdo->exec(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $ddl));
    $created[] = 'schools';
    foreach ($references as $table => $column) {
        $pdo->exec("CREATE TEMPORARY TABLE $table (id INT PRIMARY KEY AUTO_INCREMENT, $column INT UNSIGNED, admission_period_id INT, INDEX ($column)) ENGINE=InnoDB");
        $created[] = $table;
    }
    $id = school_create($pdo, ['school_code' => 'TEST_DELETE_A', 'name' => 'Trường kiểm thử A TP.HCM', 'level' => 'THCS']);
    $other = school_create($pdo, ['school_code' => 'TEST_DELETE_B', 'name' => 'Trường kiểm thử B TP.HCM', 'level' => 'THPT']);
    $before = school_find($pdo, $id);
    foreach ($references as $table => $column) {
        $insert = $pdo->prepare("INSERT INTO $table ($column, admission_period_id) VALUES (?, ?)");
        foreach ([2026, 2020] as $period) {
            $insert->execute([$id, $period]);
            try {
                school_delete($pdo, $id);
                throw new RuntimeException('Related school deleted: ' . $table);
            } catch (DomainException $expected) {
                delete_check($expected->getMessage() === 'Không thể xóa trường đang có dữ liệu liên quan', 'Wrong relation message');
            }
            delete_check(school_find($pdo, $id) === $before, 'Blocked delete changed school');
            delete_check((int)$pdo->query("SELECT COUNT(*) FROM $table WHERE $column = $id")->fetchColumn() === 1, 'Blocked delete changed relation');
            delete_check(!$pdo->inTransaction(), 'Transaction leaked');
            $pdo->exec("DELETE FROM $table");
        }
        // A relation to another school must not prevent this school from being deleted.
        $insert->execute([$other, 2026]);
    }
    school_delete($pdo, $id);
    delete_check(school_find($pdo, $id) === null, 'Unreferenced school not deleted');
    delete_check(school_find($pdo, $other) !== null, 'Other school deleted');
    foreach ($references as $table => $column) {
        delete_check((int)$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn() === 1, 'Unrelated rows changed');
    }
    try {
        school_delete($pdo, $id);
        throw new RuntimeException('Missing school accepted');
    } catch (OutOfBoundsException $expected) {
        delete_check(!$pdo->inTransaction(), 'Missing-school transaction leaked');
    }
    echo "PASS: seven relation types, historical links, blocked delete preserves data, successful delete, unrelated records preserved, missing school.\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach (array_reverse($created) as $table) $pdo->exec("DROP TEMPORARY TABLE $table");
}
