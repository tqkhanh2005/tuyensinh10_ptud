<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appeal_service.php';
require_once __DIR__ . '/../includes/excel_service.php';

require_once __DIR__.'/../includes/portal_helper.php';
require_auth();if(!auth_has_permission('appeal.manage'))require_teacher_access();
$pdo = db();
$currentUser = auth_user();
$currentUserId = (int)$currentUser['user_id'];
$periodId = (int)($_GET['period_id'] ?? $_POST['period_id'] ?? 0);
$subjectId = (int)($_GET['subject_id'] ?? $_POST['subject_id'] ?? 0);
require_once __DIR__.'/../includes/grading_filters.php';
$context=grading_filter_context($pdo,$periodId,$subjectId);$periodId=$context['period'];$subjectId=$context['subject'];
$error = '';
$message = '';
$preview = null;

$visibleAppeals=static function(int $period,?int $subject=null)use($pdo,$currentUserId):array{
    $rows=admin_appeals($pdo,$period,$subject);
    if(!auth_has_permission('council.manage'))$rows=array_values(array_filter($rows,static fn($r)=>(int)($r['assigned_grader_user_id'] ?? 0)===$currentUserId));
    return $rows;
};


function uc03_template_rows(array $rows): array
{
    $out = [];
    foreach ($rows as $r) {
        if (($r['status'] ?? '') !== 'grading') continue;
        if (empty($r['assigned_grader_user_id'])) continue;
        $out[] = [
            (int)$r['id'],
            (string)$r['anonymous_code'],
            (string)$r['candidate_code'],
            (float)$r['original_score'],
            (string)($r['assigned_grader_username'] ?? $r['assigned_grader_name'] ?? ''),
            '',
            '',
        ];
    }
    return $out;
}

function uc03_download_error_file(string $format, array $rows): void
{
    $headers = array_merge(UC03_HEADERS, ['Lỗi']);
    $data = [];
    foreach ($rows as $r) $data[] = array_merge($r['row'], [$r['error']]);
    if ($format === 'csv') excel_csv_download('uc03_loi_phuc_khao.csv', $headers, $data);
    excel_xlsx_download('uc03_loi_phuc_khao.xlsx', $headers, $data);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf();
        $action = (string)($_POST['action'] ?? '');
        $appealId = (int)($_POST['appeal_id'] ?? 0);

        if ($action === 'assign') {
            if (!auth_has_permission('council.manage')) throw new RuntimeException('403 Forbidden');
            assign_appeal_grader($pdo, $appealId, (int)($_POST['council_member_id'] ?? 0), $currentUserId);
            $message = 'Đã phân công cán bộ chấm phúc khảo.';
        } elseif ($action === 'start_grading') {
            set_appeal_grading($pdo, $appealId, $currentUserId);
            $message = 'Đã chuyển đơn sang trạng thái Đang chấm.';
        } elseif ($action === 'complete') {
            $check = validate_appeal_result($pdo, $appealId, $_POST['new_score'] ?? null, (string)($_POST['reason'] ?? ''), $currentUserId);
            if (!$check['ok']) throw new RuntimeException($check['error']);
            complete_appeal($pdo, $appealId, (float)$check['new_score'], $check['reason'], $currentUserId);
            $message = 'Đã cập nhật kết quả phúc khảo.';
        } elseif ($action === 'download_template') {
            $rows = $visibleAppeals($periodId, $subjectId > 0 ? $subjectId : null);
            excel_xlsx_download('uc03_mau_ket_qua_phuc_khao.xlsx', UC03_HEADERS, uc03_template_rows($rows));
        } elseif ($action === 'download_template_csv') {
            $rows = $visibleAppeals($periodId, $subjectId > 0 ? $subjectId : null);
            excel_csv_download('uc03_mau_ket_qua_phuc_khao.csv', UC03_HEADERS, uc03_template_rows($rows));
        } elseif ($action === 'upload_preview') {
            if ($periodId <= 0 || $subjectId <= 0) throw new InvalidArgumentException('Phải chọn kỳ tuyển sinh và môn thi trước khi upload.');
            if (!isset($_FILES['result_file']) || !is_array($_FILES['result_file']) || (int)$_FILES['result_file']['error'] !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Không thể nhận file tải lên.');
            $tmp = (string)$_FILES['result_file']['tmp_name'];
            $name = (string)$_FILES['result_file']['name'];
            $parsed = excel_parse_upload($tmp, $name);
            $preview = uc03_validate_import($pdo, $periodId, $subjectId, $parsed, $currentUserId);
            $_SESSION['uc03_preview'] = ['period_id' => $periodId, 'subject_id' => $subjectId, 'user_id' => $currentUserId, 'valid' => $preview['valid'], 'errors' => $preview['errors'], 'created_at' => time()];
            $message = 'Đã kiểm tra file. Hãy xem trước và xác nhận lưu các dòng hợp lệ.';
        } elseif ($action === 'download_errors') {
            $state = $_SESSION['uc03_preview'] ?? null;
            if (!is_array($state) || (int)$state['user_id'] !== $currentUserId) throw new RuntimeException('Không có dữ liệu preview hợp lệ để xuất lỗi.');
            uc03_download_error_file((string)($_POST['format'] ?? 'xlsx'), $state['errors'] ?? []);
        } elseif ($action === 'confirm_import') {
            $state = $_SESSION['uc03_preview'] ?? null;
            if (!is_array($state) || (int)$state['user_id'] !== $currentUserId || (int)$state['period_id'] !== $periodId || (int)$state['subject_id'] !== $subjectId) throw new RuntimeException('Preview không hợp lệ hoặc đã hết phiên.');
            complete_appeal_batch($pdo, $state['valid'] ?? [], $currentUserId);
            unset($_SESSION['uc03_preview']);
            $message = 'Đã lưu các dòng hợp lệ theo transaction và gửi thông báo kết quả.';
        } else {
            throw new InvalidArgumentException('Thao tác không hợp lệ.');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$subjects = [];
if ($periodId > 0) {
    $q = $pdo->prepare(
        'SELECT s.id, s.subject_code, s.name,
                SUM(CASE WHEN ar.status IN ("pending","grading") THEN 1 ELSE 0 END) AS pending_count
         FROM exam_subjects s
         LEFT JOIN appeal_requests ar ON ar.exam_subject_id = s.id AND ar.admission_period_id = :p
         WHERE s.admission_period_id = :p2
         GROUP BY s.id, s.subject_code, s.name
         ORDER BY s.name'
    );
    $q->execute(['p' => $periodId, 'p2' => $periodId]);
    $subjects = $q->fetchAll();
}
$rows = $periodId > 0 ? $visibleAppeals($periodId, $subjectId > 0 ? $subjectId : null) : [];
$totalRows=count($rows);$pageCount=max(1,(int)ceil($totalRows/50));$page=min($pageCount,max(1,(int)($_GET['page'] ?? 1)));$rows=array_slice($rows,($page-1)*50,50);
$previewState = $_SESSION['uc03_preview'] ?? null;

require_once __DIR__ . '/../includes/workflow_layout.php';

$pageTitle = 'Quản lý phúc khảo';
$activePage = 'appeals';

workflow_layout_start('Quản lý phúc khảo','appeals');
grading_filters($context);
if(($cfg=period_configuration($pdo,$periodId)) && $cfg['settings']['mode']==='simulation')echo '<div class="alert alert-warning">MÔ PHỎNG — Kết quả phúc khảo phục vụ dự án.</div>';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Cập nhật kết quả phúc khảo</h1>
    <p class="text-muted mb-0">Theo dõi, phân công cán bộ và cập nhật điểm phúc khảo theo quy trình kiểm tra số phách.</p>
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

<!-- Bộ lọc kỳ & môn -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
  <div class="card-body p-4">
    <form method="get" class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label small fw-semibold text-secondary">Kỳ tuyển sinh *</label>
        <input type="number" class="form-control" name="period_id" value="<?= e((string)$periodId) ?>" placeholder="Nhập ID kỳ tuyển sinh" required>
      </div>
      <div class="col-md-5">
        <label class="form-label small fw-semibold text-secondary">Môn thi</label>
        <select class="form-select" name="subject_id">
          <option value="0">Tất cả môn thi</option>
          <?php foreach ($subjects as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($subjectId === (int)$s['id'] ? 'selected' : '') ?>>
              <?= e($s['subject_code'] . ' - ' . $s['name'] . ' (' . $s['pending_count'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100 fw-semibold">
          Lọc đơn
        </button>
      </div>
    </form>
  </div>
</div>

<?php if ($periodId > 0 && $subjectId > 0): ?>
  <!-- Thao tác Import / Tải file mẫu -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
    <div class="card-header bg-white border-bottom py-3 px-4">
      <h6 class="mb-0 fw-bold text-dark">Tải file mẫu & Nhập hàng loạt</h6>
    </div>
    <div class="card-body p-4">
      <div class="d-flex flex-wrap gap-2 mb-3">
        <form method="post" class="d-inline">
          <?= csrf_field() ?>
          <input type="hidden" name="period_id" value="<?= $periodId ?>">
          <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
          <input type="hidden" name="action" value="download_template">
          <button class="btn btn-outline-success btn-sm">Tải mẫu XLSX</button>
        </form>
        <form method="post" class="d-inline">
          <?= csrf_field() ?>
          <input type="hidden" name="period_id" value="<?= $periodId ?>">
          <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
          <input type="hidden" name="action" value="download_template_csv">
          <button class="btn btn-outline-secondary btn-sm">Tải mẫu CSV</button>
        </form>
      </div>
      <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="period_id" value="<?= $periodId ?>">
        <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
        <input type="hidden" name="action" value="upload_preview">
        <div class="col-md-8">
          <label class="form-label small fw-semibold text-secondary">Chọn file kết quả phúc khảo</label>
          <input type="file" name="result_file" accept=".xlsx,.csv,.xls" class="form-control" required>
        </div>
        <div class="col-md-4">
          <button class="btn btn-primary w-100">Tải lên & Xem trước</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php if (is_array($previewState) && (int)($previewState['period_id'] ?? 0) === $periodId && (int)($previewState['subject_id'] ?? 0) === $subjectId && (int)($previewState['user_id'] ?? 0) === $currentUserId): ?>
  <!-- Card Preview -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-4 border-info">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold text-dark">Kết quả kiểm tra tệp phúc khảo</h6>
      <div class="d-flex gap-2">
        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1.5 fw-semibold">Hợp lệ: <?= count($previewState['valid'] ?? []) ?></span>
        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1.5 fw-semibold">Lỗi: <?= count($previewState['errors'] ?? []) ?></span>
      </div>
    </div>
    <div class="card-body p-4">
      <?php if (!empty($previewState['errors'])): ?>
        <div class="d-flex gap-2 mb-3">
          <form method="post" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="period_id" value="<?= $periodId ?>">
            <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
            <input type="hidden" name="action" value="download_errors">
            <input type="hidden" name="format" value="xlsx">
            <button class="btn btn-sm btn-outline-danger">Xuất lỗi XLSX</button>
          </form>
          <form method="post" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="period_id" value="<?= $periodId ?>">
            <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
            <input type="hidden" name="action" value="download_errors">
            <input type="hidden" name="format" value="csv">
            <button class="btn btn-sm btn-outline-danger">Xuất lỗi CSV</button>
          </form>
        </div>
      <?php endif; ?>
      <?php if (!empty($previewState['valid'])): ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="period_id" value="<?= $periodId ?>">
          <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
          <input type="hidden" name="action" value="confirm_import">
          <button class="btn btn-primary fw-semibold">Xác nhận lưu các dòng hợp lệ</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Bảng danh sách đơn phúc khảo -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
  <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold text-dark">Danh sách đơn phúc khảo</h6>
    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1.5 fw-semibold"><?= count($rows) ?> đơn</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Mã đơn</th>
          <th>Thí sinh</th>
          <th>Môn thi</th>
          <th>Số phách</th>
          <th>Điểm cũ</th>
          <th>Phân công cán bộ</th>
          <th>Trạng thái</th>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="font-monospace fw-bold text-dark">#<?= e((string)$r['id']) ?></td>
            <td>
              <div class="fw-semibold text-dark"><?= e($r['full_name']) ?></div>
              <div class="text-muted small font-monospace"><?= e($r['candidate_code']) ?></div>
            </td>
            <td><?= e($r['subject_code'] . ' - ' . $r['subject_name']) ?></td>
            <td class="font-monospace"><?= e((string)$r['anonymous_code']) ?></td>
            <td class="fw-bold text-danger"><?= e((string)$r['original_score']) ?></td>
            <td>
              <?php if ($r['assigned_grader_name']): ?>
                <span class="text-dark fw-semibold"><?= e($r['assigned_grader_name'] . ' (' . ($r['assigned_grader_username'] ?? '') . ')') ?></span>
              <?php else: ?>
                <form method="post" class="d-flex gap-1">
                  <?= csrf_field() ?>
                  <input type="hidden" name="period_id" value="<?= $periodId ?>">
                  <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
                  <input type="hidden" name="appeal_id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="action" value="assign">
                  <select name="council_member_id" class="form-select form-select-sm" required style="min-width: 140px;">
                    <option value="">Chọn cán bộ</option>
                    <?php foreach (appeal_grader_candidates($pdo, (int)$r['id']) as $m): ?>
                      <option value="<?= $m['council_member_id'] ?>"><?= e($m['full_name'] . ' (' . $m['username'] . ')') ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-sm btn-primary py-0 px-2">Gán</button>
                </form>
              <?php endif; ?>
            </td>
            <td><span class="badge bg-light text-secondary border rounded-pill"><?= e(appeal_status_label($r['status'])) ?></span></td>
            <td>
              <?php if ($r['status'] === 'pending' && $r['assigned_grader_user_id']): ?>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="period_id" value="<?= $periodId ?>">
                  <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
                  <input type="hidden" name="appeal_id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="action" value="start_grading">
                  <button class="btn btn-sm btn-outline-primary">Chuyển Đang chấm</button>
                </form>
              <?php elseif ($r['status'] === 'grading' && (int)$r['assigned_grader_user_id'] === $currentUserId): ?>
                <form method="post" class="d-flex gap-1">
                  <?= csrf_field() ?>
                  <input type="hidden" name="period_id" value="<?= $periodId ?>">
                  <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
                  <input type="hidden" name="appeal_id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="action" value="complete">
                  <input name="new_score" type="number" min="0" max="<?= e((string)$r['max_score']) ?>" step="0.01" value="<?= e((string)$r['original_score']) ?>" class="form-control form-control-sm" style="width: 80px;" required>
                  <input name="reason" class="form-control form-control-sm" placeholder="Lý do" required style="width: 100px;">
                  <button class="btn btn-sm btn-success py-0 px-2">Lưu</button>
                </form>
              <?php elseif ($r['status'] === 'completed'): ?>
                <span class="badge bg-success bg-opacity-10 text-success rounded-pill">Điểm mới: <?= e((string)$r['new_score']) ?></span>
              <?php else: ?>
                <span class="text-muted small">Chờ phân công</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted"><?= $periodId > 0 ? 'Không có đơn phúc khảo nào trong kỳ này.' : 'Vui lòng chọn kỳ tuyển sinh để xem danh sách.' ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$pagePath='/appeals/update_result.php';require __DIR__.'/../includes/list_pagination.php';workflow_layout_end();
