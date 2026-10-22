<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/admission_service.php';
require_once __DIR__.'/../includes/candidate_service.php';
require_permission('enrollment.confirm');
$u=auth_user();$candidate=candidate_find_by_user((int)$u['id']);
if(!$candidate){http_response_code(404);exit('Không tìm thấy hồ sơ.');}
foreach([$_GET,$_POST] as $input)foreach(['candidate_id','id'] as $key)if(isset($input[$key]) && (int)$input[$key] !== (int)$candidate['id']){http_response_code(403);exit('Không có quyền truy cập hồ sơ khác.');}
$pdo=db();$periods=enrollment_periods_for_candidate($pdo,(int)$candidate['id']);
$periodId=(int)($_POST['period_id']??$_GET['period_id']??($periods[0]['id']??0));$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    try{
        if(($_POST['acknowledge']??'')!=='1')throw new RuntimeException('Vui lòng đọc và đánh dấu xác nhận thông tin nhập học.');
        enrollment_confirm($pdo,(int)$candidate['id'],$periodId,(int)$u['id']);
        header('Location: confirm.php?period_id='.$periodId.'&saved=1',true,303);exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}
$ctx=enrollment_context($pdo,(int)$candidate['id'],$periodId);$r=$ctx['result'];$receipt=$ctx['confirmation'];
require_once __DIR__.'/../includes/workflow_layout.php';workflow_layout_start('Xác nhận nhập học','enrollment-confirm');
?>
<h1>Xác nhận nhập học</h1>
<?php if(!isset($_GET['period_id']) && ($ctx['configuration']['settings']['mode']??'')==='simulation'):?><div class="alert alert-warning"><strong>MÔ PHỎNG</strong> — Không phải xác nhận tuyển sinh chính thức.</div><?php endif;?>
<form method="get" class="mb-4"><label for="period">Kỳ tuyển sinh</label><select id="period" name="period_id" class="form-select"><?php foreach($periods as $p):?><option value="<?=$p['id']?>" <?=$periodId===(int)$p['id']?'selected':''?>><?=e($p['name'].' — '.$p['academic_year'])?></option><?php endforeach;?></select><button class="btn btn-outline-primary mt-2">Xem</button></form>
<?php if($error):?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif;?>
<?php if($ctx['error']):?><div class="alert alert-info"><?=e($ctx['error'])?></div><?php endif;?>
<?php if($r):?><div class="card"><div class="card-body">
<h2 class="h5"><?=e($r['school_name'])?></h2><p><?=e($r['full_name'])?> · SBD <?=e($r['exam_number']??'Chưa cấp')?> · Nguyện vọng <?=e((string)$r['priority_order'])?></p>
<p>Thời gian xác nhận: <?=e($ctx['period']['enrollment_start']??'Chưa thiết lập')?> đến <?=e($ctx['period']['enrollment_end']??'Chưa thiết lập')?> (giờ Việt Nam).</p>
<?php if($receipt && !$ctx['error']):?><div class="alert alert-success">Đã ghi nhận xác nhận nhập học lúc <?=e($receipt['confirmed_at'])?>.</div><p>Mã biên nhận: <strong class="text-break"><?=e($receipt['receipt_code']??'Bản ghi trước khi cấp mã biên nhận')?></strong></p><a class="btn btn-primary" href="receipt.php?period_id=<?=$periodId?>">Xem / in biên nhận</a>
<?php elseif($ctx['can_confirm']):?><form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><div class="form-check mb-3"><input class="form-check-input" id="ack" type="checkbox" name="acknowledge" value="1" required><label class="form-check-label" for="ack">Tôi đã kiểm tra đúng trường trúng tuyển và đồng ý xác nhận nhập học.</label></div><button class="btn btn-primary">Xác nhận nhập học</button></form><?php endif;?></div></div><?php endif;?>
<?php workflow_layout_end(); ?>
