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
$filterNv = max(0,(int)($_GET['nv'] ?? 0));
$periods = $pdo->query('SELECT id,name,academic_year FROM admission_periods WHERE status IN ("published","closed") ORDER BY (status="published") DESC,id DESC')->fetchAll();
$periodId = (int)($_GET['period_id'] ?? ($periods[0]['id'] ?? 0));
if ($periodId && !in_array($periodId,array_map('intval',array_column($periods,'id')),true)) {
    http_response_code(404); exit('Không tìm thấy kỳ tuyển sinh đã công bố.');
}
$where = ['a.admission_period_id=:period'];
$params = ['period'=>$periodId];
if ($schoolLevel === 'THPT' && $schoolId>0) {
    $where[]='a.school_id=:school'; $params['school']=$schoolId;
} elseif ($schoolLevel === 'THCS' && $schoolId>0) {
    $where[]='c.middle_school_id=:school'; $params['school']=$schoolId;
} else $where[]='1=0';
if ($q!=='') {
    $where[]='(c.full_name LIKE :q_name OR c.national_id LIKE :q_id OR c.candidate_code LIKE :q_code OR c.exam_number LIKE :q_exam)';
    foreach (['q_name','q_id','q_code','q_exam'] as $key) $params[$key]='%'.$q.'%';
}
if ($filterNv>0) { $where[]='a.priority_order=:nv'; $params['nv']=$filterNv; }
$whereSql=' WHERE '.implode(' AND ',$where);
$from=' FROM aspirations a JOIN candidates c ON c.id=a.candidate_id LEFT JOIN schools ms ON ms.id=c.middle_school_id LEFT JOIN schools hs ON hs.id=a.school_id';
$aspirations=[]; $nvStats=[1=>0,2=>0,3=>0,'total'=>0]; $quota=0; $page=1; $pageCount=1;
try {
    $stmt=$pdo->prepare('SELECT a.priority_order,COUNT(*) cnt'.$from.$whereSql.' GROUP BY a.priority_order');
    $stmt->execute($params);
    foreach ($stmt as $row) { $nvStats[(int)$row['priority_order']]=(int)$row['cnt']; $nvStats['total']+=(int)$row['cnt']; }
    $pageCount=max(1,(int)ceil($nvStats['total']/50));
    $page=min($pageCount,max(1,(int)($_GET['page'] ?? 1)));
    $stmt=$pdo->prepare('SELECT a.*,c.full_name,c.national_id,c.candidate_code,c.exam_number,c.date_of_birth,c.gender,ms.name middle_school_name,hs.name target_school_name,hs.school_code target_school_code'.$from.$whereSql.' ORDER BY a.priority_order,c.full_name,a.id LIMIT 50 OFFSET '.(($page-1)*50));
    $stmt->execute($params); $aspirations=$stmt->fetchAll();
    if ($schoolLevel==='THPT') {
        $stmt=$pdo->prepare('SELECT COALESCE(SUM(quota),0) FROM admission_quotas WHERE school_id=:s AND admission_period_id=:p');
        $stmt->execute(['s'=>$schoolId,'p'=>$periodId]); $quota=(int)$stmt->fetchColumn();
    }
} catch (Throwable $exception) {
    error_log('Teacher aspirations: '.$exception->getMessage());
    $loadError='Không thể tải nguyện vọng. Vui lòng thử lại.';
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

<?php if (!$periodId): ?><div class="alert alert-info">Chưa có kỳ tuyển sinh được công bố. Danh sách nguyện vọng đang để trống.</div><?php endif; ?>
<?php if (!empty($loadError)): ?><div class="alert alert-danger"><?= e($loadError) ?></div><?php endif; ?>
<p class="small text-muted">Số liệu nguyện vọng theo kỳ tuyển sinh và bộ lọc đang chọn.</p>
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
      <div class="col-12"><label class="form-label" for="period_id">Kỳ tuyển sinh</label><select class="form-select" name="period_id" id="period_id"><option value="0">Chọn kỳ tuyển sinh</option><?php foreach ($periods as $period): ?><option value="<?= (int)$period['id'] ?>" <?= $periodId===(int)$period['id']?'selected':'' ?>><?= e($period['name'].' · '.$period['academic_year']) ?></option><?php endforeach; ?></select></div>
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
          <tr data-aspiration-id="<?= (int)$a['id'] ?>">
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
$pagePath='/teacher/aspirations.php';
require __DIR__.'/../includes/list_pagination.php';
teacher_layout_end();
