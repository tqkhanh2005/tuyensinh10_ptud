<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_helper.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/admission_readiness_service.php';

require_auth();

// Chỉ Admin hoặc Sở GD&ĐT được truy cập
if (!auth_is_admin()) {
    http_response_code(403);
    exit('403 Forbidden - Khu vực chỉ dành cho Quản trị viên và Sở GD&ĐT.');
}

$pdo = db();
$readiness = admission_readiness($pdo);
$stats = [
    'candidates' => 0,
    'schools_thcs' => 0,
    'schools_thpt' => 0,
    'staff' => 0,
    'councils' => 0,
    'exam_rooms' => 0,
    'adjustments_pending' => 0,
    'total_quota' => 0,
];

try {
    $stats['candidates'] = (int)$pdo->query('SELECT COUNT(*) FROM candidates WHERE status = "active"')->fetchColumn();
    $stats['schools_thcs'] = (int)$pdo->query('SELECT COUNT(*) FROM schools WHERE level = "THCS" AND active = 1')->fetchColumn();
    $stats['schools_thpt'] = (int)$pdo->query('SELECT COUNT(*) FROM schools WHERE level = "THPT" AND active = 1')->fetchColumn();
    $stats['staff'] = (int)$pdo->query('SELECT COUNT(*) FROM staff WHERE active = 1')->fetchColumn();
    $stats['councils'] = (int)$pdo->query('SELECT COUNT(*) FROM councils')->fetchColumn();
    $stats['exam_rooms'] = (int)$pdo->query('SELECT COUNT(*) FROM exam_rooms')->fetchColumn();
    $stats['adjustments_pending'] = (int)$pdo->query('SELECT COUNT(*) FROM adjustment_requests WHERE status = "pending"')->fetchColumn();
    $stats['total_quota'] = (int)$pdo->query('SELECT COALESCE(SUM(quota), 0) FROM admission_quotas')->fetchColumn();
} catch (Throwable $e) {
    error_log('Admin Dashboard Stats Error: ' . $e->getMessage());
}

// Lấy 5 thí sinh mới nhất
$recentCandidates = [];
try {
    $cStmt = $pdo->query('
        SELECT c.id, c.national_id, c.full_name, c.eligible, c.status, s.name AS school_name
        FROM candidates c
        LEFT JOIN schools s ON s.id = c.middle_school_id
        ORDER BY c.id DESC
        LIMIT 5
    ');
    $recentCandidates = $cStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Recent Candidates Error: ' . $e->getMessage());
}

admin_layout_start('Tổng quan Quản trị', 'admin-dashboard');
?>

<!-- Welcome Banner -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white overflow-hidden" style="background: linear-gradient(135deg, #0253cd 0%, #0d2a52 100%);">
  <div class="card-body p-4 p-md-5 position-relative">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <span class="badge bg-white text-primary text-wrap mw-100 lh-base px-3 py-1.5 rounded-pill mb-3 fw-semibold">
          <i class="fas fa-university me-1.5"></i> CỔNG ĐIỀU HÀNH SỞ GIÁO DỤC VÀ ĐÀO TẠO
        </span>
        <h2 class="h3 fw-bold mb-2">Hệ thống Quản lý Tuyển sinh Lớp 10</h2>
        <p class="mb-0 text-white-50 fs-6">
          Giám sát hồ sơ thí sinh, trường học, tổ chức thi và xét tuyển<?= $readiness['period'] ? ' năm học ' . e($readiness['period']['academic_year']) : '. Chưa thiết lập kỳ tuyển sinh' ?>.
        </p>
      </div>
      <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
        <a href="<?= e(url('/candidates/import.php')) ?>" class="btn btn-light fw-bold px-4 py-2.5 rounded-pill shadow-sm me-2">
          <i class="fas fa-file-import text-primary me-1.5"></i> Import Thí sinh
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Stats Row -->
<div class="row g-3 g-md-4 mb-4">
  <!-- Thí sinh -->
  <div class="col-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
      <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="text-muted fw-semibold small">Thí sinh hoạt động</span>
          <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="fas fa-user-graduate fs-5"></i>
          </div>
        </div>
        <div class="h3 fw-bold text-dark mb-1"><?= number_format($stats['candidates']) ?></div>
        <div class="small text-muted">
          <a href="<?= e(url('/candidates/index.php')) ?>" class="text-decoration-none text-primary fw-semibold">
            Xem danh sách <i class="fas fa-arrow-right ms-1 small"></i>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Trường học -->
  <div class="col-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
      <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="text-muted fw-semibold small">Mạng lưới trường</span>
          <div class="rounded-3 bg-success bg-opacity-10 text-success p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="fas fa-school fs-5"></i>
          </div>
        </div>
        <div class="h3 fw-bold text-dark mb-1">
          <?= number_format($stats['schools_thcs'] + $stats['schools_thpt']) ?>
        </div>
        <div class="small text-muted">
          <span><?= $stats['schools_thcs'] ?> THCS</span> &bull; <span><?= $stats['schools_thpt'] ?> THPT</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Cán bộ - Giáo viên -->
  <div class="col-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
      <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="text-muted fw-semibold small">Cán bộ - Giáo viên</span>
          <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="fas fa-chalkboard-teacher fs-5"></i>
          </div>
        </div>
        <div class="h3 fw-bold text-dark mb-1"><?= number_format($stats['staff']) ?></div>
        <div class="small text-muted">
          <a href="<?= e(url('/staff/index.php')) ?>" class="text-decoration-none text-warning fw-semibold">
            Quản lý cán bộ <i class="fas fa-arrow-right ms-1 small"></i>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Hội đồng & Phòng thi -->
  <div class="col-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
      <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="text-muted fw-semibold small">Hội đồng thi</span>
          <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="fas fa-clipboard-list fs-5"></i>
          </div>
        </div>
        <div class="h3 fw-bold text-dark mb-1"><?= number_format($stats['councils']) ?></div>
        <div class="small text-muted">
          <span><?= $stats['exam_rooms'] ?> phòng thi đã lập</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Quick Navigation & Modules -->
<div class="row g-4 mb-4">
  <!-- Nhóm tác vụ chính -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
      <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
        <h5 class="h6 fw-bold mb-0 text-dark">
          <i class="fas fa-th-large text-primary me-2"></i>Các phân hệ nghiệp vụ chính
        </h5>
        <span class="badge bg-light text-secondary rounded-pill small">Quản lý toàn diện</span>
      </div>
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-6 col-xl-4">
            <a href="<?= e(url('/candidates/index.php')) ?>" class="card p-3 border rounded-3 text-decoration-none text-dark h-100 hover-shadow transition">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2.5">
                  <i class="fas fa-users fs-5"></i>
                </div>
                <div>
                  <div class="fw-bold">Quản lý thí sinh</div>
                  <div class="small text-muted">Hồ sơ, điều kiện, import</div>
                </div>
              </div>
            </a>
          </div>

          <div class="col-md-6 col-xl-4">
            <a href="<?= e(url('/schools/index.php')) ?>" class="card p-3 border rounded-3 text-decoration-none text-dark h-100 hover-shadow transition">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 text-success p-2.5">
                  <i class="fas fa-school fs-5"></i>
                </div>
                <div>
                  <div class="fw-bold">Trường học</div>
                  <div class="small text-muted">Trường THCS, THPT, mã trường</div>
                </div>
              </div>
            </a>
          </div>

          <div class="col-md-6 col-xl-4">
            <a href="<?= e(url('/staff/index.php')) ?>" class="card p-3 border rounded-3 text-decoration-none text-dark h-100 hover-shadow transition">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-2.5">
                  <i class="fas fa-user-tie fs-5"></i>
                </div>
                <div>
                  <div class="fw-bold">Giáo viên & Cán bộ</div>
                  <div class="small text-muted">Danh sách, phân công, vai trò</div>
                </div>
              </div>
            </a>
          </div>

          <div class="col-md-6 col-xl-4">
            <a href="<?= e(url('/councils/index.php')) ?>" class="card p-3 border rounded-3 text-decoration-none text-dark h-100 hover-shadow transition">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 text-info p-2.5">
                  <i class="fas fa-award fs-5"></i>
                </div>
                <div>
                  <div class="fw-bold">Hội đồng & Kỳ thi</div>
                  <div class="small text-muted">Thành lập HĐ, phòng thi</div>
                </div>
              </div>
            </a>
          </div>

          <div class="col-md-6 col-xl-4">
            <a href="<?= e(url('/exam/scores.php')) ?>" class="card p-3 border rounded-3 text-decoration-none text-dark h-100 hover-shadow transition">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-2.5">
                  <i class="fas fa-edit fs-5"></i>
                </div>
                <div>
                  <div class="fw-bold">Nhập điểm thi</div>
                  <div class="small text-muted">Vào điểm, tra cứu bảng điểm</div>
                </div>
              </div>
            </a>
          </div>

          <div class="col-md-6 col-xl-4">
            <a href="<?= e(url('/admission/index.php')) ?>" class="card p-3 border rounded-3 text-decoration-none text-dark h-100 hover-shadow transition">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2.5" style="background-color: rgba(99, 102, 241, 0.1) !important; color: #6366f1 !important;">
                  <i class="fas fa-check-double fs-5"></i>
                </div>
                <div>
                  <div class="fw-bold">Xét tuyển & Kết quả</div>
                  <div class="small text-muted">Chạy xét tuyển, trúng tuyển</div>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Danh sách thí sinh đăng ký gần đây -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
      <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
        <h5 class="h6 fw-bold mb-0 text-dark">
          <i class="fas fa-user-clock text-primary me-2"></i>Thí sinh mới đăng ký gần đây
        </h5>
        <a href="<?= e(url('/candidates/index.php')) ?>" class="small text-primary text-decoration-none fw-semibold">
          Xem tất cả <i class="fas fa-angle-right ms-1"></i>
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>CCCD/CMND</th>
              <th>Họ và tên</th>
              <th>Trường THCS</th>
              <th class="text-center">Trạng thái</th>
              <th class="text-end">Thao tác</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($recentCandidates as $rc): ?>
              <tr>
                <td class="font-monospace fw-semibold"><?= e($rc['national_id']) ?></td>
                <td class="fw-semibold text-dark"><?= e($rc['full_name']) ?></td>
                <td class="text-muted"><?= e($rc['school_name'] ?? 'Chưa cập nhật') ?></td>
                <td class="text-center">
                  <?php if (($rc['status'] ?? '') === 'active'): ?>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1">Đang hoạt động</span>
                  <?php else: ?>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2.5 py-1">Tạm ngưng</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <a href="<?= e(url('/candidates/edit.php?id=' . (int)$rc['id'])) ?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                    Chi tiết
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$recentCandidates): ?>
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">Chưa cập nhật dữ liệu thí sinh.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Cột trạng thái & Phím tắt -->
  <div class="col-lg-4">
    <!-- Phím tắt nhanh -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
      <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-bolt text-warning me-2"></i>Thao tác nhanh</h6>
      </div>
      <div class="card-body p-3">
        <div class="d-grid gap-2">
          <a href="<?= e(url('/candidates/create.php')) ?>" class="btn btn-outline-primary text-start d-flex align-items-center justify-content-between p-2.5 rounded-3">
            <span><i class="fas fa-user-plus me-2 text-primary"></i>Thêm hồ sơ thí sinh</span>
            <i class="fas fa-chevron-right small text-muted"></i>
          </a>
          <a href="<?= e(url('/candidates/import.php')) ?>" class="btn btn-outline-success text-start d-flex align-items-center justify-content-between p-2.5 rounded-3">
            <span><i class="fas fa-file-excel me-2 text-success"></i>Import danh sách Excel/CSV</span>
            <i class="fas fa-chevron-right small text-muted"></i>
          </a>
          <a href="<?= e(url('/staff/import.php')) ?>" class="btn btn-outline-warning text-start d-flex align-items-center justify-content-between p-2.5 rounded-3 text-dark">
            <span><i class="fas fa-users-cog me-2 text-warning"></i>Import cán bộ - giáo viên</span>
            <i class="fas fa-chevron-right small text-muted"></i>
          </a>
          <a href="<?= e(url('/councils/create.php')) ?>" class="btn btn-outline-info text-start d-flex align-items-center justify-content-between p-2.5 rounded-3">
            <span><i class="fas fa-plus-circle me-2 text-info"></i>Thành lập hội đồng mới</span>
            <i class="fas fa-chevron-right small text-muted"></i>
          </a>
          <a href="<?= e(url('/statistics/index.php')) ?>" class="btn btn-outline-secondary text-start d-flex align-items-center justify-content-between p-2.5 rounded-3">
            <span><i class="fas fa-file-invoice text-secondary me-2"></i>Xuất báo cáo thống kê</span>
            <i class="fas fa-chevron-right small text-muted"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Thông tin kỳ thi -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
      <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-info-circle text-info me-2"></i>Tiến độ kỳ thi Tuyển sinh 10</h6>
      </div>
      <div class="card-body p-4 small">
        <p class="text-muted">Các mục có dữ liệu được đánh dấu bên dưới. Số lượng không thay thế việc kiểm tra đầy đủ điều kiện nghiệp vụ.</p>
        <ul class="list-unstyled mb-0 d-grid gap-3">
          <?php foreach ($readiness['steps'] as $step): ?>
            <li class="d-flex gap-3">
              <i class="fas <?= $step['available'] ? 'fa-check-circle text-success' : 'fa-circle text-secondary' ?> fs-5 mt-1" aria-hidden="true"></i>
              <div class="flex-grow-1" style="min-width:0;overflow-wrap:anywhere">
                <div class="fw-bold text-dark"><?= e($step['label']) ?></div>
                <div class="text-muted"><?= e($step['detail']) ?></div>
                <?php if (auth_has_permission($step['permission'])): ?>
                  <a href="<?= e(url($step['path'])) ?>" class="small">Mở <?= e(mb_strtolower($step['label'])) ?></a>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php
admin_layout_end();
