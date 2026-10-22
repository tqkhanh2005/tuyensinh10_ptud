<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/school_batch_service.php';

function batch_check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

// A connection-local temporary table shadows schools; application records are untouched.
$pdo = db();
$ddl = $pdo->query('SHOW CREATE TABLE schools')->fetch(PDO::FETCH_NUM)[1];
$pdo->exec(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $ddl));
try {
    $row = ['name' => 'Trường kiểm thử TP.HCM', 'level' => 'THCS',
        'address' => 'Địa chỉ kiểm thử TP.HCM', 'phone' => '02812345678', 'email' => 'test@example.com'];
    $pdo->beginTransaction();
    batch_check(school_batch_validate($pdo, [$row]) === [], 'Valid row rejected');
    $duplicate = $row;
    $duplicate['phone'] = '+84 28 1234 5678';
    $duplicate['email'] = 'TEST@example.com';
    $errors = school_batch_validate($pdo, [$row, $duplicate]);
    batch_check(count($errors[0] ?? []) === 4 && count($errors[1] ?? []) === 4, 'Both duplicate rows must be marked');
    school_create($pdo, $row + ['school_code' => 'TEST_ONLY']);
    batch_check(count(school_batch_validate($pdo, [$duplicate])[0] ?? []) === 4, 'Existing duplicates not detected');
    $invalid = ['name' => '', 'level' => 'PRIMARY', 'address' => '', 'phone' => 'abc', 'email' => 'invalid'];
    batch_check(count(school_batch_validate($pdo, [$invalid])[0] ?? []) >= 5, 'Missing required/format errors');
    $pdo->rollBack();
    batch_check((int)$pdo->query('SELECT COUNT(*) FROM schools')->fetchColumn() === 0, 'Rollback did not restore table');
    $path = tempnam(sys_get_temp_dir(), 'school_xlsx_');
    try {
        file_put_contents($path, excel_xlsx_bytes(array_values(school_batch_fields()), [array_values($row)]));
        $parsed = excel_parse_xlsx($path);
        batch_check($parsed[1] === array_values($row), 'XLSX round trip changed values');
        $zip = new ZipArchive();
        $zip->open($path);
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>Tên trường</t></is></c><c r="C1" t="inlineStr"><is><t>Địa chỉ</t></is></c></row></sheetData></worksheet>');
        $zip->close();
        batch_check(excel_parse_xlsx($path)[0] === ['Tên trường', '', 'Địa chỉ'], 'Inline strings or empty columns lost');
    } finally {
        if (is_file($path)) unlink($path);
    }
    echo "PASS: required fields, formats, batch/existing duplicates, rollback, XLSX shared/inline strings.\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $pdo->exec('DROP TEMPORARY TABLE schools');
}
