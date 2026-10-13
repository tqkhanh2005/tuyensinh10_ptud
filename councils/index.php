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
try{
    $councils=council_list($pdo,$periodId,$type);
}catch(Throwable $e){
    error_log('Council list error: ' . $e->getMessage());
    $councils=[];
    $error='Không thể tải danh sách hội đồng thi. Vui lòng thử lại sau.';
}
require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Quản lý hội đồng thi';
$activePage = 'councils';

admin_layout_start('Quản lý hội đồng thi', 'councils', ['Quản lý kỳ thi' => '']);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Thành lập hội đồng</h1>
    <p class="text-muted mb-0">Quản lý hội đồng theo kỳ tuyển sinh.</p>
  </div>
  <a class="btn btn-primary fw-medium px-3" href="<?= e(url('/councils/create.php?period_id=' . $periodId)) ?>">
    Thành lập hội đồng
  </a>
</div>
<?php if ($error): ?>
  <div class="alert alert-danger mb-4"><?= e($error) ?></div>
<?php endif; ?>
<div class="table-responsive bg-white rounded-4 shadow-sm">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Loại</th>
        <th>Tên hội đồng</th>
        <th>Điểm thi</th>
        <th>Môn phụ trách</th>
        <th>Thời gian</th>
        <th>Trạng thái</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($councils as $c): ?>
        <tr>
          <td><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?= e(council_type_label($c['council_type'])) ?></span></td>
          <td class="fw-semibold text-dark"><?= e($c['name']) ?></td>
          <td class="text-muted"><?= e($c['site_name'] ?? 'Chưa cập nhật') ?></td>
          <td class="text-muted"><?= e($c['subjects'] ?? 'Chưa cập nhật') ?></td>
          <td class="text-muted small"><?= e(trim(($c['start_at'] ?? '') . ' - ' . ($c['end_at'] ?? ''), ' -') ?: 'Chưa cập nhật') ?></td>
          <td><span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill"><?= e($c['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$councils): ?>
        <tr>
          <td colspan="6" class="text-center py-5 text-muted">Chưa có hội đồng trong kỳ này.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php
admin_layout_end();
