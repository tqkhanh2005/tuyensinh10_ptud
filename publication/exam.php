<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/publication_service.php';
require_once __DIR__.'/../includes/workflow_layout.php';
require_permission('publication.manage');
$pdo=db();$periods=publication_periods($pdo);
$period=(int)($_POST['period_id'] ?? $_GET['period_id'] ?? ($periods[0]['id'] ?? 0));
$error='';$selected=null;foreach($periods as $p)if((int)$p['id']===$period)$selected=$p;
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    try{
        if(($_POST['confirm'] ?? '')!=='publish')throw new RuntimeException('Cần xác nhận công bố điểm thi.');
        publish_exam_results($pdo,$period,(int)auth_user()['user_id']);
        $_SESSION['exam_publication_success']=$period;
        header('Location: '.url('/publication/exam.php?period_id='.$period));exit;
    }catch(Throwable $e){
        if($e instanceof PDOException){error_log($e->getMessage());$error='Không thể công bố điểm thi. Dữ liệu chưa được thay đổi. Vui lòng thử lại.';}
        else $error=$e->getMessage();
    }
}
$published=$selected && exam_results_published($pdo,$period);
$ready=['rows'=>[],'errors'=>[]];
if($selected){try{$ready=exam_publication_readiness($pdo,$period);}catch(Throwable $e){error_log($e->getMessage());$error='Không thể kiểm tra danh sách điểm thi.';$ready['errors'][]=$error;}}
else $ready['errors'][]='Chưa chọn kỳ tuyển sinh hợp lệ.';
$cfg=$selected?period_configuration($pdo,$period):null;
if($selected && $selected['status']!=='published')$ready['errors'][]='Kỳ tuyển sinh chưa công bố hoặc đã đóng.';
if($cfg && !grading_state($pdo,$period))$ready['errors'][]='Cần rà soát và khóa điểm trước khi công bố.';
$students=[];$subjects=[];
foreach($ready['rows'] as $r){$subjects[$r['subject_id']]=$r['subject_name'];$students[$r['candidate_id']]['name']=$r['full_name'];$students[$r['candidate_id']]['number']=$r['exam_number'];$students[$r['candidate_id']]['scores'][$r['subject_id']]=$r;}
workflow_layout_start('Công bố điểm thi','publication');
?>
<h1 class="h3">Công bố điểm thi trước phúc khảo</h1>
<p><a href="<?=e(url('/publication/index.php'))?>">Công bố kết quả</a></p>
<form method="get"><label class="form-label">Kỳ tuyển sinh<select class="form-select" name="period_id"><?php foreach($periods as $p):?><option value="<?=(int)$p['id']?>" <?=(int)$p['id']===$period?'selected':''?>><?=e($p['name'])?></option><?php endforeach;?></select></label> <button class="btn btn-outline-primary">Xem danh sách</button></form>
<?php if($cfg && $cfg['settings']['mode']==='simulation'):?><p class="alert alert-warning">MÔ PHỎNG — Không phải kết quả chính thức.</p><?php endif;?>
<?php if(($_SESSION['exam_publication_success'] ?? null)===$period):unset($_SESSION['exam_publication_success']);?><p role="status" class="alert alert-success">Công bố điểm thi thành công. Điểm gốc đã được khóa.</p><?php endif;?>
<?php if($error):?><p role="alert" class="alert alert-danger"><?=e($error)?></p><?php endif;?>
<?php if(!$published && $ready['errors']):?><div class="alert alert-warning"><ul><?php foreach(array_unique($ready['errors']) as $issue):?><li><?=e($issue)?></li><?php endforeach;?></ul></div><?php endif;?>
<p>Điểm dưới đây là điểm chấm ban đầu, chưa thay thế bằng điểm phúc khảo. Bài vắng thi hiển thị “Vắng thi”, không quy thành điểm 0.</p>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Số báo danh</th><th>Họ tên</th><?php foreach($subjects as $name):?><th><?=e($name)?></th><?php endforeach;?><th>Trạng thái điểm</th></tr></thead><tbody>
<?php foreach($students as $c):?><tr><td><?=e($c['number'] ?? '—')?></td><td><?=e($c['name'])?></td><?php foreach($subjects as $id=>$name):$r=$c['scores'][$id] ?? null;?><td><?=e($r && $r['attendance_status']==='absent'?'Vắng thi':(string)($r['total_score'] ?? 'Chưa có điểm'))?></td><?php endforeach;?><td><?=$published?'Đã công bố':'Chưa công bố'?></td></tr><?php endforeach;?>
<?php if(!$students):?><tr><td colspan="<?=count($subjects)+3?>">Chưa có danh sách thí sinh.</td></tr><?php endif;?></tbody></table></div>
<?php if($published):?><p class="alert alert-success">Đã công bố. Không thể sửa điểm gốc; thay đổi sau phúc khảo được lưu riêng theo quy trình phúc khảo.</p><?php else:?>
<button type="button" class="btn btn-primary" id="open-publication" <?=$ready['errors']?'disabled':''?>>Công bố</button>
<dialog id="confirm-publication" aria-labelledby="confirm-title" style="max-width:560px;width:90vw">
<h2 class="h5" id="confirm-title">Xác nhận công bố điểm thi</h2>
<p>Công bố điểm thi của <?=count($students)?> thí sinh trong kỳ <strong><?=e($selected['name'] ?? '')?></strong>? Sau công bố, thí sinh được xem điểm và điểm gốc không thể sửa.</p>
<form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$period?>"><button class="btn btn-primary" name="confirm" value="publish">Xác nhận công bố</button> <button class="btn btn-secondary" type="button" id="cancel-publication">Hủy</button></form>
</dialog>
<script src="<?=e(url('/publication/exam.js'))?>" defer></script>
<?php endif;workflow_layout_end(); ?>
