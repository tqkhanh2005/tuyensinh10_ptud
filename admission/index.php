<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';
require_permission('admission.manage');
$pdo=db(); $periods=admission_period_list($pdo);
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Kỳ tuyển sinh</title></head><body>
<h1>Kỳ tuyển sinh / kế hoạch</h1>
<p><a href="<?=e(url('/admission/create.php'))?>">Tạo kỳ/kế hoạch</a></p>
<table border="1" cellpadding="8"><tr><th>Tên</th><th>Năm học</th><th>Phương thức</th><th>Đăng ký</th><th>Trạng thái</th><th>Chỉ tiêu</th></tr>
<?php foreach($periods as $p): ?>
<tr><td><?=e($p['name'])?></td><td><?=e($p['academic_year'])?></td><td><?=e($p['admission_method']??'')?></td>
<td><?=e($p['registration_start']??'')?> → <?=e($p['registration_end']??'')?></td><td><?=e($p['status'])?></td>
<td><a href="<?=e(url('/quotas/index.php?period_id='.(int)$p['id']))?>">Quản lý chỉ tiêu</a></td></tr>
<?php endforeach;?></table></body></html>
