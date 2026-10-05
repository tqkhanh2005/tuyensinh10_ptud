<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/candidate_service.php';
require_once __DIR__.'/../includes/admission_service.php';
require_permission('enrollment.confirm');
$u=auth_user();$candidate=candidate_find_by_user((int)$u['id']);
if(!$candidate){http_response_code(404);exit('Không tìm thấy thông tin hồ sơ.');}
$pdo=db();$periods=$pdo->query('SELECT * FROM admission_periods ORDER BY academic_year DESC,id DESC')->fetchAll();
$periodId=(int)($_POST['period_id']??$_GET['period_id']??($periods[0]['id']??0));$error='';$success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    try{enrollment_confirm($pdo,(int)$candidate['id'],$periodId);$success='Xác nhận nhập học thành công.';}
    catch(Throwable $e){$error=$e->getMessage();}
}
$rows=$periodId?candidate_admission_result($pdo,(int)$candidate['id'],$periodId):[];
$admitted=null;foreach($rows as $r)if($r['result_status']==='admitted'){$admitted=$r;break;}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Xác nhận nhập học</title></head><body><h1>Xác nhận nhập học</h1>
<?php if($error):?><p><?=e($error)?></p><?php endif;?><?php if($success):?><p><?=e($success)?></p><?php endif;?>
<form method="post"><?=csrf_field()?><select name="period_id"><?php foreach($periods as $p):?><option value="<?=$p['id']?>" <?=$p['id']==$periodId?'selected':''?>><?=e($p['name'].' - '.$p['academic_year'])?></option><?php endforeach;?></select><button type="submit" <?=$admitted?'':'disabled'?>>Xác nhận nhập học</button></form>
<?php if($admitted):?><p>Trúng tuyển: <?=e(($admitted['school_code']??'').' - '.($admitted['school_name']??''))?></p><p>Trạng thái: <?=e($admitted['enrollment_status'])?></p><?php else:?><p>Không tìm thấy kết quả trúng tuyển để xác nhận.</p><?php endif;?></body></html>
