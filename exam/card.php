<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/candidate_service.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_permission('exam_card.view');
$u = auth_user();
$candRow = candidate_find_by_user((int)$u['id']);
$candId = $candRow ? (int)$candRow['id'] : 0;

// Chống IDOR: không cho phép xem thẻ dự thi thí sinh khác qua query string
$tamperId = filter_input(INPUT_GET, 'candidate_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($tamperId && $tamperId !== $candId) {
    http_response_code(403);
    exit('403 Forbidden: Bạn không có quyền truy cập thẻ dự thi của thí sinh khác.');
}

try {
    $periodId=isset($_GET['period_id']) ? max(0,(int)$_GET['period_id']) : null;
    $candidate = exam_card_for_user(db(), (int)$u['id'],$periodId);
} catch (Throwable $e) {
    error_log('exam card query failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Không thể tạo thẻ dự thi, vui lòng thử lại.');
}
if (!$candidate) {
    http_response_code(404);
    exit('Bạn chưa được phân công phòng thi hoặc cấp số báo danh.');
}
if (trim((string)$candidate['exam_number']) === '' || trim((string)$candidate['exam_site_name']) === '' || trim((string)$candidate['exam_room_name']) === '' || trim((string)$candidate['exam_schedule']) === '') { ?>
    <!doctype html>
    <html lang="vi">
    <meta charset="utf-8">
    <p>Bạn chưa được phân công phòng thi hoặc cấp số báo danh.</p>

    </html><?php exit;
}
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Thẻ dự thi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>body{font:16px Arial,sans-serif;max-width:800px;margin:32px auto;padding:20px;line-height:1.6;color:#17243b}h1{text-align:center}table{border-collapse:collapse;width:100%}td,th{border:1px solid #888;padding:8px;text-align:left}button{margin-top:24px;padding:12px}.simulation{padding:12px;background:#fff1ca}@page{size:A4;margin:20mm}@media print{button{display:none}body{margin:0;padding:0}}</style>
</head>

<body>
    <?php $cfg=isset($candidate['admission_period_id'])?period_configuration(db(),(int)$candidate['admission_period_id']):null; if($cfg && $cfg['settings']['mode']==='simulation'):?><p class="simulation"><strong>MÔ PHỎNG — Không phải phiếu dự thi chính thức.</strong></p><?php endif;?>
    <h1>THẺ DỰ THI</h1>
    <?php if(isset($candidate['period_name'])):?><p><?=e($candidate['period_name'])?> · <?=e($candidate['academic_year'])?></p><?php endif;?>
    <p>Họ tên: <?= e($candidate['full_name']) ?></p>
    <p>SBD: <?= e($candidate['exam_number']) ?></p>
    <p>Điểm thi: <?= e($candidate['exam_site_name']) ?></p>
    <p>Phòng thi: <?= e($candidate['exam_room_name']) ?></p>
    <?php if(!empty($candidate['exam_site_address'])):?><p>Địa chỉ: <?=e($candidate['exam_site_address'])?></p><?php endif;?>
    <?php if(!empty($candidate['schedule'])):?><table><thead><tr><th>Môn</th><th>Ngày thi</th><th>Bắt đầu</th><th>Kết thúc</th></tr></thead><tbody><?php foreach($candidate['schedule'] as $s):?><tr><td><?=e($s['name'])?></td><td><?=e(date('d/m/Y',strtotime($s['exam_date'])))?></td><td><?=e(substr($s['start_time'],0,5))?></td><td><?=e(substr($s['end_time'],0,5))?></td></tr><?php endforeach;?></tbody></table><?php else:?><p>Giờ thi: <?=e($candidate['exam_schedule'])?></p><?php endif;?>
    <button onclick="window.print()">In / Tải thẻ</button>
</body>

</html>