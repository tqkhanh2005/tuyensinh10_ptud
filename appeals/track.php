<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/candidate_service.php';
require_once __DIR__.'/../includes/appeal_service.php';

require_role('candidate');

$pdo = db();
$user = auth_user();
$candidate = candidate_for_current_user($pdo, (int)$user['user_id']);
if (!$candidate) {
    http_response_code(404);
    exit('Không tìm thấy hồ sơ thí sinh.');
}

$periodId = (int)($_GET['period_id'] ?? 0);
$error = '';
$rows = [];

if ($periodId <= 0) {
    $error = 'Vui lòng chọn kỳ tuyển sinh.';
} else {
    try {
        $rows = candidate_appeals($pdo, (int)$candidate['id'], $periodId);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Theo dõi trạng thái phúc khảo</title>
</head>
<body>
<h1>Theo dõi trạng thái phúc khảo</h1>
<form method="get">
    <label>Kỳ tuyển sinh
        <input type="number" name="period_id" value="<?=e((string)$periodId)?>" min="1" required>
    </label>
    <button type="submit">Xem trạng thái</button>
</form>
<?php if ($error): ?>
    <p><?=e($error)?></p>
<?php elseif (!$rows): ?>
    <p>Chưa có thông tin yêu cầu phúc khảo.</p>
<?php else: ?>
<table border="1" cellpadding="6">
    <tr>
        <th>Môn thi</th>
        <th>Thời gian đăng ký</th>
        <th>Trạng thái</th>
        <th>Điểm phúc khảo</th>
        <th>Chi tiết kết quả</th>
    </tr>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?=e($r['subject_code'].' - '.$r['subject_name'])?></td>
        <td><?=e($r['submitted_at'])?></td>
        <td><?=e(appeal_status_label($r['status']))?></td>
        <td>
            <?php if ($r['status'] === 'completed' && $r['new_score'] !== null): ?>
                <?=e((string)$r['new_score'])?>
            <?php else: ?>
                Chưa có kết quả
            <?php endif; ?>
        </td>
        <td>
            <?php if ($r['status'] === 'completed' && $r['change_reason'] !== null): ?>
                <?=e($r['change_reason'])?>
            <?php else: ?>
                Kết quả phúc khảo chưa được cập nhật
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
</body>
</html>
