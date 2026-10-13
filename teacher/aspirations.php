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
$filterNv = filter_input(INPUT_GET, 'nv', FILTER_VALIDATE_INT) ?: 0;

$where = [];
$params = [];

if ($schoolLevel === 'THPT' && $schoolId > 0) {
    // Trường THPT: xem thí sinh đăng ký vào trường mình
    $where[] = 'a.school_id = :school_id';
    $params['school_id'] = $schoolId;
} elseif ($schoolLevel === 'THCS' && $schoolId > 0) {
    // Trường THCS: xem nguyện vọng của học sinh trường mình
    $where[] = 'c.middle_school_id = :school_id';
    $params['school_id'] = $schoolId;
} else {
    $where[] = '1 = 0';
}

if ($q !== '') {
    $where[] = '(c.full_name LIKE :q OR c.national_id LIKE :q OR c.candidate_code LIKE :q OR c.exam_number LIKE :q)';
    $params['q'] = '%' . $q . '%';
}

if ($filterNv > 0) {
    $where[] = 'a.priority_order = :nv';
    $params['nv'] = $filterNv;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT a.*, c.full_name, c.national_id, c.candidate_code, c.exam_number, c.date_of_birth, c.gender,
           ms.name AS middle_school_name,
           hs.name AS target_school_name, hs.school_code AS target_school_code
    FROM aspirations a
    JOIN candidates c ON c.id = a.candidate_id
    LEFT JOIN schools ms ON ms.id = c.middle_school_id
    LEFT JOIN schools hs ON hs.id = a.school_id
    $whereSql
    ORDER BY a.priority_order ASC, c.full_name ASC
    LIMIT 100
";

$aspirations = [];
$nvStats = [1 => 0, 2 => 0, 3 => 0, 'total' => 0];
$quota = 0;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $aspirations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tính thống kê theo NV
    if ($schoolLevel === 'THPT' && $schoolId > 0) {
        $st = $pdo->prepare('
            SELECT priority_order, COUNT(*) as cnt 
            FROM aspirations 
            WHERE school_id = :sid 
            GROUP BY priority_order
        ');
        $st->execute(['sid' => $schoolId]);
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $po = (int)$r['priority_order'];
            $nvStats[$po] = (int)$r['cnt'];
            $nvStats['total'] += (int)$r['cnt'];
        }

        $qStmt = $pdo->prepare('SELECT COALESCE(SUM(quota), 0) FROM admission_quotas WHERE school_id = :sid');
        $qStmt->execute(['sid' => $schoolId]);
        $quota = (int)$qStmt->fetchColumn();
    } elseif ($schoolLevel === 'THCS' && $schoolId > 0) {
        $st = $pdo->prepare('
            SELECT a.priority_order, COUNT(*) as cnt 
            FROM aspirations a 
            JOIN candidates c ON c.id = a.candidate_id 
            WHERE c.middle_school_id = :sid 
            GROUP BY a.priority_order
        ');
        $st->execute(['sid' => $schoolId]);
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $po = (int)$r['priority_order'];
            $nvStats[$po] = (int)$r['cnt'];
            $nvStats['total'] += (int)$r['cnt'];
        }
    }
} catch (Throwable $e) {
    error_log('Teacher Aspirations Error: ' . $e->getMessage());
}

teacher_layout_start('Theo dõi Nguyện vọng đăng ký', 'aspirations', ['Nguyện vọng' => '']);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="h4 fw-bold text-dark mb-1">
      <i class="fas fa-list-ol text-info me-2"></i>Theo dõi Nguyện vọng Đăng ký
    </h1>
    <p class="text-muted small mb-0">
      <?php if ($schoolLevel === 'THPT'): ?>
        Số liệu thí sinh đăng ký các nguyện vọng xét tuyển vào trường <strong><?= e($schoolName) ?></strong>.
      <?php elseif ($schoolLevel === 'THCS'): ?>
        Danh sách nguyện vọng vào lớp 10 của học sinh thuộc <strong><?= e($schoolName) ?></strong>.
      <?php else: ?>
        Danh sách nguyện vọng tuyển sinh vào lớp 10 trên toàn hệ thống.
      <?php endif; ?>
    </p>
  </div>
</div>

<!-- Thống kê tổng hợp -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
      <div class="small text-muted fw-semibold mb-1">Tổng lượt nguyện vọng</div>
      <div class="h3 fw-bold text-dark mb-0"><?= number_format($nvStats['total']) ?></div>
      <div class="small text-muted">Đăng ký vào hệ thống</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-primary border-4">
      <div class="small text-muted fw-semibold mb-1">Nguyện vọng 1 (NV1)</div>
      <div class="h3 fw-bold text-primary mb-0"><?= number_format($nvStats[1] ?? 0) ?></div>
      <div class="small text-muted">Ưu tiên cao nhất</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-info border-4">
      <div class="small text-muted fw-semibold mb-1">Nguyện vọng 2 (NV2)</div>
      <div class="h3 fw-bold text-info mb-0"><?= number_format($nvStats[2] ?? 0) ?></div>
      <div class="small text-muted">Ưu tiên kế tiếp</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-warning border-4">
      <div class="small text-muted fw-semibold mb-1">
        <?= $schoolLevel === 'THPT' ? 'Chỉ tiêu tuyển sinh' : 'Nguyện vọng 3 (NV3)' ?>
      </div>
      <div class="h3 fw-bold text-dark mb-0">
        <?= $schoolLevel === 'THPT' ? number_format($quota) : number_format($nvStats[3] ?? 0) ?>
      </div>
      <div class="small text-muted">
        <?= $schoolLevel === 'THPT' ? 'Chỉ tiêu được giao' : 'Nguyện vọng bổ sung' ?>
      </div>
    </div>
  </div>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
  <div class="card-body p-3 p-md-4">
    <form method="GET" action="<?= e(url('/teacher/aspirations.php')) ?>" class="row g-3 align-items-end">
      <div class="col-md-6">
        <label class="form-label small fw-bold text-muted mb-1">Tìm kiếm thí sinh</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light border-end-0 text-muted">
            <i class="fas fa-search"></i>
          </span>
          <input type="text" name="q" class="form-control border-start-0" 
                 placeholder="Nhập họ tên, CCCD/CMND hoặc SBD..." value="<?= e($q) ?>">
        </div>
      </div>

      <div class="col-md-4">
        <label class="form-label small fw-bold text-muted mb-1">Thứ tự nguyện vọng</label>
        <select name="nv" class="form-select form-select-sm">
          <option value="">Tất cả nguyện vọng (NV1, NV2, NV3)</option>
          <option value="1" <?= $filterNv === 1 ? 'selected' : '' ?>>Nguyện vọng 1</option>
          <option value="2" <?= $filterNv === 2 ? 'selected' : '' ?>>Nguyện vọng 2</option>
          <option value="3" <?= $filterNv === 3 ? 'selected' : '' ?>>Nguyện vọng 3</option>
        </select>
      </div>

      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-info text-white btn-sm w-100 fw-semibold rounded-pill">
          <i class="fas fa-filter me-1"></i>Lọc
        </button>
        <a href="<?= e(url('/teacher/aspirations.php')) ?>" class="btn btn-outline-secondary btn-sm rounded-pill" title="Làm mới">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Bảng nguyện vọng -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light small">
        <tr>
          <th style="white-space: nowrap;">Thứ tự</th>
          <th style="white-space: nowrap;">SBD / CCCD</th>
          <th style="white-space: nowrap;">Họ và tên thí sinh</th>
          <th style="white-space: nowrap;">Ngày sinh</th>
          <th style="white-space: nowrap;">Giới tính</th>
          <?php if ($schoolLevel === 'THPT'): ?>
            <th style="white-space: nowrap;">Trường THCS gốc</th>
          <?php else: ?>
            <th style="white-space: nowrap;">Trường THPT đăng ký</th>
          <?php endif; ?>
          <th style="white-space: nowrap;" class="text-end">Thời gian ghi nhận</th>
        </tr>
      </thead>
      <tbody class="small">
        <?php foreach ($aspirations as $a): ?>
          <tr>
            <td style="white-space: nowrap;">
              <span class="badge <?= $a['priority_order'] == 1 ? 'bg-primary' : ($a['priority_order'] == 2 ? 'bg-info' : 'bg-secondary') ?> rounded-pill px-2.5 py-1">
                NV<?= (int)$a['priority_order'] ?>
              </span>
            </td>
            <td style="white-space: nowrap;">
              <?php if (!empty($a['exam_number']) || !empty($a['candidate_code'])): ?>
                <div class="fw-bold font-monospace text-primary"><?= e($a['exam_number'] ?: $a['candidate_code']) ?></div>
              <?php endif; ?>
              <div class="text-muted font-monospace small"><?= e($a['national_id']) ?></div>
            </td>
            <td>
              <div class="fw-bold text-dark"><?= e($a['full_name']) ?></div>
            </td>
            <td style="white-space: nowrap;" class="text-muted">
              <?= e($a['date_of_birth'] ?? '—') ?>
            </td>
            <td style="white-space: nowrap;" class="text-muted">
              <?= e($a['gender'] ?? '—') ?>
            </td>
            <td>
              <?php if ($schoolLevel === 'THPT'): ?>
                <span class="text-dark"><?= e($a['middle_school_name'] ?? 'Chưa cập nhật') ?></span>
              <?php else: ?>
                <span class="text-primary fw-semibold"><?= e($a['target_school_name'] ?? 'Chưa cập nhật') ?></span>
              <?php endif; ?>
            </td>
            <td class="text-end text-muted small" style="white-space: nowrap;">
              <?= e($a['created_at']) ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($aspirations)): ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <i class="fas fa-inbox fs-2 text-muted mb-2 d-block"></i>
              Chưa có dữ liệu nguyện vọng nào phù hợp.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
teacher_layout_end();
