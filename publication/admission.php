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
        if(($_POST['confirm'] ?? '')!=='publish')throw new RuntimeException('Cần xác nhận công bố kết quả tuyển sinh.');
        publish_admission_results($pdo,$period,(int)auth_user()['user_id'],(int)($_POST['run_id'] ?? 0));
        $_SESSION['admission_publication_success']=$period;
        header('Location: '.url('/publication/admission.php?period_id='.$period));exit;
    }catch(Throwable $e){
        if($e instanceof PDOException){error_log($e->getMessage());$error='Không thể lưu công bố. Toàn bộ thay đổi đã được hoàn tác. Vui lòng thử lại.';}
        else $error=$e->getMessage();
    }
}
$run=null;$snapshot=null;$issues=[];$published=false;
try{
    $published=admission_results_published($pdo,$period);
    $q=$pdo->prepare('SELECT id,status FROM admission_runs WHERE admission_period_id=? ORDER BY id DESC LIMIT 1');$q->execute([$period]);$run=$q->fetch() ?: null;
    if($run && selection_schema($pdo))$snapshot=selection_snapshot($pdo,(int)$run['id']);
    if(!$published){
        // Validation only: no review or publication is written on GET.
        $pdo->beginTransaction();
        try{selection_publish_check($pdo,$period);}finally{if($pdo->inTransaction())$pdo->rollBack();}
    }
}catch(Throwable $e){
    if($e instanceof PDOException){error_log($e->getMessage());$issues[]='Không thể kiểm tra dữ liệu xét tuyển.';}
    else $issues[]=$e->getMessage();
}
$cfg=$selected?period_configuration($pdo,$period):null;
$report=$snapshot['report'] ?? null;$inputs=$snapshot['inputs'] ?? [];
$schools=array_column($inputs['quotas'] ?? [],'name','school_id');
$wishes=[];$aspirations=[];foreach($inputs['aspirations'] ?? [] as $a){$wishes[$a['candidate_id']][$a['priority_order']]=$schools[$a['school_id']] ?? ('Trường #'.$a['school_id']);$aspirations[$a['id']]=$a;}
$papers=[];foreach($inputs['papers'] ?? [] as $paper)$papers[$paper['candidate_id']][$paper['exam_subject_id']]=$paper;
workflow_layout_start('Công bố kết quả tuyển sinh','publication');
?>
<h1 class="h3">Công bố kết quả tuyển sinh</h1>
<p><a href="<?=e(url('/publication/index.php'))?>">Công bố kết quả</a></p>
<form method="get"><label class="form-label">Kỳ tuyển sinh<select name="period_id" class="form-select"><?php foreach($periods as $p):?><option value="<?=(int)$p['id']?>" <?=(int)$p['id']===$period?'selected':''?>><?=e($p['name'])?></option><?php endforeach;?></select></label> <button class="btn btn-outline-primary">Xem danh sách</button></form>
<?php if($cfg && $cfg['settings']['mode']==='simulation'):?><p class="alert alert-warning">MÔ PHỎNG — Không phải kết quả tuyển sinh chính thức.</p><?php endif;?>
<?php if(($_SESSION['admission_publication_success'] ?? null)===$period):unset($_SESSION['admission_publication_success']);?><p role="status" class="alert alert-success">Công bố kết quả tuyển sinh thành công. Kết quả đã được khóa.</p><?php endif;?>
<?php if($error):?><p role="alert" class="alert alert-danger"><?=e($error)?></p><?php endif;?>
<?php if($issues):?><div class="alert alert-warning"><ul><?php foreach($issues as $issue):?><li><?=e($issue)?></li><?php endforeach;?></ul><a href="<?=e(url('/admission/calculate.php?period_id='.$period))?>">Kiểm tra và duyệt kết quả xét tuyển</a></div><?php endif;?>
<p>Danh sách lấy từ lượt xét tuyển đã lưu. Điểm từng môn là điểm sau phúc khảo và xử lý điểm được dùng trong lượt xét tuyển đó.</p>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Số báo danh</th><th>Họ tên</th><?php foreach($inputs['subjects'] ?? [] as $subject):?><th><?=e($subject['name'])?></th><?php endforeach;?><th>Trạng thái điểm</th><th>NV1</th><th>NV2</th><th>NV3</th><th>Trạng thái xét tuyển</th><th>Kết quả</th></tr></thead><tbody>
<?php foreach($report['candidates'] ?? [] as $id=>$c):$admitted=$aspirations[$report['assigned'][$id] ?? 0] ?? null;?>
<tr><td><?=e($c['exam_number'] ?? '—')?></td><td><?=e($c['full_name'])?></td>
<?php foreach($inputs['subjects'] as $subject):$paper=$papers[$id][$subject['id']] ?? null;
$score=$c['subject_scores'][$subject['name']] ?? null;
if($score===null && $paper && in_array($paper['attendance_status'],['present','present_penalty'],true)){$raw=$paper['appeal_status']==='completed'?($paper['appeal_score'] ?? null):$paper['original_score'];if($raw!==null)$score=round((float)$raw*(100-(int)($paper['penalty_percent'] ?? 0))/100,2);}
?><td><?=e($paper && $paper['attendance_status']==='absent'?'Vắng thi':(string)($score ?? '—'))?></td><?php endforeach;?>
<td><?=!empty($inputs['grading'])?'Đã chốt':'Chưa chốt'?></td>
<?php foreach([1,2,3] as $nv):?><td><?=e($wishes[$id][$nv] ?? '—')?></td><?php endforeach;?>
<td><?=$published?'Đã công bố':($run && $run['status']==='calculated'?'Đã xét tuyển':'Chưa hoàn tất xét tuyển')?></td>
<td><?=e($admitted?'Trúng tuyển NV'.$admitted['priority_order'].' · '.($schools[$admitted['school_id']] ?? ''):(!empty($c['reasons'])?implode(' ',$c['reasons']):(!empty($report['tie'])?'Chưa xác định':'Không trúng tuyển')))?></td></tr>
<?php endforeach;?>
<?php if(empty($report['candidates'])):?><tr><td colspan="<?=9+count($inputs['subjects'] ?? [])?>">Chưa có danh sách kết quả xét tuyển đã lưu.</td></tr><?php endif;?></tbody></table></div>
<?php if($published):?><p class="alert alert-success">Đã công bố. Dữ liệu kết quả đã khóa, không thể xét tuyển lại.</p><?php else:?>
<button type="button" class="btn btn-primary" id="open-publication" <?=$issues || !$snapshot?'disabled':''?>>Công bố</button>
<dialog id="confirm-publication" aria-labelledby="confirm-title" style="max-width:600px;width:90vw">
<h2 class="h5" id="confirm-title">Xác nhận công bố kết quả tuyển sinh</h2>
<p>Công bố lượt xét tuyển #<?=(int)($run['id'] ?? 0)?> của kỳ <strong><?=e($selected['name'] ?? '')?></strong>?</p>
<p class="alert alert-warning">Sau khi công bố, dữ liệu kết quả sẽ bị khóa, không thể chỉnh sửa hoặc xét tuyển lại. Thí sinh sẽ được xem kết quả.</p>
<form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$period?>"><input type="hidden" name="run_id" value="<?=(int)($run['id'] ?? 0)?>"><button class="btn btn-primary" name="confirm" value="publish">Xác nhận công bố</button> <button class="btn btn-secondary" type="button" id="cancel-publication">Hủy</button></form>
</dialog>
<script src="<?=e(url('/publication/exam.js'))?>" defer></script>
<?php endif;workflow_layout_end();?>
