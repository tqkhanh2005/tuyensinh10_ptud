<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/audit_labels.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/school_admission_service.php';
require_once __DIR__.'/../includes/admin_layout.php';
require_permission('admission.manage');
$pdo=db(); $id=max(0,(int)($_GET['id'] ?? $_POST['id'] ?? 0)); $error='';
$period=$id ? admission_period_find($pdo,$id) : null;
if ($id && !$period) { http_response_code(404); exit('Không tìm thấy kỳ tuyển sinh.'); }
$cfg=$id ? period_configuration($pdo,$id) : null;
if($id && !$cfg) { http_response_code(409); exit('Kỳ cũ chưa có cấu hình theo năm. Vui lòng tạo kế hoạch mới ở trạng thái bản nháp.'); }
$data=$cfg['settings'] ?? ['name'=>'','academic_year'=>'2026-2027','mode'=>'simulation'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf(); $action=(string)($_POST['action'] ?? 'save');
    try {
        $actor=(int)auth_user()['user_id']; $revision=(int)($_POST['revision'] ?? 0);
        if ($action==='save') { $data=$_POST; $id=period_configuration_save($pdo,$id,$data,$actor,$revision); }
        elseif(in_array($action,['published','closed'],true)) period_configuration_transition($pdo,$id,$action,$actor,$revision);
        else throw new InvalidArgumentException('Thao tác không hợp lệ.');
        header('Location: '.url('/admission/manage.php?id='.$id.'&saved=1'));exit;
    } catch(Throwable $e) {
        $error=$e instanceof PDOException ? 'Không thể lưu cấu hình. Vui lòng thử lại.' : $e->getMessage();
        if($e instanceof PDOException) error_log('Period configuration: '.$e->getMessage());
    }
}
$status=$period['status'] ?? 'draft'; $locked=$status!=='draft'; $policy=admission_policy();
admin_layout_start('Thiết lập kế hoạch tuyển sinh','admission',['Xét tuyển'=>url('/admission/index.php'),'Thiết lập kế hoạch'=>'']);
?>
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><h1 class="h3">Thiết lập kế hoạch tuyển sinh</h1><p class="text-muted">TP.HCM · Lớp 10 thường THPT công lập · Múi giờ Việt Nam (UTC+7)</p></div><a class="btn btn-outline-secondary align-self-start" href="<?=e(url('/admission/index.php'))?>">Danh sách kỳ</a></div>
<?php if($error):?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="alert alert-success">Đã lưu thay đổi.</div><?php endif;?>
<div class="alert <?=($data['mode'] ?? 'simulation')==='simulation'?'alert-warning':'alert-info'?>"><strong><?=($data['mode'] ?? 'simulation')==='simulation'?'MÔ PHỎNG — không phải thông báo tuyển sinh chính thức':'CHÍNH THỨC — cần đối chiếu văn bản của kỳ áp dụng'?></strong><br>Trạng thái: <?=e(['draft'=>'Bản nháp','published'=>'Đã công bố','closed'=>'Đã đóng'][$status])?>. Sau công bố, cấu hình và chỉ tiêu được khóa; đóng kỳ sẽ dừng đăng ký, điều chỉnh, phúc khảo và xác nhận nhập học.</div>
<?php if($id):?><p><a class="btn btn-primary" href="<?=e(url('/exam/organization.php?period_id='.$id))?>">Tổ chức thi</a></p><?php endif;?>
<div class="card p-3 mb-3"><h2 class="h5">Quy tắc tham chiếu 2026–2027</h2><p class="mb-1"><?=e($policy['scope'])?>.</p><p class="mb-1">Tối đa 3 nguyện vọng; <?=e($policy['formula'])?>. Ba môn hệ số 1, điểm cộng không quá 3,5; dự thi đủ môn và mọi điểm môn phải lớn hơn 0.</p><p class="mb-1"><?=e($policy['tie_note'])?></p><a href="<?=e(url('/admission/rules.php'))?>">Xem quy tắc và nguồn văn bản</a></div>
<form method="post" action="<?=e(url('/admission/manage.php'.($id?'?id='.$id:'')))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="revision" value="<?=$cfg['revision'] ?? 0?>"><input type="hidden" name="action" value="save">
<fieldset <?=$locked?'disabled':''?>>
<div class="card p-3 p-md-4 mb-3"><h2 class="h5">1. Thông tin kế hoạch</h2><div class="row g-3">
<?php foreach(['name'=>'Tên kế hoạch','academic_year'=>'Năm học'] as $field=>$label):?><div class="col-md-6"><label class="form-label" for="<?=$field?>"><?=e($label)?> *</label><input id="<?=$field?>" name="<?=$field?>" class="form-control" required value="<?=e((string)($data[$field] ?? ''))?>"></div><?php endforeach;?>
<div class="col-md-6"><label class="form-label" for="mode">Chế độ</label><select name="mode" id="mode" class="form-select"><option value="simulation" <?=($data['mode'] ?? '')==='simulation'?'selected':''?>>Mô phỏng</option><option value="official" <?=($data['mode'] ?? '')==='official'?'selected':''?>>Chính thức</option></select></div>
<div class="col-12"><label class="form-label" for="plan_content">Nội dung kế hoạch</label><textarea class="form-control" id="plan_content" name="plan_content" rows="3"><?=e((string)($data['plan_content'] ?? ''))?></textarea></div></div></div>
<div class="card p-3 p-md-4 mb-3"><h2 class="h5">2. Thời hạn</h2><p class="small text-muted">Bản nháp được để trống. Công bố cần đủ bốn giai đoạn. Giai đoạn điều chỉnh chỉ cho sửa nguyện vọng đã đăng ký, không nhận đăng ký mới.</p>
<?php foreach(['registration'=>'Đăng ký ban đầu','adjustment'=>'Điều chỉnh nguyện vọng','appeal'=>'Tiếp nhận phúc khảo','enrollment'=>'Xác nhận nhập học'] as $stage=>$label):?><div class="row g-3 mb-3"><div class="col-12 fw-semibold"><?=e($label)?></div><?php foreach(['start'=>'Bắt đầu','end'=>'Kết thúc'] as $edge=>$edgeLabel):$field=$stage.'_'.$edge;?><div class="col-md-6"><label class="form-label" for="<?=$field?>"><?=e($edgeLabel)?></label><input class="form-control" id="<?=$field?>" name="<?=$field?>" type="datetime-local" value="<?=e(str_replace(' ','T',substr((string)($data[$field] ?? ''),0,16)))?>"></div><?php endforeach;?></div><?php endforeach;?></div>
<div class="card p-3 p-md-4 mb-3"><h2 class="h5">3. Căn cứ và nguồn dữ liệu</h2><p class="small text-muted">Bắt buộc khi công bố kỳ chính thức; mô phỏng không được hiển thị chỉ tiêu trên cổng công khai.</p>
<?php foreach(['source_url'=>'Kế hoạch / quy định áp dụng','schedule_source_url'=>'Lịch đăng ký và điều chỉnh','quota_source_url'=>'Quyết định chỉ tiêu','appeal_source_url'=>'Thời hạn phúc khảo','enrollment_source_url'=>'Thời hạn nhập học'] as $field=>$label):?><div class="mb-3"><label class="form-label" for="<?=$field?>"><?=e($label)?></label><input class="form-control" type="url" id="<?=$field?>" name="<?=$field?>" placeholder="https://..." value="<?=e((string)($data[$field] ?? ''))?>"></div><?php endforeach;?>
<label class="form-check"><input class="form-check-input" type="checkbox" name="reviewed" value="1" <?=!empty($data['reviewed'])?'checked':''?>><span class="form-check-label">Đã đối chiếu văn bản, năm áp dụng, thời hạn và chỉ tiêu.</span></label></div>
<button class="btn btn-primary mb-3" type="submit">Lưu bản nháp</button></fieldset></form>
<?php if($id):?><div class="card p-3 mb-3"><h2 class="h5">4. Chỉ tiêu và trạng thái</h2><a class="mb-3" href="<?=e(url('/quotas/index.php?period_id='.$id))?>">Thiết lập chỉ tiêu từng trường THPT</a>
<?php if($status==='draft'):foreach(period_publication_errors($pdo,$id,$cfg['settings']) as $issue):?><div class="small text-danger mb-1"><?=e($issue)?></div><?php endforeach;endif;?>
<?php if(in_array($status,['draft','published'],true)):?><form method="post"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="revision" value="<?=$cfg['revision'] ?? 0?>"><button class="btn <?=$status==='draft'?'btn-success':'btn-outline-danger'?> mt-2" name="action" value="<?=$status==='draft'?'published':'closed'?>"><?=$status==='draft'?'Công bố kỳ tuyển sinh':'Đóng kỳ tuyển sinh'?></button></form><?php endif;?></div>
<details class="card p-3"><summary>Lịch sử thiết lập</summary><?php $q=$pdo->prepare('SELECT e.action,e.created_at,u.full_name FROM admission_period_events e LEFT JOIN users u ON u.id=e.actor_user_id WHERE admission_period_id=? ORDER BY e.id DESC LIMIT 30');$q->execute([$id]);foreach($q as $event):?><div class="small py-1"><?=e($event['created_at'].' · '.($event['full_name'] ?? 'Hệ thống').' · '.period_event_label($event['action']))?></div><?php endforeach;?></details><?php endif;?>
<?php admin_layout_end(); ?>
