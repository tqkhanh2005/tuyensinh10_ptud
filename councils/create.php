<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/council_service.php';
require_permission('council.manage');
$pdo=db();$periodId=(int)($_GET['period_id']??$_POST['admission_period_id']??0);
$error='';$data=['council_type'=>'invigilation','name'=>'','exam_site_id'=>'','exam_subject_ids'=>[],'start_at'=>'','end_at'=>'','location'=>'','members'=>[]];
$staff=council_staff($pdo);$sites=council_sites($pdo,$periodId);$subjects=council_subjects($pdo,$periodId);
if($_SERVER['REQUEST_METHOD']==='POST'){
 require_csrf();
 $data=['council_type'=>(string)($_POST['council_type']??''),'name'=>trim((string)($_POST['name']??'')),'exam_site_id'=>(string)($_POST['exam_site_id']??''),'exam_subject_ids'=>array_values(array_filter(array_map('intval',(array)($_POST['exam_subject_ids']??[])))),'start_at'=>(string)($_POST['start_at']??''),'end_at'=>(string)($_POST['end_at']??''),'location'=>trim((string)($_POST['location']??'')),'members'=>[]];
 $staffIds=(array)($_POST['staff_id']??[]);$roles=(array)($_POST['role_name']??[]);$tasks=(array)($_POST['task_name']??[]);
 foreach($staffIds as $i=>$sid)$data['members'][]=['staff_id'=>(int)$sid,'role_name'=>trim((string)($roles[$i]??'')),'task_name'=>trim((string)($tasks[$i]??''))];
 try{
     council_create($pdo,array_merge($data,['admission_period_id'=>$periodId]));
     header('Location: '.url('/councils/index.php?period_id='.$periodId.'&created=1'));
     exit;
 }catch(InvalidArgumentException $e){
     $error=$e->getMessage();
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
<div class="row justify-content-center"><div class="col-xxl-10"><div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
<h1 class="h3 fw-bold mb-1">Thành lập hội đồng</h1><p class="text-muted">Kỳ tuyển sinh #<?=e((string)$periodId)?>. Hội đồng được lưu trong một transaction.</p>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<form method="post"><?=csrf_field()?><input type="hidden" name="admission_period_id" value="<?=e((string)$periodId)?>">
<div class="row g-3"><div class="col-md-4"><label class="form-label">Loại hội đồng *</label><select class="form-select" name="council_type" id="council_type" required><option value="invigilation" <?=$data['council_type']==='invigilation'?'selected':''?>>Hội đồng coi thi</option><option value="grading" <?=$data['council_type']==='grading'?'selected':''?>>Hội đồng chấm thi</option><option value="appeal" <?=$data['council_type']==='appeal'?'selected':''?>>Hội đồng chấm thi phúc khảo</option><option value="question" <?=$data['council_type']==='question'?'selected':''?>>Hội đồng ra đề</option></select></div>
<div class="col-md-8"><label class="form-label">Tên hội đồng *</label><input class="form-control" name="name" value="<?=e($data['name'])?>" required></div>
<div class="col-md-6" id="site_wrap"><label class="form-label">Điểm thi phụ trách</label><select class="form-select" name="exam_site_id"><option value="">-- Chọn điểm thi --</option><?php foreach($sites as $s):?><option value="<?=$s['id']?>" <?=$data['exam_site_id']==$s['id']?'selected':''?>><?=e($s['site_code'].' - '.$s['name'])?></option><?php endforeach;?></select></div>
<div class="col-md-6" id="subject_wrap"><label class="form-label">Môn thi phụ trách</label><select class="form-select" name="exam_subject_ids[]" multiple size="4"><?php foreach($subjects as $s):?><option value="<?=$s['id']?>" <?=in_array((int)$s['id'],$data['exam_subject_ids'],true)?'selected':''?>><?=e($s['subject_code'].' - '.$s['name'])?></option><?php endforeach;?></select><div class="form-text">Có thể chọn nhiều môn đối với hội đồng chấm thi.</div></div>
<div class="col-md-4"><label class="form-label">Thời gian bắt đầu</label><input class="form-control" type="datetime-local" name="start_at" value="<?=e($data['start_at'])?>"></div><div class="col-md-4"><label class="form-label">Thời gian kết thúc</label><input class="form-control" type="datetime-local" name="end_at" value="<?=e($data['end_at'])?>"></div><div class="col-md-4"><label class="form-label">Địa điểm</label><input class="form-control" name="location" value="<?=e($data['location'])?>"></div></div>
<hr class="my-4"><h2 class="h5 fw-bold">Thành viên</h2><p class="text-muted small">Chỉ cán bộ đang hoạt động mới được chọn. Quy tắc số lượng tối thiểu thành viên chưa được đặc tả.</p>
<div id="members"></div><button type="button" class="btn btn-outline-primary mt-2" id="add_member">+ Thêm thành viên</button>
<div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit">Xác nhận thành lập</button><a class="btn btn-outline-secondary" href="<?=e(url('/councils/index.php?period_id='.$periodId))?>">Hủy</a></div>
</form></div></div></div>
<script>
const staff=<?=json_encode(array_map(fn($s)=>['id'=>(int)$s['id'],'name'=>$s['full_name'],'active'=>(int)$s['active']],$staff),JSON_UNESCAPED_UNICODE)?>;
const box=document.getElementById('members');const type=document.getElementById('council_type');
function addMember(row={}){const d=document.createElement('div');d.className='row g-2 align-items-end mb-2 member-row';let opts='<option value="">-- Chọn cán bộ --</option>';staff.filter(s=>s.active===1).forEach(s=>opts+=`<option value="${s.id}" ${String(row.staff_id||'')===String(s.id)?'selected':''}>${s.name}</option>`);d.innerHTML=`<div class="col-md-4"><label class="form-label">Cán bộ</label><select class="form-select" name="staff_id[]" required>${opts}</select></div><div class="col-md-3 role"><label class="form-label">Vai trò</label><input class="form-control" name="role_name[]" value="${row.role_name||''}"></div><div class="col-md-4"><label class="form-label">Nhiệm vụ</label><input class="form-control" name="task_name[]" value="${row.task_name||''}"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove">Xóa</button></div>`;d.querySelector('.remove').onclick=()=>d.remove();box.appendChild(d);}
function sync(){document.getElementById('site_wrap').style.display=type.value==='invigilation'?'block':'none';document.getElementById('subject_wrap').style.display=type.value==='invigilation'?'none':'block';document.querySelectorAll('.member-row .role').forEach(x=>x.style.display=type.value==='question'?'block':'block');}
document.getElementById('add_member').onclick=()=>addMember();addMember();type.onchange=sync;sync();
</script>
<?php
admin_layout_end();
