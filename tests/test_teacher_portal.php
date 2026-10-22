<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

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

function create_test_session(int $userId, string $username, array $roles): string
{
    $sessId = bin2hex(random_bytes(16));
    $csrf = bin2hex(random_bytes(32));
    $data = [
        'auth' => [
            'user_id' => $userId,
            'id' => $userId,
            'username' => $username,
            'full_name' => 'Giáo Viên Test',
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
    return $sessId;
}

$pdo = db();

// Tạo test accounts
$tag = 'TEST_T_' . date('YmdHis');

// 1. Teacher THCS
$pdo->prepare('INSERT INTO users (username, password_hash, full_name, role, active, created_at, updated_at) VALUES (?, ?, ?, "teacher", 1, NOW(), NOW())')
    ->execute([$tag . '_thcs', password_hash('123456', PASSWORD_DEFAULT), 'Thầy Giáo THCS']);
$thcsUserId = (int)$pdo->lastInsertId();

$pdo->prepare('INSERT INTO staff (user_id, full_name, workplace, staff_role, active, created_at, updated_at) VALUES (?, ?, "Trường THCS Đoàn Thị Điểm", "Giáo viên", 1, NOW(), NOW())')
    ->execute([$thcsUserId, 'Thầy Giáo THCS']);
$thcsStaffId = (int)$pdo->lastInsertId();
$thcsSess = create_test_session($thcsUserId, $tag . '_thcs', ['teacher', 'teacher_thcs']);

// 2. Teacher THPT
$pdo->prepare('INSERT INTO users (username, password_hash, full_name, role, active, created_at, updated_at) VALUES (?, ?, ?, "teacher", 1, NOW(), NOW())')
    ->execute([$tag . '_thpt', password_hash('123456', PASSWORD_DEFAULT), 'Cô Giáo THPT']);
$thptUserId = (int)$pdo->lastInsertId();

$pdo->prepare('INSERT INTO staff (user_id, full_name, workplace, staff_role, active, created_at, updated_at) VALUES (?, ?, "Trường THPT Chuyên Lý Tự Trọng", "Giáo viên", 1, NOW(), NOW())')
    ->execute([$thptUserId, 'Cô Giáo THPT']);
$thptStaffId = (int)$pdo->lastInsertId();
$thptSess = create_test_session($thptUserId, $tag . '_thpt', ['teacher', 'teacher_thpt']);

// 3. Candidate
$candidateSess = create_test_session(431, 'candidate_test', ['candidate']);

$teacherRoutes = [
    '/teacher/index.php',
    '/teacher/students.php',
    '/teacher/aspirations.php',
    '/teacher/adjustments.php',
    '/teacher/scores.php',
    '/teacher/appeals.php',
    '/teacher/admitted.php',
    '/teacher/quotas.php',
    '/teacher/statistics.php',
];

echo "=== KIỂM THỬ TEACHER PORTAL (BẢO MẬT & PHÂN QUYỀN) ===" . PHP_EOL;

// 1. Chặn Guest trên toàn bộ route
foreach ($teacherRoutes as $route) {
    test("Guest truy cập [$route] bị redirect 302", function () use ($route) {
        $res = http_request($route, 'GET');
        assert_true($res['code'] === 302, "Expected 302, got {$res['code']}");
        assert_true(stripos($res['headers'], '/auth/login') !== false, "Expected redirect to login");
    });
}

// 2. Chặn Thí sinh trên toàn bộ route
foreach ($teacherRoutes as $route) {
    test("Candidate truy cập [$route] bị chặn 403", function () use ($route, $candidateSess) {
        $res = http_request($route, 'GET', [], $candidateSess);
        assert_true($res['code'] === 403, "Expected 403, got {$res['code']}");
    });
}

echo PHP_EOL . "=== KIỂM THỬ GIÁO VIÊN THCS TRUY CẬP TEACHER PORTAL ===" . PHP_EOL;

foreach ($teacherRoutes as $route) {
    test("Giáo viên THCS truy cập [$route] trả 200 OK với Teacher Sidebar", function () use ($route, $thcsSess) {
        $res = http_request($route, 'GET', [], $thcsSess);
        assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
        assert_true(stripos($res['body'], 'teacherSidebar') !== false, "teacherSidebar id not found in body");
        assert_true(stripos($res['body'], 'CỔNG GIÁO VIÊN') !== false, "Brand CỔNG GIÁO VIÊN not found");
        assert_true(stripos($res['body'], 'adminSidebar') === false, "Admin sidebar must NOT appear");
        assert_true(stripos($res['body'], 'navbar-brand') === false, "Public navbar must NOT appear");
    });
}

echo PHP_EOL . "=== KIỂM THỬ GIÁO VIÊN THPT TRUY CẬP TEACHER PORTAL ===" . PHP_EOL;

foreach ($teacherRoutes as $route) {
    test("Giáo viên THPT truy cập [$route] trả 200 OK với Teacher Sidebar", function () use ($route, $thptSess) {
        $res = http_request($route, 'GET', [], $thptSess);
        assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
        assert_true(stripos($res['body'], 'teacherSidebar') !== false, "teacherSidebar id not found in body");
        assert_true(stripos($res['body'], 'CỔNG GIÁO VIÊN') !== false, "Brand CỔNG GIÁO VIÊN not found");
        assert_true(stripos($res['body'], 'adminSidebar') === false, "Admin sidebar must NOT appear");
        assert_true(stripos($res['body'], 'navbar-brand') === false, "Public navbar must NOT appear");
    });
}

echo PHP_EOL . "=== KIỂM THỬ BẢO VỆ CSRF FORM ĐIỀU CHỈNH ===" . PHP_EOL;
test("POST gửi yêu cầu điều chỉnh không có CSRF bị chặn", function () use ($thcsSess) {
    $res = http_request('/teacher/adjustments.php?action=create', 'POST', [
        'submit_adjustment' => '1',
        'candidate_id' => '1',
        'reason' => 'Test reason',
    ], $thcsSess);
    assert_true($res['code'] === 400 || $res['code'] === 403, "Expected 400 or 403 without CSRF, got {$res['code']}");
});

// Cleanup test data
$pdo->prepare('DELETE FROM staff WHERE id IN (?, ?)')->execute([$thcsStaffId, $thptStaffId]);
$pdo->prepare('DELETE FROM users WHERE id IN (?, ?)')->execute([$thcsUserId, $thptUserId]);
@unlink('C:/xampp/tmp/sess_' . $thcsSess);
@unlink('C:/xampp/tmp/sess_' . $thptSess);
@unlink('C:/xampp/tmp/sess_' . $candidateSess);

echo PHP_EOL . "=== TỔNG KẾT KIỂM THỬ HOÀN TẤT ===" . PHP_EOL;
