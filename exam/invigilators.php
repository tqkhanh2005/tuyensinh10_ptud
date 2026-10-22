<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_permission('invigilation.assign');
$pdo=db();
$roomId=(int)($_GET['room_id']??$_POST['room_id']??0);
$subjectId=(int)($_GET['subject_id']??$_POST['subject_id']??0);
$error='';$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    try{
        if(($_POST['action']??'assign')==='replace') invigilator_replace($pdo,(int)$_POST['assignment_id'],(int)$_POST['replacement_staff_id']);
        else invigilator_assign($pdo,$roomId,$subjectId,(int)$_POST['staff1'],(int)$_POST['staff2']);
        $message='Phân công cán bộ coi thi thành công.';
    }catch(Throwable $e){$error=$e->getMessage();}
}
$staff=$pdo->query('SELECT id,full_name,active FROM staff ORDER BY active DESC,full_name')->fetchAll();
$assignments=[];
if($roomId>0&&$subjectId>0){
    $q=$pdo->prepare('SELECT a.id,a.staff_id,a.position,a.status,s.full_name FROM exam_invigilator_assignments a JOIN staff s ON s.id=a.staff_id WHERE a.exam_room_id=:r AND a.exam_subject_id=:s ORDER BY a.position');$q->execute(['r'=>$roomId,'s'=>$subjectId]);$assignments=$q->fetchAll();
}
?>
<?php require_once __DIR__ . '/../includes/workflow_layout.php'; workflow_layout_start('Phân công coi thi', 'exam'); ?>
<h1>Phân công cán bộ coi thi</h1>
<?php if($message):?><p><?=e($message)?></p><?php endif;?><?php if($error):?><p><?=e($error)?></p><?php endif;?>
<form method="post"><?=csrf_field()?><input type="hidden" name="room_id" value="<?=$roomId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>">
<label>Giám thị 1 <select name="staff1" required><option value="">-- Chọn --</option><?php foreach($staff as $s): if((int)$s['active']!==1)continue;?><option value="<?=$s['id']?>"><?=e($s['full_name'])?></option><?php endforeach;?></select></label>
<label>Giám thị 2 <select name="staff2" required><option value="">-- Chọn --</option><?php foreach($staff as $s): if((int)$s['active']!==1)continue;?><option value="<?=$s['id']?>"><?=e($s['full_name'])?></option><?php endforeach;?></select></label><button type="submit">Lưu phân công</button></form>
<h2>Phân công hiện tại</h2>
<table border="1"><tr><th>Vị trí</th><th>Cán bộ</th><th>Trạng thái</th><th>Thay thế</th></tr>
<?php foreach($assignments as $a):?><tr><td><?=e($a['position'])?></td><td><?=e($a['full_name'])?></td><td><?=e($a['status'])?></td><td><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="replace"><input type="hidden" name="assignment_id" value="<?=$a['id']?>"><input type="hidden" name="room_id" value="<?=$roomId?>"><input type="hidden" name="subject_id" value="<?=$subjectId?>"><select name="replacement_staff_id" required><?php foreach($staff as $s): if((int)$s['active']!==1 && (int)$s['id']!==(int)$a['staff_id'])continue; if((int)$s['id']===(int)$a['staff_id'])continue;?><option value="<?=$s['id']?>"><?=e($s['full_name'])?></option><?php endforeach;?></select><button type="submit">Thay thế</button></form></td></tr><?php endforeach;?>
<?php if(!$assignments):?><tr><td colspan="4">Chưa có phân công.</td></tr><?php endif;?></table><?php workflow_layout_end(); ?>
