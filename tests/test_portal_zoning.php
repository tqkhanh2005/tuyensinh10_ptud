<?php

declare(strict_types=1);

/**
 * Test suite for Portal Zoning (Admin, Teacher, Candidate areas)
 * Run with:
 *   C:\xampp\php\php.exe tests\test_portal_zoning.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_helper.php';

$pdo = db();
$results = [];
$sessionIds = [];

function assert_true(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException($msg);
    }
}

function test(string $name, callable $fn): void
{
    global $results;
    try {
        $fn();
        $results[] = ['status' => 'PASS', 'name' => $name];
        echo "PASS: $name" . PHP_EOL;
    } catch (Throwable $e) {
        $results[] = ['status' => 'FAIL', 'name' => $name, 'error' => $e->getMessage()];
        echo "FAIL: $name — " . $e->getMessage() . PHP_EOL;
    }
}

function create_test_session(int $userId, string $username, array $roles): string
{
    global $sessionIds;
    $sessId = bin2hex(random_bytes(16));
    $csrf = bin2hex(random_bytes(32));
    $data = [
        'auth' => [
            'user_id' => $userId,
            'id' => $userId,
            'username' => $username,
            'full_name' => 'User ' . $username,
            'role' => $roles[0] ?? '',
            'roles' => $roles,
        ],
        'csrf_token' => $csrf,
    ];
    $sessionContent = '';
    foreach ($data as $k => $v) {
        $sessionContent .= $k . '|' . serialize($v);
    }
    file_put_contents('C:/xampp/tmp/sess_' . $sessId, $sessionContent);
    $sessionIds[] = $sessId;
    return $sessId;
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

// Prepare sessions
$adminSess = create_test_session(2, 'test_so_gdtd', ['so_gdtd']);
$candidateSess = create_test_session(431, 'candidate_test', ['candidate']);

// Temporary teacher session
// Find or create test teacher staff record
$tag = 'TEST_TEACHER_' . date('YmdHis');
$pdo->prepare('INSERT INTO users (username, password_hash, full_name, role, active, created_at, updated_at) VALUES (?, ?, ?, "teacher", 1, NOW(), NOW())')
    ->execute([$tag, password_hash('123456', PASSWORD_DEFAULT), 'Thầy Giáo Test']);
$teacherUserId = (int)$pdo->lastInsertId();

// Create staff entry for this teacher
$pdo->prepare('INSERT INTO staff (user_id, full_name, workplace, staff_role, active, created_at, updated_at) VALUES (?, ?, "Trường THCS Đoàn Thị Điểm", "Giáo viên", 1, NOW(), NOW())')
    ->execute([$teacherUserId, 'Thầy Giáo Test']);
$staffId = (int)$pdo->lastInsertId();

$teacherSess = create_test_session($teacherUserId, $tag, ['staff']);

echo "=== KIỂM THỬ HỆ THỐNG GIAO DIỆN PHÂN KHU ===" . PHP_EOL;

// 1. Guest truy cập /admin/index.php bị redirect 302 về /auth/login
test('Guest truy cập /admin/index.php bị redirect 302', function () {
    $res = http_request('/admin/index.php', 'GET');
    assert_true($res['code'] === 302, "Expected 302, got {$res['code']}");
    assert_true(stripos($res['headers'], '/auth/login') !== false, "Expected redirect to /auth/login");
});

// 2. Guest truy cập /teacher/index.php bị redirect 302 về /auth/login
test('Guest truy cập /teacher/index.php bị redirect 302', function () {
    $res = http_request('/teacher/index.php', 'GET');
    assert_true($res['code'] === 302, "Expected 302, got {$res['code']}");
    assert_true(stripos($res['headers'], '/auth/login') !== false, "Expected redirect to /auth/login");
});

// 3. Candidate truy cập /admin/index.php bị 403 Forbidden
test('Candidate truy cập /admin/index.php bị 403 Forbidden', function () use ($candidateSess) {
    $res = http_request('/admin/index.php', 'GET', [], $candidateSess);
    assert_true($res['code'] === 403, "Expected 403, got {$res['code']}");
});

// 4. Candidate truy cập /teacher/index.php bị 403 Forbidden
test('Candidate truy cập /teacher/index.php bị 403 Forbidden', function () use ($candidateSess) {
    $res = http_request('/teacher/index.php', 'GET', [], $candidateSess);
    assert_true($res['code'] === 403, "Expected 403, got {$res['code']}");
});

// 5. Admin truy cập /admin/index.php trả 200 OK với sidebar quản trị
test('Admin truy cập /admin/index.php trả 200 OK với sidebar quản trị', function () use ($adminSess) {
    $res = http_request('/admin/index.php', 'GET', [], $adminSess);
    assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
    assert_true(stripos($res['body'], 'adminSidebar') !== false, "adminSidebar id not found in body");
    assert_true(stripos($res['body'], 'QUẢN TRỊ VIÊN') !== false, "Brand QUẢN TRỊ VIÊN not found");
    // Verify required menu items
    $requiredMenuItems = [
        'Tổng quan',
        'Quản lý thí sinh',
        'Quản lý trường học',
        'Quản lý giáo viên',
        'Quản lý kỳ thi',
        'Nhập điểm',
        'Phúc khảo',
        'Xét tuyển',
        'Thống kê - báo cáo',
        'Cài đặt',
    ];
    foreach ($requiredMenuItems as $item) {
        assert_true(stripos($res['body'], $item) !== false, "Missing admin menu item: $item");
    }
});

// 6. Teacher truy cập /teacher/index.php trả 200 OK với sidebar giáo viên
test('Teacher truy cập /teacher/index.php trả 200 OK với sidebar giáo viên', function () use ($teacherSess) {
    $res = http_request('/teacher/index.php', 'GET', [], $teacherSess);
    assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
    assert_true(stripos($res['body'], 'teacherSidebar') !== false, "teacherSidebar id not found in body");
    assert_true(stripos($res['body'], 'CỔNG GIÁO VIÊN') !== false, "Brand CỔNG GIÁO VIÊN not found");
    assert_true(stripos($res['body'], 'THCS Đoàn Thị Điểm') !== false, "School name not found in teacher page");
    // Verify teacher menu items
    $teacherMenuItems = [
        'Tổng quan',
        'Quản lý thí sinh',
        'Yêu cầu điều chỉnh',
        'Nhập điểm',
        'Phúc khảo',
        'Danh sách trúng tuyển',
        'Chỉ tiêu tuyển sinh',
        'Thống kê - báo cáo',
        'Cài đặt',
    ];
    foreach ($teacherMenuItems as $item) {
        assert_true(stripos($res['body'], $item) !== false, "Missing teacher menu item: $item");
    }
});

// 7. Cổng thí sinh (Trang chủ và tra cứu) giữ nguyên giao diện không có sidebar
test('Cổng thí sinh giữ nguyên giao diện không có sidebar', function () use ($candidateSess) {
    $res = http_request('/', 'GET', [], $candidateSess);
    assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
    assert_true(stripos($res['body'], 'adminSidebar') === false, "Public/Candidate portal should not contain adminSidebar");
    assert_true(stripos($res['body'], 'teacherSidebar') === false, "Public/Candidate portal should not contain teacherSidebar");
});

// 8. Hàm auth_landing_url() trả URL chính xác cho từng vai trò
test('Hàm auth_landing_url() điều hướng chính xác', function () {
    // Admin / Sở GD
    $_SESSION['auth'] = ['user_id' => 2, 'roles' => ['so_gdtd']];
    assert_true(stripos(auth_landing_url(), '/admin/index.php') !== false, "Admin landing URL should be /admin/index.php");

    // Candidate
    $_SESSION['auth'] = ['user_id' => 431, 'roles' => ['candidate']];
    assert_true(auth_landing_url() === url('/'), "Candidate landing URL should be portal home");
});

// Cleanup test fixtures
echo PHP_EOL . "=== DỌN DẸP DỮ LIỆU TEST ===" . PHP_EOL;
$pdo->prepare('DELETE FROM staff WHERE id = ?')->execute([$staffId]);
$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$teacherUserId]);
foreach ($sessionIds as $sid) {
    @unlink('C:/xampp/tmp/sess_' . $sid);
}
echo "Đã dọn dẹp xong tài khoản và dữ liệu kiểm thử." . PHP_EOL;

$pass = 0;
$fail = 0;
foreach ($results as $r) {
    if ($r['status'] === 'PASS') $pass++; else $fail++;
}

echo PHP_EOL . "TỔNG KẾT: $pass PASS / " . ($pass + $fail) . " TESTS" . PHP_EOL;
if ($fail > 0) {
    exit(1);
} else {
    exit(0);
}
