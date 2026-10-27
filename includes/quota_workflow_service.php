<?php
declare(strict_types=1);
require_once __DIR__ . '/school_admission_service.php';
require_once __DIR__ . '/excel_service.php';

/** Validate all rows before creating a confirmation or performing any writes. */
function quota_prepare_changes(array $rows, array $input): array
{
    $directory = array_column($rows, null, 'school_id');
    $errors = [];
    $changes = [];
    $replaced = 0;
    foreach ($input as $id => $value) {
        if (!isset($directory[$id])) {
            $errors[$id] = 'Trường không tồn tại hoặc không phải trường THPT đang hoạt động.';
            continue;
        }
        $row = $directory[$id];
        if (!is_scalar($value)) {
            $errors[$id] = 'Chỉ tiêu không hợp lệ.';
            continue;
        }
        $value = trim((string)$value);
        $isSet = $row['quota_status'] === 'set' && (int)$row['quota'] > 0;
        if ($value === '' && !$isSet) continue;
        if (!quota_positive_integer($value)) {
            $errors[$id] = 'Chỉ tiêu phải là số nguyên lớn hơn 0, tối đa 4.294.967.295.';
            continue;
        }
        if ($isSet && (int)$value === (int)$row['quota']) continue;
        $changes[(int)$id] = (int)$value;
        if ($isSet) $replaced++;
    }
    return ['changes' => $changes, 'errors' => $errors, 'replaced' => $replaced];
}

/** Parse atomically: no partial mapping is returned if any Excel row is invalid. */
function quota_import_table(array $table, array $rows): array
{
    $headers = array_map(static fn($v) => mb_strtolower(trim((string)$v), 'UTF-8'), array_shift($table) ?? []);
    if (count($headers) !== 2 || count(array_unique($headers)) !== 2
        || !in_array('mã trường', $headers, true) || !in_array('chỉ tiêu', $headers, true)) {
        throw new InvalidArgumentException('File phải gồm đúng 2 cột: Mã trường, Chỉ tiêu.');
    }
    $codeColumn = array_search('mã trường', $headers, true);
    $quotaColumn = array_search('chỉ tiêu', $headers, true);
    $directory = [];
    foreach ($rows as $row) $directory[mb_strtolower($row['school_code'], 'UTF-8')] = (int)$row['school_id'];
    $mapped = [];
    $errors = [];
    foreach ($table as $index => $cells) {
        if (!array_filter($cells, static fn($v) => trim((string)$v) !== '')) continue;
        $line = $index + 2;
        $code = mb_strtolower(trim((string)($cells[$codeColumn] ?? '')), 'UTF-8');
        $value = trim((string)($cells[$quotaColumn] ?? ''));
        if (count($cells) > 2) $errors[] = "Dòng $line: có nhiều hơn 2 cột.";
        if (!isset($directory[$code])) { $errors[] = "Dòng $line: mã trường không thuộc danh mục THPT đang hoạt động."; continue; }
        $id = $directory[$code];
        if (array_key_exists($id, $mapped)) $errors[] = "Dòng $line: mã trường bị lặp trong file.";
        if (!quota_positive_integer($value)) $errors[] = "Dòng $line: chỉ tiêu phải là số nguyên lớn hơn 0, tối đa 4.294.967.295.";
        $mapped[$id] = $value;
    }
    if ($errors) throw new InvalidArgumentException(implode(' ', $errors));
    if (!$mapped) throw new InvalidArgumentException('File không có dữ liệu chỉ tiêu.');
    return $mapped;
}

function quota_import_upload(array $file, array $rows): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new InvalidArgumentException('Không thể tải file. Vui lòng chọn lại file Excel .xlsx.');
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) throw new InvalidArgumentException('File không được vượt quá 2 MB.');
    if (strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION)) !== 'xlsx') throw new InvalidArgumentException('Chỉ chấp nhận file Excel .xlsx.');
    return quota_import_table(excel_parse_xlsx($file['tmp_name']), $rows);
}

/** Public and teacher views use the same publication boundary, never sum periods. */
function quota_public_rows(PDO $pdo, int $periodId = 0, string $search = ''): array
{
    if (!$periodId) {
        foreach ($pdo->query('SELECT id FROM admission_periods WHERE status IN ("published","closed") ORDER BY id DESC')->fetchAll(PDO::FETCH_COLUMN) as $id) {
            if (period_publicly_visible($pdo, (int)$id)) { $periodId = (int)$id; break; }
        }
    }
    if (!$periodId || !period_publicly_visible($pdo, $periodId)) return [];
    $sql = 'SELECT s.id,s.school_code,s.name,s.district,s.admission_area,aq.quota,aq.quota AS total_quota,p.name AS period_name,p.academic_year
        FROM admission_quotas aq JOIN schools s ON s.id=aq.school_id JOIN admission_periods p ON p.id=aq.admission_period_id
        WHERE aq.admission_period_id=:p AND aq.published_at IS NOT NULL AND aq.status="set" AND aq.quota>0 AND s.level="THPT" AND s.active=1';
    $params = ['p' => $periodId];
    if ($search !== '') {
        $sql .= ' AND (s.name LIKE :name OR s.school_code LIKE :code OR s.admission_area LIKE :area)';
        foreach (['name','code','area'] as $key) $params[$key] = '%' . $search . '%';
    }
    $q = $pdo->prepare($sql . ' ORDER BY s.name,s.id');
    $q->execute($params);
    return $q->fetchAll();
}
