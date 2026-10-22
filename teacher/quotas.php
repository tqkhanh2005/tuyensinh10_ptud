<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_helper.php';
require_once __DIR__ . '/../includes/teacher_layout.php';

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

$where = ['s.level = "THPT"', 's.active = 1'];
$params = [];

if ($q !== '') {
    $where[] = '(s.name LIKE :q OR s.school_code LIKE :q OR s.admission_area LIKE :q)';
    $params['q'] = '%' . $q . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT s.id, s.school_code, s.name, s.admission_area, s.district,
           COALESCE(SUM(aq.quota), 0) AS total_quota
    FROM schools s
    LEFT JOIN admission_quotas aq ON aq.school_id = s.id
    $whereSql
    GROUP BY s.id
    ORDER BY s.name ASC
";

$quotas = [];
$totalAllQuota = 0;
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $quotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($quotas as $row) {
        $totalAllQuota += (int)$row['total_quota'];
    }
} catch (Throwable $e) {
    error_log('Teacher Quotas Error: ' . $e->getMessage());
}

teacher_layout_start('Chỉ tiêu tuyển sinh THPT', 'quotas', ['Chỉ tiêu tuyển sinh' => '']);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="h4 fw-bold text-dark mb-1">
      <i class="fas fa-bullseye text-success me-2"></i>Chỉ Tiêu Tuyển Sinh Lớp 10 Các Trường THPT
    </h1>
    <p class="text-muted small mb-0">
      Bảng phân bổ chỉ tiêu tuyển sinh chính thức do Sở Giáo dục và Đào tạo phê duyệt.
    </p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-1.5 rounded-pill">
      Tổng chỉ tiêu toàn thành phố: <?= number_format($totalAllQuota) ?> học sinh
    </span>
  </div>
</div>

<!-- Bộ lọc tìm kiếm trường -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
  <div class="card-body p-3 p-md-4">
    <form method="GET" action="<?= e(url('/teacher/quotas.php')) ?>" class="row g-3 align-items-end">
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
        <a href="<?= e(url('/teacher/quotas.php')) ?>" class="btn btn-outline-secondary btn-sm rounded-pill" title="Làm mới">
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
              Không tìm thấy trường THPT phù hợp với từ khóa.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
teacher_layout_end();
