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
$filterStatus = trim((string)($_GET['status'] ?? ''));

$where = [];
$params = [];

if ($schoolLevel === 'THCS' && $schoolId > 0) {
    $where[] = 'c.middle_school_id = :school_id';
    $params['school_id'] = $schoolId;
}

if ($q !== '') {
    $where[] = '(c.full_name LIKE :q OR c.national_id LIKE :q OR c.candidate_code LIKE :q OR c.exam_number LIKE :q)';
    $params['q'] = '%' . $q . '%';
}

if ($filterStatus !== '') {
    $where[] = 'ar.status = :status';
    $params['status'] = $filterStatus;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT ar.*, c.full_name, c.national_id, c.candidate_code, c.exam_number,
           es.name AS subject_name,
           res.old_score, res.new_score, res.change_reason
    FROM appeal_requests ar
    JOIN candidates c ON c.id = ar.candidate_id
    JOIN exam_subjects es ON es.id = ar.exam_subject_id
    LEFT JOIN appeal_results res ON res.appeal_request_id = ar.id
    $whereSql
    ORDER BY ar.id DESC
    LIMIT 100
";

$appeals = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appeals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Teacher Appeals Error: ' . $e->getMessage());
}

teacher_layout_start('Theo dõi Phúc khảo', 'appeals', ['Phúc khảo' => '']);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="h4 fw-bold text-dark mb-1">
      <i class="fas fa-balance-scale text-warning me-2"></i>Theo dõi Đơn Phúc khảo Điểm thi
    </h1>
    <p class="text-muted small mb-0">
      Theo dõi tiến độ chấm phúc khảo và kết quả cập nhật điểm số bài thi cho học sinh.
    </p>
  </div>
  <?php if (auth_has_permission('appeal.manage') || auth_is_admin()): ?>
    <div>
      <a href="<?= e(url('/appeals/update_result.php')) ?>" class="btn btn-warning text-dark btn-sm rounded-pill fw-semibold shadow-sm">
        <i class="fas fa-check-circle me-1"></i>Vào phân hệ cập nhật kết quả phúc khảo
      </a>
    </div>
  <?php endif; ?>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
  <div class="card-body p-3 p-md-4">
    <form method="GET" action="<?= e(url('/teacher/appeals.php')) ?>" class="row g-3 align-items-end">
      <div class="col-md-7">
        <label class="form-label small fw-bold text-muted mb-1">Tìm kiếm học sinh</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light border-end-0 text-muted">
            <i class="fas fa-search"></i>
          </span>
          <input type="text" name="q" class="form-control border-start-0" 
                 placeholder="Nhập họ tên, CCCD/CMND hoặc SBD..." value="<?= e($q) ?>">
        </div>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold text-muted mb-1">Trạng thái xử lý</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Tất cả trạng thái</option>
          <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
          <option value="grading" <?= $filterStatus === 'grading' ? 'selected' : '' ?>>Đang chấm lại</option>
          <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Đã hoàn tất</option>
          <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
        </select>
      </div>

      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-warning text-dark btn-sm w-100 fw-semibold rounded-pill">
          <i class="fas fa-filter me-1"></i>Lọc
        </button>
        <a href="<?= e(url('/teacher/appeals.php')) ?>" class="btn btn-outline-secondary btn-sm rounded-pill" title="Làm mới">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Bảng danh sách đơn phúc khảo -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light small">
        <tr>
          <th style="white-space: nowrap;">Mã đơn</th>
          <th style="white-space: nowrap;">SBD / CCCD</th>
          <th style="white-space: nowrap;">Họ và tên học sinh</th>
          <th style="white-space: nowrap;">Môn phúc khảo</th>
          <th style="white-space: nowrap;" class="text-center">Điểm ban đầu</th>
          <th style="white-space: nowrap;" class="text-center">Điểm sau PK</th>
          <th style="white-space: nowrap;" class="text-center">Trạng thái</th>
          <th style="white-space: nowrap;">Lý do / Kết luận</th>
        </tr>
      </thead>
      <tbody class="small">
        <?php foreach ($appeals as $ap): ?>
          <tr>
            <td class="font-monospace fw-bold text-muted" style="white-space: nowrap;">
              #PK<?= (int)$ap['id'] ?>
            </td>
            <td style="white-space: nowrap;">
              <?php if (!empty($ap['exam_number']) || !empty($ap['candidate_code'])): ?>
                <div class="fw-bold font-monospace text-primary"><?= e($ap['exam_number'] ?: $ap['candidate_code']) ?></div>
              <?php endif; ?>
              <div class="text-muted font-monospace small"><?= e($ap['national_id']) ?></div>
            </td>
            <td>
              <div class="fw-bold text-dark"><?= e($ap['full_name']) ?></div>
            </td>
            <td style="white-space: nowrap;">
              <span class="badge bg-light text-dark border fw-medium"><?= e($ap['subject_name']) ?></span>
            </td>
            <td class="text-center font-monospace fw-semibold text-secondary" style="white-space: nowrap;">
              <?= number_format((float)$ap['original_score'], 2) ?>
            </td>
            <td class="text-center font-monospace fw-bold" style="white-space: nowrap;">
              <?php if ($ap['new_score'] !== null): ?>
                <span class="text-success fs-6"><?= number_format((float)$ap['new_score'], 2) ?></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-center" style="white-space: nowrap;">
              <?php if ($ap['status'] === 'pending'): ?>
                <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2.5 py-1">Chờ xử lý</span>
              <?php elseif ($ap['status'] === 'grading'): ?>
                <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2.5 py-1">Đang chấm lại</span>
              <?php elseif ($ap['status'] === 'completed'): ?>
                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1">Đã có kết quả</span>
              <?php else: ?>
                <span class="badge bg-secondary rounded-pill px-2.5 py-1">Đã hủy</span>
              <?php endif; ?>
            </td>
            <td class="small text-muted">
              <?php if (!empty($ap['change_reason'])): ?>
                <div class="text-dark fw-medium"><?= e($ap['change_reason']) ?></div>
              <?php else: ?>
                <div>Lý do nộp: <?= e($ap['reason']) ?></div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($appeals)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i class="fas fa-inbox fs-2 text-muted mb-2 d-block"></i>
              Chưa có đơn phúc khảo nào thuộc trường hoặc tiêu chí lọc.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
teacher_layout_end();
