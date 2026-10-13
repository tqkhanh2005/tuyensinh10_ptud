<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/appeal_service.php';

require_permission('appeal.submit');
$pdo = db();
$user = auth_user();
$candidate = candidate_for_current_user($pdo, (int)$user['user_id']);
if (!$candidate) { http_response_code(404); exit('Không tìm thấy hồ sơ thí sinh.'); }

$tamperId = filter_input(INPUT_GET, 'candidate_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'candidate_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($tamperId && $tamperId !== (int)$candidate['id']) {
    http_response_code(403);
    exit('403 Forbidden: Bạn không có quyền đăng ký phúc khảo cho thí sinh khác.');
}

$periodId = (int)($_GET['period_id'] ?? $_POST['period_id'] ?? 0);
$error = '';
$message = '';
$subjects = [];

if ($periodId > 0) {
    try { appeal_window_open($pdo, $periodId); $subjects = appeal_eligible_subjects($pdo, (int)$candidate['id'], $periodId); }
    catch (Throwable $e) { $error = $e->getMessage(); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? $_POST['csrf'] ?? null)) { http_response_code(419); exit('CSRF token không hợp lệ.'); }
    try {
        $id = create_appeal($pdo, (int)$candidate['id'], $periodId, (int)($_POST['subject_id'] ?? 0), (string)($_POST['reason'] ?? ''));
        $message = 'Đăng ký phúc khảo thành công. Mã yêu cầu: #'.$id.'. Trạng thái: Chờ xử lý.';
        $subjects = appeal_eligible_subjects($pdo, (int)$candidate['id'], $periodId);
    } catch (Throwable $e) { $error = $e->getMessage(); }
}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Đăng ký phúc khảo</title></head><body>
<h1>Đăng ký phúc khảo</h1>
<?php if($message):?><p><?=e($message)?></p><?php endif;?>
<?php if($error):?><p><?=e($error)?></p><?php endif;?>
<form method="get"><label>Kỳ tuyển sinh <input type="number" name="period_id" value="<?=e((string)$periodId)?>" required></label><button>Xem môn được phép phúc khảo</button></form>
<?php if($subjects):?><form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>">
<label>Môn thi <select name="subject_id" required><option value="">-- Chọn môn --</option><?php foreach($subjects as $s):?><option value="<?=$s['id']?>" <?=($s['appeal_id']?'disabled':'')?>><?=e($s['subject_code'].' - '.$s['name'].' | Điểm: '.$s['total_score'].($s['appeal_id']?' | Đã đăng ký':''))?></option><?php endforeach;?></select></label><br>
<label>Lý do phúc khảo<br><textarea name="reason" rows="5" required></textarea></label><br>
<button type="submit">Gửi yêu cầu phúc khảo</button><button type="reset">Hủy</button></form><?php elseif($periodId && !$error):?><p>Không có môn thi đủ điều kiện đăng ký phúc khảo.</p><?php endif;?>
</body></html>
