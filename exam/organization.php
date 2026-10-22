<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/exam_service.php';
require_once __DIR__.'/../includes/workflow_layout.php';
require_permission('exam.manage');
$pdo=db();$periods=$pdo->query('SELECT id,name,status FROM admission_periods ORDER BY id DESC')->fetchAll();
$periodId=(int)($_POST['period_id'] ?? $_GET['period_id'] ?? ($periods[0]['id'] ?? 0));
$period=null;foreach($periods as $item)if((int)$item['id']===$periodId)$period=$item;
if(!$period){http_response_code(404);exit('Chưa có kỳ tuyển sinh phù hợp.');}
$cfg=period_configuration($pdo,$periodId);
if(!$cfg){http_response_code(409);exit('Kỳ cũ chưa có kế hoạch theo năm. Hãy tạo kế hoạch mới để tổ chức thi.');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    try {
        $action=(string)($_POST['action'] ?? '');$actor=(int)auth_user()['id'];
        if($action==='schedule')exam_schedule_save($pdo,$periodId,$_POST,$actor);
        elseif($action==='allocate'){require_permission('exam.allocate');exam_organization_allocate_all($pdo,$periodId);}
        elseif($action==='number')exam_number_generate($pdo,$periodId,trim((string)($_POST['prefix'] ?? '')));
        elseif($action==='release')exam_cards_release($pdo,$periodId,$actor);
        else throw new InvalidArgumentException('Thao tác không hợp lệ.');
        header('Location: '.url('/exam/organization.php?period_id='.$periodId.'&saved=1'));exit;
    }catch(Throwable $e){$error=$e instanceof PDOException?'Không thể lưu thay đổi. Kiểm tra mã trùng và dữ liệu liên quan.':$e->getMessage();if($e instanceof PDOException)error_log($e->getMessage());}
}
$ready=exam_organization_readiness($pdo,$periodId);$state=exam_organization_state($pdo,$periodId);
$locked=!empty($state['released_at']) || $period['status']==='closed';
$roster=exam_roster($pdo,$periodId);$pages=max(1,(int)ceil(count($roster)/50));$page=min($pages,max(1,(int)($_GET['page'] ?? 1)));
workflow_layout_start('Tổ chức thi','councils');
?>
<h1 class="h3">Tổ chức thi tuyển sinh lớp 10 · TP.HCM</h1>
<form method="get" class="mb-3"><label class="form-label" for="period">Kỳ tuyển sinh</label><select id="period" class="form-select" name="period_id" onchange="this.form.submit()"><?php foreach($periods as $p):?><option value="<?=$p['id']?>" <?=$p['id']==$periodId?'selected':''?>><?=e($p['name'])?></option><?php endforeach;?></select><noscript><button>Xem</button></noscript></form>
<?php if(!isset($_GET['period_id']) && $cfg['settings']['mode']==='simulation'):?><div class="alert alert-warning">MÔ PHỎNG — Lịch và SBD ở đây phục vụ dự án, không phải thông báo tuyển sinh chính thức.</div><?php endif;?>
<?php if($error):?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['saved'])):?><div class="alert alert-success">Đã lưu thay đổi.</div><?php endif;?>
<p class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="<?=e(url('/exam/grading.php?period_id='.$periodId))?>">Chấm thi và phúc khảo</a><a class="btn btn-outline-primary" href="<?=e(url('/admission/manage.php?id='.$periodId))?>">Kế hoạch tuyển sinh</a><a class="btn btn-outline-primary" href="<?=e(url('/exam/sites.php?period_id='.$periodId))?>">Điểm thi và phòng thi</a><a class="btn btn-outline-primary" href="<?=e(url('/councils/index.php?period_id='.$periodId))?>">Hội đồng coi thi</a><a class="btn btn-outline-secondary" href="<?=e(url('/exam/roster.php?period_id='.$periodId))?>">Xuất danh sách CSV</a></p>
<div class="row g-3 mb-3"><?php foreach(['eligible'=>'Học sinh hợp lệ','sites'=>'Điểm thi','rooms'=>'Phòng thi','allocated'=>'Đã phân điểm','seated'=>'Đã xếp phòng','numbered'=>'Đã cấp SBD'] as $key=>$label):?><div class="col-6 col-lg-2"><div class="card p-3 h-100"><span class="small"><?=e($label)?></span><strong class="fs-4"><?=$ready[$key]?></strong></div></div><?php endforeach;?></div>
<?php if($locked):?><div class="alert alert-info">Đã khóa cấu hình tổ chức thi<?=!empty($state['released_at'])?' sau phát hành phiếu lúc '.e($state['released_at']):' vì kỳ đã đóng'?>.</div><?php endif;?>
<div class="card p-3 mb-3"><h2 class="h5">1. Lịch thi</h2><p>Nhập ngày theo kế hoạch của kỳ. Thời lượng: Ngữ văn 120 phút, Toán 120 phút, Ngoại ngữ 90 phút.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Môn</th><th>Ngày</th><th>Bắt đầu</th><th>Kết thúc</th></tr></thead><tbody><?php foreach($ready['schedule'] as $s):?><tr><td><?=e($s['name'])?></td><td><?=e($s['exam_date'] ?? '')?></td><td><?=e($s['start_time'] ?? '')?></td><td><?=e($s['end_time'] ?? '')?></td></tr><?php endforeach;?><?php if(!$ready['schedule']):?><tr><td colspan="4">Chưa thiết lập lịch thi.</td></tr><?php endif;?></tbody></table></div>
<form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><input type="hidden" name="action" value="schedule"><fieldset <?=$locked?'disabled':''?> class="row g-2"><div class="col-md-3"><label class="form-label">Môn<select class="form-select" name="subject_code"><option value="VAN">Ngữ văn</option><option value="TOAN">Toán</option><option value="NN">Ngoại ngữ</option></select></label></div><div class="col-md-3"><label class="form-label">Ngày thi<input class="form-control" type="date" name="exam_date" required></label></div><div class="col-md-3"><label class="form-label">Bắt đầu<input class="form-control" type="time" name="start_time" required></label></div><div class="col-md-3 align-self-end"><button class="btn btn-primary mb-2">Lưu lịch môn</button></div></fieldset></form></div>
<div class="card p-3 mb-3"><h2 class="h5">2. Xếp điểm, phòng và cấp SBD</h2><p>Thiết lập đủ sức chứa trước khi xếp tự động. Hệ thống giữ các phân bố đã có; nếu thiếu chỗ, toàn bộ lần xếp mới được hoàn tác. SBD được lưu riêng theo kỳ và giữ nguyên khi chạy lại.</p>
<div class="d-flex flex-wrap gap-3"><form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><button class="btn btn-primary" name="action" value="allocate" <?=$locked?'disabled':''?>>Xếp điểm và phòng tự động</button></form>
<form method="post" class="d-flex flex-wrap gap-2 align-items-center"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><label for="prefix">Tiền tố SBD nội bộ</label><input id="prefix" class="form-control w-auto" name="prefix" maxlength="10" pattern="[A-Z0-9]{1,10}" value="<?=e($state['number_prefix'] ?? 'HCM26')?>" required <?=$locked?'disabled':''?>><button class="btn btn-primary" name="action" value="number" <?=$locked?'disabled':''?>>Cấp SBD</button></form></div></div>
<div class="card p-3 mb-3"><h2 class="h5">3. Phân công coi thi</h2><p>Mỗi phòng có học sinh cần hai giám thị thuộc hội đồng của điểm thi cho từng môn; hệ thống chặn lịch trùng.</p>
<?php foreach(exam_sites($pdo,$periodId) as $site):?><details class="mb-2"><summary><?=e($site['name'])?></summary><div class="table-responsive"><table class="table"><thead><tr><th>Phòng</th><th>Môn thi</th><th>Thao tác</th></tr></thead><tbody><?php foreach(exam_rooms($pdo,(int)$site['id']) as $room):foreach($ready['schedule'] as $s):?><tr><td><?=e($room['name'])?></td><td><?=e($s['name'])?></td><td><a href="<?=e(url('/exam/invigilators.php?room_id='.$room['id'].'&subject_id='.$s['id'].'&period_id='.$periodId))?>">Xem / phân công</a></td></tr><?php endforeach;endforeach;?></tbody></table></div></details><?php endforeach;?></div>
<div class="card p-3 mb-3"><h2 class="h5">4. Rà soát và phát hành phiếu</h2><?php if($ready['errors']):?><ul><?php foreach($ready['errors'] as $issue):?><li><?=e($issue)?></li><?php endforeach;?></ul><?php else:?><p class="text-success">Đã đủ các điều kiện tổ chức thi.</p><?php endif;?>
<p>Sau phát hành, học sinh xem và in phiếu trong tài khoản của mình; cấu hình tổ chức thi được khóa.</p><form method="post"><?=csrf_field()?><input type="hidden" name="period_id" value="<?=$periodId?>"><button class="btn btn-success" name="action" value="release" <?=$locked || $ready['errors']?'disabled':''?>>Phát hành phiếu dự thi</button></form></div>
<h2 class="h5">Danh sách xếp phòng</h2><div class="table-responsive"><table class="table table-striped"><thead><tr><th>SBD</th><th>Họ tên</th><th>Ngày sinh</th><th>Điểm thi</th><th>Phòng</th></tr></thead><tbody><?php foreach(array_slice($roster,($page-1)*50,50) as $r):?><tr><td><?=e($r['exam_number'] ?? 'Chưa cấp')?></td><td><?=e($r['full_name'])?></td><td><?=e($r['date_of_birth'])?></td><td><?=e($r['exam_site_name'])?></td><td><?=e($r['exam_room_name'])?></td></tr><?php endforeach;?><?php if(!$roster):?><tr><td colspan="5">Chưa có danh sách xếp phòng.</td></tr><?php endif;?></tbody></table></div>
<nav class="d-flex gap-3 align-items-center"><?php if($page>1):?><a href="?period_id=<?=$periodId?>&page=<?=$page-1?>">Trang trước</a><?php endif;?><span>Trang <?=$page?> / <?=$pages?> · <?=count($roster)?> học sinh</span><?php if($page<$pages):?><a href="?period_id=<?=$periodId?>&page=<?=$page+1?>">Trang sau</a><?php endif;?></nav>
<?php workflow_layout_end(); ?>
