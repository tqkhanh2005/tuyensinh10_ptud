<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/publication_service.php';

require_permission('statistics.view');

$pdo = db();
$pid = (int)($_GET['period_id'] ?? 0);
$type = (string)($_GET['type'] ?? 'admission_result');
$error = '';
$rows = [];

if ($pid <= 0) {
    $error = 'Vui lòng chọn kỳ tuyển sinh.';
} elseif (!in_array($type, ['profile_status', 'admission_result'], true)) {
    $error = 'Loại thống kê không hợp lệ.';
} else {
    try {
        $rows = statistics($pdo, $pid, $type);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Thống kê tuyển sinh</title>
</head>
<body>
<h1>Thống kê tuyển sinh</h1>
<form method="get">
    <label>Kỳ tuyển sinh
        <input type="number" name="period_id" value="<?=e((string)$pid)?>" min="1" required>
    </label>
    <label>Loại thống kê
        <select name="type" required>
            <option value="profile_status" <?= $type === 'profile_status' ? 'selected' : '' ?>>Tình trạng hồ sơ</option>
            <option value="admission_result" <?= $type === 'admission_result' ? 'selected' : '' ?>>Kết quả xét tuyển</option>
        </select>
    </label>
    <button type="submit">Xem thống kê</button>
</form>
<?php if ($error): ?>
    <p><?=e($error)?></p>
<?php elseif (!$rows): ?>
    <p>Chưa có dữ liệu thống kê.</p>
<?php else: ?>
<table border="1" cellpadding="8">
    <tr>
        <th><?= $type === 'profile_status' ? 'Tình trạng hồ sơ' : 'Kết quả xét tuyển' ?></th>
        <th>Số lượng</th>
    </tr>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?php if ($type === 'profile_status'): ?><?= (int)$r['eligible'] === 1 ? 'Hợp lệ' : 'Không hợp lệ' ?><?php else: ?><?=e((string)$r['result_status'])?><?php endif; ?></td>
        <td><?=e((string)$r['total'])?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
</body>
</html>
