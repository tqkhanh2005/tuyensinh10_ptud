<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_helper.php';
require_once __DIR__ . '/../includes/teacher_layout.php';
require_once __DIR__ . '/../includes/quota_workflow_service.php';

require_teacher_access();

if (auth_has_role('candidate') && !auth_is_admin()) {
    http_response_code(403);
    exit('403 Forbidden - Khu vực chỉ dành cho Cán bộ - Giáo viên.');
}

$user = auth_user();
$teacherInfo = auth_get_teacher_info();
$schoolLevel = auth_teacher_level();
$schoolName = $teacherInfo['school_name'] ?? $teacherInfo['workplace'] ?? 'Trường THCS / THPT';
$schoolId = (int)($teacherInfo['school_id'] ?? 0);

$pdo = db();
$q = trim((string)($_GET['q'] ?? ''));

$periodId = (int)($_GET['period_id'] ?? 0);
$quotas = [];
$quotaError = '';
$totalAllQuota = 0;
try {
    $quotas = quota_public_rows($pdo, $periodId, $q);

    foreach ($quotas as $row) {
        $totalAllQuota += (int)$row['total_quota'];
    }
} catch (Throwable $e) {
    error_log('Teacher Quotas Error: ' . $e->getMessage());
    $quotaError = 'Không thể tải chỉ tiêu. Vui lòng thử lại sau.';
}

teacher_layout_start('Chỉ tiêu tuyển sinh THPT', 'quotas', ['Chỉ tiêu tuyển sinh' => '']);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="h4 fw-bold text-dark mb-1">
      <i class="fas fa-bullseye text-success me-2"></i>Chỉ Tiêu Tuyển Sinh Lớp 10 Các Trường THPT
    </h1>
    <p class="text-muted small mb-0">
      Chỉ hiển thị chỉ tiêu đã công bố của một kỳ tuyển sinh chính thức tại TP.HCM.
      <?php if ($quotas): ?>Kỳ: <?= e($quotas[0]['period_name']) ?> · <?= e($quotas[0]['academic_year']) ?>.<?php endif; ?>
    </p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-1.5 rounded-pill">
      Tổng chỉ tiêu trong danh sách: <?= number_format($totalAllQuota) ?> học sinh
    </span>
  </div>
</div>

<!-- Bộ lọc tìm kiếm trường -->
<?php if ($quotaError !== ''): ?><div class="alert alert-danger" role="alert"><?= e($quotaError) ?></div><?php endif; ?>
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
  <div class="card-body p-3 p-md-4">
    <form method="GET" action="<?= e(url('/teacher/quotas.php')) ?>" class="row g-3 align-items-end">
      <?php if ($periodId): ?><input type="hidden" name="period_id" value="<?= $periodId ?>"><?php endif; ?>
      <div class="col-md-9">
        <label class="form-label small fw-bold text-muted mb-1">Tìm kiếm trường THPT</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light border-end-0 text-muted">
            <i class="fas fa-search"></i>
          </span>
          <input type="text" name="q" class="form-control border-start-0" 
                 placeholder="Nhập tên trường THPT, mã trường hoặc khu vực tuyển sinh..." value="<?= e($q) ?>">
        </div>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-success btn-sm w-100 fw-semibold rounded-pill">
          <i class="fas fa-search me-1"></i>Tìm kiếm
        </button>
        <a href="<?= e(url('/teacher/quotas.php?period_id=' . $periodId)) ?>" class="btn btn-outline-secondary btn-sm rounded-pill" title="Làm mới">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Bảng danh sách chỉ tiêu -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light small">
        <tr>
          <th style="white-space: nowrap;">Mã trường</th>
          <th style="white-space: nowrap;">Tên trường THPT</th>
          <th style="white-space: nowrap;">Quận / Huyện</th>
          <th style="white-space: nowrap;">Khu vực tuyển sinh</th>
          <th style="white-space: nowrap;" class="text-end">Chỉ tiêu phê duyệt</th>
        </tr>
      </thead>
      <tbody class="small">
        <?php foreach ($quotas as $qRow): ?>
          <?php $isMySchool = ((int)$qRow['id'] === $schoolId); ?>
          <tr class="<?= $isMySchool ? 'table-success table-opacity-10 fw-semibold' : '' ?>">
            <td style="white-space: nowrap;">
              <span class="font-monospace fw-bold <?= $isMySchool ? 'text-success' : 'text-primary' ?>">
                <?= e($qRow['school_code']) ?>
              </span>
            </td>
            <td>
              <div class="fw-bold <?= $isMySchool ? 'text-success' : 'text-dark' ?>">
                <?= e($qRow['name']) ?>
                <?php if ($isMySchool): ?>
                  <span class="badge bg-success text-white ms-1 rounded-pill small">Trường của bạn</span>
                <?php endif; ?>
              </div>
            </td>
            <td class="text-muted" style="white-space: nowrap;">
              <?= e($qRow['district'] ?? 'TP.HCM') ?>
            </td>
            <td class="text-muted" style="white-space: nowrap;">
              <?= e($qRow['admission_area'] ?? 'Đại trà') ?>
            </td>
            <td class="text-end font-monospace fw-bold" style="white-space: nowrap;">
              <span class="<?= $isMySchool ? 'text-success fs-6' : 'text-dark' ?>">
                <?= (int)$qRow['total_quota'] > 0 ? number_format((int)$qRow['total_quota']) : 'Chưa công bố' ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($quotas)): ?>
          <tr>
            <td colspan="5" class="text-center py-5 text-muted">
              Chưa có chỉ tiêu chính thức được công bố hoặc không có trường phù hợp với từ khóa.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
teacher_layout_end();
