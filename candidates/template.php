<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_auth();
if (!auth_has_permission('candidate.create') && !auth_has_permission('candidate.manage')) {
    http_response_code(403);
    exit('403 Forbidden');
}

$pdo = db();
$sampleSchool = $pdo->query("SELECT school_code FROM schools WHERE level = 'THCS' AND active = 1 LIMIT 1")->fetchColumn() ?: 'THCS01';

$filename = 'mau_import_thi_sinh.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Ghi BOM UTF-8 để Excel hiển thị đúng tiếng Việt
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Header chính xác 10 cột
fputcsv($output, [
    'national_id',
    'full_name',
    'date_of_birth',
    'gender',
    'address',
    'phone',
    'email',
    'middle_school_code',
    'eligible',
    'status',
]);

// Dòng mẫu 1
fputcsv($output, [
    '092205001234',
    'Nguyễn Văn An',
    '2009-05-15',
    'Nam',
    'Số 123 Đường 30/4, Ninh Kiều, Cần Thơ',
    '0912345678',
    'nvan@example.com',
    $sampleSchool,
    '1',
    'active',
]);

// Dòng mẫu 2
fputcsv($output, [
    '092205005678',
    'Trần Thị Mai',
    '2009-08-20',
    'Nữ',
    'Số 456 Đường Nguyễn Văn Cừ, Ninh Kiều, Cần Thơ',
    '0987654321',
    'ttmai@example.com',
    $sampleSchool,
    '1',
    'active',
]);

fclose($output);
exit;
