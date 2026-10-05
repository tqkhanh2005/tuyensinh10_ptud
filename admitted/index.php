<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/publication_service.php';
require_once __DIR__.'/../includes/excel_service.php';

require_permission('admitted.view');

$pdo = db();
$pid = (int)($_GET['period_id'] ?? 0);
$schoolId = isset($_GET['school_id']) && $_GET['school_id'] !== '' ? (int)$_GET['school_id'] : null;
$enroll = isset($_GET['enrollment_status']) && $_GET['enrollment_status'] !== '' ? (string)$_GET['enrollment_status'] : null;
$download = isset($_GET['download']) ? (string)$_GET['download'] : '';
$error = '';
$rows = [];

try {
    if ($pid <= 0) {
        throw new InvalidArgumentException('Vui lòng chọn kỳ tuyển sinh.');
    }
    if ($download !== '' && !in_array($download, ['csv', 'xlsx'], true)) {
        throw new InvalidArgumentException('Định dạng tải xuống không được hỗ trợ.');
    }
    $rows = admitted_list($pdo, $pid, $schoolId, $enroll);

    // Export uses exactly the dataset/columns currently displayed in this list.
    $headers = ['SBD', 'Họ tên', 'Trường', 'NV', 'Điểm xét tuyển', 'Nhập học'];
    $exportRows = [];
    foreach ($rows as $r) {
        $exportRows[] = [
            $r['candidate_code'],
            $r['full_name'],
            $r['school_name'],
            $r['priority_order'],
            $r['admission_score'],
            $r['enrollment_status'],
        ];
    }

    if ($download === 'csv') {
        csv_download('danh_sach_trung_tuyen_' . $pid . '.csv', array_map(
            static fn(array $row): array => array_combine($headers, $row),
            $exportRows
        ));
    }

    if ($download === 'xlsx') {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('[MISSING IMPLEMENTATION DEPENDENCY — XLSX] Máy chủ chưa bật ZipArchive.');
        }
        excel_xlsx_download('danh_sach_trung_tuyen_' . $pid . '.xlsx', $headers, $exportRows);
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$schools = $pdo->query("SELECT id,school_code,name FROM schools WHERE level='THPT' AND active=1 ORDER BY name")->fetchAll();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Danh sách học sinh trúng tuyển</title>
</head>
<body>
<h1>Danh sách học sinh trúng tuyển</h1>
<form method="get">
    <label>Kỳ tuyển sinh <input type="number" name="period_id" value="<?=e((string)$pid)?>" min="1" required></label>
    <select name="school_id">
        <option value="">Tất cả trường</option>
        <?php foreach ($schools as $s): ?>
            <option value="<?=$s['id']?>" <?=$schoolId === (int)$s['id'] ? 'selected' : ''?>><?=e($s['name'])?></option>
        <?php endforeach; ?>
    </select>
    <select name="enrollment_status">
        <option value="">Tất cả trạng thái nhập học</option>
        <option value="pending" <?=$enroll === 'pending' ? 'selected' : ''?>>Chưa xác nhận</option>
        <option value="confirmed" <?=$enroll === 'confirmed' ? 'selected' : ''?>>Đã xác nhận</option>
    </select>
    <button type="submit">Xem</button>
</form>
<?php if ($error): ?>
    <p><?=e($error)?></p>
<?php elseif (!$rows): ?>
    <p>Không có thí sinh trúng tuyển phù hợp.</p>
<?php else: ?>
    <p>
        <a href="?period_id=<?=$pid?>&school_id=<?=e((string)($schoolId ?? ''))?>&enrollment_status=<?=e((string)($enroll ?? ''))?>&download=csv">Tải CSV</a>
        |
        <a href="?period_id=<?=$pid?>&school_id=<?=e((string)($schoolId ?? ''))?>&enrollment_status=<?=e((string)($enroll ?? ''))?>&download=xlsx">Tải XLSX</a>
    </p>
    <table border="1" cellpadding="8">
        <tr><th>SBD</th><th>Họ tên</th><th>Trường</th><th>NV</th><th>Điểm xét tuyển</th><th>Nhập học</th></tr>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?=e($r['candidate_code'])?></td>
            <td><?=e($r['full_name'])?></td>
            <td><?=e($r['school_name'])?></td>
            <td><?=e((string)$r['priority_order'])?></td>
            <td><?=e((string)$r['admission_score'])?></td>
            <td><?=e($r['enrollment_status'])?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</body>
</html>
