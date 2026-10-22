<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = db();
$results = [];

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

function create_test_session(int $userId, string $username, array $roles): array
{
    $sessId = bin2hex(random_bytes(16));
    $csrf = bin2hex(random_bytes(32));
    $data = [
        'auth' => [
            'user_id' => $userId,
            'id' => $userId,
            'username' => $username,
            'full_name' => 'Test ' . $username,
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
    return [$sessId, $csrf];
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

[$adminSess, $adminCsrf] = create_test_session(2, 'admin_test_quotas', ['admin']);
[$candidateSess, $candidateCsrf] = create_test_session(431, 'candidate_test_quotas', ['candidate']);

// Lấy 1 kỳ tuyển sinh mẫu để test
$samplePeriod = $pdo->query('SELECT * FROM admission_periods ORDER BY id DESC LIMIT 1')->fetch();
$periodId = $samplePeriod ? (int)$samplePeriod['id'] : 1;

// Lấy 1 trường THPT mẫu
$sampleSchool = $pdo->query("SELECT * FROM schools WHERE level='THPT' AND active=1 LIMIT 1")->fetch();
$schoolId = $sampleSchool ? (int)$sampleSchool['id'] : 1;

echo "=== KIỂM THỬ GIAO DIỆN VÀ NGHIỆP VỤ ADMIN PORTAL (KẾ HOẠCH & CHỈ TIÊU) ===" . PHP_EOL;

// 1. Kiểm tra admission/index.php
test('GET /admission/index.php hiển thị đầy đủ Admin layout và KPI cards', function () use ($adminSess) {
    $res = http_request('/admission/index.php', 'GET', [], $adminSess);
    assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
    assert_true(strpos($res['body'], 'id="adminSidebar"') !== false, "Thiếu id=adminSidebar");
    assert_true(strpos($res['body'], 'Kỳ tuyển sinh & Kế hoạch') !== false, "Thiếu tiêu đề trang");
    assert_true(strpos($res['body'], 'Tổng số kỳ') !== false, "Thiếu KPI card Tổng số kỳ");
    assert_true(strpos($res['body'], 'Đang công bố') !== false, "Thiếu KPI card Đang công bố");
    assert_true(strpos($res['body'], 'Bản nháp') !== false, "Thiếu KPI card Bản nháp");
    assert_true(strpos($res['body'], 'Đã đóng') !== false, "Thiếu KPI card Đã đóng");
    assert_true(strpos($res['body'], 'Quản lý chỉ tiêu') !== false, "Thiếu nút Quản lý chỉ tiêu");
    assert_true(strpos($res['body'], 'Chạy xét tuyển') !== false, "Thiếu nút Chạy xét tuyển");
    assert_true(strpos($res['body'], 'Tạo kỳ/kế hoạch mới') !== false, "Thiếu nút Tạo kỳ/kế hoạch mới");
});

// 2. Kiểm tra admission/create.php GET
test('GET /admission/create.php hiển thị đúng Admin layout, form Bootstrap và Breadcrumb', function () use ($adminSess) {
    $res = http_request('/admission/create.php', 'GET', [], $adminSess);
    assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
    assert_true(strpos($res['body'], 'id="adminSidebar"') !== false, "Thiếu id=adminSidebar");
    assert_true(strpos($res['body'], 'Thiết lập kế hoạch tuyển sinh') !== false, "Thiếu tiêu đề Thiết lập kế hoạch");
    assert_true(strpos($res['body'], '1. Thông tin kế hoạch') !== false, "Thiếu nhóm 1: Thông tin kế hoạch");
    assert_true(strpos($res['body'], '2. Thời gian đăng ký') !== false, "Thiếu nhóm 2: Thời gian đăng ký");
    assert_true(strpos($res['body'], '3. Nội dung kế hoạch') !== false, "Thiếu nhóm 3: Nội dung kế hoạch");
    assert_true(strpos($res['body'], 'name="name"') !== false, "Thiếu trường name");
    assert_true(strpos($res['body'], 'name="academic_year"') !== false, "Thiếu trường academic_year");
    assert_true(strpos($res['body'], 'name="admission_method"') !== false, "Thiếu trường admission_method");
    assert_true(strpos($res['body'], 'name="status"') !== false, "Thiếu trường status");
    assert_true(strpos($res['body'], 'name="registration_start"') !== false, "Thiếu trường registration_start");
    assert_true(strpos($res['body'], 'name="registration_end"') !== false, "Thiếu trường registration_end");
    assert_true(strpos($res['body'], 'name="plan_content"') !== false, "Thiếu trường plan_content");
    assert_true(strpos($res['body'], 'Bản nháp') !== false, "Thiếu nhãn tiếng Việt Bản nháp");
    assert_true(strpos($res['body'], 'Đang công bố') !== false, "Thiếu nhãn tiếng Việt Đang công bố");
    assert_true(strpos($res['body'], 'Đã đóng') !== false, "Thiếu nhãn tiếng Việt Đã đóng");
    assert_true(strpos($res['body'], 'Lưu kế hoạch') !== false, "Thiếu nút Lưu kế hoạch");
    assert_true(strpos($res['body'], 'Quay lại danh sách') !== false, "Thiếu nút Quay lại");
});

// 3. Kiểm tra validation khi submit lỗi ở admission/create.php
test('POST /admission/create.php validation lỗi giữ lại dữ liệu nhập và báo alert', function () use ($adminSess, $adminCsrf) {
    $res = http_request('/admission/create.php', 'POST', [
        'csrf_token' => $adminCsrf,
        'name' => '', // để trống
        'academic_year' => '',
        'admission_method' => 'Thi tuyển',
        'status' => 'draft',
        'plan_content' => 'Nội dung test giữ lại',
    ], $adminSess);
    
    assert_true($res['code'] === 200, "Expected 200 form reload on error, got {$res['code']}");
    assert_true(strpos($res['body'], 'alert-danger') !== false, "Thiếu alert-danger báo lỗi");
    assert_true(strpos($res['body'], 'Tên kế hoạch tuyển sinh là bắt buộc.') !== false, "Thiếu thông báo lỗi tên kế hoạch");
    assert_true(strpos($res['body'], 'Nội dung test giữ lại') !== false, "Dữ liệu người dùng nhập không được giữ lại");
});

// 4. Kiểm tra quotas/index.php không có period_id hợp lệ trả về 404
test('GET /quotas/index.php với period_id không tồn tại trả về 404 có layout', function () use ($adminSess) {
    $res = http_request('/quotas/index.php?period_id=999999999', 'GET', [], $adminSess);
    assert_true($res['code'] === 404, "Expected 404, got {$res['code']}");
    assert_true(strpos($res['body'], 'Không tìm thấy kỳ tuyển sinh') !== false, "Thiếu thông báo 404 thân thiện");
});

// 5. Kiểm tra quotas/index.php với period_id hợp lệ
test('GET /quotas/index.php hiển thị đúng Admin Layout, Breadcrumb, KPI và Form chỉ tiêu', function () use ($adminSess, $periodId) {
    $res = http_request('/quotas/index.php?period_id=' . $periodId, 'GET', [], $adminSess);
    assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
    assert_true(strpos($res['body'], 'id="adminSidebar"') !== false, "Thiếu id=adminSidebar");
    assert_true(strpos($res['body'], 'Quản lý chỉ tiêu tuyển sinh') !== false, "Thiếu tiêu đề Quản lý chỉ tiêu");
    assert_true(strpos($res['body'], 'Tổng số trường THPT') !== false, "Thiếu KPI Tổng số trường");
    assert_true(strpos($res['body'], 'Đã thiết lập chỉ tiêu') !== false, "Thiếu KPI Đã thiết lập");
    assert_true(strpos($res['body'], 'Tổng chỉ tiêu tuyển sinh') !== false, "Thiếu KPI Tổng chỉ tiêu");
    assert_true(strpos($res['body'], 'Tìm kiếm trường') !== false, "Thiếu bộ lọc tìm kiếm trường");
    assert_true(strpos($res['body'], 'Khu vực tuyển sinh') !== false, "Thiếu bộ lọc khu vực");
    assert_true(strpos($res['body'], 'Mã trường') !== false, "Thiếu cột Mã trường");
    assert_true(strpos($res['body'], 'Tên trường THPT') !== false, "Thiếu cột Tên trường");
    assert_true(strpos($res['body'], 'Chỉ tiêu tuyển sinh') !== false, "Thiếu cột Chỉ tiêu");
    assert_true(strpos($res['body'], 'Trạng thái') !== false, "Thiếu cột Trạng thái");
    assert_true(strpos($res['body'], 'Lưu tất cả chỉ tiêu') !== false, "Thiếu nút Lưu tất cả");
});

// 6. Kiểm tra validation quotas: từ chối 0, âm, số thập phân
test('POST /quotas/index.php từ chối số 0 hoặc số không hợp lệ và hiển thị alert-danger', function () use ($adminSess, $adminCsrf, $periodId, $schoolId) {
    $res = http_request('/quotas/index.php?period_id=' . $periodId, 'POST', [
        'csrf_token' => $adminCsrf,
        'quota' => [
            (string)$schoolId => '0', // 0 không hợp lệ
        ],
    ], $adminSess);

    assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
    assert_true(strpos($res['body'], 'alert-danger') !== false, "Thiếu alert-danger khi chỉ tiêu là 0");
    assert_true(strpos($res['body'], 'Chỉ tiêu tuyển sinh phải là số nguyên dương lớn hơn 0') !== false, "Thiếu thông báo lỗi hợp lệ");
});

// 7. Kiểm tra quotas: lưu chỉ tiêu hợp lệ thành công
test('POST /quotas/index.php lưu thành công với số nguyên dương hợp lệ', function () use ($adminSess, $adminCsrf, $periodId, $schoolId, $pdo) {
    $res = http_request('/quotas/index.php?period_id=' . $periodId, 'POST', [
        'csrf_token' => $adminCsrf,
        'quota' => [
            (string)$schoolId => '120',
        ],
    ], $adminSess);

    assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
    assert_true(strpos($res['body'], 'alert-success') !== false, "Thiếu alert-success khi lưu thành công");
    assert_true(strpos($res['body'], 'Lưu chỉ tiêu tuyển sinh thành công') !== false, "Thiếu thông báo thành công");
    
    // Kiểm tra DB
    $stmt = $pdo->prepare('SELECT quota, status FROM admission_quotas WHERE admission_period_id = :p AND school_id = :s');
    $stmt->execute(['p' => $periodId, 's' => $schoolId]);
    $saved = $stmt->fetch();
    assert_true($saved !== false && (int)$saved['quota'] === 120 && $saved['status'] === 'set', "DB quota không khớp với dữ liệu đã lưu");
});

// 8. Kiểm tra Sidebar Active State trên tất cả các route
test('Sidebar item [Xét tuyển] active đúng trên /admission/index.php, /admission/create.php, /quotas/index.php, /admission/calculate.php', function () use ($adminSess, $periodId) {
    $urls = [
        '/admission/index.php',
        '/admission/create.php',
        '/quotas/index.php?period_id=' . $periodId,
        '/admission/calculate.php',
    ];

    foreach ($urls as $u) {
        $res = http_request($u, 'GET', [], $adminSess);
        assert_true($res['code'] === 200, "Route $u returned {$res['code']}");
        preg_match_all('/class="[^"]*sidebar-nav-link[^"]*active[^"]*"[^>]*>(.*?)<\/a>/s', $res['body'], $matches);
        $activeLinksHtml = implode(' ', $matches[1] ?? []);
        assert_true(strpos($activeLinksHtml, 'Xét tuyển') !== false, "Trên $u, mục [Xét tuyển] chưa active. Active: " . strip_tags($activeLinksHtml));
    }
});

// 9. Kiểm tra Bảo mật và Phân quyền: Guest bị redirect, Candidate bị 403
echo PHP_EOL . "=== KIỂM THỬ BẢO MẬT & PHÂN QUYỀN (GUEST / CANDIDATE) ===" . PHP_EOL;

$testedRoutes = [
    '/admission/index.php',
    '/admission/create.php',
    '/quotas/index.php?period_id=' . $periodId,
];

foreach ($testedRoutes as $route) {
    test("Guest truy cập [$route] bị redirect về /auth/login", function () use ($route) {
        $res = http_request($route, 'GET', []);
        assert_true($res['code'] === 302, "Expected 302, got {$res['code']}");
        assert_true(stripos($res['headers'], 'Location:') !== false, "Thiếu Location header");
    });

    test("Candidate truy cập [$route] bị từ chối 403 Forbidden", function () use ($route, $candidateSess) {
        $res = http_request($route, 'GET', [], $candidateSess);
        assert_true($res['code'] === 403, "Expected 403, got {$res['code']}");
    });
}

// Dọn dẹp session
@unlink('C:/xampp/tmp/sess_' . $adminSess);
@unlink('C:/xampp/tmp/sess_' . $candidateSess);

$totalPass = count(array_filter($results, fn($r) => $r['status'] === 'PASS'));
$totalTests = count($results);
echo PHP_EOL . "=====================================" . PHP_EOL;
echo "TỔNG KẾT: $totalPass PASS / $totalTests TESTS" . PHP_EOL;
echo "=====================================" . PHP_EOL;
