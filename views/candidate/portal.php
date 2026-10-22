<?php
/**
 * Giao diện Cổng thông tin Thí sinh (Candidate Portal View)
 * Đảm bảo:
 * - Hiển thị đúng dữ liệu thí sinh hiện tại từ server session
 * - Không có Admin sidebar hoặc Teacher sidebar
 * - Responsive: Desktop 1366x768, 1920x1080, Mobile 375x667
 * - Bảo mật: Escape toàn bộ output, không lộ SQL/Stack trace
 */
declare(strict_types=1);

$pageTitle = 'Cổng thông tin Thí sinh - TUYỂN SINH LỚP 10';
$activePage = 'candidate-portal';
$bodyClass = 'tw-bg-slate-50 tw-text-slate-800';

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/navbar.php';
?>

<main class="container py-4 py-md-5" style="margin-top: 5rem;">

  <!-- Breadcrumb & Welcome Banner -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>" class="text-decoration-none">Trang chủ</a></li>
      <li class="breadcrumb-item active" aria-current="page">Cổng thí sinh</li>
    </ol>
  </nav>

  <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
    <div class="p-4 p-md-5 text-white position-relative" style="background: linear-gradient(135deg, #0052cc 0%, #1e40af 60%, #0369a1 100%);">
      <div class="row align-items-center g-3">
        <div class="col-md-8">
          <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white/20 text-white small fw-bold mb-2 backdrop-blur-sm">
            <i class="fas fa-user-graduate"></i> CỔNG THÔNG TIN THÍ SINH
          </div>
          <h1 class="h2 fw-bold text-white mb-2">Xin chào, <?= e($candidateDetails['full_name'] ?? 'Thí sinh') ?></h1>
          <p class="text-white/90 mb-0 small">
            Quản lý hồ sơ đăng ký, tra cứu thẻ dự thi, nguyện vọng xét tuyển và kết quả tuyển sinh Lớp 10.
          </p>
        </div>
        <div class="col-md-4 text-md-end">
          <div class="d-inline-flex flex-column align-items-md-end bg-white/10 p-3 rounded-4 backdrop-blur-sm border border-white/20 text-start text-md-end w-100 w-md-auto">
            <span class="text-white/80 small">Số báo danh (SBD)</span>
            <span class="fs-4 fw-black text-warning font-monospace"><?= e($candidateDetails['exam_number'] ?: 'Chưa cấp') ?></span>
            <span class="text-white/80 small mt-1">CCCD: <span class="font-monospace text-white fw-bold"><?= e($candidateDetails['national_id'] ?? '---') ?></span></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Status Strip -->
    <div class="bg-light border-top px-4 py-3">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 small">
        <div class="d-flex flex-wrap align-items-center gap-3">
          <div>
            <span class="text-muted me-1">Trạng thái hồ sơ:</span>
            <?php if (($candidateDetails['status'] ?? '') === 'active'): ?>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill fw-semibold">
                <i class="fas fa-check-circle me-1"></i>Đang hoạt động
              </span>
            <?php else: ?>
              <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2.5 py-1 rounded-pill fw-semibold">
                <i class="fas fa-lock me-1"></i>Tạm khóa
              </span>
            <?php endif; ?>
          </div>

          <div>
            <span class="text-muted me-1">Điều kiện dự thi:</span>
            <?php if ((int)($candidateDetails['eligible'] ?? 1) === 1): ?>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1 rounded-pill fw-semibold">
                <i class="fas fa-id-badge me-1"></i>Đủ điều kiện
              </span>
            <?php else: ?>
              <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1 rounded-pill fw-semibold">
                <i class="fas fa-times-circle me-1"></i>Chưa đủ điều kiện
              </span>
            <?php endif; ?>
          </div>

          <div>
            <span class="text-muted me-1">Xét tuyển:</span>
            <?php 
              $admStatus = $candidateDetails['admission_status'] ?? 'not_processed';
              if ($admStatus === 'admitted'): ?>
              <span class="badge bg-success px-2.5 py-1 rounded-pill fw-bold">
                <i class="fas fa-star me-1"></i>Trúng tuyển
              </span>
            <?php elseif ($admStatus === 'enrolled'): ?>
              <span class="badge bg-info text-white px-2.5 py-1 rounded-pill fw-bold">
                <i class="fas fa-school me-1"></i>Đã nhập học
              </span>
            <?php else: ?>
              <span class="badge bg-light text-secondary border px-2.5 py-1 rounded-pill">
                Chưa xét tuyển
              </span>
            <?php endif; ?>
          </div>
        </div>

        <div>
          <a href="<?= e(url('/auth/change-password.php')) ?>" class="text-secondary hover:text-primary text-decoration-none">
            <i class="fas fa-key me-1"></i>Đổi mật khẩu
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions Grid -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <a href="<?= e(url('/aspirations/index.php')) ?>" class="card h-100 border-0 shadow-sm rounded-4 p-3 text-decoration-none text-dark bg-white hover-lift transition">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="fas fa-list-ol fs-5"></i>
          </div>
          <div class="min-w-0">
            <div class="fw-bold small text-truncate">Nguyện vọng</div>
            <div class="text-muted text-[11px]"><?= count($aspirations) ?> đã đăng ký</div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-3">
      <a href="<?= e(url('/exam/card.php')) ?>" class="card h-100 border-0 shadow-sm rounded-4 p-3 text-decoration-none text-dark bg-white hover-lift transition">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="fas fa-id-card fs-5"></i>
          </div>
          <div class="min-w-0">
            <div class="fw-bold small text-truncate">Thẻ dự thi</div>
            <div class="text-muted text-[11px]"><?= !empty($candidateDetails['exam_number']) ? 'Đã có SBD' : 'Chờ cấp SBD' ?></div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-3">
      <a href="<?= e(url('/admission/results.php')) ?>" class="card h-100 border-0 shadow-sm rounded-4 p-3 text-decoration-none text-dark bg-white hover-lift transition">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="fas fa-award fs-5"></i>
          </div>
          <div class="min-w-0">
            <div class="fw-bold small text-truncate">Kết quả tuyển sinh</div>
            <div class="text-muted text-[11px]"><?= count($admissionResults) > 0 ? 'Đã có kết quả' : 'Tra cứu kết quả' ?></div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-6 col-md-3">
      <a href="<?= e(url('/adjustments/create.php')) ?>" class="card h-100 border-0 shadow-sm rounded-4 p-3 text-decoration-none text-dark bg-white hover-lift transition">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 bg-info bg-opacity-10 text-info p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="fas fa-edit fs-5"></i>
          </div>
          <div class="min-w-0">
            <div class="fw-bold small text-truncate">Điều chỉnh hồ sơ</div>
            <div class="text-muted text-[11px]"><?= count($adjustmentRequests) ?> yêu cầu</div>
          </div>
        </div>
      </a>
    </div>
  </div>

  <!-- Main Content Grid -->
  <div class="row g-4">

    <!-- Cột trái: Thông tin cá nhân & Thẻ dự thi -->
    <div class="col-lg-5">

      <!-- Card: Thông tin hồ sơ cá nhân -->
      <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold text-dark fs-6">
            <i class="fas fa-user text-primary me-2"></i>Hồ sơ cá nhân
          </h5>
          <a href="<?= e(url('/adjustments/create.php')) ?>" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold py-1 px-3" style="font-size: 12px;">
            <i class="fas fa-pen me-1"></i>Điều chỉnh
          </a>
        </div>
        <div class="card-body p-4">
          <div class="d-flex flex-column gap-3 small">
            <div class="d-flex justify-content-between pb-2 border-bottom">
              <span class="text-muted">Họ và tên:</span>
              <span class="fw-bold text-dark text-end"><?= e($candidateDetails['full_name'] ?? '---') ?></span>
            </div>
            <div class="d-flex justify-content-between pb-2 border-bottom">
              <span class="text-muted">Số CCCD / Định danh:</span>
              <span class="font-monospace fw-semibold text-dark text-end"><?= e($candidateDetails['national_id'] ?? '---') ?></span>
            </div>
            <div class="d-flex justify-content-between pb-2 border-bottom">
              <span class="text-muted">Ngày sinh:</span>
              <span class="fw-semibold text-dark text-end">
                <?= !empty($candidateDetails['date_of_birth']) ? date('d/m/Y', strtotime($candidateDetails['date_of_birth'])) : 'Chưa cập nhật' ?>
              </span>
            </div>
            <div class="d-flex justify-content-between pb-2 border-bottom">
              <span class="text-muted">Giới tính:</span>
              <span class="fw-semibold text-dark text-end"><?= e($candidateDetails['gender'] ?: 'Chưa cập nhật') ?></span>
            </div>
            <div class="d-flex justify-content-between pb-2 border-bottom">
              <span class="text-muted">Số điện thoại:</span>
              <span class="font-monospace fw-semibold text-dark text-end"><?= e($candidateDetails['phone'] ?: 'Chưa cập nhật') ?></span>
            </div>
            <div class="d-flex justify-content-between pb-2 border-bottom">
              <span class="text-muted">Email:</span>
              <span class="fw-semibold text-dark text-end text-truncate ms-2" style="max-width: 200px;"><?= e($candidateDetails['email'] ?: 'Chưa cập nhật') ?></span>
            </div>
            <div class="d-flex justify-content-between pb-2 border-bottom">
              <span class="text-muted">Trường THCS:</span>
              <span class="fw-bold text-primary text-end"><?= e($candidateDetails['middle_school_name'] ?? 'Chưa cập nhật') ?></span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Địa chỉ liên hệ:</span>
              <span class="fw-semibold text-dark text-end ms-2" style="max-width: 220px;"><?= e($candidateDetails['address'] ?: 'Chưa cập nhật') ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Card: Thông tin Thẻ dự thi & Phòng thi -->
      <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold text-dark fs-6">
            <i class="fas fa-ticket-alt text-success me-2"></i>Thông tin Dự thi
          </h5>
          <a href="<?= e(url('/exam/card.php')) ?>" class="btn btn-outline-success btn-sm rounded-pill fw-semibold py-1 px-3" style="font-size: 12px;">
            <i class="fas fa-print me-1"></i>Xem thẻ
          </a>
        </div>
        <div class="card-body p-4">
          <?php if (!empty($candidateDetails['exam_number']) || !empty($examCard)): ?>
            <div class="d-flex flex-column gap-3 small">
              <div class="d-flex justify-content-between pb-2 border-bottom">
                <span class="text-muted">Số báo danh (SBD):</span>
                <span class="font-monospace fw-bold fs-6 text-primary"><?= e($candidateDetails['exam_number'] ?? ($examCard['exam_number'] ?? '---')) ?></span>
              </div>
              <div class="d-flex justify-content-between pb-2 border-bottom">
                <span class="text-muted">Điểm thi:</span>
                <span class="fw-semibold text-dark text-end"><?= e($candidateDetails['exam_site_name'] ?? ($examCard['exam_site_name'] ?? 'Chưa bố trí')) ?></span>
              </div>
              <div class="d-flex justify-content-between pb-2 border-bottom">
                <span class="text-muted">Phòng thi:</span>
                <span class="fw-semibold text-dark text-end"><?= e($candidateDetails['exam_room_name'] ?? ($examCard['exam_room_name'] ?? 'Chưa bố trí')) ?></span>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Lịch thi:</span>
                <span class="fw-semibold text-dark text-end"><?= e($candidateDetails['exam_schedule'] ?? ($examCard['exam_schedule'] ?? 'Theo lịch Sở GD&ĐT')) ?></span>
              </div>
            </div>
          <?php else: ?>
            <div class="text-center py-3 text-muted small">
              <i class="fas fa-clock fa-2x text-slate-300 mb-2 d-block"></i>
              Hội đồng thi đang tiến hành phân bổ số báo danh và phòng thi. Vui lòng quay lại kiểm tra sau.
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- Cột phải: Nguyện vọng, Điểm thi & Lịch sử điều chỉnh -->
    <div class="col-lg-7">

      <!-- Card: Danh sách Nguyện vọng -->
      <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold text-dark fs-6">
            <i class="fas fa-list-check text-primary me-2"></i>Nguyện vọng đã đăng ký
          </h5>
          <a href="<?= e(url('/aspirations/index.php')) ?>" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold py-1 px-3" style="font-size: 12px;">
            <i class="fas fa-cog me-1"></i>Quản lý NV
          </a>
        </div>
        <div class="card-body p-0">
          <?php if (!empty($aspirations)): ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                  <tr>
                    <th class="text-center" style="width: 100px;">Thứ tự</th>
                    <th>Trường THPT</th>
                    <th class="text-center" style="width: 120px;">Mã trường</th>
                    <th>Kỳ tuyển sinh</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($aspirations as $asp): ?>
                    <tr>
                      <td class="text-center">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-2.5 py-1 rounded-pill fw-bold">
                          NV <?= (int)$asp['priority_order'] ?>
                        </span>
                      </td>
                      <td class="fw-semibold text-dark"><?= e($asp['school_name']) ?></td>
                      <td class="text-center font-monospace text-muted"><?= e($asp['school_code']) ?></td>
                      <td class="text-muted small"><?= e($asp['period_name'] ?? '') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center py-4 text-muted small">
              <i class="fas fa-clipboard-list fa-2x text-slate-300 mb-2 d-block"></i>
              Bạn chưa đăng ký nguyện vọng xét tuyển nào.
              <div class="mt-2">
                <a href="<?= e(url('/aspirations/index.php')) ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                  Đăng ký nguyện vọng ngay
                </a>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Card: Kết quả xét tuyển & Điểm thi -->
      <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold text-dark fs-6">
            <i class="fas fa-graduation-cap text-warning me-2"></i>Điểm thi & Kết quả xét tuyển
          </h5>
          <div class="d-flex gap-2">
            <a href="<?= e(url('/results/exam.php')) ?>" class="btn btn-outline-secondary btn-sm rounded-pill fw-semibold py-1 px-3" style="font-size: 12px;">
              Xem điểm thi
            </a>
            <a href="<?= e(url('/admission/results.php')) ?>" class="btn btn-primary btn-sm rounded-pill fw-semibold py-1 px-3" style="font-size: 12px;">
              Xem kết quả
            </a>
          </div>
        </div>
        <div class="card-body p-4">
          <?php if (!empty($admissionResults)): ?>
            <div class="table-responsive mb-3">
              <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                  <tr>
                    <th class="text-center">NV</th>
                    <th>Trường THPT</th>
                    <th class="text-center">Điểm XT</th>
                    <th class="text-center">Kết quả</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($admissionResults as $res): ?>
                    <?php $isAdmitted = ($res['result_status'] === 'admitted'); ?>
                    <tr class="<?= $isAdmitted ? 'table-success bg-opacity-25' : '' ?>">
                      <td class="text-center fw-bold">NV <?= (int)$res['priority_order'] ?></td>
                      <td class="fw-semibold"><?= e(($res['school_code'] ?? '') . ' - ' . ($res['school_name'] ?? '')) ?></td>
                      <td class="text-center fw-bold text-primary font-monospace">
                        <?= is_numeric($res['admission_score']) ? number_format((float)$res['admission_score'], 2) : e((string)$res['admission_score']) ?>
                      </td>
                      <td class="text-center">
                        <?php if ($isAdmitted): ?>
                          <span class="badge bg-success px-2.5 py-1 rounded-pill fw-bold">
                            <i class="fas fa-check me-1"></i>Trúng tuyển
                          </span>
                        <?php else: ?>
                          <span class="badge bg-light text-secondary border px-2.5 py-1 rounded-pill">
                            Không trúng tuyển
                          </span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <!-- Confirm Enrollment button if admitted -->
            <?php 
              $hasAdmitted = false;
              foreach ($admissionResults as $res) {
                if ($res['result_status'] === 'admitted') {
                  $hasAdmitted = true;
                  break;
                }
              }
            ?>
            <?php if ($hasAdmitted): ?>
              <div class="p-3 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                  <div class="fw-bold text-success small"><i class="fas fa-award me-1"></i>Chúc mừng! Bạn đã trúng tuyển nguyện vọng.</div>
                  <div class="text-muted small">Vui lòng hoàn tất xác nhận nhập học trực tuyến theo quy định.</div>
                </div>
                <a href="<?= e(url('/enrollment/confirm.php')) ?>" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                  Xác nhận nhập học ngay
                </a>
              </div>
            <?php endif; ?>

          <?php elseif (!empty($examScores)): ?>
            <h6 class="fw-bold text-secondary small mb-2">Bảng điểm thi đã công bố:</h6>
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle mb-0 text-center small">
                <thead class="table-light">
                  <tr>
                    <?php foreach ($examScores as $sc): ?>
                      <th><?= e($sc['subject_name']) ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <tr class="fw-bold font-monospace text-primary fs-6">
                    <?php foreach ($examScores as $sc): ?>
                      <td><?= is_numeric($sc['total_score']) ? number_format((float)$sc['total_score'], 2) : e((string)$sc['total_score']) ?></td>
                    <?php endforeach; ?>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="text-end mt-2">
              <a href="<?= e(url('/appeals/register.php')) ?>" class="text-muted small text-decoration-none hover:text-primary">
                <i class="fas fa-file-signature me-1"></i>Đăng ký phúc khảo môn thi
              </a>
            </div>
          <?php else: ?>
            <div class="text-center py-3 text-muted small">
              <i class="fas fa-hourglass-half fa-2x text-slate-300 mb-2 d-block"></i>
              Điểm thi và kết quả xét tuyển chính thức chưa được công bố. Hội đồng tuyển sinh sẽ cập nhật ngay khi hoàn tất chấm thi.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Card: Lịch sử yêu cầu điều chỉnh hồ sơ -->
      <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold text-dark fs-6">
            <i class="fas fa-history text-info me-2"></i>Yêu cầu điều chỉnh hồ sơ
          </h5>
          <a href="<?= e(url('/adjustments/create.php')) ?>" class="btn btn-outline-info btn-sm rounded-pill fw-semibold py-1 px-3" style="font-size: 12px;">
            <i class="fas fa-plus me-1"></i>Gửi yêu cầu mới
          </a>
        </div>
        <div class="card-body p-0">
          <?php if (!empty($adjustmentRequests)): ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                  <tr>
                    <th style="width: 80px;">Mã</th>
                    <th>Lý do điều chỉnh</th>
                    <th class="text-center" style="width: 130px;">Trạng thái</th>
                    <th style="width: 140px;">Ngày gửi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($adjustmentRequests as $adj): ?>
                    <tr>
                      <td class="font-monospace fw-bold text-muted">#<?= (int)$adj['id'] ?></td>
                      <td>
                        <div class="text-dark fw-medium"><?= e($adj['reason']) ?></div>
                        <?php if (!empty($adj['review_reason'])): ?>
                          <div class="small text-muted mt-1">
                            <em>Phản hồi: <?= e($adj['review_reason']) ?></em>
                          </div>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <?php if ($adj['status'] === 'approved'): ?>
                          <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill fw-semibold">
                            Đã duyệt
                          </span>
                        <?php elseif ($adj['status'] === 'rejected'): ?>
                          <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1 rounded-pill fw-semibold">
                            Từ chối
                          </span>
                        <?php else: ?>
                          <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2.5 py-1 rounded-pill fw-semibold">
                            Chờ duyệt
                          </span>
                        <?php endif; ?>
                      </td>
                      <td class="text-muted small">
                        <?= !empty($adj['created_at']) ? date('d/m/Y H:i', strtotime($adj['created_at'])) : '---' ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center py-4 text-muted small">
              <i class="fas fa-check-circle fa-2x text-slate-300 mb-2 d-block"></i>
              Hồ sơ của bạn chưa có yêu cầu điều chỉnh nào đang xử lý.
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

</main>

<?php 
require __DIR__ . '/../../includes/footer.php';
require __DIR__ . '/../../includes/scripts.php';
?>
</body>
</html>
