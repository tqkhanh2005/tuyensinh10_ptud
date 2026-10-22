<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_once __DIR__ . '/../includes/excel_service.php';
require_permission('score.enter');
$pdo = db();
$periodId = (int)($_GET['period_id'] ?? $_POST['period_id'] ?? 0);
$subjectId = (int)($_GET['subject_id'] ?? $_POST['subject_id'] ?? 0);
require_once __DIR__.'/../includes/grading_filters.php';
$context=grading_filter_context($pdo,$periodId,$subjectId);$periodId=$context['period'];$subjectId=$context['subject'];
$error = '';
$message = '';
$preview = null;
$batchId = (int)($_GET['batch_id'] ?? $_POST['batch_id'] ?? 0);

if (isset($_GET['download']) && $_GET['download'] === 'template') {
    if(!$subjectId){http_response_code(409);exit('Chưa có môn thi.');}
    $subject = exam_subject_context($pdo, $periodId, $subjectId);
    $q = $pdo->prepare('SELECT p.anonymous_code FROM exam_papers p WHERE p.admission_period_id=:p AND p.exam_subject_id=:s AND p.anonymous_code IS NOT NULL AND p.attendance_status IN ("present","present_penalty") ORDER BY p.anonymous_code');
    $q->execute(['p' => $periodId, 's' => $subjectId]);
    $rows = [];
    foreach ($q as $r) $rows[] = [(string)$r['anonymous_code'], '', '', ''];
    excel_xlsx_download('mau_nhap_diem.xls.xlsx', ['anonymous_code', 'score_1', 'score_2', 'total_score'], $rows);
}

if (isset($_GET['download']) && $_GET['download'] === 'errors' && $batchId > 0) {
    $q = $pdo->prepare('SELECT row_number,anonymous_code,error_message FROM exam_score_import_rows r JOIN exam_score_import_batches b ON b.id=r.batch_id WHERE r.batch_id=:b AND b.user_id=:u AND r.is_valid=0 ORDER BY row_number');
    $q->execute(['b' => $batchId, 'u' => (int)auth_user()['id']]);
    $rows = [];
    foreach ($q as $r) $rows[] = [$r['row_number'], $r['anonymous_code'], $r['error_message']];
    excel_xlsx_download('loi_nhap_diem.xls.xlsx', ['row', 'anonymous_code', 'error'], $rows);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'manual_preview') {
            $preview = validate_score_rows($pdo, $periodId, $subjectId, (array)($_POST['score'] ?? []));
        } elseif ($action === 'manual_save') {
            $result = validate_score_rows($pdo, $periodId, $subjectId, (array)($_POST['score'] ?? []));
            if (!$result['valid']) throw new InvalidArgumentException('Không có dữ liệu hợp lệ để lưu.');
            save_scores($pdo, (int)auth_user()['id'], $periodId, $subjectId, $result['valid']);
            $message = 'Đã lưu các dòng điểm hợp lệ.';
        } elseif ($action === 'upload_preview') {
            if (!isset($_FILES['score_file']) || $_FILES['score_file']['error'] !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Vui lòng tải file Excel hợp lệ.');
            if ((int)$_FILES['score_file']['size'] > 5 * 1024 * 1024) throw new InvalidArgumentException('File vượt quá giới hạn kỹ thuật 5 MB.');
            $batchId = create_score_import_batch($pdo, (int)auth_user()['id'], $periodId, $subjectId, (string)$_FILES['score_file']['name'], excel_parse_upload((string)$_FILES['score_file']['tmp_name'], (string)$_FILES['score_file']['name']));
            $q = $pdo->prepare('SELECT valid_rows,invalid_rows,status FROM exam_score_import_batches WHERE id=:id AND user_id=:u');
            $q->execute(['id' => $batchId, 'u' => (int)auth_user()['id']]); $preview = $q->fetch();
            $message = 'Đã kiểm tra file; chưa lưu điểm.';
        } elseif ($action === 'import_save') {
            save_score_import_batch($pdo, $batchId, (int)auth_user()['id']);
            $message = 'Đã lưu các dòng hợp lệ từ file.';
            $batchId = 0;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$q = $pdo->prepare('SELECT p.anonymous_code,p.paper_status FROM exam_papers p WHERE p.admission_period_id=:p AND p.exam_subject_id=:s AND p.anonymous_code IS NOT NULL AND p.paper_status="coded" ORDER BY p.anonymous_code');
$q->execute(['p' => $periodId, 's' => $subjectId]);
$papers = $q->fetchAll();$paperCount=count($papers);$pageCount=max(1,(int)ceil($paperCount/50));$page=min($pageCount,max(1,(int)($_GET['page'] ?? 1)));$papers=array_slice($papers,($page-1)*50,50);
$gradeLocked=(bool)grading_state($pdo,$periodId);
$componentCount=1;if($subjectId)$componentCount=(int)exam_subject_context($pdo,$periodId,$subjectId)['component_count'];

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Nhập điểm thi theo số phách';
$activePage = 'scores';

admin_layout_start('Nhập điểm thi theo số phách', 'scores', ['Nhập điểm' => '']);
grading_filters($context);
if($cfg=period_configuration($pdo,$periodId)){if($cfg['settings']['mode']==='simulation')echo '<div class="alert alert-warning">MÔ PHỎNG — Điểm thi phục vụ dự án, không phải thông báo chính thức.</div>';}
echo '<p><a href="'.e(url('/exam/grading.php?period_id='.$periodId)).'">Chấm thi và phúc khảo</a></p>'; 
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Nhập điểm theo số phách</h1>
    <p class="text-muted mb-0">Hỗ trợ tải file mẫu, kiểm tra cấu trúc/dữ liệu điểm thi và nhập trực tiếp theo quy trình bảo mật số phách.</p>
  </div>
  <div>
    <a class="btn btn-outline-primary" href="?period_id=<?= $periodId ?>&subject_id=<?= $subjectId ?>&download=template">
      Tải file mẫu Excel
    </a>
  </div>
</div>

<?php if ($message): ?>
  <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <?= e($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <?= e($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
  </div>
<?php endif; ?>

<?php if($gradeLocked):?><div class="alert alert-info">Điểm đã rà soát và khóa. Thay đổi qua quy trình phúc khảo sau công bố.</div><?php endif;?>
<!-- Card Upload File Điểm -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h6 class="mb-0 fw-bold text-dark">Nhập điểm hàng loạt từ tệp bảng tính</h6>
  </div>
  <div class="card-body p-4">
    <form method="post" enctype="multipart/form-data" class="row g-3 align-items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="upload_preview">
      <input type="hidden" name="period_id" value="<?= $periodId ?>">
      <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
      <div class="col-md-8">
        <label class="form-label small fw-semibold text-secondary">Chọn tệp điểm thi (.xlsx, .xls, .csv, tối đa 5 MB) *</label>
        <input type="file" name="score_file" accept=".xlsx,.xls,.csv" class="form-control" required>
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-success w-100 fw-semibold">
          Kiểm tra & Xem trước file
        </button>
      </div>
    </form>
  </div>
</div>

<?php if ($batchId > 0 && $preview): ?>
  <!-- Card Preview Import File -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-4 border-primary">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold text-dark">Kết quả kiểm tra tệp #<?= $batchId ?></h6>
      <div class="d-flex gap-2">
        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1.5 fw-semibold">Hợp lệ: <?= (int)$preview['valid_rows'] ?></span>
        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1.5 fw-semibold">Lỗi: <?= (int)$preview['invalid_rows'] ?></span>
      </div>
    </div>
    <div class="card-body p-4">
      <?php if ((int)$preview['invalid_rows'] > 0): ?>
        <div class="mb-3">
          <a class="btn btn-sm btn-outline-danger" href="?period_id=<?= $periodId ?>&subject_id=<?= $subjectId ?>&batch_id=<?= $batchId ?>&download=errors">
            Tải danh sách các dòng lỗi
          </a>
        </div>
      <?php endif; ?>
      <?php if ((int)$preview['valid_rows'] > 0): ?>
        <form method="post" class="d-inline-flex gap-2">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="import_save">
          <input type="hidden" name="period_id" value="<?= $periodId ?>">
          <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
          <input type="hidden" name="batch_id" value="<?= $batchId ?>">
          <button class="btn btn-primary fw-semibold" type="submit">
            Xác nhận lưu các dòng hợp lệ
          </button>
          <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Hủy bỏ</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Card Nhập trực tiếp & Bảng điểm theo số phách -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
  <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold text-dark">Nhập điểm trực tiếp theo số phách</h6>
    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1.5 fw-semibold"><?=$paperCount?> bài thi chờ nhập</span>
  </div>
  <div class="card-body p-0">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="manual_preview">
      <input type="hidden" name="period_id" value="<?= $periodId ?>">
      <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 180px;">Số phách</th>
              <th>Điểm phần 1</th>
              <th>Điểm phần 2</th>
              <th>Tổng điểm</th>
              <th>Trạng thái bài thi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($papers as $p): ?>
              <tr>
                <td class="font-monospace fw-bold text-dark"><?= e($p['anonymous_code']) ?></td>
                <td>
                  <input name="score[<?= e($p['anonymous_code']) ?>][score_1]" <?=$gradeLocked?'disabled':''?> type="number" step="<?=period_configuration($pdo,$periodId)?'0.25':'0.01'?>" min="0" max="10" class="form-control form-control-sm" style="max-width: 120px;" placeholder="Điểm 1">
                </td>
                <td>
                  <input <?=$componentCount===1?'disabled':''?> name="score[<?= e($p['anonymous_code']) ?>][score_2]" <?=$gradeLocked?'disabled':''?> type="number" step="<?=period_configuration($pdo,$periodId)?'0.25':'0.01'?>" min="0" max="10" class="form-control form-control-sm" style="max-width: 120px;" placeholder="Điểm 2">
                </td>
                <td>
                  <input name="score[<?= e($p['anonymous_code']) ?>][total_score]" <?=$gradeLocked?'disabled':''?> type="number" step="<?=period_configuration($pdo,$periodId)?'0.25':'0.01'?>" min="0" max="10" class="form-control form-control-sm" style="max-width: 120px;" placeholder="Tổng điểm">
                </td>
                <td>
                  <span class="badge bg-light text-secondary border rounded-pill"><?= e($p['paper_status']) ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$papers): ?>
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">Chưa có bài thi nào được đánh số phách trong môn này.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($papers): ?>
        <div class="p-3 bg-light border-top text-end">
          <button type="submit" class="btn btn-primary px-4 fw-semibold">
            Kiểm tra & Xem trước
          </button>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if (is_array($preview) && array_key_exists('valid', $preview)): ?>
  <!-- Kết quả kiểm tra nhập thủ công -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-4 border-info">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold text-dark">Kết quả kiểm tra dữ liệu trực tiếp</h6>
      <div class="d-flex gap-2">
        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1.5 fw-semibold">Hợp lệ: <?= count($preview['valid']) ?></span>
        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1.5 fw-semibold">Lỗi: <?= count($preview['errors']) ?></span>
      </div>
    </div>
    <div class="card-body p-4">
      <?php if ($preview['errors']): ?>
        <div class="alert alert-warning mb-3">
          <div class="fw-bold mb-1">Các dòng gặp lỗi:</div>
          <ul class="mb-0 ps-3">
            <?php foreach ($preview['errors'] as $err): ?>
              <li>Phách <strong><?= e($err['code']) ?></strong>: <?= e($err['error']) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <?php if ($preview['valid']): ?>
        <form method="post" class="d-inline-flex gap-2">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="manual_save">
          <input type="hidden" name="period_id" value="<?= $periodId ?>">
          <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
          <?php foreach ($preview['valid'] as $v): ?>
            <input type="hidden" name="score[<?= e($v['code']) ?>][score_1]" value="<?= e((string)($v['score_1'] ?? '')) ?>">
            <input type="hidden" name="score[<?= e($v['code']) ?>][score_2]" value="<?= e((string)($v['score_2'] ?? '')) ?>">
            <input type="hidden" name="score[<?= e($v['code']) ?>][total_score]" value="<?= e((string)$v['total_score']) ?>">
          <?php endforeach; ?>
          <button class="btn btn-primary fw-semibold" type="submit">
            Xác nhận lưu dòng hợp lệ
          </button>
          <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Hủy bỏ</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php
$pagePath='/exam/scores.php';require __DIR__.'/../includes/list_pagination.php';
admin_layout_end();

