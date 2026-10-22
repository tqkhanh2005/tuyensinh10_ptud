<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/exam_service.php';
require_once __DIR__.'/../includes/grading_filters.php';
require_once __DIR__.'/../includes/workflow_layout.php';
require_permission('exam.manage');$pdo=db();
$context=grading_filter_context($pdo,(int)($_POST['period_id'] ?? $_GET['period_id'] ?? 0),(int)($_POST['subject_id'] ?? $_GET['subject_id'] ?? 0));$periodId=$context['period'];$subjectId=$context['subject'];$error='';
if(!$periodId){http_response_code(404);exit('Chưa có kỳ tuyển sinh.');}
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();try{
        $actor=(int)auth_user()['user_id'];$action=(string)($_POST['action'] ?? '');
        if($action==='assign'){require_permission('council.manage');grading_assign_initial($pdo,$periodId,$subjectId,(int)($_POST['room_id'] ?? 0),(int)($_POST['member_id'] ?? 0),$actor);}
        elseif($action==='review'){require_permission('score.enter');grading_review($pdo,$periodId,$actor);}
        else throw new InvalidArgumentException('Thao tác không hợp lệ.');
        header('Location: '.url('/exam/grading.php?period_id='.$periodId.'&subject_id='.$subjectId.'&saved=1'));exit;
    }catch(Throwable $e){$error=$e instanceof PDOException?'Không thể lưu thay đổi. Vui lòng tải lại và kiểm tra dữ liệu.':$e->getMessage();if($e instanceof PDOException)error_log($e->getMessage());}
}
$ready=grading_readiness($pdo,$periodId);$state=grading_state($pdo,$periodId);$cfg=period_configuration($pdo,$periodId);
$members=$pdo->prepare('SELECT cm.id,st.full_name,c.name council_name FROM council_members cm JOIN councils c ON c.id=cm.council_id AND c.council_type="grading" AND c.status="established" JOIN council_subjects cs ON cs.council_id=c.id JOIN staff st ON st.id=cm.staff_id AND st.active=1 JOIN users u ON u.id=st.user_id AND u.active=1 WHERE c.admission_period_id=? AND cs.exam_subject_id=? ORDER BY st.full_name');$members->execute([$periodId,$subjectId]);$members=$members->fetchAll();
workflow_layout_start('Chấm thi và phúc khảo','scores');
?>
<h1 class="h3">Chấm thi và phúc khảo · TP.HCM</h1>
<?php grading_filters($context);?>
<?php if($cfg && $cfg['settings']['mode']==='simulation' && !isset($_GET['period_id'])):?><div class="alert alert-warning">MÔ PHỎNG — Không phải kết quả tuyển sinh chính thức.</div><?php endif;?>
<?php if($error):?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif;?><?php if(isset($_GET['saved'])):?><div class="alert alert-success">Đã lưu thay đổi.</div><?php endif;?>
<p class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="<?=e(url('/exam/organization.php?period_id='.$periodId))?>">Tổ chức thi</a><a class="btn btn-outline-primary" href="<?=e(url('/exam/scores.php?period_id='.$periodId.'&subject_id='.$subjectId))?>">Nhập điểm theo phách</a><a class="btn btn-outline-primary" href="<?=e(url('/councils/index.php?period_id='.$periodId))?>">Hội đồng chấm thi</a><a class="btn btn-outline-primary" href="<?=e(url('/appeals/update_result.php?period_id='.$periodId))?>">Xử lý phúc khảo</a><a class="btn btn-outline-secondary" href="<?=e(url('/publication/index.php?period_id='.$periodId))?>">Công bố điểm</a></p>
<div class="card p-3 mb-3"><h2 class="h5">Tiến độ <?=$ready['students']?> học sinh</h2><div class="table-responsive"><table class="table"><thead><tr><th>Môn</th><th>Bài thi</th><th>Có mặt</th><th>Vắng</th><th>Đã cấp phách</th><th>Có điểm</th><th>Đã ghép phách</th></tr></thead><tbody><?php foreach($ready['subjects'] as $s):?><tr><td><?=e($s['name'])?></td><?php foreach(['papers','present','absent','coded','scored','matched'] as $key):?><td><?=(int)$s[$key]?></td><?php endforeach;?></tr><?php endforeach;?><?php if(!$ready['subjects']):?><tr><td colspan="7">Chưa có lịch thi và danh sách bài thi.</td></tr><?php endif;?></tbody></table></div></div>
<div class="card p-3 mb-3"><h2 class="h5">1. Bài thi, điểm danh và số phách</h2><p>Chọn phòng/môn để lập bài thi. Điểm danh khi đến giờ thi; cấp phách và nhập điểm sau khi môn thi kết thúc. Phách đã cấp được giữ nguyên; điểm danh bị khóa sau đánh phách.</p>
<?php foreach(exam_sites($pdo,$periodId) as $site):?><details class="mb-2"><summary><?=e($site['name'])?></summary><div class="d-flex flex-wrap gap-2 p-2"><?php foreach(exam_rooms($pdo,(int)$site['id']) as $room):?><a class="btn btn-sm btn-outline-primary" href="<?=e(url('/exam/papers.php?period_id='.$periodId.'&subject_id='.$subjectId.'&room_id='.$room['id']))?>"><?=e($room['name'])?></a><?php endforeach;?></div></details><?php endforeach;?></div>
<div class="card p-3 mb-3"><h2 class="h5">2. Phân công người chấm ban đầu</h2><p>Chọn thành viên hội đồng chấm của môn đang xem. Việc nhập điểm và người trực tiếp chấm bài được ghi nhận riêng; phúc khảo phải giao người khác.</p><form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>"><input type="hidden" name="action" value="assign"><fieldset <?=$state?'disabled':''?> class="row g-2"><div class="col-md-5"><label class="form-label w-100">Phạm vi<select class="form-select" name="room_id"><option value="0">Toàn bộ phòng của môn</option><?php foreach(exam_sites($pdo,$periodId) as $site):foreach(exam_rooms($pdo,(int)$site['id']) as $room):?><option value="<?=$room['id']?>"><?=e($site['name'].' · '.$room['name'])?></option><?php endforeach;endforeach;?></select></label></div><div class="col-md-5"><label class="form-label w-100">Người chấm<select class="form-select" name="member_id" required><option value="">Chọn giáo viên</option><?php foreach($members as $m):?><option value="<?=$m['id']?>"><?=e($m['full_name'].' · '.$m['council_name'])?></option><?php endforeach;?></select></label></div><div class="col-md-2 align-self-end"><button class="btn btn-primary mb-2">Phân công</button></div></fieldset></form></div>
<div class="card p-3 mb-3"><h2 class="h5">3. Rà soát và khóa điểm trước công bố</h2><?php if($state):?><div class="alert alert-info">Đã rà soát và khóa điểm lúc <?=e($state['reviewed_at'])?>. Thay đổi sau công bố đi qua quy trình phúc khảo có lưu điểm gốc, kết quả và lịch sử.</div><?php elseif($ready['errors']):?><ul><?php foreach($ready['errors'] as $issue):?><li><?=e($issue)?></li><?php endforeach;?></ul><?php else:?><p class="text-success">Đủ danh sách, điểm danh, phách, người chấm và điểm thi.</p><?php endif;?>
<form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="action" value="review"><button class="btn btn-success" <?=$state || $ready['errors']?'disabled':''?>>Xác nhận rà soát và khóa điểm</button></form></div>
<?php workflow_layout_end();?>
