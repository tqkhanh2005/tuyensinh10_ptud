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

function create_test_session(int $userId, string $username, array $roles): string
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

$adminSess = create_test_session(2, 'test_so_gdtd', ['so_gdtd']);
$candidateSess = create_test_session(431, 'candidate_test', ['candidate']);

// Find sample school, candidate, and staff ID
$sampleSchoolId = (int)$pdo->query('SELECT id FROM schools LIMIT 1')->fetchColumn() ?: 1;
$sampleCandidateId = (int)$pdo->query('SELECT id FROM candidates LIMIT 1')->fetchColumn() ?: 1;
$sampleStaffId = (int)$pdo->query('SELECT id FROM staff LIMIT 1')->fetchColumn() ?: 1;

$routes = [
    [
        'url' => '/admin/index.php',
        'title' => 'Tổng quan Quản trị',
        'active_text' => 'Tổng quan',
    ],
    [
        'url' => '/schools/index.php',
        'title' => 'Quản lý trường học',
        'active_text' => 'Quản lý trường học',
    ],
    [
        'url' => '/schools/create.php',
        'title' => 'Thêm trường học',
        'active_text' => 'Quản lý trường học',
    ],
    [
        'url' => '/schools/edit.php?id=' . $sampleSchoolId,
        'title' => 'Sửa trường học',
        'active_text' => 'Quản lý trường học',
    ],
    [
        'url' => '/candidates/index.php',
        'title' => 'Quản lý thí sinh',
        'active_text' => 'Quản lý thí sinh',
    ],
    [
        'url' => '/candidates/create.php',
        'title' => 'Thêm thí sinh',
        'active_text' => 'Quản lý thí sinh',
    ],
    [
        'url' => '/candidates/edit.php?id=' . $sampleCandidateId,
        'title' => 'Sửa thí sinh',
        'active_text' => 'Quản lý thí sinh',
    ],
    [
        'url' => '/candidates/import.php',
        'title' => 'Nhập danh sách thí sinh',
        'active_text' => 'Quản lý thí sinh',
    ],
    [
        'url' => '/staff/index.php',
        'title' => 'Quản lý cán bộ - giáo viên',
        'active_text' => 'Quản lý giáo viên',
    ],
    [
        'url' => '/staff/create.php',
        'title' => 'Thêm cán bộ - giáo viên',
        'active_text' => 'Quản lý giáo viên',
    ],
    [
        'url' => '/staff/edit.php?id=' . $sampleStaffId,
        'title' => 'Sửa cán bộ - giáo viên',
        'active_text' => 'Quản lý giáo viên',
    ],
    [
        'url' => '/staff/import.php',
        'title' => 'Nhập danh sách cán bộ - giáo viên',
        'active_text' => 'Quản lý giáo viên',
    ],
    [
        'url' => '/councils/index.php',
        'title' => 'Quản lý hội đồng thi',
        'active_text' => 'Quản lý kỳ thi',
    ],
    [
        'url' => '/councils/create.php',
        'title' => 'Thành lập hội đồng',
        'active_text' => 'Quản lý kỳ thi',
    ],
    [
        'url' => '/exam/rooms.php',
        'title' => 'Quản lý phòng thi',
        'active_text' => 'Quản lý kỳ thi',
    ],
    [
        'url' => '/exam/sites.php',
        'title' => 'Quản lý điểm thi',
        'active_text' => 'Quản lý kỳ thi',
    ],
    [
        'url' => '/exam/scores.php',
        'title' => 'Nhập điểm',
        'active_text' => 'Nhập điểm',
    ],
    [
        'url' => '/appeals/update_result.php',
        'title' => 'Quản lý phúc khảo',
        'active_text' => 'Phúc khảo',
    ],
    [
        'url' => '/admission/index.php',
        'title' => 'Kỳ tuyển sinh & Kế hoạch',
        'active_text' => 'Xét tuyển',
    ],
    [
        'url' => '/admission/calculate.php',
        'title' => 'Xét tuyển thí sinh',
        'active_text' => 'Xét tuyển',
    ],
    [
        'url' => '/statistics/index.php',
        'title' => 'Thống kê - Báo cáo tuyển sinh',
        'active_text' => 'Thống kê - báo cáo',
    ],
];

echo "=== KIỂM THỬ TÍCH HỢP ADMIN LAYOUT (" . count($routes) . " ROUTES) ===" . PHP_EOL;

foreach ($routes as $route) {
    test("Admin route [{$route['url']}] hiển thị đúng Admin Layout & Sidebar Active", function () use ($route, $adminSess) {
        $res = http_request($route['url'], 'GET', [], $adminSess);
        assert_true($res['code'] === 200, "Expected HTTP 200, got {$res['code']}");
        assert_true(strpos($res['body'], 'id="adminSidebar"') !== false, "Thiếu id=\"adminSidebar\"");
        assert_true(strpos($res['body'], 'class="portal-topbar"') !== false, "Thiếu portal-topbar");
        assert_true(strpos($res['body'], 'id="portalNavbar"') === false, "Phát hiện navbar công khai (portalNavbar) trong Admin Portal!");
        
        // Kiểm tra 5 nhóm sidebar
        assert_true(strpos($res['body'], 'TỔNG QUAN') !== false, "Thiếu nhóm TỔNG QUAN trong sidebar");
        assert_true(strpos($res['body'], 'DỮ LIỆU CƠ SỞ') !== false, "Thiếu nhóm DỮ LIỆU CƠ SỞ trong sidebar");
        assert_true(strpos($res['body'], 'KỲ THI') !== false, "Thiếu nhóm KỲ THI trong sidebar");
        assert_true(strpos($res['body'], 'TUYỂN SINH') !== false, "Thiếu nhóm TUYỂN SINH trong sidebar");
        assert_true(strpos($res['body'], 'CÀI ĐẶT') !== false, "Thiếu nhóm CÀI ĐẶT trong sidebar");

        // Kiểm tra nút Xem trang công khai
        assert_true(strpos($res['body'], 'Xem trang công khai') !== false, "Thiếu nút 'Xem trang công khai'");

        // Kiểm tra mục active
        preg_match_all('/class="[^"]*sidebar-nav-link[^"]*active[^"]*"[^>]*>(.*?)<\/a>/s', $res['body'], $matches);
        $activeLinksHtml = implode(' ', $matches[1] ?? []);
        assert_true(strpos($activeLinksHtml, $route['active_text']) !== false, "Mục [{$route['active_text']}] chưa được active đúng. Active hiện tại: " . strip_tags($activeLinksHtml));
    });
}

// Kiểm tra quyền từ chối
echo PHP_EOL . "=== KIỂM THỬ BẢO MẬT VÀ PHÂN QUYỀN ===" . PHP_EOL;

$guardedRoutes = [
    '/admin/index.php',
    '/schools/index.php',
    '/schools/create.php',
    '/candidates/index.php',
    '/staff/index.php',
    '/councils/index.php',
    '/admission/index.php',
];

foreach ($guardedRoutes as $gr) {
    test("Guest truy cập [$gr] bị redirect về /auth/login", function () use ($gr) {
        $res = http_request($gr, 'GET', []);
        assert_true($res['code'] === 302, "Expected 302, got {$res['code']}");
        assert_true(stripos($res['headers'], 'Location:') !== false, "Thiếu header Location");
    });

    test("Candidate truy cập [$gr] bị từ chối 403 Forbidden", function () use ($gr, $candidateSess) {
        $res = http_request($gr, 'GET', [], $candidateSess);
        assert_true($res['code'] === 403, "Expected 403, got {$res['code']}");
    });
}

// Cleanup sessions
@unlink('C:/xampp/tmp/sess_' . $adminSess);
@unlink('C:/xampp/tmp/sess_' . $candidateSess);

$totalPass = count(array_filter($results, fn($r) => $r['status'] === 'PASS'));
$totalTests = count($results);
echo PHP_EOL . "=====================================" . PHP_EOL;
echo "TỔNG KẾT: $totalPass PASS / $totalTests TESTS" . PHP_EOL;
echo "=====================================" . PHP_EOL;
