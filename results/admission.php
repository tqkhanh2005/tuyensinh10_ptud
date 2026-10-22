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
    exit('403 Forbidden: Bạn không có quyền xem kết quả của thí sinh khác.');
}

$periodId = (int)($_GET['period_id'] ?? 0);
$error = '';
$rows = [];

if ($periodId <= 0) {
    $error = 'Vui lòng chọn kỳ tuyển sinh.';
} else {
    try {
        $rows = public_admission_results($pdo, $periodId, (int)$candidate['id']);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<?php require_once __DIR__ . '/../includes/workflow_layout.php'; workflow_layout_start('Xem kết quả tuyển sinh', 'admission-results'); ?>
<h1>Xem kết quả tuyển sinh</h1>
<form method="get">
    <label>Kỳ tuyển sinh
        <input type="number" name="period_id" value="<?=e((string)$periodId)?>" min="1" required>
    </label>
    <button type="submit">Xem kết quả</button>
</form>
<?php if ($error): ?>
    <p><?=e($error)?></p>
<?php elseif (!$rows): ?>
    <p>Chưa có kết quả xét tuyển cho kỳ tuyển sinh này.</p>
<?php else: ?>
<table border="1" cellpadding="8">
    <tr><th>SBD</th><th>Họ tên</th><th>NV</th><th>Trường</th><th>Điểm xét tuyển</th><th>Trạng thái</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?=e($r['candidate_code'])?></td>
        <td><?=e($r['full_name'])?></td>
        <td><?=e((string)$r['priority_order'])?></td>
        <td><?=e($r['school_name'] ?? '')?></td>
        <td><?=e((string)$r['admission_score'])?></td>
        <td><?=e((string)$r['result_status'])?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php workflow_layout_end(); ?>
