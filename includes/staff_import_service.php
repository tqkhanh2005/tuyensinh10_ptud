<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

const STAFF_IMPORT_HEADERS = [
    'full_name',
    'workplace',
    'phone',
    'email',
    'staff_role',
    'active',
];

const STAFF_MAX_IMPORT_ROWS = 5000;
const STAFF_MAX_FILE_SIZE = 10485760; // 10MB

/**
 * Đọc file CSV cán bộ - giáo viên và trả về header cùng mảng dòng dữ liệu thô.
 *
 * @throws InvalidArgumentException
 */
function staff_import_parse_csv(string $filePath): array
{
    if (!file_exists($filePath) || !is_readable($filePath)) {
        throw new InvalidArgumentException('Tệp tải lên không tồn tại hoặc không thể đọc.');
    }

    $fileSize = filesize($filePath);
    if ($fileSize === 0) {
        throw new InvalidArgumentException('Tệp tải lên rỗng.');
    }

    if ($fileSize > STAFF_MAX_FILE_SIZE) {
        throw new InvalidArgumentException('Kích thước tệp vượt quá giới hạn tối đa 10 MB.');
    }

    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        throw new InvalidArgumentException('Không thể mở tệp tải lên.');
    }

    $header = fgetcsv($handle);
    if ($header === false || $header === null || count($header) === 0) {
        fclose($handle);
        throw new InvalidArgumentException('Tệp tải lên không có tiêu đề cột (header).');
    }

    // Xóa BOM UTF-8 nếu có ở ô đầu tiên
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]);
    $cleanHeader = array_map(static fn($col) => trim((string)$col), $header);

    if (count($cleanHeader) !== count(STAFF_IMPORT_HEADERS)) {
        fclose($handle);
        throw new InvalidArgumentException(
            'Tiêu đề tệp không đúng cấu trúc quy định. Yêu cầu đúng ' . count(STAFF_IMPORT_HEADERS) .
            ' cột theo thứ tự: ' . implode(', ', STAFF_IMPORT_HEADERS) . '.'
        );
    }

    foreach (STAFF_IMPORT_HEADERS as $index => $expectedCol) {
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

        if (count($data) === 1 && trim((string)$data[0]) === '') {
            continue;
        }

        if (count($data) !== count(STAFF_IMPORT_HEADERS)) {
            fclose($handle);
            throw new InvalidArgumentException(
                "Dòng $lineNumber có số lượng cột (" . count($data) . ") không khớp với tiêu đề (" . count(STAFF_IMPORT_HEADERS) . ")."
            );
        }

        if (count($rows) >= STAFF_MAX_IMPORT_ROWS) {
            fclose($handle);
            throw new InvalidArgumentException('Số lượng dòng dữ liệu vượt quá giới hạn tối đa ' . STAFF_MAX_IMPORT_ROWS . ' dòng.');
        }

        $rowAssoc = [];
        foreach (STAFF_IMPORT_HEADERS as $idx => $colName) {
            $val = trim((string)($data[$idx] ?? ''));
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
 * Kiểm tra và xác thực toàn bộ batch dữ liệu cán bộ - giáo viên.
 */
function staff_import_validate_batch(PDO $pdo, array $rawRows): array
{
    // Tải cache cán bộ đang hoạt động trong hệ thống
    $stmt = $pdo->query('SELECT phone, email, full_name, workplace FROM staff WHERE active = 1');
    $dbActivePhones = [];
    $dbActiveEmails = [];
    $dbActiveNameWorkplaces = [];

    while ($s = $stmt->fetch()) {
        $p = trim((string)($s['phone'] ?? ''));
        if ($p !== '') {
            $dbActivePhones[$p] = true;
        }
        $em = strtolower(trim((string)($s['email'] ?? '')));
        if ($em !== '') {
            $dbActiveEmails[$em] = true;
        }
        $nw = mb_strtolower(trim((string)$s['full_name'])) . '|' . mb_strtolower(trim((string)$s['workplace']));
        $dbActiveNameWorkplaces[$nw] = true;
    }

    // Cache theo dõi trùng lặp nội bộ trong file
    $filePhones = [];
    $fileEmails = [];
    $fileNameWorkplaces = [];

    $errors = [];
    $normalizedRows = [];
    $errorLines = [];

    foreach ($rawRows as $row) {
        $line = (int)$row['_line_number'];
        $lineHasError = false;

        $fullName = trim((string)($row['full_name'] ?? ''));
        $workplace = trim((string)($row['workplace'] ?? ''));
        $phone = trim((string)($row['phone'] ?? ''));
        $email = trim((string)($row['email'] ?? ''));
        $staffRole = trim((string)($row['staff_role'] ?? ''));
        $activeStr = trim((string)($row['active'] ?? ''));

        // 1. Kiểm tra full_name
        if ($fullName === '') {
            $errors[] = [
                'line' => $line,
                'column' => 'full_name',
                'value' => $fullName,
                'reason' => 'Họ tên bắt buộc nhập, không được để trống.',
            ];
            $lineHasError = true;
        }

        // 2. Kiểm tra workplace
        if ($workplace === '') {
            $errors[] = [
                'line' => $line,
                'column' => 'workplace',
                'value' => $workplace,
                'reason' => 'Đơn vị công tác bắt buộc nhập, không được để trống.',
            ];
            $lineHasError = true;
        }

        // 3. Kiểm tra phone
        if ($phone !== '') {
            if (!preg_match('/^[0-9+().\-\s]{7,30}$/', $phone)) {
                $errors[] = [
                    'line' => $line,
                    'column' => 'phone',
                    'value' => $phone,
                    'reason' => 'Số điện thoại không đúng định dạng (7-30 ký tự số/dấu hợp lệ).',
                ];
                $lineHasError = true;
            } else {
                // Trùng trong file
                if (isset($filePhones[$phone])) {
                    $prevLine = $filePhones[$phone];
                    $errors[] = [
                        'line' => $line,
                        'column' => 'phone',
                        'value' => $phone,
                        'reason' => "Số điện thoại bị trùng lặp với dòng $prevLine trong tệp tải lên.",
                    ];
                    $lineHasError = true;
                } else {
                    $filePhones[$phone] = $line;
                }

                // Trùng trong DB
                if (isset($dbActivePhones[$phone])) {
                    $errors[] = [
                        'line' => $line,
                        'column' => 'phone',
                        'value' => $phone,
                        'reason' => 'Số điện thoại đã tồn tại ở một cán bộ đang hoạt động trong hệ thống.',
                    ];
                    $lineHasError = true;
                }
            }
        }

        // 4. Kiểm tra email
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = [
                    'line' => $line,
                    'column' => 'email',
                    'value' => $email,
                    'reason' => 'Địa chỉ email không đúng định dạng hợp lệ.',
                ];
                $lineHasError = true;
            } else {
                $lowerEmail = strtolower($email);
                // Trùng trong file
                if (isset($fileEmails[$lowerEmail])) {
                    $prevLine = $fileEmails[$lowerEmail];
                    $errors[] = [
                        'line' => $line,
                        'column' => 'email',
                        'value' => $email,
                        'reason' => "Địa chỉ email bị trùng lặp với dòng $prevLine trong tệp tải lên.",
                    ];
                    $lineHasError = true;
                } else {
                    $fileEmails[$lowerEmail] = $line;
                }

                // Trùng trong DB
                if (isset($dbActiveEmails[$lowerEmail])) {
                    $errors[] = [
                        'line' => $line,
                        'column' => 'email',
                        'value' => $email,
                        'reason' => 'Địa chỉ email đã tồn tại ở một cán bộ đang hoạt động trong hệ thống.',
                    ];
                    $lineHasError = true;
                }
            }
        }

        // 5. Kiểm tra cặp (Họ tên + Đơn vị công tác)
        if ($fullName !== '' && $workplace !== '') {
            $nwKey = mb_strtolower($fullName) . '|' . mb_strtolower($workplace);
            if (isset($fileNameWorkplaces[$nwKey])) {
                $prevLine = $fileNameWorkplaces[$nwKey];
                $errors[] = [
                    'line' => $line,
                    'column' => 'full_name, workplace',
                    'value' => "$fullName ($workplace)",
                    'reason' => "Cặp Họ tên và Đơn vị công tác bị trùng lặp với dòng $prevLine trong tệp tải lên.",
                ];
                $lineHasError = true;
            } else {
                $fileNameWorkplaces[$nwKey] = $line;
            }

            if (isset($dbActiveNameWorkplaces[$nwKey])) {
                $errors[] = [
                    'line' => $line,
                    'column' => 'full_name, workplace',
                    'value' => "$fullName ($workplace)",
                    'reason' => 'Cán bộ - giáo viên với Họ tên và Đơn vị công tác này đã tồn tại trong hệ thống.',
                ];
                $lineHasError = true;
            }
        }

        // 6. Kiểm tra staff_role
        if ($staffRole === '') {
            $errors[] = [
                'line' => $line,
                'column' => 'staff_role',
                'value' => $staffRole,
                'reason' => 'Vai trò bắt buộc nhập, không được để trống.',
            ];
            $lineHasError = true;
        }

        // 7. Kiểm tra active
        if ($activeStr !== '0' && $activeStr !== '1') {
            $errors[] = [
                'line' => $line,
                'column' => 'active',
                'value' => $activeStr,
                'reason' => 'Trạng thái hoạt động (active) chỉ nhận giá trị 1 (Hoạt động) hoặc 0 (Không hoạt động).',
            ];
            $lineHasError = true;
        }

        if ($lineHasError) {
            $errorLines[$line] = true;
        }

        $normalizedRows[] = [
            '_line_number' => $line,
            'full_name' => $fullName,
            'workplace' => $workplace,
            'phone' => $phone !== '' ? $phone : null,
            'email' => $email !== '' ? $email : null,
            'staff_role' => $staffRole,
            'active' => (int)$activeStr,
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
 * Thực hiện import toàn bộ batch cán bộ - giáo viên trong một Transaction duy nhất.
 *
 * @throws Throwable
 */
function staff_import_execute(PDO $pdo, array $normalizedRows): int
{
    if (empty($normalizedRows)) {
        throw new InvalidArgumentException('Không có dữ liệu hợp lệ để nhập vào hệ thống.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO staff (full_name, workplace, phone, email, staff_role, active, created_at, updated_at)
         VALUES (:full_name, :workplace, :phone, :email, :staff_role, :active, NOW(), NOW())'
    );

    $importedCount = 0;
    $pdo->beginTransaction();

    try {
        foreach ($normalizedRows as $row) {
            $stmt->execute([
                'full_name' => $row['full_name'],
                'workplace' => $row['workplace'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'staff_role' => $row['staff_role'],
                'active' => $row['active'],
            ]);
            $importedCount++;
        }

        $pdo->commit();
        return $importedCount;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Staff import execute error: ' . $e->getMessage());
        throw $e;
    }
}
