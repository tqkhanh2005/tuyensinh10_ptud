<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
if (!auth_has_permission('staff.create') && !auth_has_permission('staff.manage')) {
    http_response_code(403);
    exit('403 Forbidden');
}

$filename = 'mau_import_can_bo_giao_vien.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Ghi BOM UTF-8 để Excel hiển thị đúng tiếng Việt
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Header chính xác 6 cột
fputcsv($output, [
    'full_name',
    'workplace',
    'phone',
    'email',
    'staff_role',
    'active',
]);

// Dòng mẫu 1
fputcsv($output, [
    'Nguyễn Văn Hùng',
    'Trường THCS Đoàn Thị Điểm',
    '0901234567',
    'nvhung@example.com',
    'Cán bộ coi thi',
    '1',
]);

// Dòng mẫu 2
fputcsv($output, [
    'Lê Thị Cúc',
    'Trường THCS Lương Thế Vinh',
    '0909876543',
    'ltcuc@example.com',
    'Giám khảo',
    '1',
]);

fclose($output);
exit;
