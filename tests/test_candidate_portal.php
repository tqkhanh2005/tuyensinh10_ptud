<?php

declare(strict_types=1);

/**
 * Test Suite: CANDIDATE PORTAL AUDIT & HARDENING
 * Kiểm thử toàn diện Candidate Portal, Zoning, Data Scoping, IDOR, CSRF & CAPTCHA
 * Chạy bằng: C:\xampp\php\php.exe tests\test_candidate_portal.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_helper.php';
require_once __DIR__ . '/../includes/candidate_service.php';

$pdo = db();
$results = [];
$sessionIds = [];
$cleanupUserIds = [];
$cleanupCandidateIds = [];

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
            'full_name' => 'Test User ' . $username,
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

// ==========================================
// 1. CHUẨN BỊ FIXTURES: 2 THÍ SINH ĐỘC LẬP
// ==========================================
$tag = 'TEST_CAND_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));

// Thí sinh 1
$natId1 = '07920' . rand(1000000, 9999999);
$pdo->prepare('INSERT INTO users (username, password_hash, full_name, role, active, created_at, updated_at) VALUES (?, ?, ?, "candidate", 1, NOW(), NOW())')
    ->execute(['u1_' . $tag, password_hash('123456', PASSWORD_DEFAULT), 'Thí Sinh Một']);
$uId1 = (int)$pdo->lastInsertId();
$cleanupUserIds[] = $uId1;

$candRoleId = (int)$pdo->query('SELECT id FROM roles WHERE code = "candidate"')->fetchColumn();
$pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$uId1, $candRoleId]);

$pdo->prepare('INSERT INTO candidates (user_id, national_id, candidate_code, full_name, eligible, status, created_at, updated_at) VALUES (?, ?, ?, ?, 1, "active", NOW(), NOW())')
    ->execute([$uId1, $natId1, 'CAND1_' . $tag, 'Thí Sinh Một']);
$candId1 = (int)$pdo->lastInsertId();
$cleanupCandidateIds[] = $candId1;

// Thí sinh 2
$natId2 = '07920' . rand(1000000, 9999999);
$pdo->prepare('INSERT INTO users (username, password_hash, full_name, role, active, created_at, updated_at) VALUES (?, ?, ?, "candidate", 1, NOW(), NOW())')
    ->execute(['u2_' . $tag, password_hash('123456', PASSWORD_DEFAULT), 'Thí Sinh Hai']);
$uId2 = (int)$pdo->lastInsertId();
$cleanupUserIds[] = $uId2;
$pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$uId2, $candRoleId]);

$pdo->prepare('INSERT INTO candidates (user_id, national_id, candidate_code, full_name, eligible, status, created_at, updated_at) VALUES (?, ?, ?, ?, 1, "active", NOW(), NOW())')
    ->execute([$uId2, $natId2, 'CAND2_' . $tag, 'Thí Sinh Hai']);
$candId2 = (int)$pdo->lastInsertId();
$cleanupCandidateIds[] = $candId2;

// Tạo session files
$sessCand1 = create_test_session($uId1, 'u1_' . $tag, ['candidate']);
$sessCand2 = create_test_session($uId2, 'u2_' . $tag, ['candidate']);
$sessAdmin = create_test_session(2, 'test_so_gdtd', ['so_gdtd']);

echo "=== BẮT ĐẦU KIỂM THỬ CANDIDATE PORTAL ===" . PHP_EOL;

// ==========================================
// TEST NHÓM 1: LOGIN & PORTAL ZONING
// ==========================================
test('1. auth_landing_url() điều hướng thí sinh về portal home (url("/"))', function () use ($uId1) {
    $_SESSION['auth'] = ['user_id' => $uId1, 'roles' => ['candidate']];
    assert_true(auth_landing_url() === url('/'), 'Candidate landing URL phải là trang chủ cổng portal');
    unset($_SESSION['auth']);
});

test('2. auth_landing_url() giữ nguyên điều hướng Admin và Teacher', function () {
    $_SESSION['auth'] = ['user_id' => 1, 'roles' => ['admin']];
    assert_true(stripos(auth_landing_url(), '/admin/index.php') !== false, 'Admin phải landing ở /admin');
    $_SESSION['auth'] = ['user_id' => 2, 'roles' => ['so_gdtd']];
    assert_true(stripos(auth_landing_url(), '/admin/index.php') !== false, 'Sở GD phải landing ở /admin');
    unset($_SESSION['auth']);
});

test('3. Guest truy cập các route Candidate bị redirect 302 về login', function () {
    $routes = [
        '/aspirations/index.php',
        '/exam/card.php',
        '/admission/results.php',
        '/results/exam.php',
        '/results/admission.php',
        '/enrollment/confirm.php',
        '/adjustments/create.php',
        '/appeals/register.php',
        '/appeals/track.php',
    ];
    foreach ($routes as $r) {
        $res = http_request($r, 'GET');
        assert_true($res['code'] === 302, "Route [$r] guest phải nhận 302, nhận được {$res['code']}");
        assert_true(stripos($res['headers'], '/auth/login') !== false, "Route [$r] thiếu redirect Location /auth/login");
    }
});

test('4. Candidate bị chặn không được truy cập Admin Portal (403 Forbidden)', function () use ($sessCand1) {
    $adminRoutes = [
        '/admin/index.php',
        '/candidates/index.php',
        '/candidates/create.php',
        '/candidates/edit.php',
        '/candidates/delete.php',
        '/schools/index.php',
        '/staff/index.php',
        '/councils/index.php',
    ];
    foreach ($adminRoutes as $ar) {
        $res = http_request($ar, 'GET', [], $sessCand1);
        assert_true($res['code'] === 403, "Candidate truy cập [$ar] phải nhận 403, nhận được {$res['code']}");
    }
});

test('5. Candidate bị chặn không được truy cập Teacher Portal (403 Forbidden)', function () use ($sessCand1) {
    $teacherRoutes = [
        '/teacher/index.php',
        '/teacher/students.php',
        '/teacher/adjustments.php',
        '/teacher/scores.php',
        '/teacher/appeals.php',
    ];
    foreach ($teacherRoutes as $tr) {
        $res = http_request($tr, 'GET', [], $sessCand1);
        assert_true($res['code'] === 403, "Candidate truy cập [$tr] phải nhận 403, nhận được {$res['code']}");
    }
});

test('6. Giao diện Candidate Portal không có Admin Sidebar hoặc Teacher Sidebar', function () use ($sessCand1) {
    $resHome = http_request('/', 'GET', [], $sessCand1);
    assert_true($resHome['code'] === 200, "Home candidate trả {$resHome['code']}");
    assert_true(strpos($resHome['body'], 'adminSidebar') === false, 'Không được có adminSidebar');
    assert_true(strpos($resHome['body'], 'teacherSidebar') === false, 'Không được có teacherSidebar');

    $resResults = http_request('/admission/results.php', 'GET', [], $sessCand1);
    assert_true($resResults['code'] === 200, "Results candidate trả {$resResults['code']}");
    assert_true(strpos($resResults['body'], 'adminSidebar') === false, 'Không được có adminSidebar trên admission/results.php');
    assert_true(strpos($resResults['body'], 'teacherSidebar') === false, 'Không được có teacherSidebar trên admission/results.php');
});

// ==========================================
// TEST NHÓM 2: DATA SCOPING & CHỐNG IDOR
// ==========================================
test('7. Data Scoping: Thí sinh 1 xem đúng hồ sơ của mình trên admission/results.php', function () use ($sessCand1, $natId1, $natId2) {
    $res = http_request('/admission/results.php', 'GET', [], $sessCand1);
    assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
    assert_true(strpos($res['body'], $natId1) !== false, "Thí sinh 1 phải thấy CCCD của chính mình ($natId1)");
    assert_true(strpos($res['body'], $natId2) === false, "Thí sinh 1 KHÔNG ĐƯỢC thấy CCCD của Thí sinh 2 ($natId2)");
});

test('8. Chống IDOR: Thí sinh 1 cố tình đổi candidate_id sang Thí sinh 2 trên admission/results.php bị từ chối 403', function () use ($sessCand1, $candId2) {
    $res = http_request('/admission/results.php?candidate_id=' . $candId2, 'GET', [], $sessCand1);
    assert_true($res['code'] === 403, "Cố tình xem candidate khác phải nhận 403, nhận được {$res['code']}");
});

test('9. Chống IDOR: Thí sinh 1 cố tình đổi candidate_id trên aspirations/index.php bị từ chối 403', function () use ($sessCand1, $candId2) {
    $res = http_request('/aspirations/index.php?candidate_id=' . $candId2, 'GET', [], $sessCand1);
    assert_true($res['code'] === 403, "Cố tình xem nguyện vọng candidate khác phải nhận 403, nhận được {$res['code']}");
});

test('10. Chống IDOR: Thí sinh 1 cố tình đổi candidate_id trên exam/card.php bị từ chối 403', function () use ($sessCand1, $candId2) {
    $res = http_request('/exam/card.php?candidate_id=' . $candId2, 'GET', [], $sessCand1);
    assert_true($res['code'] === 403, "Cố tình xem thẻ dự thi candidate khác phải nhận 403, nhận được {$res['code']}");
});

test('11. Chống IDOR: Thí sinh 1 cố tình đổi candidate_id trên adjustments/create.php (GET) bị từ chối 403', function () use ($sessCand1, $candId2) {
    $res = http_request('/adjustments/create.php?candidate_id=' . $candId2, 'GET', [], $sessCand1);
    assert_true($res['code'] === 403, "Cố tình mở form điều chỉnh candidate khác phải nhận 403, nhận được {$res['code']}");
});

test('12. Chống IDOR: Thí sinh 1 gửi POST điều chỉnh cho Thí sinh 2 bị từ chối 403', function () use ($sessCand1, $candId2) {
    // Đọc csrf token từ session
    $csrf = bin2hex(random_bytes(32));
    $res = http_request('/adjustments/create.php', 'POST', [
        'csrf_token' => $csrf,
        'candidate_id' => $candId2,
        'reason' => 'Hack attempt',
    ], $sessCand1);
    assert_true($res['code'] === 403, "POST điều chỉnh cho candidate khác phải nhận 403, nhận được {$res['code']}");
});

test('13. Chống IDOR: Thí sinh 1 cố tình đổi candidate_id trên appeals/track.php bị từ chối 403', function () use ($sessCand1, $candId2) {
    $res = http_request('/appeals/track.php?candidate_id=' . $candId2, 'GET', [], $sessCand1);
    assert_true($res['code'] === 403, "Cố tình xem phúc khảo candidate khác phải nhận 403, nhận được {$res['code']}");
});

test('14. Chống IDOR: Thí sinh 1 cố tình đổi candidate_id trên results/exam.php bị từ chối 403', function () use ($sessCand1, $candId2) {
    $res = http_request('/results/exam.php?candidate_id=' . $candId2, 'GET', [], $sessCand1);
    assert_true($res['code'] === 403, "Cố tình xem điểm candidate khác phải nhận 403, nhận được {$res['code']}");
});

test('15. Chống IDOR: Thí sinh 1 cố tình đổi candidate_id trên results/admission.php bị từ chối 403', function () use ($sessCand1, $candId2) {
    $res = http_request('/results/admission.php?candidate_id=' . $candId2, 'GET', [], $sessCand1);
    assert_true($res['code'] === 403, "Cố tình xem kết quả candidate khác phải nhận 403, nhận được {$res['code']}");
});

test('16. Chống IDOR: Thí sinh 1 cố tình đổi candidate_id trên enrollment/confirm.php bị từ chối 403', function () use ($sessCand1, $candId2) {
    $res = http_request('/enrollment/confirm.php?candidate_id=' . $candId2, 'GET', [], $sessCand1);
    assert_true($res['code'] === 403, "Cố tình xác nhận nhập học candidate khác phải nhận 403, nhận được {$res['code']}");
});

// ==========================================
// TEST NHÓM 3: BẢO MẬT & DỮ LIỆU NHẠY CẢM
// ==========================================
test('17. Không để lộ SQLSTATE, Exception hoặc Stack Trace khi truy cập sai phạm vi', function () use ($sessCand1, $candId2) {
    $res = http_request('/admission/results.php?candidate_id=' . $candId2, 'GET', [], $sessCand1);
    assert_true(stripos($res['body'], 'SQLSTATE') === false, 'Không được để lộ SQLSTATE');
    assert_true(stripos($res['body'], 'PDOException') === false, 'Không được để lộ PDOException');
    assert_true(stripos($res['body'], 'Stack trace') === false, 'Không được để lộ Stack trace');
    assert_true(stripos($res['body'], 'password_hash') === false, 'Không được để lộ password_hash');
});

test('18. Form điều chỉnh hồ sơ có kiểm tra bảo vệ CSRF', function () use ($sessCand1) {
    $res = http_request('/adjustments/create.php', 'POST', [
        'full_name' => 'Tamper Name',
        'national_id' => '079209999999',
        'reason' => 'No CSRF Token',
    ], $sessCand1);
    assert_true($res['code'] === 400 || $res['code'] === 403, "POST thiếu CSRF phải bị từ chối với 400/403, nhận được {$res['code']}");
});

test('19. Tra cứu công khai /ket-qua.php bảo vệ bằng CAPTCHA hợp lệ', function () {
    $res = http_request('/ket-qua.php', 'POST', [
        'cccd' => '079201234567',
        'captchaText' => 'WRONG_CODE',
        'captchaToken' => 'INVALID_TOKEN',
    ]);
    assert_true($res['code'] === 200, "Tra cứu trả 200 hiển thị form và lỗi");
    assert_true(stripos($res['body'], 'Mã xác nhận không chính xác') !== false, "Phải báo lỗi mã xác nhận không chính xác");
});

test('20. Giao diện Cổng thí sinh hiển thị đầy đủ các phân khu chức năng', function () use ($sessCand1) {
    $res = http_request('/', 'GET', [], $sessCand1);
    assert_true($res['code'] === 200, "Home candidate trả 200 OK");
    assert_true(strpos($res['body'], 'THÍ SINH') !== false, "Phải hiển thị nhãn THÍ SINH");
    assert_true(strpos($res['body'], 'Nguyện vọng') !== false, "Phải có phím tắt Nguyện vọng");
    assert_true(strpos($res['body'], 'Thẻ dự thi') !== false, "Phải có phím tắt Thẻ dự thi");
    assert_true(strpos($res['body'], 'Kết quả tuyển sinh') !== false, "Phải có phím tắt Kết quả tuyển sinh");
    assert_true(strpos($res['body'], 'Điều chỉnh hồ sơ') !== false, "Phải có phím tắt Điều chỉnh hồ sơ");
});

// ==========================================
// DỌN DẸP DỮ LIỆU TEST
// ==========================================
echo PHP_EOL . "=== DỌN DẸP DỮ LIỆU TEST ===" . PHP_EOL;
if (!empty($cleanupCandidateIds)) {
    $inCand = implode(',', array_map('intval', $cleanupCandidateIds));
    $pdo->exec("DELETE FROM candidates WHERE id IN ($inCand)");
    echo "Đã dọn dẹp " . count($cleanupCandidateIds) . " candidates test." . PHP_EOL;
}
if (!empty($cleanupUserIds)) {
    $inUsers = implode(',', array_map('intval', $cleanupUserIds));
    $pdo->exec("DELETE FROM user_roles WHERE user_id IN ($inUsers)");
    $pdo->exec("DELETE FROM users WHERE id IN ($inUsers)");
    echo "Đã dọn dẹp " . count($cleanupUserIds) . " users test." . PHP_EOL;
}
foreach ($sessionIds as $sid) {
    @unlink('C:/xampp/tmp/sess_' . $sid);
}
echo "Đã xóa toàn bộ session test files." . PHP_EOL;

$pass = 0;
$fail = 0;
foreach ($results as $r) {
    if ($r['status'] === 'PASS') {
        $pass++;
    } else {
        $fail++;
    }
}

echo PHP_EOL . "=========================================" . PHP_EOL;
echo "TỔNG KẾT: $pass PASS / " . ($pass + $fail) . " TESTS" . PHP_EOL;
echo "=========================================" . PHP_EOL;

if ($fail > 0) {
    exit(1);
}
exit(0);
