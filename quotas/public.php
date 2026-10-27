<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/quota_workflow_service.php';
$pdo=db();
$periodId=(int)($_GET['period_id']??0);
$search = is_scalar($_GET['q'] ?? '') ? trim((string)($_GET['q'] ?? '')) : '';
$error = '';
try { $rows = quota_public_rows($pdo, $periodId, $search); }
catch (Throwable $e) { error_log('Public quotas: ' . $e->getMessage()); $rows = []; $error = 'Không thể tải chỉ tiêu. Vui lòng thử lại sau.'; }
?>
<?php require_once __DIR__ . '/../includes/workflow_layout.php'; workflow_layout_start('Chỉ tiêu tuyển sinh', 'quotas'); ?>
<link rel="stylesheet" href="<?= e(url('/quotas/quotas.css')) ?>">
<section class="quota-page">
<h1>Chỉ tiêu tuyển sinh lớp 10 tại TP.HCM</h1>
<?php if ($rows): ?><p class="text-muted"><?= e($rows[0]['period_name']) ?> · <?= e($rows[0]['academic_year']) ?></p><?php endif; ?>
<form method="get" class="d-flex flex-wrap gap-2 mb-4"><input type="hidden" name="period_id" value="<?= $periodId ?>"><label class="visually-hidden" for="public-quota-search">Tìm trường</label><input class="form-control w-auto" id="public-quota-search" name="q" value="<?= e($search) ?>" placeholder="Mã trường hoặc tên trường"><button class="btn btn-primary">Tìm kiếm</button></form>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php elseif(!$rows):?><p>Chưa có chỉ tiêu chính thức được công bố hoặc không có trường phù hợp.</p><?php endif;?>
<div class="quota-table-card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th scope="col">Mã trường</th><th scope="col">Tên trường</th><th scope="col">Khu vực</th><th scope="col">Chỉ tiêu</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><?=e($r['school_code'])?></td><td><?=e($r['name'])?></td><td><?=e($r['admission_area']??'')?></td><td><?=e((string)$r['quota'])?></td></tr><?php endforeach;?>
</tbody></table></div></div></section><?php workflow_layout_end(); ?>
