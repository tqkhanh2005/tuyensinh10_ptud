<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_helper.php';
require_once __DIR__ . '/../includes/staff_service.php';

function assert_true(bool $cond, string $msg = 'Assertion failed'): void
{
    if (!$cond) {
        throw new RuntimeException($msg);
    }
}

function test(string $name, callable $fn): void
{
    try {
        $fn();
        echo "PASS: $name" . PHP_EOL;
    } catch (Throwable $e) {
        echo "FAIL: $name — " . $e->getMessage() . PHP_EOL;
    }
}

function http_request(string $path, string $method = 'GET', array $params = [], ?string $sessId = null): array
{
    $url = 'http://localhost/tuyensinh10_ptud_copy' . $path;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if ($sessId !== null) {
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $sessId);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    } elseif (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    curl_close($ch);

    return [
        'code' => $code,
        'headers' => $headers,
        'body' => $body,
    ];
}

function create_test_session_file(int $userId, string $username, array $roles, ?string $csrf = null): string
{
    $sessId = bin2hex(random_bytes(16));
    $csrfToken = $csrf ?? bin2hex(random_bytes(32));
    $data = [
        'auth' => [
            'user_id' => $userId,
            'id' => $userId,
            'username' => $username,
            'full_name' => 'Giáo Viên Test',
            'role' => $roles[0] ?? '',
            'roles' => $roles,
        ],
        'csrf_token' => $csrfToken,
    ];
    $sessionContent = '';
    foreach ($data as $k => $v) {
        $sessionContent .= $k . '|' . serialize($v);
    }
    file_put_contents('C:/xampp/tmp/sess_' . $sessId, $sessionContent);
    return $sessId;
}

$pdo = db();
$tag = 'TT_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));

// Lấy danh sách 2 trường THCS đang hoạt động
$schools = $pdo->query('SELECT id, name, school_code FROM schools WHERE level = "THCS" AND active = 1 ORDER BY id ASC LIMIT 2')->fetchAll(PDO::FETCH_ASSOC);
if (count($schools) < 2) {
    throw new RuntimeException('Cần ít nhất 2 trường THCS active để kiểm thử data scoping.');
}
$schoolA = $schools[0];
$schoolB = $schools[1];

// Lấy 1 trường THPT đang hoạt động
$schoolTHPT = $pdo->query('SELECT id, name, school_code FROM schools WHERE level = "THPT" AND active = 1 ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if (!$schoolTHPT) {
    throw new RuntimeException('Cần ít nhất 1 trường THPT active để kiểm thử.');
}

// IDs lưu lại để cleanup
$cleanupUsers = [];
$cleanupStaff = [];
$cleanupCandidates = [];
$cleanupSessions = [];

echo "=== BẮT ĐẦU KIỂM THỬ: TẠO TÀI KHOẢN GIÁO VIÊN VÀ RBAC CHÍNH THỨC ===" . PHP_EOL;

// 1. Role teacher tồn tại
test('1. Role teacher tồn tại trong bảng roles', function () use ($pdo) {
    $stmt = $pdo->query('SELECT id, code, name, active FROM roles WHERE code = "teacher" AND active = 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    assert_true(!empty($row), 'Role teacher không tồn tại hoặc không active.');
    assert_true($row['code'] === 'teacher', 'Code role không chính xác.');
});

// 2. Teacher có permission đúng
test('2. Teacher có quyền tối thiểu, không có quyền quản trị toàn hệ thống', function () use ($pdo) {
    $stmt = $pdo->query('
        SELECT p.code
        FROM role_permissions rp
        JOIN roles r ON r.id = rp.role_id
        JOIN permissions p ON p.id = rp.permission_id
        WHERE r.code = "teacher"
    ');
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $expectedPerms = [
        'results.view',
        'admitted.view',
        'quota.view',
        'statistics.view',
        'adjustment.submit',
        'appeal.view',
    ];
    foreach ($expectedPerms as $ep) {
        assert_true(in_array($ep, $perms, true), "Thiếu permission bắt buộc: $ep");
    }

    $forbiddenPerms = [
        'candidate.delete',
        'staff.manage',
        'staff.delete',
        'score.enter',
        'enrollment.confirm',
        'school.manage',
    ];
    foreach ($forbiddenPerms as $fp) {
        assert_true(!in_array($fp, $perms, true), "Không được cấp permission nhạy cảm: $fp");
    }
});

// 3. Tạo staff không cấp account vẫn hoạt động
test('3. Tạo staff không cấp account hoạt động bình thường qua staff_create', function () use ($tag, $schoolA, &$cleanupStaff) {
    $staffData = [
        'full_name' => "Staff Không Account $tag",
        'school_id' => $schoolA['id'],
        'workplace' => $schoolA['name'],
        'phone' => '0981' . substr(preg_replace('/[^0-9]/', '', $tag), -6),
        'email' => "staff_noacc_{$tag}@example.com",
        'staff_role' => 'Cán bộ văn phòng',
    ];

    $staffId = staff_create($staffData);
    assert_true($staffId > 0, 'staff_create phải trả về ID > 0');
    $cleanupStaff[] = $staffId;

    $st = staff_find($staffId);
    assert_true($st !== null, 'Không tìm thấy staff vừa tạo');
    assert_true($st['user_id'] === null, 'user_id phải là NULL khi không tạo account');
    assert_true((int)$st['school_id'] === (int)$schoolA['id'], 'school_id phải được lưu chính xác');
});

// 4. Tạo staff có account tạo đúng users, staff.user_id, user_roles
$teacherUserA = null;
$teacherStaffA = null;
$usernameA = 'gv_thcs_a_' . substr(preg_replace('/[^a-zA-Z0-9]/', '', $tag), -8);
$plainPassA = 'GvPassA_123456';

test('4. Tạo staff có account tạo đúng users, staff.user_id và user_roles', function () use (
    $tag, $schoolA, $usernameA, $plainPassA, $pdo,
    &$teacherUserA, &$teacherStaffA, &$cleanupStaff, &$cleanupUsers
) {
    $staffData = [
        'full_name' => "Giáo viên Trường A $tag",
        'school_id' => $schoolA['id'],
        'workplace' => $schoolA['name'],
        'phone' => '0982' . substr(preg_replace('/[^0-9]/', '', $tag), -6),
        'email' => "gv_a_{$tag}@example.com",
        'staff_role' => 'Giáo viên',
    ];
    $accountData = [
        'username' => $usernameA,
        'password' => $plainPassA,
    ];

    $staffId = staff_create_with_account($staffData, $accountData);
    assert_true($staffId > 0, 'staff_create_with_account phải trả về staffId > 0');
    $cleanupStaff[] = $staffId;
    $teacherStaffA = staff_find($staffId);

    assert_true($teacherStaffA !== null, 'Không tìm thấy staff');
    assert_true(!empty($teacherStaffA['user_id']), 'staff.user_id không được để trống');
    $userId = (int)$teacherStaffA['user_id'];
    $cleanupUsers[] = $userId;

    $uStmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $uStmt->execute(['id' => $userId]);
    $user = $uStmt->fetch(PDO::FETCH_ASSOC);
    assert_true($user !== null, 'Không tìm thấy user vừa tạo');
    assert_true($user['username'] === $usernameA, 'Username không khớp');
    assert_true((int)$user['active'] === 1, 'User phải active = 1');
    $teacherUserA = $user;

    // Kiểm tra user_roles
    $urStmt = $pdo->prepare('
        SELECT r.code 
        FROM user_roles ur 
        JOIN roles r ON r.id = ur.role_id 
        WHERE ur.user_id = :uid
    ');
    $urStmt->execute(['uid' => $userId]);
    $userRoles = $urStmt->fetchAll(PDO::FETCH_COLUMN);
    assert_true(in_array('teacher', $userRoles, true), 'user_roles phải chứa role teacher');
});

// 5. Password được hash, không lưu plaintext
test('5. Mật khẩu được mã hóa an toàn bằng password_hash, không lưu plaintext', function () use (&$teacherUserA, $plainPassA) {
    assert_true($teacherUserA !== null, 'User teacher chưa được tạo');
    $hash = (string)$teacherUserA['password_hash'];
    assert_true($hash !== $plainPassA, 'Mật khẩu bị lưu plaintext!');
    assert_true(password_verify($plainPassA, $hash) === true, 'password_verify phải trả về true với mật khẩu gốc');
});

// 6. Username trùng bị từ chối
test('6. Đăng ký username trùng lặp bị từ chối với exception rõ ràng', function () use ($tag, $schoolA, $usernameA) {
    $staffData = [
        'full_name' => "Giáo viên Trùng Username $tag",
        'school_id' => $schoolA['id'],
        'workplace' => $schoolA['name'],
        'phone' => '0983' . substr(preg_replace('/[^0-9]/', '', $tag), -6),
        'email' => "gv_trung_{$tag}@example.com",
        'staff_role' => 'Giáo viên',
    ];
    $accountData = [
        'username' => $usernameA, // Trùng với user A
        'password' => 'PassNew123!',
    ];

    $failed = false;
    try {
        staff_create_with_account($staffData, $accountData);
    } catch (Throwable $e) {
        $failed = true;
        assert_true(stripos($e->getMessage(), 'đã được sử dụng') !== false || stripos($e->getMessage(), 'tồn tại') !== false,
            'Thông báo lỗi phải đề cập username trùng');
    }
    assert_true($failed, 'staff_create_with_account phải ném exception khi trùng username');
});

// 7. Staff inactive không đăng nhập được
test('7. Staff inactive bị chặn không nhận diện là teacher', function () use ($pdo, &$teacherStaffA, &$teacherUserA) {
    // Tạm thời vô hiệu hóa staff
    $pdo->prepare('UPDATE staff SET active = 0 WHERE id = :id')->execute(['id' => $teacherStaffA['id']]);

    // Tạo session cho user này
    $_SESSION['auth'] = [
        'user_id' => (int)$teacherUserA['id'],
        'id' => (int)$teacherUserA['id'],
        'username' => $teacherUserA['username'],
        'role' => 'teacher',
        'roles' => ['teacher'],
    ];

    $isTeacher = auth_is_teacher();
    assert_true($isTeacher === false, 'auth_is_teacher() phải trả về false khi staff inactive');

    // Khôi phục lại active
    $pdo->prepare('UPDATE staff SET active = 1 WHERE id = :id')->execute(['id' => $teacherStaffA['id']]);
    unset($_SESSION['auth']);
});

// 8. Guest vào Teacher Portal nhận 302
test('8. Guest truy cập Teacher Portal nhận HTTP 302 chuyển hướng tới /auth/login', function () {
    $res = http_request('/teacher/index.php', 'GET');
    assert_true($res['code'] === 302, "Expected 302, got {$res['code']}");
    assert_true(stripos($res['headers'], '/auth/login') !== false, 'Phải redirect tới login');
});

// 9. Candidate vào Teacher Portal nhận 403
test('9. Candidate truy cập Teacher Portal bị chặn với HTTP 403 Forbidden', function () use (&$cleanupSessions) {
    $candSess = create_test_session_file(999991, 'cand_test', ['candidate']);
    $cleanupSessions[] = $candSess;

    $res = http_request('/teacher/index.php', 'GET', [], $candSess);
    assert_true($res['code'] === 403, "Expected 403 Forbidden, got {$res['code']}");
});

// Tạo thêm giáo viên Trường B và giáo viên THPT để test data scoping
$usernameB = 'gv_thcs_b_' . substr(preg_replace('/[^a-zA-Z0-9]/', '', $tag), -8);
$plainPassB = 'GvPassB_123456';
$staffDataB = [
    'full_name' => "Giáo viên Trường B $tag",
    'school_id' => $schoolB['id'],
    'workplace' => $schoolB['name'],
    'phone' => '0984' . substr(preg_replace('/[^0-9]/', '', $tag), -6),
    'email' => "gv_b_{$tag}@example.com",
    'staff_role' => 'Giáo viên',
];
$staffIdB = staff_create_with_account($staffDataB, ['username' => $usernameB, 'password' => $plainPassB]);
$cleanupStaff[] = $staffIdB;
$staffB = staff_find($staffIdB);
$cleanupUsers[] = (int)$staffB['user_id'];

// Giáo viên THPT
$usernameTHPT = 'gv_thpt_' . substr(preg_replace('/[^a-zA-Z0-9]/', '', $tag), -8);
$plainPassTHPT = 'GvPassTHPT_123';
$staffDataTHPT = [
    'full_name' => "Giáo viên THPT $tag",
    'school_id' => $schoolTHPT['id'],
    'workplace' => $schoolTHPT['name'],
    'phone' => '0985' . substr(preg_replace('/[^0-9]/', '', $tag), -6),
    'email' => "gv_thpt_{$tag}@example.com",
    'staff_role' => 'Giáo viên',
];
$staffIdTHPT = staff_create_with_account($staffDataTHPT, ['username' => $usernameTHPT, 'password' => $plainPassTHPT]);
$cleanupStaff[] = $staffIdTHPT;
$staffTHPT = staff_find($staffIdTHPT);
$cleanupUsers[] = (int)$staffTHPT['user_id'];

// Tạo 2 nhóm candidate: Candidate A thuộc School A, Candidate B thuộc School B
$candCodeA = 'CAND_A_' . substr(preg_replace('/[^0-9]/', '', $tag), -6);
$pdo->prepare('
    INSERT INTO candidates (national_id, candidate_code, full_name, middle_school_id, eligible, status, created_at, updated_at)
    VALUES (?, ?, ?, ?, 1, "active", NOW(), NOW())
')->execute(['001' . substr(preg_replace('/[^0-9]/', '', $tag), -9), $candCodeA, "Thí sinh Thuộc Trường A $tag", $schoolA['id']]);
$candIdA = (int)$pdo->lastInsertId();
$cleanupCandidates[] = $candIdA;

$candCodeB = 'CAND_B_' . substr(preg_replace('/[^0-9]/', '', $tag), -6);
$pdo->prepare('
    INSERT INTO candidates (national_id, candidate_code, full_name, middle_school_id, eligible, status, created_at, updated_at)
    VALUES (?, ?, ?, ?, 1, "active", NOW(), NOW())
')->execute(['002' . substr(preg_replace('/[^0-9]/', '', $tag), -9), $candCodeB, "Thí sinh Thuộc Trường B $tag", $schoolB['id']]);
$candIdB = (int)$pdo->lastInsertId();
$cleanupCandidates[] = $candIdB;

// Tạo session thật cho Teacher A và Teacher B
$sessTeacherA = create_test_session_file((int)$teacherUserA['id'], $usernameA, ['teacher']);
$cleanupSessions[] = $sessTeacherA;

$sessTeacherB = create_test_session_file((int)$staffB['user_id'], $usernameB, ['teacher']);
$cleanupSessions[] = $sessTeacherB;

// 10. Teacher đăng nhập thành công và nhận 200 ở /teacher/index.php
test('10. Teacher đăng nhập thành công và nhận HTTP 200 tại /teacher/index.php', function () use ($sessTeacherA) {
    $res = http_request('/teacher/index.php', 'GET', [], $sessTeacherA);
    assert_true($res['code'] === 200, "Expected 200 OK, got {$res['code']}");
    assert_true(stripos($res['body'], 'teacherSidebar') !== false, 'teacherSidebar phải xuất hiện');
    assert_true(stripos($res['body'], 'CỔNG GIÁO VIÊN') !== false, 'Brand CỔNG GIÁO VIÊN phải xuất hiện');
});

// 11. Teacher được redirect đúng sau login
test('11. auth_landing_url() trả về đúng /teacher/index.php cho tài khoản teacher', function () use (&$teacherUserA) {
    $_SESSION['auth'] = [
        'user_id' => (int)$teacherUserA['id'],
        'id' => (int)$teacherUserA['id'],
        'username' => $teacherUserA['username'],
        'role' => 'teacher',
        'roles' => ['teacher'],
    ];
    $url = auth_landing_url();
    assert_true(stripos($url, '/teacher/index.php') !== false, "auth_landing_url phải chuyển hướng tới /teacher/index.php, nhận được: $url");
    unset($_SESSION['auth']);
});

// 12. Teacher trường A không thấy candidate trường B
test('12. Data Scoping: Teacher Trường A thấy Candidate A nhưng KHÔNG thấy Candidate B', function () use ($sessTeacherA, $candCodeA, $candCodeB) {
    $res = http_request('/teacher/students.php', 'GET', [], $sessTeacherA);
    assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
    assert_true(stripos($res['body'], $candCodeA) !== false, "Teacher A phải nhìn thấy Candidate A ($candCodeA)");
    assert_true(stripos($res['body'], $candCodeB) === false, "Teacher A KHÔNG ĐƯỢC nhìn thấy Candidate B ($candCodeB)");
});

// 13. Teacher trường A không sửa candidate trường B
test('13. Teacher Trường A không thể mở form sửa/điều chỉnh candidate Trường B', function () use ($sessTeacherA, $candIdB) {
    // Thử truy cập trang sửa admin của candidate B
    $resAdmin = http_request('/candidates/edit.php?id=' . $candIdB, 'GET', [], $sessTeacherA);
    assert_true($resAdmin['code'] === 403, "Truy cập /candidates/edit.php phải nhận 403, nhận được {$resAdmin['code']}");

    // Thử mở form tạo adjustment cho Candidate B từ Teacher Portal
    $resAdj = http_request('/teacher/adjustments.php?action=create&candidate_id=' . $candIdB, 'GET', [], $sessTeacherA);
    assert_true(stripos($resAdj['body'], 'Bạn không có quyền xem hoặc điều chỉnh hồ sơ học sinh thuộc trường khác') !== false,
        'Phải hiển thị thông báo lỗi phạm vi trường học khi chọn candidate trường khác');
});

// 14. Teacher trường A không gửi adjustment cho candidate trường B
test('14. Teacher Trường A gửi POST điều chỉnh cho candidate Trường B bị từ chối', function () use ($sessTeacherA, $candIdB) {
    // Lấy csrf_token từ session
    $csrf = bin2hex(random_bytes(32));
    $sessWithCsrf = create_test_session_file((int)$GLOBALS['teacherUserA']['id'], $GLOBALS['usernameA'], ['teacher'], $csrf);
    $GLOBALS['cleanupSessions'][] = $sessWithCsrf;

    $res = http_request('/teacher/adjustments.php?action=create', 'POST', [
        'csrf_token' => $csrf,
        'submit_adjustment' => '1',
        'candidate_id' => $candIdB,
        'reason' => 'Hack scope test',
        'full_name' => 'Tên Bị Sửa Trái Phép',
    ], $sessWithCsrf);

    assert_true(stripos($res['body'], 'Bạn không có quyền gửi yêu cầu điều chỉnh cho học sinh thuộc trường khác') !== false,
        'POST điều chỉnh trái trường phải bị chặn');
});

// 15. Teacher không truy cập được Admin Portal
test('15. Teacher truy cập các route Admin Portal bị chặn hoàn toàn với HTTP 403', function () use ($sessTeacherA) {
    $adminRoutes = [
        '/admin/index.php',
        '/schools/index.php',
        '/candidates/index.php',
        '/staff/index.php',
        '/councils/index.php',
        '/admission/index.php',
    ];
    foreach ($adminRoutes as $route) {
        $res = http_request($route, 'GET', [], $sessTeacherA);
        assert_true($res['code'] === 403, "Teacher vào [$route] phải nhận 403, nhận được {$res['code']}");
    }
});

// 16. Admin Portal và các role khác không bị ảnh hưởng
test('16. Admin Portal và auth_landing_url cho admin/candidate giữ nguyên độ chính xác', function () {
    $_SESSION['auth'] = [
        'user_id' => 1,
        'roles' => ['admin'],
    ];
    $adminUrl = auth_landing_url();
    assert_true(stripos($adminUrl, '/admin/index.php') !== false, 'Admin phải landing ở /admin/index.php');

    $_SESSION['auth'] = [
        'user_id' => 2,
        'roles' => ['so_gdtd'],
    ];
    $soUrl = auth_landing_url();
    assert_true(stripos($soUrl, '/admin/index.php') !== false, 'Sở GD&ĐT phải landing ở /admin/index.php');

    $_SESSION['auth'] = [
        'user_id' => 3,
        'roles' => ['candidate'],
    ];
    $candUrl = auth_landing_url();
    assert_true(stripos($candUrl, '/admin') === false && stripos($candUrl, '/teacher') === false,
        'Candidate không được landing ở admin hoặc teacher');

    unset($_SESSION['auth']);
});

// CLEANUP
echo PHP_EOL . "=== DỌN DẸP DỮ LIỆU TEST ===" . PHP_EOL;
if (!empty($cleanupCandidates)) {
    $inC = implode(',', array_map('intval', $cleanupCandidates));
    $pdo->exec("DELETE FROM candidates WHERE id IN ($inC)");
    echo "Đã dọn dẹp " . count($cleanupCandidates) . " candidates test." . PHP_EOL;
}

if (!empty($cleanupStaff)) {
    $inS = implode(',', array_map('intval', $cleanupStaff));
    $pdo->exec("DELETE FROM staff WHERE id IN ($inS)");
    echo "Đã dọn dẹp " . count($cleanupStaff) . " staff test." . PHP_EOL;
}

if (!empty($cleanupUsers)) {
    $inU = implode(',', array_map('intval', $cleanupUsers));
    $pdo->exec("DELETE FROM user_roles WHERE user_id IN ($inU)");
    $pdo->exec("DELETE FROM users WHERE id IN ($inU)");
    echo "Đã dọn dẹp " . count($cleanupUsers) . " users và user_roles test." . PHP_EOL;
}

foreach ($cleanupSessions as $sid) {
    @unlink('C:/xampp/tmp/sess_' . $sid);
}

echo PHP_EOL . "=== HOÀN TẤT KIỂM THỬ 16/16 TESTS ===" . PHP_EOL;
