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
$hasNumbers=(bool)$pdo->query("SHOW TABLES LIKE 'exam_candidate_numbers'")->fetchColumn();
$numberExpression=$hasNumbers?'COALESCE((SELECT n.exam_number FROM exam_candidate_numbers n WHERE n.admission_period_id=ar.admission_period_id AND n.candidate_id=c.id),c.exam_number)':'c.exam_number';
$filterEnroll = trim((string)($_GET['enrollment_status'] ?? ''));
if(!in_array($filterEnroll,['','pending','confirmed'],true)){http_response_code(400);exit('Trạng thái không hợp lệ.');}

$periods=$pdo->query('SELECT id,name FROM admission_periods ORDER BY (status="published") DESC,id DESC')->fetchAll();$periodId=(int)($_GET['period_id'] ?? ($periods[0]['id'] ?? 0));
$where = ['ar.result_status = "admitted"', 'ar.admission_period_id='.(int)$periodId, 'EXISTS(SELECT 1 FROM publication_events pe WHERE pe.admission_period_id=ar.admission_period_id AND pe.publication_type="admission_results" AND pe.status="published")', 'ar.admission_run_id=(SELECT MAX(r.id) FROM admission_runs r WHERE r.admission_period_id=ar.admission_period_id AND r.status="finalized")'];
$params = [];

if ($schoolLevel === 'THPT' && $schoolId > 0) {
    // Trường THPT: xem danh sách trúng tuyển vào trường mình
    $where[] = 'ar.school_id = :school_id';
    $params['school_id'] = $schoolId;
} elseif ($schoolLevel === 'THCS' && $schoolId > 0) {
    // Trường THCS: xem học sinh trường mình trúng tuyển vào các trường
    $where[] = 'c.middle_school_id = :school_id';
    $params['school_id'] = $schoolId;
} else {
    $where[] = '1 = 0';
}

if ($q !== '') {
    $where[] = '(c.full_name LIKE :q1 OR c.national_id LIKE :q2 OR c.candidate_code LIKE :q3 OR '.$numberExpression.' LIKE :q4)';
    foreach(['q1','q2','q3','q4'] as $key)$params[$key]='%'.$q.'%';
}

if ($filterEnroll !== '') {
    $where[] = 'ar.enrollment_status = :enroll';
    $params['enroll'] = $filterEnroll;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT ar.*, c.full_name, c.national_id, c.candidate_code, $numberExpression AS exam_number, c.date_of_birth, c.gender,
           ms.name AS middle_school_name,
           hs.name AS admitted_school_name, hs.school_code AS admitted_school_code, ec.confirmed_at
    FROM admission_results ar
    JOIN candidates c ON c.id = ar.candidate_id
    LEFT JOIN schools ms ON ms.id = c.middle_school_id
    LEFT JOIN schools hs ON hs.id = ar.school_id
    LEFT JOIN enrollment_confirmations ec ON ec.admission_result_id=ar.id AND ec.candidate_id=ar.candidate_id AND ec.school_id=ar.school_id
    $whereSql
    ORDER BY ar.admission_score DESC, ar.rank_no ASC
";

$admittedList = [];
$totalAdmitted = 0;
$confirmedCount = 0;
$quota = 0;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $admittedList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counts=$pdo->prepare('SELECT COUNT(*),COALESCE(SUM(ar.enrollment_status="confirmed"),0) confirmed FROM admission_results ar JOIN candidates c ON c.id=ar.candidate_id '.$whereSql);$counts->execute($params);$counts=$counts->fetch(PDO::FETCH_NUM);$totalAdmitted=(int)$counts[0];$confirmedCount=(int)$counts[1];
    if($schoolLevel==='THPT'){$qt=$pdo->prepare('SELECT COALESCE(SUM(quota),0) FROM admission_quotas WHERE school_id=? AND admission_period_id=?');$qt->execute([$schoolId,$periodId]);$quota=(int)$qt->fetchColumn();}
} catch (Throwable $e) {
    error_log('Teacher Admitted Error: ' . $e->getMessage());
}

$pageCount=max(1,(int)ceil(count($admittedList)/50));$page=min($pageCount,max(1,(int)($_GET['page']??1)));$admittedList=array_slice($admittedList,($page-1)*50,50);$pagePath='/teacher/admitted.php';
$periodQuery=$pdo->prepare('SELECT enrollment_end FROM admission_periods WHERE id=?');$periodQuery->execute([$periodId]);$enrollmentEnd=$periodQuery->fetchColumn() ?: null;
require_once __DIR__.'/../includes/enrollment_service.php';
teacher_layout_start('Danh sách trúng tuyển', 'admitted', ['Danh sách trúng tuyển' => '']);
require_once __DIR__.'/../includes/admission_period_configuration.php';
?>
<form method="get" class="mb-3"><label class="form-label w-100">Kỳ tuyển sinh<select name="period_id" class="form-select" onchange="this.form.submit()"><?php foreach($periods as $p):?><option value="<?=$p['id']?>" <?=$p['id']==$periodId?'selected':''?>><?=e($p['name'])?></option><?php endforeach;?></select></label><noscript><button>Xem</button></noscript></form>
<?php if(($cfg=period_configuration($pdo,$periodId)) && $cfg['settings']['mode']==='simulation'):?><div class="alert alert-warning">MÔ PHỎNG — Danh sách phục vụ dự án, không phải kết quả chính thức.</div><?php endif;?>
<?php
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="h4 fw-bold text-dark mb-1">
      <i class="fas fa-award text-success me-2"></i>Danh sách Học sinh Trúng tuyển
    </h1>
    <p class="text-muted small mb-0">
      <?php if ($schoolLevel === 'THPT'): ?>
        Danh sách học sinh có kết quả trúng tuyển đã công bố vào trường <strong><?= e($schoolName) ?></strong> và tình trạng nhập học.
      <?php elseif ($schoolLevel === 'THCS'): ?>
        Kết quả trúng tuyển vào các trường THPT của học sinh trường <strong><?= e($schoolName) ?></strong>.
      <?php else: ?>
        Danh sách học sinh trúng tuyển trong kỳ thi tuyển sinh lớp 10.
      <?php endif; ?>
    </p>
  </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-success border-4">
      <div class="small text-muted fw-semibold mb-1">Tổng số trúng tuyển</div>
      <div class="h3 fw-bold text-success mb-0"><?= number_format($totalAdmitted) ?></div>
      <div class="small text-muted">Học sinh đạt điểm chuẩn</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-primary border-4">
      <div class="small text-muted fw-semibold mb-1">Đã xác nhận nhập học</div>
      <div class="h3 fw-bold text-primary mb-0"><?= number_format($confirmedCount) ?></div>
      <div class="small text-muted">
        <?= $totalAdmitted > 0 ? round(($confirmedCount / $totalAdmitted) * 100, 1) : 0 ?>% tỷ lệ xác nhận
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-warning border-4">
      <div class="small text-muted fw-semibold mb-1">Chờ xác nhận</div>
      <div class="h3 fw-bold text-warning mb-0"><?= number_format(max(0, $totalAdmitted - $confirmedCount)) ?></div>
      <div class="small text-muted">Chưa hoàn tất thủ tục</div>
    </div>
  </div>

  <?php if ($schoolLevel === 'THPT'): ?>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-info border-4">
        <div class="small text-muted fw-semibold mb-1">Chỉ tiêu được giao</div>
        <div class="h3 fw-bold text-dark mb-0"><?= number_format($quota) ?></div>
        <div class="small text-muted">
          <?= $quota > 0 ? round(($totalAdmitted / $quota) * 100, 1) : 0 ?>% chỉ tiêu đạt được
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
  <div class="card-body p-3 p-md-4">
    <form method="GET" action="<?= e(url('/teacher/admitted.php')) ?>" class="row g-3 align-items-end"><input type="hidden" name="period_id" value="<?=$periodId?>">
      <div class="col-md-6">
        <label class="form-label small fw-bold text-muted mb-1">Tìm kiếm học sinh</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light border-end-0 text-muted">
            <i class="fas fa-search"></i>
          </span>
          <input type="text" name="q" class="form-control border-start-0" 
                 placeholder="Nhập họ tên, CCCD/CMND hoặc SBD..." value="<?= e($q) ?>">
        </div>
      </div>

      <div class="col-md-4">
        <label class="form-label small fw-bold text-muted mb-1">Trạng thái nhập học</label>
        <select name="enrollment_status" class="form-select form-select-sm">
          <option value="">Tất cả trạng thái</option>
          <option value="confirmed" <?= $filterEnroll === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận nhập học</option>
          <option value="pending" <?= $filterEnroll === 'pending' ? 'selected' : '' ?>>Chưa xác nhận</option>
        </select>
      </div>

      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-success btn-sm w-100 fw-semibold rounded-pill">
          <i class="fas fa-filter me-1"></i>Lọc
        </button>
        <a href="<?= e(url('/teacher/admitted.php')) ?>" class="btn btn-outline-secondary btn-sm rounded-pill" title="Làm mới">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__.'/../includes/list_pagination.php';?>
<!-- Bảng trúng tuyển -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light small">
        <tr>
          <th style="white-space: nowrap;">Thứ hạng</th>
          <th style="white-space: nowrap;">SBD / CCCD</th>
          <th style="white-space: nowrap;">Họ và tên</th>
          <th style="white-space: nowrap;">Ngày sinh</th>
          <?php if ($schoolLevel === 'THPT'): ?>
            <th style="white-space: nowrap;">Trường THCS gốc</th>
          <?php else: ?>
            <th style="white-space: nowrap;">Trường THPT trúng tuyển</th>
          <?php endif; ?>
          <th style="white-space: nowrap;" class="text-center">Nguyện vọng</th>
          <th style="white-space: nowrap;" class="text-end">Điểm xét tuyển</th>
          <th style="white-space: nowrap;" class="text-center">Xác nhận nhập học</th>
        </tr>
      </thead>
      <tbody class="small">
        <?php foreach ($admittedList as $idx => $item): ?>
          <tr>
            <td style="white-space: nowrap;" class="font-monospace fw-bold text-muted">
              #<?= $item['rank_no'] ? (int)$item['rank_no'] : ($idx + 1) ?>
            </td>
            <td style="white-space: nowrap;">
              <?php if (!empty($item['exam_number']) || !empty($item['candidate_code'])): ?>
                <div class="fw-bold font-monospace text-primary"><?= e($item['exam_number'] ?: $item['candidate_code']) ?></div>
              <?php endif; ?>
              <div class="text-muted font-monospace small"><?= e($item['national_id']) ?></div>
            </td>
            <td>
              <div class="fw-bold text-dark"><?= e($item['full_name']) ?></div>
            </td>
            <td style="white-space: nowrap;" class="text-muted">
              <?= e($item['date_of_birth'] ?? '—') ?>
            </td>
            <td>
              <?php if ($schoolLevel === 'THPT'): ?>
                <span class="text-muted"><?= e($item['middle_school_name'] ?? 'Chưa cập nhật') ?></span>
              <?php else: ?>
                <span class="text-primary fw-semibold"><?= e($item['admitted_school_name'] ?? 'Chưa cập nhật') ?></span>
              <?php endif; ?>
            </td>
            <td class="text-center" style="white-space: nowrap;">
              <span class="badge <?= $item['priority_order'] == 1 ? 'bg-primary' : 'bg-info' ?> rounded-pill">
                NV<?= (int)$item['priority_order'] ?>
              </span>
            </td>
            <td class="text-end fw-bold text-success font-monospace" style="white-space: nowrap; font-size: 0.95rem;">
              <?= number_format((float)$item['admission_score'], 2) ?>
            </td>
            <td class="text-center" style="white-space: nowrap;">
              <?php if ($item['enrollment_status'] === 'confirmed'): ?>
                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1">
                  <i class="fas fa-check-circle me-1"></i>Đã xác nhận
                </span>
              <?php else: ?>
                <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2.5 py-1">
                  <?=e(enrollment_state_label('pending',$enrollmentEnd))?>
                </span>
              <?php endif; ?>
              <?php if($item['confirmed_at']):?><div class="small text-muted"><?=e($item['confirmed_at'])?></div><?php endif;?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($admittedList)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i class="fas fa-inbox fs-2 text-muted mb-2 d-block"></i>
              Chưa có danh sách trúng tuyển được công bố cho tiêu chí này.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
teacher_layout_end();
