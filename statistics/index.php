<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/publication_service.php';

require_permission('statistics.view');

$pdo = db();
$pid = (int)($_GET['period_id'] ?? 0);
$type = (string)($_GET['type'] ?? 'admission_result');
$error = '';
$rows = [];

if ($pid <= 0) {
    $error = 'Vui lòng chọn kỳ tuyển sinh.';
} elseif (!in_array($type, ['profile_status', 'admission_result'], true)) {
    $error = 'Loại thống kê không hợp lệ.';
} else {
    try {
        $rows = statistics($pdo, $pid, $type);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Thống kê - Báo cáo tuyển sinh';
$activePage = 'statistics';

admin_layout_start('Thống kê - Báo cáo tuyển sinh', 'statistics', ['Thống kê - báo cáo' => '']);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Thống kê - Báo cáo tuyển sinh</h1>
    <p class="text-muted mb-0">Tổng hợp dữ liệu hồ sơ đăng ký và kết quả xét tuyển theo từng kỳ tuyển sinh.</p>
  </div>
</div>

<!-- Bộ lọc thống kê -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
  <div class="card-body p-4">
    <form method="get" class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label small fw-semibold text-secondary">ID Kỳ tuyển sinh *</label>
        <input type="number" class="form-control" name="period_id" value="<?= e((string)$pid) ?>" min="1" placeholder="Nhập ID kỳ tuyển sinh" required>
      </div>
      <div class="col-md-5">
        <label class="form-label small fw-semibold text-secondary">Loại báo cáo thống kê *</label>
        <select name="type" class="form-select" required>
          <option value="profile_status" <?= $type === 'profile_status' ? 'selected' : '' ?>>Tình trạng hồ sơ thí sinh</option>
          <option value="admission_result" <?= $type === 'admission_result' ? 'selected' : '' ?>>Kết quả xét tuyển</option>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100 fw-semibold">
          Xem báo cáo
        </button>
      </div>
    </form>
  </div>
</div>

<?php if ($error): ?>
  <div class="alert alert-danger mb-4"><?= e($error) ?></div>
<?php elseif (!$rows && $pid > 0): ?>
  <div class="alert alert-info mb-4">Chưa có dữ liệu thống kê cho kỳ tuyển sinh này.</div>
<?php elseif ($rows): ?>
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold text-dark">
        <?= $type === 'profile_status' ? 'Báo cáo tình trạng hồ sơ' : 'Báo cáo kết quả xét tuyển' ?>
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?= $type === 'profile_status' ? 'Tình trạng hồ sơ' : 'Kết quả xét tuyển' ?></th>
            <th class="text-end" style="width: 200px;">Số lượng thí sinh</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="fw-semibold text-dark">
                <?php if ($type === 'profile_status'): ?>
                  <?= (int)$r['eligible'] === 1 ? '<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1">Đủ điều kiện dự thi</span>' : '<span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2.5 py-1">Chưa đủ điều kiện</span>' ?>
                <?php else: ?>
                  <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1"><?= e((string)$r['result_status']) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-end font-monospace fw-bold fs-6"><?= number_format((int)$r['total']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php
admin_layout_end();

