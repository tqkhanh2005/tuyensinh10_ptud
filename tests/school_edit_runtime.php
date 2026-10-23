<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/school_edit_service.php';

function edit_check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

// Shadow the directory with a connection-local table; never modify real school records.
$pdo = db();
$ddl = $pdo->query('SHOW CREATE TABLE schools')->fetch(PDO::FETCH_NUM)[1];
$pdo->exec(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $ddl));
try {
    $original = ['school_code' => 'TEST_EDIT_1', 'name' => 'Trường kiểm thử A TP.HCM',
        'level' => 'THCS', 'address' => 'Địa chỉ A TP.HCM', 'phone' => '02812345678',
        'email' => 'school-a@example.com', 'district' => 'Khu vực kiểm thử', 'admission_area' => 'Vùng A'];
    $id = school_create($pdo, $original);
    $other = ['school_code' => 'TEST_EDIT_2', 'name' => 'Trường kiểm thử B TP.HCM',
        'level' => 'THPT', 'address' => 'Địa chỉ B TP.HCM', 'phone' => '02812345679', 'email' => 'school-b@example.com'];
    school_create($pdo, $other);
    edit_check(school_edit_save($pdo, $id, $original) === [], 'Unchanged school must not collide with itself');
    $changed = $original;
    $changed['name'] = 'Trường kiểm thử C TP.HCM';
    $changed['level'] = 'THPT';
    $changed['school_code'] = 'TAMPERED';
    $changed['district'] = 'TAMPERED';
    $changed['admission_area'] = 'TAMPERED';
    edit_check(school_edit_save($pdo, $id, $changed) === [], 'Valid edit rejected');
    $saved = school_find($pdo, $id);
    edit_check($saved['name'] === $changed['name'], 'Changes not saved');
    foreach (['level', 'school_code', 'district', 'admission_area'] as $key) {
        edit_check($saved[$key] === $original[$key], 'Protected metadata changed: ' . $key);
    }
    foreach (['name', 'address', 'phone', 'email'] as $key) {
        $bad = $changed;
        $bad[$key] = $key === 'phone' ? '+84 28 1234 5679' : $other[$key];
        edit_check(count(school_edit_save($pdo, $id, $bad)) > 0, 'Duplicate accepted: ' . $key);
        edit_check(school_find($pdo, $id) === $saved, 'Failed edit changed data');
    }
    $invalid = ['name' => '', 'level' => 'INVALID', 'address' => '', 'phone' => 'abc', 'email' => 'invalid'];
    edit_check(count(school_edit_save($pdo, $id, $invalid)) >= 4, 'Invalid fields accepted');
    edit_check(school_find($pdo, $id) === $saved, 'Invalid edit changed data');
    $long = $changed;
    $long['name'] = str_repeat('ộ', 151);
    edit_check(count(school_edit_save($pdo, $id, $long)) > 0, 'SQL length limit not validated');
    try {
        school_edit_save($pdo, 2147483647, $changed);
        throw new RuntimeException('Missing school accepted');
    } catch (OutOfBoundsException $expected) {
        edit_check(!$pdo->inTransaction(), 'Missing-school transaction still active');
    }
    echo "PASS: self exclusion, valid edit, protected metadata, all duplicate fields, invalid input, length limit, unchanged state on failure, missing school.\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $pdo->exec('DROP TEMPORARY TABLE schools');
}
