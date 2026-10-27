<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php'; require_once __DIR__.'/../includes/publication_service.php';
require_permission('publication.manage');
$pdo=db(); $periods=publication_periods($pdo); $message=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!csrf_verify($_POST['csrf']??null)){http_response_code(419);exit('CSRF token không hợp lệ.');}
  $pid=(int)($_POST['period_id']??0);$type=$_POST['type']??'';
  try{if($type==='exam'){header('Location: '.url('/publication/exam.php?period_id='.$pid));exit;}elseif($type==='admission'){header('Location: '.url('/publication/admission.php?period_id='.$pid));exit;}else throw new RuntimeException('Loại công bố không hợp lệ.');$message='Công bố thành công.';}catch(Throwable $e){$error=$e->getMessage();}
}
?><?php require_once __DIR__ . '/../includes/workflow_layout.php'; workflow_layout_start('Công bố kết quả', 'publication'); ?>
<h1>Công bố kết quả</h1><?php if($message):?><p><?=e($message)?></p><?php endif;?><?php if($error):?><p><?=e($error)?></p><?php endif;?>
<table border="1" cellpadding="8"><tr><th>Kỳ tuyển sinh</th><th>Công bố điểm thi</th><th>Công bố KQ tuyển sinh</th></tr><?php foreach($periods as $p):$cfg=period_configuration($pdo,(int)$p['id']);$simulation=$cfg && $cfg['settings']['mode']==='simulation';$latest=$pdo->prepare('SELECT id,status FROM admission_runs WHERE admission_period_id=? ORDER BY id DESC LIMIT 1');$latest->execute([$p['id']]);$latest=$latest->fetch();$snap=$latest && selection_schema($pdo)?selection_snapshot($pdo,(int)$latest['id']):null;?><tr><td><?=e($p['name'].' - '.$p['academic_year'])?><?php if($simulation):?><p><strong>MÔ PHỎNG — không phải kết quả chính thức</strong></p><?php endif;?><?php if($cfg):?><p><a href="<?=e(url('/admission/calculate.php?period_id='.$p['id']))?>">Xét tuyển và duyệt kết quả</a></p><?php endif;?></td><td><?php if(publication_exists($pdo,(int)$p['id'],'exam_results')):?>Đã công bố · <a href="<?=e(url('/publication/exam.php?period_id='.$p['id']))?>">Xem điểm</a><?php else:?><a href="<?=e(url('/publication/exam.php?period_id='.$p['id']))?>">Xem điểm và công bố</a><?php endif;?></td><td><?php if(publication_exists($pdo,(int)$p['id'],'admission_results')):?>Đã công bố · <?php endif;?><a href="<?=e(url('/publication/admission.php?period_id='.$p['id']))?>">Xem kết quả và công bố</a></td></tr><?php endforeach;?></table><?php workflow_layout_end(); ?>
