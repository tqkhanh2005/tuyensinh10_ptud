<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/council_service.php';
require_permission('council.manage');
function council_form_text($value): string {return is_scalar($value)?trim((string)$value):'';}
$pdo=db();$periodId=(int)council_form_text($_POST['admission_period_id']??$_GET['period_id']??0);
$error='';$data=['council_type'=>'invigilation','name'=>'','exam_site_id'=>'','exam_subject_ids'=>[],'start_at'=>'','end_at'=>'','location'=>'','members'=>[]];
$staff=council_staff($pdo);$sites=council_sites($pdo,$periodId);$subjects=council_subjects($pdo,$periodId);
if($_SERVER['REQUEST_METHOD']==='POST'){
 require_csrf();
 $data=['council_type'=>council_form_text($_POST['council_type']??''),'name'=>council_form_text($_POST['name']??''),'exam_site_id'=>council_form_text($_POST['exam_site_id']??''),'exam_subject_ids'=>array_values(array_filter(array_map(static fn($value)=>(int)council_form_text($value),(array)($_POST['exam_subject_ids']??[])))),'start_at'=>council_form_text($_POST['start_at']??''),'end_at'=>council_form_text($_POST['end_at']??''),'location'=>council_form_text($_POST['location']??''),'members'=>[]];
 $staffIds=(array)($_POST['staff_id']??[]);$roles=(array)($_POST['role_name']??[]);$tasks=(array)($_POST['task_name']??[]);
 foreach($staffIds as $i=>$sid)$data['members'][]=['staff_id'=>(int)council_form_text($sid),'role_name'=>council_form_text($roles[$i]??''),'task_name'=>council_form_text($tasks[$i]??'')];
 try{
     $createdId=council_create($pdo,array_merge($data,['admission_period_id'=>$periodId]));
     $_SESSION['council_created']=$createdId;
     header('Location: '.url('/councils/index.php?period_id='.$periodId.'&created=1'));
     exit;
 }catch(InvalidArgumentException $e){
     $error=$e->getMessage();
 }catch(PDOException $e){
     error_log('Council create error: '.$e->getMessage());
     $error=(int)($e->errorInfo[1]??0)===1062?'Tên hội đồng đã tồn tại trong kỳ tuyển sinh này. Vui lòng chọn tên khác.':'Không thể lưu hội đồng. Dữ liệu chưa thay đổi; vui lòng thử lại.';
 }catch(Throwable $e){
     error_log('Council create error: ' . $e->getMessage());
     $error='Không thể thành lập hội đồng thi. Vui lòng kiểm tra lại thông tin hoặc thử lại sau.';
 }
}
require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Thành lập hội đồng';
$activePage = 'councils';

admin_layout_start('Thành lập hội đồng', 'councils', ['Quản lý kỳ thi' => url('/councils/index.php'), 'Thành lập hội đồng' => '']);
?>
<link rel="stylesheet" href="<?=e(url('/councils/councils.css'))?>">
<div class="row justify-content-center"><div class="col-xxl-10"><div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
<h1 class="h3 fw-bold mb-1">Thành lập hội đồng</h1><p class="text-muted">Kỳ tuyển sinh #<?=e((string)$periodId)?>. Thông tin và phân công thành viên được lưu đồng thời.</p>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<form method="post" id="council-form"><?=csrf_field()?><input type="hidden" name="admission_period_id" value="<?=e((string)$periodId)?>">
<div class="row g-3"><div class="col-md-4"><label class="form-label">Loại hội đồng *</label><select class="form-select" name="council_type" id="council_type" required><option value="invigilation" <?=$data['council_type']==='invigilation'?'selected':''?>>Hội đồng coi thi</option><option value="grading" <?=$data['council_type']==='grading'?'selected':''?>>Hội đồng chấm thi</option><option value="appeal" <?=$data['council_type']==='appeal'?'selected':''?>>Hội đồng chấm thi phúc khảo</option><option value="question" <?=$data['council_type']==='question'?'selected':''?>>Hội đồng ra đề</option></select></div>
<div class="col-md-8"><label class="form-label">Tên hội đồng *</label><input class="form-control" name="name" maxlength="200" value="<?=e($data['name'])?>" required></div>
<div class="col-md-6" id="site_wrap"><label class="form-label">Điểm thi phụ trách</label><select class="form-select" name="exam_site_id"><option value="">-- Chọn điểm thi --</option><?php foreach($sites as $s):?><option value="<?=$s['id']?>" <?=$data['exam_site_id']==$s['id']?'selected':''?>><?=e($s['site_code'].' - '.$s['name'])?></option><?php endforeach;?></select></div>
<div class="col-md-6" id="subject_wrap"><label class="form-label" for="council-subjects">Môn thi phụ trách</label>
<?php if(!$subjects):?><div class="alert alert-warning mb-2" role="status">Kỳ tuyển sinh #<?=$periodId?> chưa có môn thi. Hãy thiết lập môn/lịch thi của đúng kỳ trước khi thành lập hội đồng ra đề, chấm thi hoặc phúc khảo. <a href="<?=e(url('/exam/organization.php?period_id='.$periodId))?>">Mở thiết lập môn và lịch thi</a></div><?php endif;?><select id="council-subjects" class="form-select" data-has-subjects="<?= $subjects ? '1' : '0' ?>" name="exam_subject_ids[]" multiple size="4"><option value=""><?= $subjects ? '-- Chọn môn thi --' : 'Chưa có môn thi trong kỳ này' ?></option><?php foreach($subjects as $s):?><option value="<?=$s['id']?>" <?=in_array((int)$s['id'],$data['exam_subject_ids'],true)?'selected':''?>><?=e($s['subject_code'].' - '.$s['name'])?></option><?php endforeach;?></select><div class="form-text">Hội đồng ra đề/phúc khảo chọn đúng một môn; hội đồng chấm thi có thể chọn nhiều môn.</div></div>
<div class="col-md-4"><label class="form-label">Thời gian bắt đầu</label><input class="form-control" type="datetime-local" name="start_at" value="<?=e($data['start_at'])?>"></div><div class="col-md-4"><label class="form-label">Thời gian kết thúc</label><input class="form-control" type="datetime-local" name="end_at" value="<?=e($data['end_at'])?>"></div><div class="col-md-4"><label class="form-label">Địa điểm</label><input class="form-control" name="location" maxlength="255" value="<?=e($data['location'])?>"></div></div>
<hr class="my-4"><h2 class="h5 fw-bold">Thành viên</h2><p class="text-muted small">Chỉ cán bộ đang hoạt động mới được chọn. Hãy thêm thành viên và phân công vai trò phù hợp; không chọn trùng người.</p>
<div id="members"></div><button type="button" class="btn btn-outline-primary mt-2" id="add_member">+ Thêm thành viên</button>
<div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit">Xác nhận thành lập</button><a class="btn btn-outline-secondary" href="<?=e(url('/councils/index.php?period_id='.$periodId))?>">Hủy</a></div>
</form></div></div></div>
<script type="application/json" id="council-staff"><?=json_encode(array_map(fn($s)=>['id'=>(int)$s['id'],'name'=>$s['full_name'].' · '.$s['workplace'],'active'=>(int)$s['active']],$staff),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?></script>
<script type="application/json" id="council-members"><?=json_encode($data['members'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?></script>
<script src="<?=e(url('/councils/form.js'))?>" defer></script>
<?php admin_layout_end(); ?>
