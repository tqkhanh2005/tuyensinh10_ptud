<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/candidate_service.php';
require_once __DIR__.'/../includes/publication_service.php';

require_role('candidate');

$pdo = db();
$user = auth_user();
$candidate = candidate_find_by_user((int)$user['user_id']);
if (!$candidate) {
    http_response_code(404);
    exit('Không tìm thấy hồ sơ thí sinh.');
}

$tamperId = filter_input(INPUT_GET, 'candidate_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($tamperId && $tamperId !== (int)$candidate['id']) {
    http_response_code(403);
    exit('403 Forbidden: Bạn không có quyền xem điểm của thí sinh khác.');
}

$periodId = (int)($_GET['period_id'] ?? 0);
$error = '';
$rows = [];

if ($periodId <= 0) {
    $error = 'Vui lòng chọn kỳ tuyển sinh.';
} else {
    try {
        $rows = public_exam_results($pdo, $periodId, (int)$candidate['id']);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Xem điểm thi</title>
</head>
<body>
<h1>Xem điểm thi</h1>
<form method="get">
    <label>Kỳ tuyển sinh
        <input type="number" name="period_id" value="<?=e((string)$periodId)?>" min="1" required>
    </label>
    <button type="submit">Xem điểm</button>
</form>
<?php if ($error): ?>
    <p><?=e($error)?></p>
<?php elseif (!$rows): ?>
    <p>Chưa có dữ liệu điểm thi cho kỳ tuyển sinh này.</p>
<?php else: ?>
<table border="1" cellpadding="8">
    <tr><th>SBD</th><th>Họ tên</th><th>Môn</th><th>Điểm</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?=e($r['candidate_code'])?></td>
        <td><?=e($r['full_name'])?></td>
        <td><?=e($r['subject_name'])?></td>
        <td><?=e((string)$r['total_score'])?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
</body>
</html>
