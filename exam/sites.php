<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_permission('exam.manage');
$pdo = db();
$periodId = (int)($_GET['period_id'] ?? $_POST['period_id'] ?? 0);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        create_exam_site($pdo, $_POST);
        header('Location: ' . url('/exam/sites.php?period_id=' . $periodId));
        exit;
    } catch (Throwable $e) {
        $error = $e instanceof PDOException ? 'Không thể tạo điểm thi. Kiểm tra dữ liệu hoặc mã trùng.' : $e->getMessage();
    }
}
$sites = exam_sites($pdo, $periodId);

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Quản lý điểm thi';
$activePage = 'councils';

admin_layout_start('Quản lý điểm thi', 'councils', [
    'Quản lý kỳ thi' => url('/exam/organization.php?period_id=' . $periodId),
    'Điểm thi' => '',
]);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Quản lý điểm thi</h1>
    <p class="text-muted mb-0">Thiết lập địa điểm tổ chức thi tuyển sinh theo kỳ tuyển sinh.</p>
  </div>
  <a class="btn btn-outline-secondary" href="<?= e(url('/exam/organization.php?period_id=' . $periodId)) ?>">
    Tổ chức thi
  </a>
</div>

<?php if ($error): ?>
  <div class="alert alert-danger mb-4"><?= e($error) ?></div>
<?php endif; ?>

<!-- Form thêm điểm thi -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h6 class="mb-0 fw-bold text-dark">Thêm điểm thi mới</h6>
  </div>
  <div class="card-body p-4">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <input type="hidden" name="period_id" value="<?= $periodId ?>">
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Mã điểm thi *</label>
        <input class="form-control" name="site_code" placeholder="VD: DT01" required>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Tên điểm thi *</label>
        <input class="form-control" name="name" placeholder="VD: THPT Nguyễn Thị Minh Khai" required>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Địa chỉ</label>
        <input class="form-control" name="address" placeholder="VD: 275 Điện Biên Phủ, TP.HCM">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold">Sức chứa *</label>
        <div class="input-group">
          <input type="number" min="1" step="1" class="form-control" name="capacity" placeholder="600" required>
          <button class="btn btn-primary" type="submit">Thêm</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Bảng danh sách điểm thi -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
  <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold text-dark">Danh sách điểm thi</h6>
    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1.5 fw-semibold"><?= count($sites) ?> điểm thi</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 140px;">Mã điểm thi</th>
          <th>Tên điểm thi</th>
          <th>Địa chỉ</th>
          <th class="text-end" style="width: 130px;">Sức chứa</th>
          <th class="text-end" style="width: 150px;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sites as $s): ?>
          <tr>
            <td class="font-monospace fw-bold text-primary"><?= e($s['site_code']) ?></td>
            <td class="fw-semibold text-dark"><?= e($s['name']) ?></td>
            <td class="text-muted small"><?= e($s['address'] ?? 'Chưa cập nhật') ?></td>
            <td class="text-end font-monospace"><?= e((string)$s['capacity']) ?> thí sinh</td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary px-2.5" href="<?= e(url('/exam/rooms.php?site_id=' . (int)$s['id'])) ?>">
                Quản lý phòng
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$sites): ?>
          <tr>
            <td colspan="5" class="text-center py-5 text-muted">Chưa có điểm thi nào trong kỳ này.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
admin_layout_end();