<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

const CANDIDATE_IMPORT_HEADERS = [
    'national_id',
    'full_name',
    'date_of_birth',
    'gender',
    'address',
    'phone',
    'email',
    'middle_school_code',
    'eligible',
    'status',
];

const CANDIDATE_MAX_IMPORT_ROWS = 5000;
const CANDIDATE_MAX_FILE_SIZE = 10485760; // 10MB

/**
 * Đọc file CSV và trả về header cùng mảng dòng dữ liệu thô.
 *
 * @throws InvalidArgumentException
 */
function candidate_import_parse_csv(string $filePath): array
{
    if (!file_exists($filePath) || !is_readable($filePath)) {
        throw new InvalidArgumentException('Tệp tải lên không tồn tại hoặc không thể đọc.');
    }

    $fileSize = filesize($filePath);
    if ($fileSize === 0) {
        throw new InvalidArgumentException('Tệp tải lên rỗng.');
    }

    if ($fileSize > CANDIDATE_MAX_FILE_SIZE) {
        throw new InvalidArgumentException('Kích thước tệp vượt quá giới hạn tối đa 10 MB.');
    }

    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        throw new InvalidArgumentException('Không thể mở tệp tải lên.');
    }

    // Consume the BOM before CSV parsing, including files with a quoted first header.
    if (fread($handle, 3) !== "\xEF\xBB\xBF") {
        rewind($handle);
    }
    // Đọc dòng đầu tiên (Header)
    $header = fgetcsv($handle);
    if ($header === false || $header === null || count($header) === 0) {
        fclose($handle);
        throw new InvalidArgumentException('Tệp tải lên không có tiêu đề cột (header).');
    }

    // Xóa BOM UTF-8 nếu có ở ô đầu tiên
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]);

    // Chuẩn hóa tên cột trong header
    $cleanHeader = array_map(static fn($col) => trim((string)$col), $header);

    // Kiểm tra header: số lượng, tên cột, thứ tự cột
    if (count($cleanHeader) !== count(CANDIDATE_IMPORT_HEADERS)) {
        fclose($handle);
        throw new InvalidArgumentException(
            'Tiêu đề tệp không đúng cấu trúc quy định. Yêu cầu đúng ' . count(CANDIDATE_IMPORT_HEADERS) .
            ' cột theo thứ tự: ' . implode(', ', CANDIDATE_IMPORT_HEADERS) . '.'
        );
    }

    foreach (CANDIDATE_IMPORT_HEADERS as $index => $expectedCol) {
        if (($cleanHeader[$index] ?? '') !== $expectedCol) {
            fclose($handle);
            throw new InvalidArgumentException(
                'Cột thứ ' . ($index + 1) . ' không hợp lệ. Mong đợi "' . $expectedCol . '", nhận được "' . ($cleanHeader[$index] ?? '') . '".'
            );
        }
    }

    $rows = [];
    $lineNumber = 1;

    while (($data = fgetcsv($handle)) !== false) {
        $lineNumber++;

        // Bỏ qua dòng hoàn toàn rỗng
        if (count($data) === 1 && trim((string)$data[0]) === '') {
            continue;
        }

        if (count($data) !== count(CANDIDATE_IMPORT_HEADERS)) {
            fclose($handle);
            throw new InvalidArgumentException(
                "Dòng $lineNumber có số lượng cột (" . count($data) . ") không khớp với tiêu đề (" . count(CANDIDATE_IMPORT_HEADERS) . ")."
            );
        }

        if (count($rows) >= CANDIDATE_MAX_IMPORT_ROWS) {
            fclose($handle);
            throw new InvalidArgumentException('Số lượng dòng dữ liệu vượt quá giới hạn tối đa ' . CANDIDATE_MAX_IMPORT_ROWS . ' dòng.');
        }

        $rowAssoc = [];
        foreach (CANDIDATE_IMPORT_HEADERS as $idx => $colName) {
            $val = trim((string)($data[$idx] ?? ''));
            if (!mb_check_encoding($val, 'UTF-8')) {
                fclose($handle);
                throw new InvalidArgumentException("Dòng $lineNumber không phải văn bản UTF-8 hợp lệ. Vui lòng lưu lại tệp CSV UTF-8.");
            }
            // Chống formula injection: nếu bắt đầu bằng =, +, -, @
            if (preg_match('/^[=+\-@]/', $val)) {
                $val = "'" . $val;
            }
            $rowAssoc[$colName] = $val;
        }
        $rowAssoc['_line_number'] = $lineNumber;
        $rows[] = $rowAssoc;
    }

    fclose($handle);

    if (empty($rows)) {
        throw new InvalidArgumentException('Tệp tải lên không chứa dòng dữ liệu nào.');
    }

    return $rows;
}

/**
 * Kiểm tra và xác thực toàn bộ batch dữ liệu.
 */
function candidate_import_validate_batch(PDO $pdo, array $rawRows): array
{
    // 1. Tải cache trường học THCS
    $schoolsStmt = $pdo->query('SELECT id, school_code, level, active FROM schools');
    $schoolsMap = [];
    while ($sch = $schoolsStmt->fetch()) {
        $schoolsMap[$sch['school_code']] = [
            'id' => (int)$sch['id'],
            'level' => (string)$sch['level'],
            'active' => (int)$sch['active'],
        ];
    }

    // 2. Tải cache national_id và username hiện có trong hệ thống
    $existingCandidatesStmt = $pdo->query('SELECT national_id FROM candidates');
    $existingCandidates = [];
    while ($cid = $existingCandidatesStmt->fetchColumn()) {
        $existingCandidates[(string)$cid] = true;
    }

    $existingUsersStmt = $pdo->query('SELECT username FROM users');
    $existingUsers = [];
    while ($u = $existingUsersStmt->fetchColumn()) {
        $existingUsers[(string)$u] = true;
    }

    // 3. Theo dõi national_id xuất hiện trong file để bắt trùng lặp nội bộ
    $fileNationalIds = [];
    $errors = [];
    $normalizedRows = [];
    $errorLines = [];

    foreach ($rawRows as $row) {
        $line = (int)$row['_line_number'];
        $lineHasError = false;

        $nationalId = trim((string)($row['national_id'] ?? ''));
        $fullName = trim((string)($row['full_name'] ?? ''));
        $dob = trim((string)($row['date_of_birth'] ?? ''));
        $gender = trim((string)($row['gender'] ?? ''));
        $address = trim((string)($row['address'] ?? ''));
        $phone = trim((string)($row['phone'] ?? ''));
        $email = trim((string)($row['email'] ?? ''));
        $schoolCode = trim((string)($row['middle_school_code'] ?? ''));
        $eligible = trim((string)($row['eligible'] ?? ''));
        $status = trim((string)($row['status'] ?? ''));

        // Kiểm tra national_id
        if ($nationalId === '') {
            $errors[] = [
                'line' => $line,
                'column' => 'national_id',
                'value' => $nationalId,
                'reason' => 'Số CMND/CCCD bắt buộc nhập, không được để trống.',
            ];
            $lineHasError = true;
        } else {
            if (isset($fileNationalIds[$nationalId])) {
                $prevLine = $fileNationalIds[$nationalId];
                $errors[] = [
                    'line' => $line,
                    'column' => 'national_id',
                    'value' => $nationalId,
                    'reason' => "Số CMND/CCCD bị trùng lặp với dòng $prevLine trong tệp tải lên.",
                ];
                $lineHasError = true;
            } else {
                $fileNationalIds[$nationalId] = $line;
            }

            if (isset($existingCandidates[$nationalId]) || isset($existingUsers[$nationalId])) {
                $errors[] = [
                    'line' => $line,
                    'column' => 'national_id',
                    'value' => $nationalId,
                    'reason' => 'Số CMND/CCCD hoặc tên tài khoản đã tồn tại trong hệ thống.',
                ];
                $lineHasError = true;
            }
        }

        // Kiểm tra full_name
        if ($fullName === '') {
            $errors[] = [
                'line' => $line,
                'column' => 'full_name',
                'value' => $fullName,
                'reason' => 'Họ và tên bắt buộc nhập, không được để trống.',
            ];
            $lineHasError = true;
        }

        // Kiểm tra date_of_birth (YYYY-MM-DD nếu có)
        if ($dob !== '') {
            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dob, $dobMatches)) {
                $errors[] = [
                    'line' => $line,
                    'column' => 'date_of_birth',
                    'value' => $dob,
                    'reason' => 'Ngày sinh không đúng định dạng YYYY-MM-DD.',
                ];
                $lineHasError = true;
            } else {
                $year = (int)$dobMatches[1];
                $month = (int)$dobMatches[2];
                $day = (int)$dobMatches[3];
                if (!checkdate($month, $day, $year)) {
                    $errors[] = [
                        'line' => $line,
                        'column' => 'date_of_birth',
                        'value' => $dob,
                        'reason' => 'Ngày sinh không hợp lệ trên lịch.',
                    ];
                    $lineHasError = true;
                }
            }
        }

        // Kiểm tra phone
        if ($phone !== '' && !preg_match('/^[0-9+().\-\s]{7,30}$/', $phone)) {
            $errors[] = [
                'line' => $line,
                'column' => 'phone',
                'value' => $phone,
                'reason' => 'Số điện thoại không đúng định dạng (7-30 ký tự số/dấu hợp lệ).',
            ];
            $lineHasError = true;
        }

        // Kiểm tra email
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = [
                'line' => $line,
                'column' => 'email',
                'value' => $email,
                'reason' => 'Địa chỉ email không đúng định dạng hợp lệ.',
            ];
            $lineHasError = true;
        }

        // Kiểm tra middle_school_code
        $middleSchoolId = null;
        if ($schoolCode !== '') {
            if (!isset($schoolsMap[$schoolCode])) {
                $errors[] = [
                    'line' => $line,
                    'column' => 'middle_school_code',
                    'value' => $schoolCode,
                    'reason' => 'Mã trường THCS không tồn tại trong hệ thống.',
                ];
                $lineHasError = true;
            } else {
                $sch = $schoolsMap[$schoolCode];
                if ($sch['level'] !== 'THCS') {
                    $errors[] = [
                        'line' => $line,
                        'column' => 'middle_school_code',
                        'value' => $schoolCode,
                        'reason' => 'Mã trường không phải là trường THCS (cấp học hiện tại: ' . $sch['level'] . ').',
                    ];
                    $lineHasError = true;
                } elseif ($sch['active'] !== 1) {
                    $errors[] = [
                        'line' => $line,
                        'column' => 'middle_school_code',
                        'value' => $schoolCode,
                        'reason' => 'Trường THCS đang ở trạng thái không hoạt động.',
                    ];
                    $lineHasError = true;
                } else {
                    $middleSchoolId = $sch['id'];
                }
            }
        }

        // Kiểm tra eligible (bắt buộc: 1 hoặc 0)
        if ($eligible !== '0' && $eligible !== '1') {
            $errors[] = [
                'line' => $line,
                'column' => 'eligible',
                'value' => $eligible,
                'reason' => 'Đủ điều kiện dự thi bắt buộc nhập và chỉ nhận giá trị 1 (Có) hoặc 0 (Không).',
            ];
            $lineHasError = true;
        }

        // Kiểm tra status (bắt buộc: active hoặc inactive)
        if ($status !== 'active' && $status !== 'inactive') {
            $errors[] = [
                'line' => $line,
                'column' => 'status',
                'value' => $status,
                'reason' => 'Trạng thái bắt buộc nhập và chỉ nhận giá trị active hoặc inactive.',
            ];
            $lineHasError = true;
        }

        if ($lineHasError) {
            $errorLines[$line] = true;
        }

        $normalizedRows[] = [
            '_line_number' => $line,
            'national_id' => $nationalId,
            'full_name' => $fullName,
            'date_of_birth' => $dob !== '' ? $dob : null,
            'gender' => $gender !== '' ? $gender : null,
            'address' => $address !== '' ? $address : null,
            'phone' => $phone !== '' ? $phone : null,
            'email' => $email !== '' ? $email : null,
            'middle_school_code' => $schoolCode,
            'middle_school_id' => $middleSchoolId,
            'eligible' => (int)$eligible,
            'status' => $status,
            'is_valid' => !$lineHasError,
        ];
    }

    $totalRows = count($rawRows);
    $errorRowsCount = count($errorLines);
    $validRowsCount = $totalRows - $errorRowsCount;

    return [
        'total_rows' => $totalRows,
        'valid_rows' => $validRowsCount,
        'error_rows' => $errorRowsCount,
        'errors' => $errors,
        'preview_data' => array_slice($normalizedRows, 0, 50),
        'normalized_rows' => $normalizedRows,
        'can_import' => ($errorRowsCount === 0 && $totalRows > 0),
    ];
}

/**
 * Thực hiện import toàn bộ batch trong một Transaction duy nhất.
 *
 * @throws Throwable
 */
function candidate_import_execute(PDO $pdo, array $normalizedRows): int
{
    if (empty($normalizedRows)) {
        throw new InvalidArgumentException('Không có dữ liệu hợp lệ để nhập vào hệ thống.');
    }

    $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE code = "candidate" AND active = 1 LIMIT 1');
    $roleStmt->execute();
    $roleId = $roleStmt->fetchColumn();
    if (!$roleId) {
        throw new RuntimeException('Không tìm thấy vai trò (role) "candidate" đang hoạt động trong hệ thống.');
    }
    $roleId = (int)$roleId;

    $candidateInsert = $pdo->prepare(
        'INSERT INTO candidates
            (national_id, full_name, date_of_birth, gender, address, phone, email, middle_school_id, eligible, status, created_at, updated_at)
         VALUES
            (:national_id, :full_name, :date_of_birth, :gender, :address, :phone, :email, :middle_school_id, :eligible, :status, NOW(), NOW())'
    );

    $userInsert = $pdo->prepare(
        'INSERT INTO users
            (username, password_hash, full_name, role, email, phone, active, created_at, updated_at)
         VALUES
            (:username, :password_hash, :full_name, "candidate", :email, :phone, 1, NOW(), NOW())'
    );

    $userRoleInsert = $pdo->prepare(
        'INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)'
    );

    $linkCandidateUser = $pdo->prepare(
        'UPDATE candidates SET user_id = :user_id WHERE id = :id'
    );

    $defaultPasswordHash = password_hash('1111', PASSWORD_DEFAULT);
    $importedCount = 0;

    $pdo->beginTransaction();

    try {
        foreach ($normalizedRows as $row) {
            // 1. Thêm bản ghi thí sinh
            $candidateInsert->execute([
                'national_id' => $row['national_id'],
                'full_name' => $row['full_name'],
                'date_of_birth' => $row['date_of_birth'],
                'gender' => $row['gender'],
                'address' => $row['address'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'middle_school_id' => $row['middle_school_id'],
                'eligible' => $row['eligible'],
                'status' => $row['status'],
            ]);
            $candidateId = (int)$pdo->lastInsertId();

            // 2. Thêm tài khoản người dùng tương ứng
            $userInsert->execute([
                'username' => $row['national_id'],
                'password_hash' => $defaultPasswordHash,
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
            ]);
            $userId = (int)$pdo->lastInsertId();

            // 3. Phân vai trò candidate cho tài khoản
            $userRoleInsert->execute([
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);

            // 4. Liên kết user_id vào hồ sơ candidate
            $linkCandidateUser->execute([
                'user_id' => $userId,
                'id' => $candidateId,
            ]);

            $importedCount++;
        }

        $pdo->commit();
        return $importedCount;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Candidate import execute error: ' . $e->getMessage());
        throw $e;
    }
}
