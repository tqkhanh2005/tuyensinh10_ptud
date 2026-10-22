<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/candidate_service.php';
require_once __DIR__ . '/../includes/admission_service.php';
require_permission('admission.view');
$u = auth_user();
$candidate = candidate_find_by_user((int)$u['id']);
if (!$candidate) {
    http_response_code(404);
    exit('Không tìm thấy hồ sơ.');
}

// Chống IDOR: không cho phép xem hồ sơ thí sinh khác qua query string
$tamperId = filter_input(INPUT_GET, 'candidate_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($tamperId && $tamperId !== (int)$candidate['id']) {
    http_response_code(403);
    exit('403 Forbidden: Bạn không có quyền truy cập dữ liệu của thí sinh khác.');
}
$pdo = db();
require_once __DIR__.'/../includes/candidate_exam_periods.php';
$periods=candidate_exam_periods($pdo,(int)$candidate['id']);
$periodId = (int)($_GET['period_id'] ?? ($periods[0]['id'] ?? 0));
$rows = $periodId ? candidate_admission_result($pdo, (int)$candidate['id'], $periodId) : [];
$pageTitle = 'Kết quả xét tuyển - TUYỂN SINH LỚP 10';
$activePage = 'admission-results';
$bodyClass = 'tw-bg-surface tw-text-on-surface';

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-5" style="margin-top: 5rem;">
<?php if(($cfg=period_configuration($pdo,$periodId)) && $cfg['settings']['mode']==='simulation'):?><div class="alert alert-warning">MÔ PHỎNG — Kết quả phục vụ dự án, không phải kết quả chính thức.</div><?php endif;?>
  <!-- Tiêu đề trang & Tóm tắt hồ sơ thí sinh -->
  <div class="row align-items-center mb-4 g-3">
    <div class="col-lg-7">
      <h1 class="h3 fw-bold text-dark mb-1">Kết quả xét tuyển Tuyển sinh Lớp 10</h1>
      <p class="text-muted mb-0">Tra cứu kết quả xét tuyển các nguyện vọng đã đăng ký theo từng kỳ tuyển sinh.</p>
    </div>
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
        <div class="d-flex align-items-center gap-3">
          <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 48px; height: 48px; min-width: 48px;">
            <i class="fas fa-user-graduate fs-5"></i>
          </div>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-bold text-dark text-truncate fs-6" title="<?= e($candidate['full_name']) ?>"><?= e($candidate['full_name']) ?></div>
            <div class="text-muted small">CCCD: <span class="fw-semibold text-dark"><?= e($candidate['national_id']) ?></span></div>
          </div>
          <div>
            <?php if ((int)($candidate['eligible'] ?? 1) === 1): ?>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill small">Hợp lệ</span>
            <?php else: ?>
              <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1 rounded-pill small">Chưa đủ ĐK</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bộ lọc Kỳ tuyển sinh -->
  <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <form method="get" action="<?= e(url('/admission/results.php')) ?>" class="row g-3 align-items-end">
      <div class="col-md-8 col-lg-6">
        <label for="period_id" class="form-label fw-bold text-secondary small mb-2">
          <i class="fas fa-calendar-alt me-1 text-primary"></i>Chọn kỳ tuyển sinh
        </label>
        <select name="period_id" id="period_id" class="form-select form-select-lg fs-6">
          <?php foreach ($periods as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $p['id'] == $periodId ? 'selected' : '' ?>>
              <?= e($p['name'] . ' - ' . $p['academic_year']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4 col-lg-3">
        <button type="submit" class="btn btn-primary btn-lg w-100 fs-6 fw-semibold">
          <i class="fas fa-search me-2"></i>Xem kết quả
        </button>
      </div>
    </form>
  </div>

  <!-- Danh sách kết quả xét tuyển -->
  <?php if (!$rows): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-4">
      <div class="mb-3 text-muted">
        <i class="fas fa-clipboard-list fa-3x text-primary opacity-50"></i>
      </div>
      <h5 class="fw-bold text-dark mb-2">Kết quả xét tuyển chưa được cập nhật</h5>
      <p class="text-muted mb-0 mx-auto" style="max-width: 520px;">
        Kết quả xét tuyển của kỳ tuyển sinh đã chọn hiện chưa được công bố hoặc đang trong quá trình xét duyệt. Vui lòng quay lại kiểm tra sau.
      </p>
    </div>
  <?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
      <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark">
          <i class="fas fa-list-ol me-2 text-primary"></i>Danh sách nguyện vọng & Kết quả
        </h5>
        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">
          Tổng số: <?= count($rows) ?> nguyện vọng
        </span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width: 130px;">Nguyện vọng</th>
              <th>Trường THPT</th>
              <th class="text-center" style="width: 140px;">Tổng điểm thi</th>
              <th class="text-center" style="width: 140px;">Điểm cộng</th>
              <th class="text-center" style="width: 150px;">Điểm xét tuyển</th>
              <th class="text-center" style="width: 190px;">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php
                $isAdmitted = ($r['result_status'] === 'admitted');
                $rowClass = $isAdmitted ? 'table-success bg-opacity-25' : '';
              ?>
              <tr class="<?= $rowClass ?>">
                <td class="text-center">
                  <span class="badge bg-secondary bg-opacity-10 text-dark px-3 py-2 rounded-pill fw-bold">
                    NV <?= e((string)$r['priority_order']) ?>
                  </span>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= e(($r['school_code'] ?? '') . ' - ' . ($r['school_name'] ?? '')) ?></div>
                </td>
                <td class="text-center fw-semibold text-secondary">
                  <?= is_numeric($r['score_total']) ? number_format((float)$r['score_total'], 2) : e((string)$r['score_total']) ?>
                </td>
                <td class="text-center text-muted">
                  <?= is_numeric($r['priority_points']) ? number_format((float)$r['priority_points'], 2) : e((string)$r['priority_points']) ?>
                </td>
                <td class="text-center fw-bold text-primary fs-6">
                  <?= is_numeric($r['admission_score']) ? number_format((float)$r['admission_score'], 2) : e((string)$r['admission_score']) ?>
                </td>
                <td class="text-center">
                  <?php if ($isAdmitted): ?>
                    <span class="badge bg-success px-3 py-2 rounded-pill fw-bold shadow-sm">
                      <i class="fas fa-check-circle me-1"></i>Trúng tuyển
                    </span>
                  <?php elseif ($r['result_status'] === 'not_admitted'): ?>
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                      Không trúng tuyển
                    </span>
                  <?php else: ?>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2 rounded-pill">
                      Không đủ điều kiện xét tuyển<?php if(!empty($r['selection_reason'])):?><small class="d-block"><?=e($r['selection_reason'])?></small><?php endif;?>
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
<?php require __DIR__ . '/../includes/scripts.php'; ?>
</body>
</html>
