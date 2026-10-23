<?php
declare(strict_types=1);

require_once __DIR__ . '/school_admission_service.php';
require_once __DIR__ . '/excel_service.php';

function school_batch_fields(): array
{
    return ['name' => 'Tên trường', 'level' => 'Cấp học', 'address' => 'Địa chỉ', 'phone' => 'Số điện thoại', 'email' => 'Email'];
}

function school_batch_rows(array $input): array
{
    $rows = [];
    foreach ($input as $row) {
        $clean = [];
        foreach (school_batch_fields() as $key => $label) {
            $value = is_array($row) ? ($row[$key] ?? '') : '';
            $clean[$key] = is_scalar($value) ? trim((string)$value) : '';
        }
        $rows[] = $clean;
    }
    return $rows ?: [array_fill_keys(array_keys(school_batch_fields()), '')];
}

function school_batch_import(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new InvalidArgumentException('Không thể tải file. Vui lòng chọn lại file Excel.');
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('File không được vượt quá 2 MB.');
    }
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
        throw new InvalidArgumentException('Sai định dạng file. Chỉ chấp nhận file Excel .xlsx.');
    }
    $table = excel_parse_upload($file['tmp_name'], $file['name']);
    $headers = array_shift($table) ?? [];
    $headers = array_map(static fn($v) => mb_strtolower(trim((string)$v), 'UTF-8'), $headers);
    $map = [];
    foreach (school_batch_fields() as $key => $label) {
        $aliases = [mb_strtolower($label, 'UTF-8')];
        if ($key === 'phone') $aliases[] = 'sđt';
        if ($key === 'email') $aliases[] = 'email liên hệ';
        $matches = array_keys(array_filter($headers, static fn($h) => in_array($h, $aliases, true)));
        if (count($matches) !== 1) throw new InvalidArgumentException('File phải có đúng một cột ' . $label . '.');
        $map[$key] = $matches[0];
    }
    $rows = [];
    foreach ($table as $cells) {
        if (!array_filter($cells, static fn($v) => trim((string)$v) !== '')) continue;
        $row = [];
        foreach ($map as $key => $column) $row[$key] = $cells[$column] ?? '';
        $rows[] = $row;
        if (count($rows) > 100) throw new InvalidArgumentException('Mỗi lần chỉ được nhập tối đa 100 trường.');
    }
    if (!$rows) throw new InvalidArgumentException('File không có dòng dữ liệu.');
    return school_batch_rows($rows);
}

/** Caller owns the transaction so validation and inserts form one atomic operation. */
function school_batch_validate(PDO $pdo, array $rows, ?int $excludeSchoolId = null): array
{
    $errors = [];
    $limits = ['name' => 150, 'address' => 255, 'phone' => 30, 'email' => 150];
    // Lock the existing directory (including its index range) until the batch is saved.
    $pdo->query('SELECT id FROM schools ORDER BY id FOR UPDATE')->fetchAll();
    $seen = [];
    foreach ($rows as $i => $row) {
        foreach (school_batch_fields() as $key => $label) {
            if ($row[$key] === '') $errors[$i][] = $label . ': bắt buộc nhập.';
            if (isset($limits[$key]) && mb_strlen($row[$key], 'UTF-8') > $limits[$key]) {
                $errors[$i][] = $label . ': tối đa ' . $limits[$key] . ' ký tự.';
            }
        }
        if (!in_array($row['level'], ['THCS', 'THPT'], true)) $errors[$i][] = 'Cấp học phải là THCS hoặc THPT.';
        if ($row['email'] !== '' && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) $errors[$i][] = 'Email sai định dạng.';
        $phone = preg_replace('/[\s().-]+/', '', $row['phone']);
        if ($row['phone'] !== '' && !preg_match('/^(?:0[0-9]{9,10}|\+84[0-9]{9,10})$/', $phone)) {
            $errors[$i][] = 'Số điện thoại sai định dạng (dùng đầu 0 hoặc +84).';
        }
        foreach (['name', 'address', 'phone', 'email'] as $key) {
            if ($row[$key] === '') continue;
            $value = $key === 'phone' ? preg_replace('/^\+84/', '0', $phone) : preg_replace('/\s+/u', ' ', $row[$key]);
            // Use the database collation for comparisons, including Vietnamese case/accent rules.
            foreach ($seen[$key] ?? [] as $previous => $oldValue) {
                $compare = $pdo->prepare('SELECT CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci');
                $compare->execute([$value, $oldValue]);
                if ($compare->fetchColumn()) {
                    $errors[$i][] = school_batch_fields()[$key] . ': trùng với dòng ' . ($previous + 1) . '.';
                    $errors[$previous][] = school_batch_fields()[$key] . ': trùng với dòng ' . ($i + 1) . '.';
                }
            }
            $seen[$key][$i] = $value;
            $column = $key === 'phone'
                ? "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '.', ''), '-', ''), '(', ''), ')', ''), '+84', '0')"
                : "TRIM($key)";
            $check = $pdo->prepare("SELECT school_code FROM schools WHERE $column = ?" . ($excludeSchoolId !== null ? ' AND id <> ?' : '') . ' LIMIT 1');
            $check->execute($excludeSchoolId !== null ? [$value, $excludeSchoolId] : [$value]);
            if ($check->fetchColumn() !== false) $errors[$i][] = school_batch_fields()[$key] . ': đã tồn tại trong hệ thống.';
        }
    }
    return $errors;
}
