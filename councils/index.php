<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/council_service.php';
require_permission('council.manage');
$pdo=db();
$periodId=(int)($_GET['period_id']??0);
$type=$_GET['type']??null;
$error='';
try{$councils=council_list($pdo,$periodId,$type);}catch(Throwable $e){$councils=[];$error=$e->getMessage();}
$pageTitle='Thành lập hội đồng - TUYỂN SINH LỚP 10';$activePage='councils';$bodyClass='tw-bg-background tw-font-body tw-text-on-surface';
require __DIR__.'/../includes/header.php';require __DIR__.'/../includes/navbar.php';
?>
<main class="container py-5">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
<div><h1 class="h3 fw-bold mb-1">Thành lập hội đồng</h1><p class="text-muted mb-0">Quản lý hội đồng theo kỳ tuyển sinh.</p></div>
<a class="btn btn-primary" href="<?=e(url('/councils/create.php?period_id='.$periodId))?>">+ Thành lập hội đồng</a>
</div>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="table-responsive bg-white rounded-4 shadow-sm"><table class="table table-hover align-middle mb-0"><thead><tr><th>Loại</th><th>Tên hội đồng</th><th>Điểm thi</th><th>Môn phụ trách</th><th>Thời gian</th><th>Trạng thái</th></tr></thead><tbody>
<?php foreach($councils as $c):?><tr><td><?=e(council_type_label($c['council_type']))?></td><td><?=e($c['name'])?></td><td><?=e($c['site_name']??'')?></td><td><?=e($c['subjects']??'')?></td><td><?=e(trim(($c['start_at']??'').' - '.($c['end_at']??''),' -'))?></td><td><?=e($c['status'])?></td></tr><?php endforeach;?>
<?php if(!$councils):?><tr><td colspan="6" class="text-center py-5 text-muted">Chưa có hội đồng trong kỳ này.</td></tr><?php endif;?></tbody></table></div>
</main><?php require __DIR__.'/../includes/footer.php';require __DIR__.'/../includes/scripts.php';?></body></html>
