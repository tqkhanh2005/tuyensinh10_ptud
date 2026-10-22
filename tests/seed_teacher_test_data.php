<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/staff_service.php';
require_once __DIR__ . '/../includes/candidate_service.php';

$pdo = db();

// Tìm trường THCS và THPT active
$thcsSchool = $pdo->query('SELECT id, name, school_code FROM schools WHERE level = "THCS" AND active = 1 ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$thptSchool = $pdo->query('SELECT id, name, school_code FROM schools WHERE level = "THPT" AND active = 1 ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);

if (!$thcsSchool || !$thptSchool) {
    exit("Lỗi: Không tìm thấy trường THCS hoặc THPT active trong cơ sở dữ liệu.\n");
}

echo "=== KHỞI TẠO DỮ LIỆU KIỂM THỬ TEACHER PORTAL CHÍNH THỨC ===" . PHP_EOL;

// 1. Tạo Giáo viên THCS
$thcsUsername = 'teacher_thcs_test';
$thcsPass = bin2hex(random_bytes(8)) . 'A1!'; // Mật khẩu an toàn ngẫu nhiên

// Xóa tài khoản cũ nếu đã tồn tại để đảm bảo tính idempotent
$oldUserThcs = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$oldUserThcs->execute([$thcsUsername]);
$oldId = $oldUserThcs->fetchColumn();
if ($oldId) {
    $pdo->prepare('DELETE FROM staff WHERE user_id = ?')->execute([$oldId]);
    $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$oldId]);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$oldId]);
}

$thcsStaffId = staff_create_with_account([
    'full_name' => 'Thầy Giáo THCS Test',
    'school_id' => $thcsSchool['id'],
    'workplace' => $thcsSchool['name'],
    'phone' => '0912000001',
    'email' => 'gv_thcs@tuyensinh10.test',
    'staff_role' => 'Giáo viên THCS',
], [
    'username' => $thcsUsername,
    'password' => $thcsPass,
]);

$thcsStaff = staff_find($thcsStaffId);
$thcsUserId = (int)$thcsStaff['user_id'];

// 2. Tạo Giáo viên THPT
$thptUsername = 'teacher_thpt_test';
$thptPass = bin2hex(random_bytes(8)) . 'B1!'; // Mật khẩu an toàn ngẫu nhiên

$oldUserThpt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$oldUserThpt->execute([$thptUsername]);
$oldIdThpt = $oldUserThpt->fetchColumn();
if ($oldIdThpt) {
    $pdo->prepare('DELETE FROM staff WHERE user_id = ?')->execute([$oldIdThpt]);
    $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$oldIdThpt]);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$oldIdThpt]);
}

$thptStaffId = staff_create_with_account([
    'full_name' => 'Cô Giáo THPT Test',
    'school_id' => $thptSchool['id'],
    'workplace' => $thptSchool['name'],
    'phone' => '0912000002',
    'email' => 'gv_thpt@tuyensinh10.test',
    'staff_role' => 'Giáo viên THPT',
], [
    'username' => $thptUsername,
    'password' => $thptPass,
]);

$thptStaff = staff_find($thptStaffId);
$thptUserId = (int)$thptStaff['user_id'];

echo "--- THÔNG TIN TÀI KHOẢN ĐÃ TẠO (KHÔNG CHỨA MẬT KHẨU) ---" . PHP_EOL;
echo "[1] Giáo viên THCS:" . PHP_EOL;
echo "  - Username: " . $thcsUsername . PHP_EOL;
echo "  - User ID: " . $thcsUserId . PHP_EOL;
echo "  - Staff ID: " . $thcsStaffId . PHP_EOL;
echo "  - Trường: " . $thcsSchool['name'] . " (" . $thcsSchool['school_code'] . ")" . PHP_EOL;
echo "  - Cấp học: THCS" . PHP_EOL;

echo PHP_EOL . "[2] Giáo viên THPT:" . PHP_EOL;
echo "  - Username: " . $thptUsername . PHP_EOL;
echo "  - User ID: " . $thptUserId . PHP_EOL;
echo "  - Staff ID: " . $thptStaffId . PHP_EOL;
echo "  - Trường: " . $thptSchool['name'] . " (" . $thptSchool['school_code'] . ")" . PHP_EOL;
echo "  - Cấp học: THPT" . PHP_EOL;

echo PHP_EOL . "=== TẠO DỮ LIỆU THÀNH CÔNG ===" . PHP_EOL;
