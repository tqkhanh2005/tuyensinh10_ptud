<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/admission_service.php';
require_permission('admission.manage');
$pdo=db();
$periods=$pdo->query('SELECT * FROM admission_periods ORDER BY academic_year DESC,id DESC')->fetchAll();
$periodId=(int)($_POST['period_id']??$_GET['period_id']??($periods[0]['id']??0));
$error='';$success='';$run=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    try{$runId=admission_calculate($pdo,$periodId,(int)auth_user()['id']);$run=latest_admission_run($pdo,$periodId);$success='Xét tuyển thành công.';}
    catch(Throwable $e){$error=$e->getMessage();$run=latest_admission_run($pdo,$periodId);}
}else{$run=latest_admission_run($pdo,$periodId);}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Xét tuyển thí sinh</title></head><body>
<h1>Xét tuyển thí sinh</h1>
<?php if($error):?><p><?=e($error)?></p><?php endif;?><?php if($success):?><p><?=e($success)?></p><?php endif;?>
<form method="post"><?=csrf_field()?><label>Kỳ tuyển sinh <select name="period_id"><?php foreach($periods as $p):?><option value="<?=$p['id']?>" <?=$p['id']==$periodId?'selected':''?>><?=e($p['name'].' - '.$p['academic_year'])?></option><?php endforeach;?></select></label><button type="submit">Thực hiện xét tuyển</button></form>
<?php if($run):?><p>Run #<?=e((string)$run['id'])?> — trạng thái: <?=e($run['status'])?></p><?php $rows=admission_results($pdo,(int)$run['id']);?><table border="1" cellpadding="6"><tr><th>Thí sinh</th><th>Nguyện vọng</th><th>Trường</th><th>Tổng điểm</th><th>Ưu tiên</th><th>Điểm xét tuyển</th><th>Kết quả</th></tr><?php foreach($rows as $r):?><tr><td><?=e($r['candidate_code'].' - '.$r['full_name'])?></td><td><?=e((string)$r['priority_order'])?></td><td><?=e(($r['school_code']??'').' - '.($r['school_name']??''))?></td><td><?=e((string)$r['score_total'])?></td><td><?=e((string)$r['priority_points'])?></td><td><?=e((string)$r['admission_score'])?></td><td><?=e($r['result_status'])?></td></tr><?php endforeach;?></table><?php endif;?></body></html>
