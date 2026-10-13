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

$where = [];
$params = [];

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

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Lấy danh sách điểm thi theo học sinh
$sql = "
    SELECT c.id, c.national_id, c.full_name, c.candidate_code, c.exam_number, c.date_of_birth,
           ms.name AS middle_school_name,
           GROUP_CONCAT(CONCAT(es.name, ': ', sc.total_score) ORDER BY es.id SEPARATOR ' | ') AS subject_scores,
           SUM(sc.total_score) AS grand_total
    FROM candidates c
    LEFT JOIN schools ms ON ms.id = c.middle_school_id
    LEFT JOIN exam_papers ep ON ep.candidate_id = c.id
    LEFT JOIN exam_subjects es ON es.id = ep.exam_subject_id
    LEFT JOIN scores sc ON sc.exam_paper_id = ep.id
    $whereSql
    GROUP BY c.id
    ORDER BY grand_total DESC, c.full_name ASC
    LIMIT 100
";

$candidateScores = [];
$totalScored = 0;
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $candidateScores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cntStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT c.id) 
        FROM candidates c 
        JOIN exam_papers ep ON ep.candidate_id = c.id 
        JOIN scores sc ON sc.exam_paper_id = ep.id 
        $whereSql
    ");
    $cntStmt->execute($params);
    $totalScored = (int)$cntStmt->fetchColumn();
} catch (Throwable $e) {
    error_log('Teacher Scores Error: ' . $e->getMessage());
}

teacher_layout_start('Bảng điểm thi tuyển sinh', 'scores', ['Nhập điểm' => '']);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="h4 fw-bold text-dark mb-1">
      <i class="fas fa-file-signature text-primary me-2"></i>Bảng Điểm Thi Tuyển Sinh
    </h1>
    <p class="text-muted small mb-0">
      <?php if ($schoolLevel === 'THCS'): ?>
        Theo dõi kết quả điểm thi tuyển sinh vào lớp 10 của học sinh thuộc <strong><?= e($schoolName) ?></strong>.
      <?php elseif ($schoolLevel === 'THPT'): ?>
        Tra cứu bảng điểm thi của thí sinh có nguyện vọng đăng ký vào <strong><?= e($schoolName) ?></strong>.
      <?php else: ?>
        Tra cứu bảng điểm thi tuyển sinh lớp 10 toàn hệ thống.
      <?php endif; ?>
    </p>
  </div>
  <?php if (auth_has_permission('score.enter') || auth_is_admin()): ?>
    <div>
      <a href="<?= e(url('/exam/scores.php')) ?>" class="btn btn-primary btn-sm rounded-pill fw-semibold">
        <i class="fas fa-edit me-1"></i>Vào phân hệ nhập điểm phách (Admin/Hội đồng)
      </a>
    </div>
  <?php endif; ?>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
  <div class="card-body p-3 p-md-4">
    <form method="GET" action="<?= e(url('/teacher/scores.php')) ?>" class="row g-3 align-items-end">
      <div class="col-md-9">
        <label class="form-label small fw-bold text-muted mb-1">Tìm kiếm thí sinh</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light border-end-0 text-muted">
            <i class="fas fa-search"></i>
          </span>
          <input type="text" name="q" class="form-control border-start-0" 
                 placeholder="Nhập họ tên, CCCD/CMND hoặc Số báo danh..." value="<?= e($q) ?>">
        </div>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold rounded-pill">
          <i class="fas fa-search me-1"></i>Tra cứu
        </button>
        <a href="<?= e(url('/teacher/scores.php')) ?>" class="btn btn-outline-secondary btn-sm rounded-pill" title="Làm mới">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Bảng kết quả điểm thi -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
  <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
    <div class="small fw-bold text-dark">
      Số thí sinh đã có điểm: <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?= number_format($totalScored) ?></span>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light small">
        <tr>
          <th style="white-space: nowrap;">SBD / CCCD</th>
          <th style="white-space: nowrap;">Họ và tên</th>
          <th style="white-space: nowrap;">Ngày sinh</th>
          <?php if ($schoolLevel !== 'THCS'): ?>
            <th style="white-space: nowrap;">Trường THCS</th>
          <?php endif; ?>
          <th style="white-space: nowrap;">Điểm các môn thi</th>
          <th style="white-space: nowrap;" class="text-end">Tổng điểm bài thi</th>
        </tr>
      </thead>
      <tbody class="small">
        <?php foreach ($candidateScores as $cs): ?>
          <tr>
            <td style="white-space: nowrap;">
              <?php if (!empty($cs['exam_number']) || !empty($cs['candidate_code'])): ?>
                <div class="fw-bold text-primary font-monospace"><?= e($cs['exam_number'] ?: $cs['candidate_code']) ?></div>
              <?php endif; ?>
              <div class="text-muted font-monospace small"><?= e($cs['national_id']) ?></div>
            </td>
            <td>
              <div class="fw-bold text-dark"><?= e($cs['full_name']) ?></div>
            </td>
            <td style="white-space: nowrap;" class="text-muted">
              <?= e($cs['date_of_birth'] ?? '—') ?>
            </td>
            <?php if ($schoolLevel !== 'THCS'): ?>
              <td class="text-muted small">
                <?= e($cs['middle_school_name'] ?? 'Chưa cập nhật') ?>
              </td>
            <?php endif; ?>
            <td>
              <?php if (!empty($cs['subject_scores'])): ?>
                <span class="text-dark fw-medium"><?= e($cs['subject_scores']) ?></span>
              <?php else: ?>
                <span class="text-muted fst-italic">Chưa có dữ liệu điểm</span>
              <?php endif; ?>
            </td>
            <td class="text-end fw-bold font-monospace" style="white-space: nowrap;">
              <?php if ($cs['grand_total'] !== null): ?>
                <span class="text-primary fs-6"><?= number_format((float)$cs['grand_total'], 2) ?></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($candidateScores)): ?>
          <tr>
            <td colspan="<?= $schoolLevel !== 'THCS' ? 6 : 5 ?>" class="text-center py-5 text-muted">
              <i class="fas fa-inbox fs-2 text-muted mb-2 d-block"></i>
              Chưa có dữ liệu điểm thi hoặc không tìm thấy thí sinh phù hợp.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
teacher_layout_end();
