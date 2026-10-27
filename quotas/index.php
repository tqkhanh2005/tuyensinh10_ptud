<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quota_workflow_service.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_permission('admission.manage');
$pdo = db();
$periodId = (int)($_GET['period_id'] ?? 0);
$period = admission_period_find($pdo, $periodId);
if (!$period) {
    http_response_code(404);
    admin_layout_start('Không tìm thấy kỳ tuyển sinh', 'quotas');
    echo '<div class="alert alert-warning">Vui lòng chọn một kỳ tuyển sinh hợp lệ.</div><a class="btn btn-primary" href="' . e(url('/admission/index.php')) . '">Danh sách kỳ tuyển sinh</a>';
    admin_layout_end();
    exit;
}
$configuration = period_configuration($pdo, $periodId);
$rows = quota_rows($pdo, $periodId);
$blocked = quota_edit_block_reason($pdo, $periodId);
if (isset($_GET['template'])) excel_xlsx_download('mau_chi_tieu.xlsx', ['Mã trường', 'Chỉ tiêu'], []);
$values = [];
foreach ($rows as $row) $values[$row['school_id']] = (int)$row['quota'] > 0 ? (string)$row['quota'] : '';
$errors = [];
$error = '';
$notice = '';
$confirmation = null;
$dirty = false;
$snapshot = quota_snapshot($rows);
$postedSnapshot = $snapshot;
$revision = $configuration['revision'] ?? null;
$postedRevision = $revision;
$pendingKey = 'quota_confirmation_' . $periodId;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $input = is_array($_POST['quota'] ?? null) ? $_POST['quota'] : [];
    foreach ($input as $id => $value) $values[$id] = is_scalar($value) ? trim((string)$value) : '';
    $dirty = $input !== [];
    $postedSnapshot = is_string($_POST['snapshot'] ?? null) ? $_POST['snapshot'] : '';
    $postedRevision = isset($_POST['configuration_revision']) && is_scalar($_POST['configuration_revision']) ? (int)$_POST['configuration_revision'] : null;
    try {
        if ($action === 'publish') {
            quota_publish($pdo, $periodId);
            $notice = 'Đã công bố chỉ tiêu chính thức.';
        } else {
            if ($blocked !== '') throw new RuntimeException($blocked);
            if (!hash_equals($snapshot, $postedSnapshot) || ($revision !== null && $postedRevision !== $revision)) {
                throw new RuntimeException('Danh sách trường hoặc chỉ tiêu đã thay đổi. Dữ liệu nhập được giữ lại; hãy ghi lại các thay đổi và tải lại trang để đối chiếu.');
            }
            if ($action === 'import') {
                $imported = quota_import_upload($_FILES['excel'] ?? [], $rows);
                $values = array_replace($values, $imported);
                $dirty = true;
                unset($_SESSION[$pendingKey]);
                $notice = 'Đã điền chỉ tiêu từ Excel vào bảng, chưa lưu vào CSDL. Vui lòng rà soát và chọn Lưu tất cả.';
            } elseif ($action === 'preview' || $action === 'confirm') {
                $prepared = quota_prepare_changes($rows, array_replace($values, $input));
                $errors = $prepared['errors'];
                if ($errors) throw new InvalidArgumentException('Chưa lưu trường nào. Vui lòng sửa tất cả các dòng được đánh dấu.');
                if (!$prepared['changes']) throw new InvalidArgumentException('Chưa có chỉ tiêu thay đổi để lưu.');
                if ($action === 'preview') {
                    $confirmation = ['token' => bin2hex(random_bytes(24)), 'changes' => $prepared['changes'], 'snapshot' => $snapshot, 'expires' => time() + 1800];
                    $_SESSION[$pendingKey] = $confirmation;
                } else {
                    $pending = $_SESSION[$pendingKey] ?? null;
                    $token = is_string($_POST['confirmation_token'] ?? null) ? $_POST['confirmation_token'] : '';
                    if (!$pending || !hash_equals($pending['token'], $token) || $pending['expires'] < time() || $pending['snapshot'] !== $snapshot || $pending['changes'] !== $prepared['changes']) {
                        throw new RuntimeException('Xác nhận đã hết hạn hoặc dữ liệu đã đổi. Vui lòng chọn Lưu tất cả để xác nhận lại.');
                    }
                    quota_save_all($pdo, $periodId, $prepared['changes'], $postedRevision, $postedSnapshot);
                    unset($_SESSION[$pendingKey]);
                    header('Location: ' . url('/quotas/index.php?period_id=' . $periodId . '&saved=1'));
                    exit;
                }
            } else throw new InvalidArgumentException('Thao tác không hợp lệ.');
        }
    } catch (Throwable $e) {
        error_log('Quota workflow: ' . $e->getMessage());
        $error = $e instanceof PDOException ? 'Không thể xử lý chỉ tiêu. Chưa lưu trường nào, vui lòng thử lại.' : $e->getMessage();
    }
}
if (isset($_GET['saved'])) $notice = 'Lưu chỉ tiêu tuyển sinh thành công.';
$setCount = count(array_filter($rows, static fn($r) => $r['quota_status'] === 'set' && (int)$r['quota'] > 0));
admin_layout_start('Đặt chỉ tiêu tuyển sinh', 'quotas', ['Kỳ tuyển sinh' => url('/admission/index.php'), 'Chỉ tiêu' => '']);
?>
<link rel="stylesheet" href="<?= e(url('/quotas/quotas.css')) ?>">
<section class="quota-page">
  <div class="quota-heading">
    <div><h1>Đặt chỉ tiêu tuyển sinh</h1><p class="text-muted mb-0"><?= e($period['name']) ?> · <?= e($period['academic_year']) ?> · TP.HCM</p></div>
    <div class="quota-actions">
      <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quota-import" <?= $blocked !== '' ? 'disabled' : '' ?>>Nhập từ Excel</button>
      <button type="submit" form="quota-form" name="action" value="preview" class="btn btn-primary" <?= $blocked !== '' || !$rows ? 'disabled' : '' ?>>Lưu tất cả</button>
    </div>
  </div>
  <?php if ($configuration): ?><div class="alert alert-info"><?= $configuration['settings']['mode'] === 'simulation' ? 'MÔ PHỎNG — không phải chỉ tiêu chính thức.' : 'Kỳ chính thức — chỉ tiêu chỉ hiển thị công khai sau khi được công bố.' ?></div><?php endif; ?>
  <?php if ($blocked !== ''): ?><div class="alert alert-warning" role="status"><?= e($blocked) ?></div><?php else: ?>
  <p class="small text-muted">Chỉ nhập số nguyên lớn hơn 0. Ô trống là chưa đặt; không dùng ô trống để xóa chỉ tiêu đã đặt. Chỉ được sửa trước khi kỳ thi bắt đầu. Nếu chưa có lịch thi, hệ thống cho phép thiết lập trong kỳ nháp.</p>
  <?php endif; ?>
  <?php if ($notice !== ''): ?><div class="alert alert-success" role="status"><?= e($notice) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?>
    <?php if ($errors): ?><ul class="mb-0"><?php foreach ($errors as $id => $message): ?><li><a href="#quota-row-<?= e((string)$id) ?>" class="quota-error-link">Trường <?= e((string)$id) ?></a>: <?= e($message) ?></li><?php endforeach; ?></ul><?php endif; ?>
  </div><?php endif; ?>
  <p class="text-muted">Tổng số: <strong><?= count($rows) ?></strong> trường THPT · Đã đặt: <strong><?= $setCount ?></strong> · Chưa đặt: <strong><?= count($rows) - $setCount ?></strong></p>
  <div class="quota-filters">
    <div><label for="quota-search" class="form-label">Tìm trường</label><input id="quota-search" class="form-control" placeholder="Mã trường hoặc tên trường" value="<?= e(is_scalar($_GET['q'] ?? '') ? (string)($_GET['q'] ?? '') : '') ?>"></div>
    <div><label for="quota-status" class="form-label">Trạng thái đã lưu</label><select id="quota-status" class="form-select"><option value="">Tất cả trạng thái</option><option value="set">Đã đặt</option><option value="not_set">Chưa đặt</option></select></div>
    <div><label for="quota-page-size" class="form-label">Số dòng mỗi trang</label><select id="quota-page-size" class="form-select"><option>20</option><option>50</option><option>100</option></select></div>
  </div>
  <form method="post" enctype="multipart/form-data" id="quota-form" novalidate data-dirty="<?= $dirty ? '1' : '0' ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="snapshot" value="<?= e($postedSnapshot) ?>">
    <?php if ($postedRevision !== null): ?><input type="hidden" name="configuration_revision" value="<?= (int)$postedRevision ?>"><?php endif; ?>
    <div class="quota-table-card"><div class="table-responsive">
      <table class="table table-hover align-middle mb-0"><thead><tr><th scope="col">Mã trường</th><th scope="col">Tên trường THPT</th><th scope="col">Chỉ tiêu</th><th scope="col">Trạng thái</th></tr></thead><tbody>
      <?php foreach ($rows as $row): $id = (int)$row['school_id']; $isSet = $row['quota_status'] === 'set' && (int)$row['quota'] > 0; ?>
        <tr id="quota-row-<?= $id ?>" data-quota-row data-search="<?= e($row['school_code'] . ' ' . $row['name']) ?>" data-status="<?= $isSet ? 'set' : 'not_set' ?>" class="<?= isset($errors[$id]) ? 'table-danger' : '' ?>">
          <td class="font-monospace"><?= e($row['school_code']) ?></td><td><?= e($row['name']) ?></td>
          <td><label class="visually-hidden" for="quota-<?= $id ?>">Chỉ tiêu <?= e($row['name']) ?></label>
            <input type="text" inputmode="numeric" id="quota-<?= $id ?>" name="quota[<?= $id ?>]" class="form-control quota-input <?= isset($errors[$id]) ? 'is-invalid' : '' ?>" value="<?= e($values[$id]) ?>" <?= $blocked !== '' ? 'readonly' : '' ?> <?= isset($errors[$id]) ? 'aria-invalid="true" aria-describedby="quota-error-' . $id . '"' : '' ?> placeholder="Chưa đặt">
            <?php if (isset($errors[$id])): ?><div id="quota-error-<?= $id ?>" class="invalid-feedback"><?= e($errors[$id]) ?></div><?php endif; ?>
          </td><td><span class="badge <?= $isSet ? 'bg-primary bg-opacity-10 text-primary' : 'bg-secondary bg-opacity-10 text-secondary' ?>"><?= $isSet ? 'Đã đặt' : 'Chưa đặt' ?></span></td>
        </tr>
      <?php endforeach; ?>
      <tr id="quota-empty" <?= $rows ? 'hidden' : '' ?>><td colspan="4" class="text-center text-muted py-5">Không có trường THPT phù hợp.</td></tr>
      </tbody></table>
    </div></div>
    <div class="quota-pagination"><span id="quota-page-info" aria-live="polite"></span><div class="quota-actions"><button type="button" id="quota-prev" class="btn btn-outline-secondary">Trang trước</button><button type="button" id="quota-next" class="btn btn-outline-secondary">Trang sau</button></div></div>
    <div class="modal fade" id="quota-import" tabindex="-1" aria-labelledby="quota-import-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
      <div class="modal-header"><h2 id="quota-import-title" class="modal-title fs-5">Nhập chỉ tiêu từ Excel</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
      <div class="modal-body"><a href="?period_id=<?= $periodId ?>&amp;template=1" id="quota-template" class="btn btn-outline-primary mb-3">Tải mẫu Excel (.xlsx)</a><p class="text-muted">File gồm đúng 2 cột: Mã trường, Chỉ tiêu. Chỉ chấp nhận .xlsx, tối đa 2 MB. Mã trường phải thuộc danh mục THPT đang hoạt động, không được lặp.</p><label for="quota-excel" class="form-label">Chọn file Excel</label><input type="file" id="quota-excel" name="excel" accept=".xlsx" class="form-control"><p class="small text-muted mt-3 mb-0">Dữ liệu hợp lệ sẽ điền vào bảng, chưa lưu vào CSDL. Nếu file có lỗi, toàn bộ dữ liệu file sẽ không được điền.</p></div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" name="action" value="import" class="btn btn-primary" <?= $blocked !== '' ? 'disabled' : '' ?>>Nhập vào bảng</button></div>
    </div></div></div>
    <?php if ($confirmation): ?>
    <input type="hidden" name="confirmation_token" value="<?= e($confirmation['token']) ?>">
    <div class="modal fade" id="quota-confirm" tabindex="-1" aria-labelledby="quota-confirm-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
      <div class="modal-header"><h2 id="quota-confirm-title" class="modal-title fs-5">Xác nhận lưu chỉ tiêu</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
      <div class="modal-body"><p>Tổng số trường sẽ được cập nhật: <strong><?= count($confirmation['changes']) ?></strong>.</p><p>Số trường thay đổi chỉ tiêu đã đặt trước đó: <strong><?= $prepared['replaced'] ?></strong>.</p><p class="text-muted mb-0">Chỉ tiêu chỉ được lưu khi bạn xác nhận.</p></div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Quay lại rà soát</button><button type="submit" name="action" value="confirm" class="btn btn-primary">Xác nhận lưu</button></div>
    </div></div></div>
    <?php endif; ?>
  </form>
  <?php if ($configuration && $configuration['settings']['mode'] === 'official' && $period['status'] === 'published'): ?>
  <form method="post" class="mt-4"><?= csrf_field() ?><button class="btn btn-outline-primary" name="action" value="publish">Công bố chỉ tiêu chính thức</button></form>
  <?php endif; ?>
  <a class="btn btn-outline-secondary mt-4" href="<?= e(url('/admission/index.php')) ?>">Quay lại kỳ tuyển sinh</a>
</section>
<script src="<?= e(url('/quotas/quotas.js')) ?>" defer></script>
<?php admin_layout_end();
