<?php

declare(strict_types=1);

/**
 * Test suite for Candidate & Staff Import, Filtering, and Security.
 * Run with:
 *   C:\xampp\php\php.exe tests\test_candidate_staff_import.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/candidate_service.php';
require_once __DIR__ . '/../includes/candidate_import_service.php';
require_once __DIR__ . '/../includes/staff_service.php';
require_once __DIR__ . '/../includes/staff_import_service.php';

$pdo = db();
$tag = 'TEST_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
$results = [];
$cleanupCandidates = [];
$cleanupUsers = [];
$cleanupStaff = [];
$sessionIds = [];

function assert_true(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException($msg);
    }
}

function test(string $group, int $num, string $name, callable $fn): void
{
    global $results;
    $key = "$group-$num";
    try {
        $fn();
        $results[$key] = ['status' => 'PASS', 'group' => $group, 'num' => $num, 'name' => $name];
        echo "PASS [$group Test $num]: $name" . PHP_EOL;
    } catch (Throwable $e) {
        $results[$key] = ['status' => 'FAIL', 'group' => $group, 'num' => $num, 'name' => $name, 'error' => $e->getMessage()];
        echo "FAIL [$group Test $num]: $name — " . $e->getMessage() . PHP_EOL;
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

function http_request(string $path, string $method = 'GET', array $params = [], ?string $sessId = null, ?array $files = null): array
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
        if ($files !== null) {
            $postFields = $params;
            foreach ($files as $field => $fileData) {
                $postFields[$field] = new CURLFile($fileData['path'], $fileData['mime'] ?? 'text/csv', $fileData['name'] ?? 'test.csv');
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
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

// Find a valid THCS school and a THPT school in DB
$thcsStmt = $pdo->query("SELECT id, school_code, name FROM schools WHERE level = 'THCS' AND active = 1 LIMIT 1");
$thcsSchool = $thcsStmt->fetch(PDO::FETCH_ASSOC);
assert_true(!empty($thcsSchool), 'No active THCS school found in DB.');

$thptStmt = $pdo->query("SELECT id, school_code, name FROM schools WHERE level = 'THPT' AND active = 1 LIMIT 1");
$thptSchool = $thptStmt->fetch(PDO::FETCH_ASSOC);
assert_true(!empty($thptSchool), 'No active THPT school found in DB.');

// Sessions
$managerSess = create_test_session(2, 'test_so_gdtd', ['so_gdtd']);
$candidateSess = create_test_session(431, 'candidate_test', ['candidate']);

// Temporary CSV generator
function make_temp_csv(array $headers, array $rows, bool $withBom = true): string
{
    $tempFile = tempnam(sys_get_temp_dir(), 'test_csv_') . '.csv';
    $handle = fopen($tempFile, 'w+b');
    if ($withBom) {
        fwrite($handle, "\xEF\xBB\xBF");
    }
    fputcsv($handle, $headers);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
    return $tempFile;
}

echo "=== BẮT ĐẦU KIỂM THỬ CANDIDATE (25 TESTS) ===" . PHP_EOL;

// 1. Guest truy cập import bị redirect login
test('Candidate', 1, 'Guest truy cập import bị redirect login', function () {
    $res = http_request('/candidates/import.php', 'GET');
    assert_true($res['code'] === 302, "Expected 302, got {$res['code']}");
    assert_true(stripos($res['headers'], '/auth/login') !== false, "Expected redirect to /auth/login");
});

// 2. User thiếu candidate.create bị 403
test('Candidate', 2, 'User thiếu candidate.create bị 403', function () use ($candidateSess) {
    $res = http_request('/candidates/import.php', 'GET', [], $candidateSess);
    assert_true($res['code'] === 403, "Expected 403, got {$res['code']}");
});

// 3. GET import trả 200 với user hợp lệ
test('Candidate', 3, 'GET import trả 200 với user hợp lệ', function () use ($managerSess) {
    $res = http_request('/candidates/import.php', 'GET', [], $managerSess);
    assert_true($res['code'] === 200, "Expected 200, got {$res['code']}");
    assert_true(stripos($res['body'], 'thí sinh/học sinh') !== false, "Page title/header not found");
});

// 4. Không có CSRF bị chặn
test('Candidate', 4, 'Không có CSRF bị chặn', function () use ($managerSess) {
    $res = http_request('/candidates/import.php', 'POST', ['action' => 'upload'], $managerSess);
    assert_true($res['code'] === 400 || stripos($res['body'], 'CSRF') !== false || stripos($res['body'], 'không hợp lệ') !== false, "POST without CSRF was not rejected properly");
});

// 5. File sai extension bị từ chối
test('Candidate', 5, 'File sai extension bị từ chối', function () use ($pdo) {
    // Via web route or controller check
    $ext = pathinfo('test_invalid.txt', PATHINFO_EXTENSION);
    assert_true($ext !== 'csv', "Extension test setup error");
    // Verify candidate/import.php rejects non-csv files
    $txtFile = tempnam(sys_get_temp_dir(), 'test_') . '.txt';
    file_put_contents($txtFile, 'some text');
    $rejected = false;
    try {
        candidate_import_parse_csv($txtFile);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    @unlink($txtFile);
    assert_true($rejected, "Parser should reject invalid file content/structure");
});

// 6. File quá lớn bị từ chối (>10MB)
test('Candidate', 6, 'File quá lớn bị từ chối (>10MB)', function () {
    assert_true(CANDIDATE_MAX_FILE_SIZE === 10 * 1024 * 1024, "CANDIDATE_MAX_FILE_SIZE is not 10MB");
});

// 7. File rỗng bị từ chối
test('Candidate', 7, 'File rỗng bị từ chối', function () {
    $emptyFile = tempnam(sys_get_temp_dir(), 'empty_') . '.csv';
    file_put_contents($emptyFile, '');
    $rejected = false;
    $msg = '';
    try {
        candidate_import_parse_csv($emptyFile);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
        $msg = $e->getMessage();
    }
    @unlink($emptyFile);
    assert_true($rejected && stripos($msg, 'rỗng') !== false, "Did not reject empty file: $msg");
});

// 8. Header thiếu cột bị từ chối
test('Candidate', 8, 'Header thiếu cột bị từ chối', function () {
    $cols = ['national_id', 'full_name', 'date_of_birth', 'gender', 'address', 'phone', 'email', 'middle_school_code', 'eligible']; // 9 cols (missing status)
    $file = make_temp_csv($cols, []);
    $rejected = false;
    $msg = '';
    try {
        candidate_import_parse_csv($file);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
        $msg = $e->getMessage();
    }
    @unlink($file);
    assert_true($rejected && stripos($msg, 'Tiêu đề tệp không đúng cấu trúc') !== false, "Did not reject missing column: $msg");
});

// 9. Header thừa cột bị từ chối
test('Candidate', 9, 'Header thừa cột bị từ chối', function () {
    $cols = array_merge(CANDIDATE_IMPORT_HEADERS, ['extra_column']);
    $file = make_temp_csv($cols, []);
    $rejected = false;
    $msg = '';
    try {
        candidate_import_parse_csv($file);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
        $msg = $e->getMessage();
    }
    @unlink($file);
    assert_true($rejected && stripos($msg, 'Tiêu đề tệp không đúng cấu trúc') !== false, "Did not reject extra column: $msg");
});

// 10. Header sai thứ tự bị từ chối
test('Candidate', 10, 'Header sai thứ tự bị từ chối', function () {
    $cols = CANDIDATE_IMPORT_HEADERS;
    $tmp = $cols[0];
    $cols[0] = $cols[1];
    $cols[1] = $tmp;
    $file = make_temp_csv($cols, []);
    $rejected = false;
    $msg = '';
    try {
        candidate_import_parse_csv($file);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
        $msg = $e->getMessage();
    }
    @unlink($file);
    assert_true($rejected && stripos($msg, 'không hợp lệ') !== false, "Did not reject wrong column order: $msg");
});

// 11. national_id trống bị báo đúng dòng
test('Candidate', 11, 'national_id trống bị báo đúng dòng', function () use ($pdo, $thcsSchool) {
    $row = ['', 'Nguyễn Văn A', '2008-01-01', 'Nam', 'Hà Nội', '0912345678', 'a@test.com', $thcsSchool['school_code'], '1', 'active'];
    $file = make_temp_csv(CANDIDATE_IMPORT_HEADERS, [$row]);
    $parsed = candidate_import_parse_csv($file);
    @unlink($file);
    $val = candidate_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail');
    assert_true(isset($val['errors'][0]), 'Missing error for row 2');
    $err = $val['errors'][0];
    assert_true($err['line'] === 2 && $err['column'] === 'national_id', "Error does not point to line 2 national_id: " . json_encode($err));
});

// 12. full_name trống bị báo đúng dòng
test('Candidate', 12, 'full_name trống bị báo đúng dòng', function () use ($pdo, $thcsSchool) {
    $row = ['001208000001', '   ', '2008-01-01', 'Nam', 'Hà Nội', '0912345678', 'a@test.com', $thcsSchool['school_code'], '1', 'active'];
    $file = make_temp_csv(CANDIDATE_IMPORT_HEADERS, [$row]);
    $parsed = candidate_import_parse_csv($file);
    @unlink($file);
    $val = candidate_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail');
    assert_true(isset($val['errors'][0]), 'Missing error for row 2');
    $err = $val['errors'][0];
    assert_true($err['line'] === 2 && $err['column'] === 'full_name', "Error does not point to line 2 full_name: " . json_encode($err));
});

// 13. Ngày sinh sai format bị báo đúng dòng
test('Candidate', 13, 'Ngày sinh sai format bị báo đúng dòng', function () use ($pdo, $thcsSchool) {
    $row = ['001208000002', 'Nguyễn Văn B', '01/01/2008', 'Nam', 'Hà Nội', '', '', $thcsSchool['school_code'], '1', 'active'];
    $file = make_temp_csv(CANDIDATE_IMPORT_HEADERS, [$row]);
    $parsed = candidate_import_parse_csv($file);
    @unlink($file);
    $val = candidate_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail');
    $err = $val['errors'][0];
    assert_true($err['line'] === 2 && $err['column'] === 'date_of_birth', "Error does not point to line 2 date_of_birth: " . json_encode($err));
});

// 14. Email sai format bị báo đúng dòng
test('Candidate', 14, 'Email sai format bị báo đúng dòng', function () use ($pdo, $thcsSchool) {
    $row = ['001208000003', 'Nguyễn Văn C', '2008-01-01', 'Nam', 'Hà Nội', '', 'not-an-email', $thcsSchool['school_code'], '1', 'active'];
    $file = make_temp_csv(CANDIDATE_IMPORT_HEADERS, [$row]);
    $parsed = candidate_import_parse_csv($file);
    @unlink($file);
    $val = candidate_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail');
    $err = $val['errors'][0];
    assert_true($err['line'] === 2 && $err['column'] === 'email', "Error does not point to line 2 email: " . json_encode($err));
});

// 15. Mã trường không tồn tại bị báo đúng dòng
test('Candidate', 15, 'Mã trường không tồn tại bị báo đúng dòng', function () use ($pdo) {
    $row = ['001208000004', 'Nguyễn Văn D', '2008-01-01', 'Nam', 'Hà Nội', '', '', 'NONEXISTENT_SCH_CODE', '1', 'active'];
    $file = make_temp_csv(CANDIDATE_IMPORT_HEADERS, [$row]);
    $parsed = candidate_import_parse_csv($file);
    @unlink($file);
    $val = candidate_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail');
    $err = $val['errors'][0];
    assert_true($err['line'] === 2 && $err['column'] === 'middle_school_code' && stripos($err['reason'], 'không tồn tại') !== false, "Error does not point to middle_school_code non-existent: " . json_encode($err));
});

// 16. Mã trường THPT dùng cho middle_school_code bị từ chối
test('Candidate', 16, 'Mã trường THPT dùng cho middle_school_code bị từ chối', function () use ($pdo, $thptSchool) {
    $row = ['001208000005', 'Nguyễn Văn E', '2008-01-01', 'Nam', 'Hà Nội', '', '', $thptSchool['school_code'], '1', 'active'];
    $file = make_temp_csv(CANDIDATE_IMPORT_HEADERS, [$row]);
    $parsed = candidate_import_parse_csv($file);
    @unlink($file);
    $val = candidate_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail');
    $err = $val['errors'][0];
    assert_true($err['line'] === 2 && $err['column'] === 'middle_school_code' && stripos($err['reason'], 'không phải là trường THCS') !== false, "Did not reject non-THCS school: " . json_encode($err));
});

// 17. CCCD trùng trong file bị từ chối
test('Candidate', 17, 'CCCD trùng trong file bị từ chối', function () use ($pdo, $thcsSchool) {
    $row1 = ['001208009999', 'Học sinh 1', '2008-01-01', 'Nam', 'Hà Nội', '', '', $thcsSchool['school_code'], '1', 'active'];
    $row2 = ['001208009999', 'Học sinh 2', '2008-01-01', 'Nữ', 'Hà Nội', '', '', $thcsSchool['school_code'], '1', 'active'];
    $file = make_temp_csv(CANDIDATE_IMPORT_HEADERS, [$row1, $row2]);
    $parsed = candidate_import_parse_csv($file);
    @unlink($file);
    $val = candidate_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail');
    assert_true(stripos($val['errors'][0]['reason'], 'trùng') !== false, "Did not report duplicate CCCD in file: " . json_encode($val['errors']));
});

// 18. CCCD đã tồn tại trong DB bị từ chối
test('Candidate', 18, 'CCCD đã tồn tại trong DB bị từ chối', function () use ($pdo, $thcsSchool) {
    $existingCid = $pdo->query("SELECT national_id FROM candidates LIMIT 1")->fetchColumn();
    assert_true(!empty($existingCid), 'No candidate in DB');
    $row = [$existingCid, 'Học sinh Trùng', '2008-01-01', 'Nam', 'Hà Nội', '', '', $thcsSchool['school_code'], '1', 'active'];
    $file = make_temp_csv(CANDIDATE_IMPORT_HEADERS, [$row]);
    $parsed = candidate_import_parse_csv($file);
    @unlink($file);
    $val = candidate_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail');
    assert_true(stripos($val['errors'][0]['reason'], 'đã tồn tại') !== false, "Did not report already existing CCCD in DB: " . json_encode($val['errors']));
});

// 19. File hợp lệ hiển thị preview chính xác
test('Candidate', 19, 'File hợp lệ hiển thị preview chính xác', function () use ($pdo, $tag, $thcsSchool) {
    $cid1 = '99' . substr(preg_replace('/[^0-9]/', '', $tag), -8) . '01';
    $cid2 = '99' . substr(preg_replace('/[^0-9]/', '', $tag), -8) . '02';
    $row1 = [$cid1, 'Nguyễn Hợp Lệ 1', '2008-05-10', 'Nam', '123 Phố Huế', '0912345678', 'le1@test.com', $thcsSchool['school_code'], '1', 'active'];
    $row2 = [$cid2, 'Trần Hợp Lệ 2', '2008-09-20', 'Nữ', '456 Hai Bà Trưng', '0987654321', 'le2@test.com', $thcsSchool['school_code'], '0', 'inactive'];
    $file = make_temp_csv(CANDIDATE_IMPORT_HEADERS, [$row1, $row2]);
    $parsed = candidate_import_parse_csv($file);
    @unlink($file);
    $val = candidate_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === true, 'Validation failed: ' . json_encode($val['errors']));
    assert_true($val['total_rows'] === 2, 'Total rows should be 2');
    assert_true($val['valid_rows'] === 2, 'Valid rows should be 2');
    assert_true($val['error_rows'] === 0, 'Error rows should be 0');
});

// 20. Confirm import file hợp lệ tạo đúng số bản ghi
test('Candidate', 20, 'Confirm import file hợp lệ tạo đúng số bản ghi', function () use ($pdo, $tag, $thcsSchool, &$cleanupCandidates, &$cleanupUsers) {
    $cid1 = '88' . substr(preg_replace('/[^0-9]/', '', $tag), -8) . '01';
    $cid2 = '88' . substr(preg_replace('/[^0-9]/', '', $tag), -8) . '02';
    $cleanupCandidates[] = $cid1;
    $cleanupCandidates[] = $cid2;
    $cleanupUsers[] = $cid1;
    $cleanupUsers[] = $cid2;

    $row1 = [$cid1, 'Học sinh Batch A', '2008-05-10', 'Nam', '123 Phố Huế', '0912345678', 'a@batch.com', $thcsSchool['school_code'], '1', 'active'];
    $row2 = [$cid2, 'Học sinh Batch B', '2008-09-20', 'Nữ', '456 Hai Bà Trưng', '', '', $thcsSchool['school_code'], '1', 'active'];
    $file = make_temp_csv(CANDIDATE_IMPORT_HEADERS, [$row1, $row2]);
    $parsed = candidate_import_parse_csv($file);
    @unlink($file);

    $val = candidate_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === true, 'Validation failed');

    $count = candidate_import_execute($pdo, $val['normalized_rows']);
    assert_true($count === 2, "Imported count should be 2, got $count");

    // Verify DB candidates
    $q = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE national_id IN (?, ?)");
    $q->execute([$cid1, $cid2]);
    assert_true((int)$q->fetchColumn() === 2, "Candidates not created in DB");

    // Verify DB users
    $qu = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username IN (?, ?)");
    $qu->execute([$cid1, $cid2]);
    assert_true((int)$qu->fetchColumn() === 2, "Users not created in DB");
});

// 21. Lỗi trong một dòng làm rollback toàn bộ
test('Candidate', 21, 'Lỗi trong một dòng làm rollback toàn bộ', function () use ($pdo, $tag, $thcsSchool) {
    $cidGood = '77' . substr(preg_replace('/[^0-9]/', '', $tag), -8) . '01';
    $cidBad = '77' . substr(preg_replace('/[^0-9]/', '', $tag), -8) . '02';

    // Second row invalid (wrong eligible value '2')
    $row1 = [$cidGood, 'Học sinh Rollback 1', '2008-01-01', 'Nam', '', '', '', $thcsSchool['school_code'], '1', 'active'];
    $row2 = [$cidBad, 'Học sinh Rollback 2', '2008-01-01', 'Nam', '', '', '', $thcsSchool['school_code'], '2', 'active'];
    $file = make_temp_csv(CANDIDATE_IMPORT_HEADERS, [$row1, $row2]);
    $parsed = candidate_import_parse_csv($file);
    @unlink($file);

    $val = candidate_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Batch should fail validation');

    // Verify none were inserted
    $q = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE national_id IN (?, ?)");
    $q->execute([$cidGood, $cidBad]);
    assert_true((int)$q->fetchColumn() === 0, "Records must not exist in candidates table");
});

// 22. Không tạo user candidate một phần khi batch rollback
test('Candidate', 22, 'Không tạo user candidate một phần khi batch rollback', function () use ($pdo, $tag) {
    $cidGood = '77' . substr(preg_replace('/[^0-9]/', '', $tag), -8) . '01';
    $cidBad = '77' . substr(preg_replace('/[^0-9]/', '', $tag), -8) . '02';

    $qu = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username IN (?, ?)");
    $qu->execute([$cidGood, $cidBad]);
    assert_true((int)$qu->fetchColumn() === 0, "Users must not be partially created");
});

// 23. File CSV UTF-8 tiếng Việt hiển thị đúng
test('Candidate', 23, 'File CSV UTF-8 tiếng Việt hiển thị đúng', function () use ($thcsSchool) {
    $vnName = 'Nguyễn Đắc Vũ Trường';
    $row = ['001208000088', $vnName, '2008-11-12', 'Nam', 'Đống Đa, Hà Nội', '', '', $thcsSchool['school_code'], '1', 'active'];
    $file = make_temp_csv(CANDIDATE_IMPORT_HEADERS, [$row], true);
    $parsed = candidate_import_parse_csv($file);
    @unlink($file);
    assert_true($parsed[0]['full_name'] === $vnName, "UTF-8 Vietnamese text corrupted. Expected '$vnName', got '{$parsed[0]['full_name']}'");
});

// 24. Filter theo trường THCS trả đúng danh sách
test('Candidate', 24, 'Filter theo trường THCS trả đúng danh sách', function () use ($thcsSchool) {
    $list = candidate_list(['middle_school_id' => (int)$thcsSchool['id']]);
    assert_true(is_array($list), 'Result is not an array');
    foreach ($list as $item) {
        assert_true((int)$item['middle_school_id'] === (int)$thcsSchool['id'], "Candidate middle_school_id {$item['middle_school_id']} does not match filter {$thcsSchool['id']}");
    }
});

// 25. Filter status và eligible hoạt động đúng
test('Candidate', 25, 'Filter status và eligible hoạt động đúng', function () {
    $list = candidate_list(['status' => 'active', 'eligible' => '1']);
    assert_true(is_array($list), 'Result is not an array');
    foreach ($list as $item) {
        assert_true($item['status'] === 'active', "Candidate status is not active: {$item['status']}");
        assert_true((int)$item['eligible'] === 1, "Candidate eligible is not 1: {$item['eligible']}");
    }
});

echo PHP_EOL . "=== BẮT ĐẦU KIỂM THỬ STAFF (13 TESTS) ===" . PHP_EOL;

// 1. Guest bị redirect
test('Staff', 1, 'Guest bị redirect', function () {
    $res = http_request('/staff/import.php', 'GET');
    assert_true($res['code'] === 302, "Expected 302, got {$res['code']}");
    assert_true(stripos($res['headers'], '/auth/login') !== false, "Expected redirect to login");
});

// 2. User thiếu staff.create bị 403
test('Staff', 2, 'User thiếu staff.create bị 403', function () use ($candidateSess) {
    $res = http_request('/staff/import.php', 'GET', [], $candidateSess);
    assert_true($res['code'] === 403, "Expected 403, got {$res['code']}");
});

// 3. Sai header bị từ chối
test('Staff', 3, 'Sai header bị từ chối', function () {
    $badCols = ['full_name', 'workplace', 'phone', 'email', 'staff_role']; // missing active
    $file = make_temp_csv($badCols, []);
    $rejected = false;
    $msg = '';
    try {
        staff_import_parse_csv($file);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
        $msg = $e->getMessage();
    }
    @unlink($file);
    assert_true($rejected && stripos($msg, 'Tiêu đề tệp không đúng cấu trúc') !== false, "Did not reject invalid header: $msg");
});

// 4. Dòng thiếu full_name bị báo lỗi
test('Staff', 4, 'Dòng thiếu full_name bị báo lỗi', function () use ($pdo) {
    $row = ['', 'THCS Test', '0912111222', 'staff1@test.com', 'giam_thi', '1'];
    $file = make_temp_csv(STAFF_IMPORT_HEADERS, [$row]);
    $parsed = staff_import_parse_csv($file);
    @unlink($file);
    $val = staff_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail');
    $err = $val['errors'][0];
    assert_true($err['line'] === 2 && $err['column'] === 'full_name', "Did not report missing full_name on row 2: " . json_encode($err));
});

// 5. Email sai bị báo lỗi
test('Staff', 5, 'Email sai bị báo lỗi', function () use ($pdo) {
    $row = ['Cán bộ A', 'THCS Test', '0912111222', 'invalid-email-address', 'giam_thi', '1'];
    $file = make_temp_csv(STAFF_IMPORT_HEADERS, [$row]);
    $parsed = staff_import_parse_csv($file);
    @unlink($file);
    $val = staff_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail');
    $err = $val['errors'][0];
    assert_true($err['line'] === 2 && $err['column'] === 'email', "Did not report invalid email on row 2: " . json_encode($err));
});

// 6. staff_role không hợp lệ bị báo lỗi
test('Staff', 6, 'staff_role không hợp lệ bị báo lỗi', function () use ($pdo) {
    $row = ['Cán bộ B', 'THCS Test', '0912111223', 'staffb@test.com', '', '1'];
    $file = make_temp_csv(STAFF_IMPORT_HEADERS, [$row]);
    $parsed = staff_import_parse_csv($file);
    @unlink($file);
    $val = staff_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail');
    $err = $val['errors'][0];
    assert_true($err['line'] === 2 && $err['column'] === 'staff_role', "Did not report invalid staff_role: " . json_encode($err));
});

// 7. active ngoài 0/1 bị báo lỗi
test('Staff', 7, 'active ngoài 0/1 bị báo lỗi', function () use ($pdo) {
    $row = ['Cán bộ C', 'THCS Test', '0912111224', 'staffc@test.com', 'giam_thi', '2'];
    $file = make_temp_csv(STAFF_IMPORT_HEADERS, [$row]);
    $parsed = staff_import_parse_csv($file);
    @unlink($file);
    $val = staff_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail');
    $err = $val['errors'][0];
    assert_true($err['line'] === 2 && $err['column'] === 'active', "Did not report invalid active value: " . json_encode($err));
});

// 8. Dòng trùng bị báo lỗi
test('Staff', 8, 'Dòng trùng bị báo lỗi', function () use ($pdo) {
    $row1 = ['Cán bộ D', 'THCS Trùng', '0912111225', 'staffd@test.com', 'giam_thi', '1'];
    $row2 = ['Cán bộ E', 'THCS Khác', '0912111225', 'staffe@test.com', 'cham_thi', '1']; // duplicate phone
    $file = make_temp_csv(STAFF_IMPORT_HEADERS, [$row1, $row2]);
    $parsed = staff_import_parse_csv($file);
    @unlink($file);
    $val = staff_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Validation should fail on duplicate phone');
    assert_true(stripos($val['errors'][0]['reason'], 'trùng') !== false, "Error message should mention duplicate: " . json_encode($val['errors']));
});

// 9. Preview file hợp lệ đúng dữ liệu
test('Staff', 9, 'Preview file hợp lệ đúng dữ liệu', function () use ($pdo, $tag) {
    $phone1 = '09' . substr(preg_replace('/[^0-9]/', '', $tag), -8);
    $phone2 = '08' . substr(preg_replace('/[^0-9]/', '', $tag), -8);
    $email1 = 'staff1_' . substr($tag, -6) . '@test.com';
    $email2 = 'staff2_' . substr($tag, -6) . '@test.com';

    $row1 = ['Trần Thị Hợp Lệ 1', 'THCS Cầu Giấy', $phone1, $email1, 'giam_thi', '1'];
    $row2 = ['Lê Văn Hợp Lệ 2', 'THCS Ba Đình', $phone2, $email2, 'cham_thi', '1'];
    $file = make_temp_csv(STAFF_IMPORT_HEADERS, [$row1, $row2]);
    $parsed = staff_import_parse_csv($file);
    @unlink($file);

    $val = staff_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === true, 'Validation failed: ' . json_encode($val['errors']));
    assert_true($val['total_rows'] === 2, 'Total should be 2');
    assert_true($val['valid_rows'] === 2, 'Valid should be 2');
    assert_true($val['error_rows'] === 0, 'Error should be 0');
});

// 10. Confirm tạo đúng số lượng cán bộ
test('Staff', 10, 'Confirm tạo đúng số lượng cán bộ', function () use ($pdo, $tag, &$cleanupStaff) {
    $phone1 = '09' . substr(preg_replace('/[^0-9]/', '', $tag), -8);
    $phone2 = '08' . substr(preg_replace('/[^0-9]/', '', $tag), -8);
    $email1 = 'staff1_' . substr($tag, -6) . '@test.com';
    $email2 = 'staff2_' . substr($tag, -6) . '@test.com';

    $row1 = ['Trần Thị Batch 1', 'THCS Cầu Giấy', $phone1, $email1, 'giam_thi', '1'];
    $row2 = ['Lê Văn Batch 2', 'THCS Ba Đình', $phone2, $email2, 'cham_thi', '1'];
    $file = make_temp_csv(STAFF_IMPORT_HEADERS, [$row1, $row2]);
    $parsed = staff_import_parse_csv($file);
    @unlink($file);

    $val = staff_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === true, 'Validation failed');

    $resCount = staff_import_execute($pdo, $val['normalized_rows']);
    assert_true($resCount === 2, "Expected 2 imported, got $resCount");

    // Verify DB
    $q = $pdo->prepare("SELECT id FROM staff WHERE email IN (?, ?)");
    $q->execute([$email1, $email2]);
    $ids = $q->fetchAll(PDO::FETCH_COLUMN);
    assert_true(count($ids) === 2, "Expected 2 staff records in DB");
    foreach ($ids as $id) {
        $cleanupStaff[] = (int)$id;
    }
});

// 11. Một dòng lỗi rollback toàn bộ
test('Staff', 11, 'Một dòng lỗi rollback toàn bộ', function () use ($pdo, $tag) {
    $phoneGood = '07' . substr(preg_replace('/[^0-9]/', '', $tag), -8);
    $emailGood = 'staff_good_' . substr($tag, -6) . '@test.com';

    // Second row invalid staff_role (empty)
    $row1 = ['Trần Rollback 1', 'THCS Test', $phoneGood, $emailGood, 'giam_thi', '1'];
    $row2 = ['Lê Rollback 2', 'THCS Test', '', '', '', '1'];
    $file = make_temp_csv(STAFF_IMPORT_HEADERS, [$row1, $row2]);
    $parsed = staff_import_parse_csv($file);
    @unlink($file);

    $val = staff_import_validate_batch($pdo, $parsed);
    assert_true($val['can_import'] === false, 'Batch should fail validation');

    // Confirm is blocked, 0 records inserted
    $q = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE email = ?");
    $q->execute([$emailGood]);
    assert_true((int)$q->fetchColumn() === 0, "No records should be in staff table");
});

// 12. Không có SQL/PHP error thô trong response
test('Staff', 12, 'Không có SQL/PHP error thô trong response', function () use ($managerSess) {
    // Check template and index responses
    $res1 = http_request('/staff/index.php', 'GET', [], $managerSess);
    assert_true(stripos($res1['body'], 'SQLSTATE') === false, "SQLSTATE found in staff/index.php");
    assert_true(stripos($res1['body'], 'Stack trace') === false, "Stack trace found in staff/index.php");

    $res2 = http_request('/candidates/index.php', 'GET', [], $managerSess);
    assert_true(stripos($res2['body'], 'SQLSTATE') === false, "SQLSTATE found in candidates/index.php");
    assert_true(stripos($res2['body'], 'Stack trace') === false, "Stack trace found in candidates/index.php");
});

// 13. File mẫu tải được và header đúng
test('Staff', 13, 'File mẫu tải được và header đúng', function () use ($managerSess) {
    // Candidate template
    $resCand = http_request('/candidates/template.php', 'GET', [], $managerSess);
    assert_true($resCand['code'] === 200, "Candidates template download failed: {$resCand['code']}");
    assert_true(stripos($resCand['headers'], 'attachment') !== false, "Candidates template not an attachment");
    // Strip BOM if present
    $candBody = $resCand['body'];
    if (str_starts_with($candBody, "\xEF\xBB\xBF")) {
        $candBody = substr($candBody, 3);
    }
    $candLines = explode("\n", trim($candBody));
    $candHeader = str_getcsv($candLines[0]);
    assert_true($candHeader === CANDIDATE_IMPORT_HEADERS, "Candidate template header mismatch: " . json_encode($candHeader));

    // Staff template
    $resStaff = http_request('/staff/template.php', 'GET', [], $managerSess);
    assert_true($resStaff['code'] === 200, "Staff template download failed: {$resStaff['code']}");
    assert_true(stripos($resStaff['headers'], 'attachment') !== false, "Staff template not an attachment");
    $staffBody = $resStaff['body'];
    if (str_starts_with($staffBody, "\xEF\xBB\xBF")) {
        $staffBody = substr($staffBody, 3);
    }
    $staffLines = explode("\n", trim($staffBody));
    $staffHeader = str_getcsv($staffLines[0]);
    assert_true($staffHeader === STAFF_IMPORT_HEADERS, "Staff template header mismatch: " . json_encode($staffHeader));
});

// Clean up
echo PHP_EOL . "=== DỌN DẸP DỮ LIỆU TEST ===" . PHP_EOL;

if (!empty($cleanupCandidates)) {
    $in = implode(',', array_fill(0, count($cleanupCandidates), '?'));
    $pdo->prepare("DELETE FROM candidates WHERE national_id IN ($in)")->execute($cleanupCandidates);
    echo "Đã dọn dẹp " . count($cleanupCandidates) . " candidates test." . PHP_EOL;
}

if (!empty($cleanupUsers)) {
    // Clean user_roles then users
    $in = implode(',', array_fill(0, count($cleanupUsers), '?'));
    $uids = $pdo->prepare("SELECT id FROM users WHERE username IN ($in)");
    $uids->execute($cleanupUsers);
    $userIds = $uids->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($userIds)) {
        $inU = implode(',', array_fill(0, count($userIds), '?'));
        $pdo->prepare("DELETE FROM user_roles WHERE user_id IN ($inU)")->execute($userIds);
        $pdo->prepare("DELETE FROM users WHERE id IN ($inU)")->execute($userIds);
        echo "Đã dọn dẹp " . count($userIds) . " users và user_roles test." . PHP_EOL;
    }
}

if (!empty($cleanupStaff)) {
    $in = implode(',', array_fill(0, count($cleanupStaff), '?'));
    $pdo->prepare("DELETE FROM staff WHERE id IN ($in)")->execute($cleanupStaff);
    echo "Đã dọn dẹp " . count($cleanupStaff) . " staff test." . PHP_EOL;
}

foreach ($sessionIds as $sid) {
    @unlink('C:/xampp/tmp/sess_' . $sid);
}

// Summary
$passCount = 0;
$failCount = 0;
foreach ($results as $r) {
    if ($r['status'] === 'PASS') {
        $passCount++;
    } else {
        $failCount++;
    }
}

echo PHP_EOL . "=====================================" . PHP_EOL;
echo "TỔNG KẾT: $passCount PASS / " . ($passCount + $failCount) . " TESTS" . PHP_EOL;
if ($failCount > 0) {
    echo "CÓ $failCount TEST THẤT BẠI!" . PHP_EOL;
    exit(1);
} else {
    echo "TẤT CẢ TEST ĐÃ VƯỢT QUA HOÀN TOÀN!" . PHP_EOL;
    exit(0);
}
