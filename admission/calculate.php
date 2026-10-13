<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/admission_service.php';
require_permission('admission.manage');
$pdo=db();
$periods=$pdo->query('SELECT * FROM admission_periods ORDER BY academic_year DESC,id DESC')->fetchAll();
$periodId=(int)($_POST['period_id']??$_GET['period_id']??($periods[0]['id']??0));
$error='';$success='';$run=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    try{$runId=admission_calculate($pdo,$periodId,(int)auth_user()['id']);$run=latest_admission_run($pdo,$periodId);$success='Xét tuyển thành công.';}
    catch(Throwable $e){$error=$e->getMessage();$run=latest_admission_run($pdo,$periodId);}
}else{$run=latest_admission_run($pdo,$periodId);}
require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Xét tuyển thí sinh';
$activePage = 'admission';

admin_layout_start('Xét tuyển thí sinh', 'admission', ['Xét tuyển' => url('/admission/index.php'), 'Chạy xét tuyển' => '']);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Xét tuyển thí sinh</h1>
    <p class="text-muted mb-0">Thực hiện thuật toán xét tuyển nguyện vọng tự động theo chỉ tiêu và điểm xét tuyển.</p>
  </div>
  <a class="btn btn-outline-secondary" href="<?= e(url('/admission/index.php')) ?>">
    Danh sách kỳ tuyển sinh
  </a>
</div>

<?php if ($success): ?>
  <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <?= e($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <?= e($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
  </div>
<?php endif; ?>

<!-- Form thực hiện xét tuyển -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h6 class="mb-0 fw-bold text-dark">Chọn kỳ tuyển sinh để chạy xét tuyển</h6>
  </div>
  <div class="card-body p-4">
    <form method="post" class="row g-3 align-items-end">
      <?= csrf_field() ?>
      <div class="col-md-8">
        <label class="form-label small fw-semibold text-secondary">Kỳ tuyển sinh *</label>
        <select name="period_id" class="form-select" required>
          <?php foreach ($periods as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $p['id'] == $periodId ? 'selected' : '' ?>>
              <?= e($p['name'] . ' - ' . $p['academic_year']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-primary w-100 fw-semibold">
          Bắt đầu thực hiện xét tuyển
        </button>
      </div>
    </form>
  </div>
</div>

<?php if ($run): ?>
  <?php $rows = admission_results($pdo, (int)$run['id']); ?>
  <!-- Bảng kết quả chạy xét tuyển -->
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold text-dark">
        Kết quả đợt chạy #<?= e((string)$run['id']) ?>
        <span class="badge bg-info bg-opacity-10 text-info rounded-pill ms-2 fw-semibold">Trạng thái: <?= e($run['status']) ?></span>
      </h6>
      <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1.5 fw-semibold"><?= count($rows) ?> kết quả</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Thí sinh</th>
            <th class="text-center" style="width: 100px;">Nguyện vọng</th>
            <th>Trường xét tuyển</th>
            <th class="text-end" style="width: 110px;">Tổng điểm</th>
            <th class="text-end" style="width: 100px;">Ưu tiên</th>
            <th class="text-end" style="width: 130px;">Điểm xét tuyển</th>
            <th class="text-center" style="width: 130px;">Kết quả</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td>
                <div class="fw-semibold text-dark"><?= e($r['full_name']) ?></div>
                <div class="text-muted small font-monospace"><?= e($r['candidate_code']) ?></div>
              </td>
              <td class="text-center font-monospace fw-bold">NV<?= e((string)$r['priority_order']) ?></td>
              <td><?= e(($r['school_code'] ?? '') . ' - ' . ($r['school_name'] ?? '')) ?></td>
              <td class="text-end font-monospace"><?= e((string)$r['score_total']) ?></td>
              <td class="text-end font-monospace text-muted">+<?= e((string)$r['priority_points']) ?></td>
              <td class="text-end font-monospace fw-bold text-primary"><?= e((string)$r['admission_score']) ?></td>
              <td class="text-center">
                <?php if ($r['result_status'] === 'admitted'): ?>
                  <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1">Trúng tuyển</span>
                <?php else: ?>
                  <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2.5 py-1"><?= e($r['result_status']) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">Chưa có kết quả xét tuyển cho đợt chạy này.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php
admin_layout_end();

