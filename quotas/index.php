<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_permission('admission.manage');

$pdo = db();
$periodId = (int)($_GET['period_id'] ?? 0);
$period = admission_period_find($pdo, $periodId);

if (!$period) {
    http_response_code(404);
    admin_layout_start('Không tìm thấy kỳ tuyển sinh', 'admission');
    ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
      <div class="text-danger mb-3">
        <i class="fas fa-exclamation-triangle fa-3x"></i>
      </div>
      <h4 class="fw-bold text-dark mb-2">Không tìm thấy kỳ tuyển sinh</h4>
      <p class="text-muted mb-4">Kỳ tuyển sinh yêu cầu không tồn tại hoặc đã bị xóa khỏi hệ thống.</p>
      <div>
        <a href="<?= e(url('/admission/index.php')) ?>" class="btn btn-primary px-4 fw-semibold">
          <i class="fas fa-arrow-left me-1.5"></i> Quay lại danh sách kỳ tuyển sinh
        </a>
      </div>
    </div>
    <?php
    admin_layout_end();
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
$area = trim((string)($_GET['area'] ?? ''));

// Lấy danh sách các khu vực tuyển sinh của trường THPT đang active
$areaStmt = $pdo->query("SELECT DISTINCT admission_area FROM schools WHERE level='THPT' AND active=1 AND admission_area IS NOT NULL AND admission_area != '' ORDER BY admission_area");
$areaList = $areaStmt ? $areaStmt->fetchAll(PDO::FETCH_COLUMN) : [];

// Danh sách toàn bộ trường THPT để tính tổng quan (KPI cards)
$allRows = quota_rows($pdo, $periodId);
$totalSchools = count($allRows);
$setSchools = count(array_filter($allRows, fn($r) => ($r['quota_status'] ?? '') === 'set' && (int)($r['quota'] ?? 0) > 0));
$totalQuota = array_sum(array_map(fn($r) => (int)($r['quota'] ?? 0), $allRows));

$successMessage = '';
$errorMessage = '';
$postedQuotas = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postedQuotas = (array)($_POST['quota'] ?? []);
    $quotasToSave = [];
    $hasInvalid = false;

    foreach ($postedQuotas as $schoolId => $val) {
        $sIdStr = (string)$schoolId;
        $valStr = trim((string)$val);

        if ($valStr === '') {
            continue;
        }

        // Kiểm tra số nguyên dương nghiêm ngặt: không được là 0, âm hoặc số thập phân
        if (!ctype_digit($sIdStr) || !ctype_digit($valStr) || (int)$valStr <= 0) {
            $hasInvalid = true;
            $errorMessage = 'Chỉ tiêu tuyển sinh phải là số nguyên dương lớn hơn 0 (không chấp nhận số 0, số âm hoặc số thập phân).';
            break;
        }

        $quotasToSave[(int)$sIdStr] = (int)$valStr;
    }

    if (!$hasInvalid) {
        if (empty($quotasToSave)) {
            $errorMessage = 'Vui lòng nhập chỉ tiêu cho ít nhất một trường trước khi lưu.';
        } else {
            try {
                quota_save_all($pdo, $periodId, $quotasToSave);
                $successMessage = 'Lưu chỉ tiêu tuyển sinh thành công.';

                // Tải lại dữ liệu mới nhất sau khi lưu
                $allRows = quota_rows($pdo, $periodId);
                $totalSchools = count($allRows);
                $setSchools = count(array_filter($allRows, fn($r) => ($r['quota_status'] ?? '') === 'set' && (int)($r['quota'] ?? 0) > 0));
                $totalQuota = array_sum(array_map(fn($r) => (int)($r['quota'] ?? 0), $allRows));
                $postedQuotas = null;
            } catch (Throwable $e) {
                error_log('quota_save_all error: ' . $e->getMessage());
                $errorMessage = 'Không thể lưu chỉ tiêu. Vui lòng kiểm tra lại dữ liệu và thử lại.';
            }
        }
    }
}

// Lọc dữ liệu hiển thị bảng
if ($q !== '' || $area !== '') {
    $rows = quota_rows($pdo, $periodId, $q, $area);
} else {
    $rows = $allRows;
}

$statusMap = [
    'published' => ['label' => 'Đang công bố', 'badge' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25'],
    'draft' => ['label' => 'Bản nháp', 'badge' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25'],
    'closed' => ['label' => 'Đã đóng', 'badge' => 'bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25'],
];
$periodStatus = $statusMap[$period['status'] ?? 'draft'] ?? ['label' => $period['status'], 'badge' => 'bg-light text-dark'];

admin_layout_start(
    'Quản lý chỉ tiêu - ' . $period['name'],
    'admission',
    [
        'Xét tuyển' => url('/admission/index.php'),
        'Kỳ tuyển sinh' => url('/admission/index.php'),
        'Quản lý chỉ tiêu' => '',
    ]
);
?>

<!-- Header & Thông tin kỳ tuyển sinh -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
      <h1 class="h3 fw-bold mb-0">Quản lý chỉ tiêu tuyển sinh</h1>
      <span class="badge <?= $periodStatus['badge'] ?> rounded-pill px-2.5 py-1 fw-semibold small">
        <?= e($periodStatus['label']) ?>
      </span>
    </div>
    <p class="text-muted mb-0">
      Kỳ: <span class="fw-bold text-dark"><?= e($period['name']) ?></span> &bull; 
      Năm học: <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-0.5 fw-semibold"><?= e($period['academic_year']) ?></span>
      <?php if (!empty($period['admission_method'])): ?>
        &bull; Phương thức: <span class="text-secondary"><?= e($period['admission_method']) ?></span>
      <?php endif; ?>
    </p>
  </div>
  <a class="btn btn-outline-secondary" href="<?= e(url('/admission/index.php')) ?>">
    <i class="fas fa-arrow-left me-1.5"></i> Quay lại kỳ tuyển sinh
  </a>
</div>

<!-- Thông báo Thành công / Lỗi -->
<?php if ($successMessage): ?>
  <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-2">
      <i class="fas fa-check-circle fs-5"></i>
      <div><?= e($successMessage) ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
  </div>
<?php endif; ?>

<?php if ($errorMessage): ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-2">
      <i class="fas fa-exclamation-triangle fs-5"></i>
      <div><?= e($errorMessage) ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
  </div>
<?php endif; ?>

<!-- Tổng quan chỉ tiêu (KPI Cards) -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-4">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="fas fa-school fs-5"></i>
        </div>
        <div>
          <div class="text-muted small fw-semibold">Tổng số trường THPT</div>
          <div class="fs-4 fw-bold text-dark"><?= $totalSchools ?> trường</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-4">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="fas fa-clipboard-check fs-5"></i>
        </div>
        <div>
          <div class="text-muted small fw-semibold">Đã thiết lập chỉ tiêu</div>
          <div class="fs-4 fw-bold text-success">
            <?= $setSchools ?> / <?= $totalSchools ?>
            <span class="fs-6 text-muted fw-normal">trường</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-4">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 bg-info bg-opacity-10 text-info p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="fas fa-users fs-5"></i>
        </div>
        <div>
          <div class="text-muted small fw-semibold">Tổng chỉ tiêu tuyển sinh</div>
          <div class="fs-4 fw-bold text-primary">
            <?= number_format($totalQuota) ?>
            <span class="fs-6 text-muted fw-normal">học sinh</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bộ lọc tìm kiếm và khu vực -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
  <div class="card-body p-3 p-md-4">
    <form method="get" action="<?= e(url('/quotas/index.php')) ?>" class="row g-2 align-items-end">
      <input type="hidden" name="period_id" value="<?= (int)$periodId ?>" />

      <div class="col-md-5">
        <label class="form-label small fw-semibold text-secondary">Tìm kiếm trường</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0 text-muted">
            <i class="fas fa-search"></i>
          </span>
          <input type="text"
                 name="q"
                 class="form-control border-start-0"
                 placeholder="Nhập mã trường hoặc tên trường..."
                 value="<?= e($q) ?>" />
        </div>
      </div>

      <div class="col-md-4">
        <label class="form-label small fw-semibold text-secondary">Khu vực tuyển sinh</label>
        <select name="area" class="form-select">
          <option value="">-- Tất cả khu vực --</option>
          <?php foreach ($areaList as $aItem): ?>
            <option value="<?= e($aItem) ?>" <?= $area === $aItem ? 'selected' : '' ?>>
              <?= e($aItem) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary fw-semibold flex-grow-1">
          <i class="fas fa-filter me-1"></i> Lọc
        </button>
        <?php if ($q !== '' || $area !== ''): ?>
          <a href="<?= e(url('/quotas/index.php?period_id=' . (int)$periodId)) ?>" class="btn btn-outline-secondary" title="Xóa bộ lọc">
            <i class="fas fa-undo"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Form lưu chỉ tiêu -->
<?php if ($totalSchools === 0): ?>
  <div class="card border-0 shadow-sm rounded-4 bg-white p-5 text-center mb-4">
    <div class="text-secondary mb-3">
      <i class="far fa-building fa-3x"></i>
    </div>
    <h5 class="fw-bold text-dark mb-1">Chưa có trường THPT nào hoạt động</h5>
    <p class="text-muted mb-0">Hệ thống chưa có trường cấp THPT ở trạng thái hoạt động để thiết lập chỉ tiêu.</p>
  </div>
<?php else: ?>
  <form method="post" action="<?= e(url('/quotas/index.php?' . http_build_query(['period_id' => $periodId, 'q' => $q, 'area' => $area]))) ?>">
    <?= csrf_field() ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
      <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold text-dark">Danh sách chỉ tiêu theo trường</h6>
        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1.5 fw-semibold">
          Hiển thị <?= count($rows) ?> / <?= $totalSchools ?> trường
        </span>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 140px;">Mã trường</th>
              <th>Tên trường THPT</th>
              <th style="width: 180px;">Khu vực</th>
              <th style="width: 180px;" class="text-end">Chỉ tiêu tuyển sinh</th>
              <th style="width: 150px;" class="text-center">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php
                $currVal = $postedQuotas !== null
                    ? ($postedQuotas[$r['school_id']] ?? '')
                    : ((int)$r['quota'] > 0 ? (int)$r['quota'] : '');
                $isSet = ($r['quota_status'] ?? '') === 'set' && (int)$r['quota'] > 0;
              ?>
              <tr>
                <td>
                  <span class="font-monospace fw-bold text-secondary"><?= e($r['school_code']) ?></span>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= e($r['name']) ?></div>
                </td>
                <td>
                  <?php if (!empty($r['admission_area'])): ?>
                    <span class="badge bg-light text-dark border px-2 py-1">
                      <i class="fas fa-map-marker-alt me-1 text-primary small"></i><?= e($r['admission_area']) ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted small fst-italic">—</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex align-items-center justify-content-end">
                    <input type="number"
                           name="quota[<?= (int)$r['school_id'] ?>]"
                           class="form-control form-control-sm text-end fw-bold text-primary"
                           style="max-width: 130px;"
                           min="1"
                           step="1"
                           value="<?= e((string)$currVal) ?>"
                           placeholder="Chưa đặt" />
                  </div>
                </td>
                <td class="text-center">
                  <?php if ($isSet): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 fw-semibold">
                      <i class="fas fa-check-circle me-1 small"></i> Đã thiết lập
                    </span>
                  <?php else: ?>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-1 fw-semibold">
                      <i class="far fa-circle me-1 small"></i> Chưa thiết lập
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>

            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                  <div class="py-3">
                    <i class="fas fa-filter fa-2x text-secondary mb-2"></i>
                    <div>Không tìm thấy trường THPT nào phù hợp điều kiện lọc.</div>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Action Footer -->
      <div class="card-footer bg-light border-top p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a href="<?= e(url('/admission/index.php')) ?>" class="btn btn-outline-secondary px-3">
          <i class="fas fa-arrow-left me-1.5"></i> Quay lại kỳ tuyển sinh
        </a>
        <button type="submit" class="btn btn-primary px-4 fw-semibold" <?= empty($rows) ? 'disabled' : '' ?>>
          <i class="fas fa-save me-1.5"></i> Lưu tất cả chỉ tiêu
        </button>
      </div>
    </div>
  </form>
<?php endif; ?>

<?php
admin_layout_end();