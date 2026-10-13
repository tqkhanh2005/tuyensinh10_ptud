<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_permission('exam.manage');
$pdo = db();
$siteId = (int)($_GET['site_id'] ?? $_POST['site_id'] ?? 0);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        create_exam_room($pdo, $_POST);
        header('Location: ' . url('/exam/rooms.php?site_id=' . $siteId));
        exit;
    } catch (Throwable $e) {
        $error = 'Không thể tạo phòng thi. Kiểm tra dữ liệu hoặc mã trùng.';
    }
}
$rooms = exam_rooms($pdo, $siteId);

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Quản lý phòng thi';
$activePage = 'councils';

admin_layout_start('Quản lý phòng thi', 'councils', [
    'Quản lý kỳ thi' => url('/councils/index.php'),
    'Điểm thi' => url('/exam/sites.php'),
    'Phòng thi' => '',
]);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Quản lý phòng thi</h1>
    <p class="text-muted mb-0">Thiết lập phòng thi và sức chứa theo điểm thi.</p>
  </div>
  <a class="btn btn-outline-secondary" href="<?= e(url('/exam/sites.php')) ?>">
    Quay lại điểm thi
  </a>
</div>

<?php if ($error): ?>
  <div class="alert alert-danger mb-4"><?= e($error) ?></div>
<?php endif; ?>

<!-- Form thêm phòng -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h6 class="mb-0 fw-bold text-dark">Thêm phòng thi mới</h6>
  </div>
  <div class="card-body p-4">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <input type="hidden" name="site_id" value="<?= $siteId ?>">
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Mã phòng *</label>
        <input class="form-control" name="room_code" placeholder="VD: P01" required>
      </div>
      <div class="col-md-5">
        <label class="form-label small fw-semibold">Tên phòng *</label>
        <input class="form-control" name="name" placeholder="VD: Phòng thi số 01" required>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Sức chứa *</label>
        <div class="input-group">
          <input type="number" min="0" class="form-control" name="capacity" placeholder="24" required>
          <button class="btn btn-primary" type="submit">Thêm</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Bảng danh sách phòng -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
  <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold text-dark">Danh sách phòng thi</h6>
    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1.5 fw-semibold"><?= count($rooms) ?> phòng</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 150px;">Mã phòng</th>
          <th>Tên phòng</th>
          <th class="text-end" style="width: 150px;">Sức chứa</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rooms as $r): ?>
          <tr>
            <td class="font-monospace fw-bold text-primary"><?= e($r['room_code']) ?></td>
            <td class="fw-semibold text-dark"><?= e($r['name']) ?></td>
            <td class="text-end font-monospace"><?= e((string)$r['capacity']) ?> thí sinh</td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rooms): ?>
          <tr>
            <td colspan="3" class="text-center py-5 text-muted">Chưa có phòng thi nào cho điểm thi này.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
admin_layout_end();