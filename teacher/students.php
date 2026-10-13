<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_helper.php';
require_once __DIR__ . '/../includes/teacher_layout.php';

require_auth();

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
$filterEligible = trim((string)($_GET['eligible'] ?? ''));
$filterStatus = trim((string)($_GET['status'] ?? ''));

$params = [];
$where = [];

if ($schoolLevel === 'THCS' && $schoolId > 0) {
    $where[] = 'c.middle_school_id = :school_id';
    $params['school_id'] = $schoolId;
} elseif ($schoolLevel === 'THPT' && $schoolId > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM aspirations a WHERE a.candidate_id = c.id AND a.school_id = :school_id)';
    $params['school_id'] = $schoolId;
} else {
    $where[] = '1 = 0';
}

if ($q !== '') {
    $where[] = '(c.full_name LIKE :q OR c.national_id LIKE :q OR c.candidate_code LIKE :q OR c.exam_number LIKE :q)';
    $params['q'] = '%' . $q . '%';
}

if ($filterEligible !== '') {
    $where[] = 'c.eligible = :eligible';
    $params['eligible'] = (int)$filterEligible;
}

if ($filterStatus !== '') {
    $where[] = 'c.status = :status';
    $params['status'] = $filterStatus;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT c.*, s.name AS middle_school_name
    FROM candidates c
    LEFT JOIN schools s ON s.id = c.middle_school_id
    $whereSql
    ORDER BY c.id DESC
    LIMIT 100
";

$students = [];
$totalCount = 0;
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countSql = "SELECT COUNT(*) FROM candidates c $whereSql";
    $cStmt = $pdo->prepare($countSql);
    $cStmt->execute($params);
    $totalCount = (int)$cStmt->fetchColumn();
} catch (Throwable $e) {
    error_log('Teacher Students Error: ' . $e->getMessage());
}

teacher_layout_start('Quản lý thí sinh / Học sinh', 'candidates', ['Quản lý thí sinh' => '']);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="h4 fw-bold text-dark mb-1">
      <i class="fas fa-user-graduate text-success me-2"></i>Quản lý Thí sinh / Học sinh
    </h1>
    <p class="text-muted small mb-0">
      <?php if ($schoolLevel === 'THCS'): ?>
        Danh sách học sinh lớp 9 thuộc <strong><?= e($schoolName) ?></strong> tham gia kỳ thi tuyển sinh vào lớp 10.
      <?php elseif ($schoolLevel === 'THPT'): ?>
        Danh sách thí sinh có đăng ký nguyện vọng dự tuyển vào <strong><?= e($schoolName) ?></strong>.
      <?php else: ?>
        Danh sách thí sinh trên hệ thống tuyển sinh lớp 10.
      <?php endif; ?>
    </p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <a href="<?= e(url('/teacher/adjustments.php')) ?>" class="btn btn-outline-warning btn-sm rounded-pill fw-semibold">
      <i class="fas fa-user-edit me-1"></i>Yêu cầu điều chỉnh hồ sơ
    </a>
  </div>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
  <div class="card-body p-3 p-md-4">
    <form method="GET" action="<?= e(url('/teacher/students.php')) ?>" class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label small fw-bold text-muted mb-1">Từ khóa tìm kiếm</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light border-end-0 text-muted">
            <i class="fas fa-search"></i>
          </span>
          <input type="text" name="q" class="form-control border-start-0" 
                 placeholder="Nhập họ tên, CCCD/CMND hoặc SBD..." value="<?= e($q) ?>">
        </div>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold text-muted mb-1">Điều kiện dự thi</label>
        <select name="eligible" class="form-select form-select-sm">
          <option value="">Tất cả điều kiện</option>
          <option value="1" <?= $filterEligible === '1' ? 'selected' : '' ?>>Đủ điều kiện dự thi</option>
          <option value="0" <?= $filterEligible === '0' ? 'selected' : '' ?>>Chưa đủ điều kiện</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted mb-1">Trạng thái hồ sơ</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Tất cả</option>
          <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Hoạt động</option>
          <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Tạm khóa</option>
        </select>
      </div>

      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-success btn-sm w-100 fw-semibold rounded-pill">
          <i class="fas fa-filter me-1"></i>Lọc
        </button>
        <a href="<?= e(url('/teacher/students.php')) ?>" class="btn btn-outline-secondary btn-sm rounded-pill" title="Làm mới bộ lọc">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Bảng danh sách học sinh -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
  <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2.5 py-1 rounded-pill">
        Tổng số: <?= number_format($totalCount) ?> học sinh
      </span>
      <?php if ($schoolId > 0): ?>
        <span class="text-muted small d-none d-md-inline">&bull; Đơn vị: <strong><?= e($schoolName) ?></strong></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light small">
        <tr>
          <th style="white-space: nowrap;">SBD / CCCD</th>
          <th style="white-space: nowrap;">Họ và tên</th>
          <th style="white-space: nowrap;">Ngày sinh</th>
          <th style="white-space: nowrap;">Giới tính</th>
          <th style="white-space: nowrap;">Phòng thi</th>
          <?php if ($schoolLevel !== 'THCS'): ?>
            <th style="white-space: nowrap;">Trường THCS</th>
          <?php endif; ?>
          <th class="text-center" style="white-space: nowrap;">Điều kiện</th>
          <th class="text-center" style="white-space: nowrap;">Trạng thái</th>
          <th class="text-end" style="white-space: nowrap;">Thao tác</th>
        </tr>
      </thead>
      <tbody class="small">
        <?php foreach ($students as $s): ?>
          <tr>
            <td style="white-space: nowrap;">
              <?php if (!empty($s['exam_number']) || !empty($s['candidate_code'])): ?>
                <div class="fw-bold text-primary font-monospace"><?= e($s['exam_number'] ?: $s['candidate_code']) ?></div>
              <?php endif; ?>
              <div class="text-muted font-monospace small"><?= e($s['national_id']) ?></div>
            </td>
            <td>
              <div class="fw-bold text-dark"><?= e($s['full_name']) ?></div>
              <div class="text-muted small"><?= e($s['address'] ?? '') ?></div>
            </td>
            <td style="white-space: nowrap;" class="text-muted">
              <?= e($s['date_of_birth'] ?? '—') ?>
            </td>
            <td style="white-space: nowrap;" class="text-muted">
              <?= e($s['gender'] ?? '—') ?>
            </td>
            <td style="white-space: nowrap;">
              <span class="badge bg-light text-dark border">
                <?= e($s['exam_room_name'] ?? 'Chưa xếp phòng') ?>
              </span>
            </td>
            <?php if ($schoolLevel !== 'THCS'): ?>
              <td class="text-muted small">
                <?= e($s['middle_school_name'] ?? 'Chưa cập nhật') ?>
              </td>
            <?php endif; ?>
            <td class="text-center" style="white-space: nowrap;">
              <?php if ((int)$s['eligible'] === 1): ?>
                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1">
                  <i class="fas fa-check-circle me-1"></i>Đủ ĐK
                </span>
              <?php else: ?>
                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2.5 py-1">
                  <i class="fas fa-times-circle me-1"></i>Chưa đủ ĐK
                </span>
              <?php endif; ?>
            </td>
            <td class="text-center" style="white-space: nowrap;">
              <?php if ($s['status'] === 'active'): ?>
                <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1">Hoạt động</span>
              <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1">Tạm khóa</span>
              <?php endif; ?>
            </td>
            <td class="text-end" style="white-space: nowrap;">
              <a href="<?= e(url('/teacher/adjustments.php?candidate_id=' . (int)$s['id'])) ?>" 
                 class="btn btn-sm btn-outline-warning py-1 px-2.5 rounded-pill" title="Gửi yêu cầu điều chỉnh thông tin cho học sinh này">
                <i class="fas fa-edit me-1"></i>Báo sai sót
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($students)): ?>
          <tr>
            <td colspan="<?= $schoolLevel === 'THCS' ? 8 : 9 ?>" class="text-center py-5 text-muted">
              <i class="fas fa-inbox fs-2 text-muted mb-2 d-block"></i>
              Không tìm thấy hồ sơ học sinh nào phù hợp với bộ lọc.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
teacher_layout_end();
