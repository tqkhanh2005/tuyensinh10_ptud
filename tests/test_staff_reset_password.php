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
            'full_name' => 'Test User',
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

// Lấy thông tin tài khoản admin test/admin hiện có
$adminUser = $pdo->query('
    SELECT u.id, u.username 
    FROM users u 
    JOIN user_roles ur ON ur.user_id = u.id 
    JOIN roles r ON r.id = ur.role_id 
    WHERE r.code = "admin" AND u.active = 1 
    LIMIT 1
')->fetch(PDO::FETCH_ASSOC);

if (!$adminUser) {
    // Thử so_gdtd
    $adminUser = $pdo->query('
        SELECT u.id, u.username 
        FROM users u 
        JOIN user_roles ur ON ur.user_id = u.id 
        JOIN roles r ON r.id = ur.role_id 
        WHERE r.code = "so_gdtd" AND u.active = 1 
        LIMIT 1
    ')->fetch(PDO::FETCH_ASSOC);
}
if (!$adminUser) {
    throw new RuntimeException('Cần ít nhất một tài khoản admin hoặc so_gdtd active để kiểm thử.');
}

// Lấy thông tin hai giáo viên test
$thcsStaff = $pdo->query('
    SELECT s.id as staff_id, s.user_id, u.username 
    FROM staff s 
    JOIN users u ON u.id = s.user_id 
    WHERE u.username = "teacher_thcs_test" 
    LIMIT 1
')->fetch(PDO::FETCH_ASSOC);

$thptStaff = $pdo->query('
    SELECT s.id as staff_id, s.user_id, u.username 
    FROM staff s 
    JOIN users u ON u.id = s.user_id 
    WHERE u.username = "teacher_thpt_test" 
    LIMIT 1
')->fetch(PDO::FETCH_ASSOC);

if (!$thcsStaff || !$thptStaff) {
    throw new RuntimeException('Không tìm thấy tài khoản teacher_thcs_test hoặc teacher_thpt_test trong database.');
}

// Lấy 1 tài khoản candidate để test phân quyền
$candUser = $pdo->query('
    SELECT u.id, u.username 
    FROM users u 
    JOIN user_roles ur ON ur.user_id = u.id 
    JOIN roles r ON r.id = ur.role_id 
    WHERE r.code = "candidate" AND u.active = 1 
    LIMIT 1
')->fetch(PDO::FETCH_ASSOC);

$cleanupSessions = [];
$cleanupStaff = [];
$cleanupUsers = [];

echo "=== BẮT ĐẦU KIỂM THỬ: ĐẶT LẠI MẬT KHẨU TÀI KHOẢN TEACHER PORTAL ===" . PHP_EOL;

// 1. Guest bị chặn
test('1. Guest bị chặn khi truy cập trang đặt lại mật khẩu', function () use ($thcsStaff) {
    $resGet = http_request('/staff/reset_password.php?id=' . (int)$thcsStaff['staff_id']);
    assert_true($resGet['code'] === 302, 'Guest GET phải nhận HTTP 302 redirect');
    assert_true(stripos($resGet['headers'], 'Location:') !== false && stripos($resGet['headers'], '/auth/login') !== false,
        'Guest GET phải bị redirect về trang đăng nhập');

    $resPost = http_request('/staff/reset_password.php', 'POST', ['id' => (int)$thcsStaff['staff_id']]);
    assert_true($resPost['code'] === 302, 'Guest POST phải nhận HTTP 302 redirect');
    assert_true(stripos($resPost['headers'], '/auth/login') !== false, 'Guest POST phải bị redirect về login');
});

// 2. Candidate bị chặn
test('2. Candidate bị chặn (403 Forbidden)', function () use ($candUser, $thcsStaff, &$cleanupSessions) {
    $candSess = create_test_session_file((int)($candUser['id'] ?? 999), $candUser['username'] ?? 'cand_test', ['candidate']);
    $cleanupSessions[] = $candSess;

    $resGet = http_request('/staff/reset_password.php?id=' . (int)$thcsStaff['staff_id'], 'GET', [], $candSess);
    assert_true($resGet['code'] === 403, 'Candidate GET phải nhận HTTP 403 Forbidden, nhận được: ' . $resGet['code']);

    $resPost = http_request('/staff/reset_password.php', 'POST', [
        'id' => (int)$thcsStaff['staff_id'],
        'password' => 'NewPassword123!',
        'password_confirm' => 'NewPassword123!',
    ], $candSess);
    assert_true($resPost['code'] === 403, 'Candidate POST phải nhận HTTP 403 Forbidden, nhận được: ' . $resPost['code']);
});

// 3. Admin / Sở có thể truy cập form đặt lại mật khẩu
test('3. Admin / Sở có thể mở form đặt lại mật khẩu cho giáo viên', function () use ($adminUser, $thcsStaff, &$cleanupSessions) {
    $adminSess = create_test_session_file((int)$adminUser['id'], $adminUser['username'], ['admin']);
    $cleanupSessions[] = $adminSess;

    $res = http_request('/staff/reset_password.php?id=' . (int)$thcsStaff['staff_id'], 'GET', [], $adminSess);
    assert_true($res['code'] === 200, 'Admin GET phải nhận HTTP 200 OK, nhận được: ' . $res['code']);
    assert_true(stripos($res['body'], 'Đặt lại mật khẩu') !== false, 'Trang phải chứa tiêu đề Đặt lại mật khẩu');
    assert_true(stripos($res['body'], 'name="csrf_token"') !== false, 'Trang phải chứa input csrf_token');
    assert_true(stripos($res['body'], 'name="password"') !== false, 'Trang phải chứa input password');
    assert_true(stripos($res['body'], 'name="password_confirm"') !== false, 'Trang phải chứa input password_confirm');
    assert_true(stripos($res['body'], $thcsStaff['username']) !== false, 'Trang phải hiển thị username của giáo viên');
});

// 4. Thiếu hoặc sai CSRF bị từ chối
test('4. Thiếu hoặc sai CSRF token trên POST bị từ chối', function () use ($adminUser, $thcsStaff, &$cleanupSessions) {
    $adminSess = create_test_session_file((int)$adminUser['id'], $adminUser['username'], ['admin']);
    $cleanupSessions[] = $adminSess;

    // Không gửi CSRF
    $resNoCsrf = http_request('/staff/reset_password.php?id=' . (int)$thcsStaff['staff_id'], 'POST', [
        'id' => (int)$thcsStaff['staff_id'],
        'password' => 'SomePass123',
        'password_confirm' => 'SomePass123',
    ], $adminSess);
    assert_true(in_array($resNoCsrf['code'], [400, 403], true), 'Thiếu CSRF phải bị từ chối 400 hoặc 403, nhận được: ' . $resNoCsrf['code']);

    // CSRF sai
    $resBadCsrf = http_request('/staff/reset_password.php?id=' . (int)$thcsStaff['staff_id'], 'POST', [
        'id' => (int)$thcsStaff['staff_id'],
        'csrf_token' => 'invalid_csrf_token_value_xyz',
        'password' => 'SomePass123',
        'password_confirm' => 'SomePass123',
    ], $adminSess);
    assert_true(in_array($resBadCsrf['code'], [400, 403], true), 'Sai CSRF phải bị từ chối 400 hoặc 403, nhận được: ' . $resBadCsrf['code']);
});

// 5. Validation form: mật khẩu ngắn hơn 6 ký tự hoặc không khớp
test('5. Mật khẩu ngắn hơn 6 ký tự hoặc không khớp bị từ chối với thông báo lỗi', function () use ($adminUser, $thcsStaff, &$cleanupSessions) {
    $adminCsrf = bin2hex(random_bytes(32));
    $adminSess = create_test_session_file((int)$adminUser['id'], $adminUser['username'], ['admin'], $adminCsrf);
    $cleanupSessions[] = $adminSess;

    // Mật khẩu quá ngắn
    $resShort = http_request('/staff/reset_password.php?id=' . (int)$thcsStaff['staff_id'], 'POST', [
        'id' => (int)$thcsStaff['staff_id'],
        'csrf_token' => $adminCsrf,
        'password' => '123',
        'password_confirm' => '123',
    ], $adminSess);
    assert_true($resShort['code'] === 200, 'Lỗi validation phải trả về form với HTTP 200');
    assert_true(stripos($resShort['body'], 'tối thiểu 6 ký tự') !== false, 'Phải thông báo lỗi độ dài tối thiểu 6 ký tự');

    // Mật khẩu xác nhận không khớp
    $resMismatch = http_request('/staff/reset_password.php?id=' . (int)$thcsStaff['staff_id'], 'POST', [
        'id' => (int)$thcsStaff['staff_id'],
        'csrf_token' => $adminCsrf,
        'password' => 'ValidPass123',
        'password_confirm' => 'DifferentPass456',
    ], $adminSess);
    assert_true($resMismatch['code'] === 200, 'Lỗi mismatch phải trả về form với HTTP 200');
    assert_true(stripos($resMismatch['body'], 'không khớp') !== false, 'Phải thông báo lỗi mật khẩu không khớp');
});

// 6. Không đặt mật khẩu qua GET
test('6. Không cho phép đổi mật khẩu qua method GET', function () use ($adminUser, $thcsStaff, $pdo, &$cleanupSessions) {
    $adminSess = create_test_session_file((int)$adminUser['id'], $adminUser['username'], ['admin']);
    $cleanupSessions[] = $adminSess;

    $oldHash = $pdo->query('SELECT password_hash FROM users WHERE id = ' . (int)$thcsStaff['user_id'])->fetchColumn();

    $res = http_request('/staff/reset_password.php', 'GET', [
        'id' => (int)$thcsStaff['staff_id'],
        'password' => 'HackedPasswordViaGet123',
        'password_confirm' => 'HackedPasswordViaGet123',
    ], $adminSess);

    $currentHash = $pdo->query('SELECT password_hash FROM users WHERE id = ' . (int)$thcsStaff['user_id'])->fetchColumn();
    assert_true($currentHash === $oldHash, 'GET request không được phép thay đổi password_hash');
});

// 7. Đặt lại mật khẩu thành công cho teacher_thcs_test
$newPassTHCS = 'TeacherThcsPass@' . bin2hex(random_bytes(4));
test('7. Đặt lại mật khẩu thành công cho teacher_thcs_test và redirect về staff/index.php?reset=1', function () use ($adminUser, $thcsStaff, $newPassTHCS, &$cleanupSessions) {
    $adminCsrf = bin2hex(random_bytes(32));
    $adminSess = create_test_session_file((int)$adminUser['id'], $adminUser['username'], ['admin'], $adminCsrf);
    $cleanupSessions[] = $adminSess;

    $res = http_request('/staff/reset_password.php?id=' . (int)$thcsStaff['staff_id'], 'POST', [
        'id' => (int)$thcsStaff['staff_id'],
        'csrf_token' => $adminCsrf,
        'password' => $newPassTHCS,
        'password_confirm' => $newPassTHCS,
    ], $adminSess);

    assert_true($res['code'] === 302, 'Đặt lại thành công phải nhận 302 redirect, nhận được: ' . $res['code']);
    assert_true(stripos($res['headers'], '/staff/index.php?reset=1') !== false,
        'Phải redirect về /staff/index.php?reset=1, headers: ' . $res['headers']);

    // Kiểm tra trang /staff/index.php?reset=1 có hiển thị thông báo thành công
    $resIndex = http_request('/staff/index.php?reset=1', 'GET', [], $adminSess);
    assert_true($resIndex['code'] === 200, 'staff/index.php phải trả về 200 OK');
    assert_true(stripos($resIndex['body'], 'Đặt lại mật khẩu tài khoản thành công') !== false,
        'staff/index.php phải hiển thị thông báo "Đặt lại mật khẩu tài khoản thành công"');
    assert_true(stripos($resIndex['body'], 'Đặt lại mật khẩu') !== false,
        'staff/index.php phải có nút "Đặt lại mật khẩu" trong danh sách');
});

// 8. Đăng nhập bằng mật khẩu mới thành công và mật khẩu cũ không còn đăng nhập được
test('8. Mật khẩu mới đăng nhập được vào Teacher Portal, mật khẩu sai bị từ chối', function () use ($newPassTHCS) {
    // Mật khẩu sai / cũ bị từ chối
    $failAuth = auth_login('teacher_thcs_test', 'WrongOldPassword123456');
    assert_true($failAuth === false, 'Mật khẩu sai/cũ không được phép đăng nhập');

    // Mật khẩu mới thành công
    $successAuth = auth_login('teacher_thcs_test', $newPassTHCS);
    assert_true($successAuth === true, 'Mật khẩu mới phải đăng nhập thành công');

    $authUser = auth_user();
    assert_true($authUser !== null, 'auth_user phải tồn tại sau đăng nhập');
    assert_true($authUser['username'] === 'teacher_thcs_test', 'Username trong auth_user phải là teacher_thcs_test');
    assert_true(in_array('teacher', $authUser['roles'], true), 'User phải có role teacher');

    // Landing URL là /teacher/index.php
    $landingUrl = auth_landing_url();
    assert_true(stripos($landingUrl, '/teacher/index.php') !== false,
        'Giáo viên sau khi đăng nhập phải được điều hướng tới /teacher/index.php, nhận được: ' . $landingUrl);

    // Dọn dẹp session auth hiện tại
    unset($_SESSION['auth']);
});

// 9. Đặt lại mật khẩu thành công cho teacher_thpt_test
$newPassTHPT = 'TeacherThptPass@' . bin2hex(random_bytes(4));
test('9. Đặt lại mật khẩu thành công cho teacher_thpt_test', function () use ($adminUser, $thptStaff, $newPassTHPT, &$cleanupSessions) {
    $adminCsrf = bin2hex(random_bytes(32));
    $adminSess = create_test_session_file((int)$adminUser['id'], $adminUser['username'], ['admin'], $adminCsrf);
    $cleanupSessions[] = $adminSess;

    $res = http_request('/staff/reset_password.php?id=' . (int)$thptStaff['staff_id'], 'POST', [
        'id' => (int)$thptStaff['staff_id'],
        'csrf_token' => $adminCsrf,
        'password' => $newPassTHPT,
        'password_confirm' => $newPassTHPT,
    ], $adminSess);

    assert_true($res['code'] === 302, 'Đặt lại thành công phải nhận 302 redirect');
    assert_true(stripos($res['headers'], '/staff/index.php?reset=1') !== false, 'Redirect về staff/index.php?reset=1');

    // Đăng nhập tài khoản THPT với mật khẩu mới
    $successAuth = auth_login('teacher_thpt_test', $newPassTHPT);
    assert_true($successAuth === true, 'teacher_thpt_test với mật khẩu mới phải đăng nhập thành công');

    $landingUrl = auth_landing_url();
    assert_true(stripos($landingUrl, '/teacher/index.php') !== false, 'teacher_thpt_test landing tại /teacher/index.php');

    unset($_SESSION['auth']);
});

// 10. Không thể đặt lại mật khẩu cho staff không có tài khoản hoặc user không có role teacher
test('10. Từ chối đặt lại mật khẩu cho staff không có tài khoản hoặc không có role teacher', function () use ($pdo, $adminUser, &$cleanupStaff, &$cleanupUsers, &$cleanupSessions) {
    $adminSess = create_test_session_file((int)$adminUser['id'], $adminUser['username'], ['admin']);
    $cleanupSessions[] = $adminSess;

    // Tạo staff test KHÔNG có user_id
    $tag = 'TEST_NO_ACC_' . bin2hex(random_bytes(3));
    $pdo->prepare('
        INSERT INTO staff (full_name, workplace, staff_role, phone, email, active, created_at, updated_at)
        VALUES (:fn, "Test School", "Giáo viên", "0900112233", :email, 1, NOW(), NOW())
    ')->execute([
        'fn' => 'Cán Bộ Không Tài Khoản ' . $tag,
        'email' => strtolower($tag) . '@test.edu.vn',
    ]);
    $noAccStaffId = (int)$pdo->lastInsertId();
    $cleanupStaff[] = $noAccStaffId;

    // Thử truy cập form đặt lại cho staff không có tài khoản
    $resNoAcc = http_request('/staff/reset_password.php?id=' . $noAccStaffId, 'GET', [], $adminSess);
    assert_true($resNoAcc['code'] === 200, 'HTTP code 200');
    assert_true(stripos($resNoAcc['body'], 'chưa được cấp tài khoản') !== false,
        'Phải hiển thị cảnh báo cán bộ chưa được cấp tài khoản');
    assert_true(stripos($resNoAcc['body'], '<form') === false,
        'Không được hiển thị form đặt lại mật khẩu nếu không có tài khoản');

    // Thử gọi trực tiếp service staff_reset_password() cho staff không có user_id
    $threwNoAcc = false;
    try {
        staff_reset_password($noAccStaffId, 'NewPassword123');
    } catch (RuntimeException $e) {
        $threwNoAcc = true;
    }
    assert_true($threwNoAcc, 'staff_reset_password() phải ném RuntimeException khi staff không có user_id');

    // Tạo staff test liên kết với user có role CANDIDATE (không phải teacher)
    $pdo->prepare('INSERT INTO users (username, password_hash, full_name, active, created_at, updated_at) VALUES (:u, :p, :fn, 1, NOW(), NOW())')
        ->execute([
            'u' => 'fake_cand_' . strtolower($tag),
            'p' => password_hash('Pass123456', PASSWORD_DEFAULT),
            'fn' => 'Fake Candidate ' . $tag,
        ]);
    $fakeUserId = (int)$pdo->lastInsertId();
    $cleanupUsers[] = $fakeUserId;

    // Gán role candidate
    $candRoleId = (int)$pdo->query('SELECT id FROM roles WHERE code = "candidate"')->fetchColumn();
    $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:u, :r)')->execute(['u' => $fakeUserId, 'r' => $candRoleId]);

    // Tạo staff liên kết fakeUserId
    $pdo->prepare('
        INSERT INTO staff (user_id, full_name, workplace, staff_role, phone, email, active, created_at, updated_at)
        VALUES (:uid, :fn, "Test School", "Thí sinh gán nhầm", "0900998877", :email, 1, NOW(), NOW())
    ')->execute([
        'uid' => $fakeUserId,
        'fn' => 'Cán Bộ Role Candidate ' . $tag,
        'email' => 'fake_cand_' . strtolower($tag) . '@test.edu.vn',
    ]);
    $candStaffId = (int)$pdo->lastInsertId();
    $cleanupStaff[] = $candStaffId;

    // Form phải cảnh báo chỉ cho phép giáo viên
    $resCandStaff = http_request('/staff/reset_password.php?id=' . $candStaffId, 'GET', [], $adminSess);
    assert_true(stripos($resCandStaff['body'], 'Chỉ cho phép đặt lại mật khẩu cho tài khoản có vai trò Giáo viên') !== false,
        'Phải từ chối tài khoản không phải teacher');

    // Service phải từ chối
    $threwRole = false;
    try {
        staff_reset_password($candStaffId, 'NewPassword123');
    } catch (RuntimeException $e) {
        $threwRole = true;
    }
    assert_true($threwRole, 'staff_reset_password() phải ném RuntimeException khi user không có role teacher');
});

// 11. Kiểm tra an toàn: Không lộ password_hash hay mật khẩu trong response và log
test('11. Tuyệt đối không lộ password_hash hoặc mật khẩu trong response HTML', function () use ($adminUser, $thcsStaff, &$cleanupSessions) {
    $adminSess = create_test_session_file((int)$adminUser['id'], $adminUser['username'], ['admin']);
    $cleanupSessions[] = $adminSess;

    $res = http_request('/staff/reset_password.php?id=' . (int)$thcsStaff['staff_id'], 'GET', [], $adminSess);
    assert_true(strpos($res['body'], '$2y$') === false, 'Response HTML không được chứa bcrypt hash ($2y$)');
    assert_true(strpos($res['body'], '$2a$') === false, 'Response HTML không được chứa bcrypt hash ($2a$)');
    assert_true(strpos($res['body'], '$2b$') === false, 'Response HTML không được chứa bcrypt hash ($2b$)');
    assert_true(stripos($res['body'], 'password_hash') === false, 'Response HTML không được chứa chuỗi password_hash');
});

// 12. Kiểm tra nút "Đặt lại mật khẩu" trên trang staff/edit.php
test('12. Nút "Đặt lại mật khẩu" xuất hiện trên trang staff/edit.php cho cán bộ có tài khoản', function () use ($adminUser, $thcsStaff, &$cleanupSessions) {
    $adminSess = create_test_session_file((int)$adminUser['id'], $adminUser['username'], ['admin']);
    $cleanupSessions[] = $adminSess;

    $res = http_request('/staff/edit.php?id=' . (int)$thcsStaff['staff_id'], 'GET', [], $adminSess);
    assert_true($res['code'] === 200, 'staff/edit.php phải trả về 200 OK');
    assert_true(stripos($res['body'], 'staff/reset_password.php?id=' . (int)$thcsStaff['staff_id']) !== false,
        'staff/edit.php phải có link tới staff/reset_password.php');
    assert_true(stripos($res['body'], 'Đặt lại mật khẩu') !== false,
        'staff/edit.php phải có nút "Đặt lại mật khẩu"');
});

// DỌN DẸP DỮ LIỆU TẠM
echo PHP_EOL . "=== DỌN DẸP DỮ LIỆU TEST TẠM ===" . PHP_EOL;
if (!empty($cleanupStaff)) {
    $inS = implode(',', array_map('intval', $cleanupStaff));
    $pdo->exec("DELETE FROM staff WHERE id IN ($inS)");
    echo "Đã dọn dẹp " . count($cleanupStaff) . " staff test tạm." . PHP_EOL;
}
if (!empty($cleanupUsers)) {
    $inU = implode(',', array_map('intval', $cleanupUsers));
    $pdo->exec("DELETE FROM user_roles WHERE user_id IN ($inU)");
    $pdo->exec("DELETE FROM users WHERE id IN ($inU)");
    echo "Đã dọn dẹp " . count($cleanupUsers) . " users test tạm." . PHP_EOL;
}
foreach ($cleanupSessions as $sid) {
    @unlink('C:/xampp/tmp/sess_' . $sid);
}

echo PHP_EOL . "=== HOÀN TẤT KIỂM THỬ ĐẶT LẠI MẬT KHẨU ===" . PHP_EOL;
