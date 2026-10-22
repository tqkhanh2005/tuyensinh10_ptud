<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/candidate_service.php';
require_once __DIR__ . '/../includes/candidate_import_service.php';

require_permission('candidate.create');

$pdo = db();
$error = '';
$previewResult = null;
$batchId = '';

// Khởi tạo container lưu trữ batch trong session
if (!isset($_SESSION['candidate_import_batches'])) {
    $_SESSION['candidate_import_batches'] = [];
}

// XỬ LÝ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = trim((string)($_POST['action'] ?? 'upload'));

    if ($action === 'upload') {
        // BƯỚC 1: XỬ LÝ UPLOAD VÀ PREVIEW
        $file = $_FILES['import_file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $error = 'Vui lòng chọn tệp CSV cần nhập dữ liệu.';
        } elseif (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $error = 'Có lỗi xảy ra trong quá trình tải tệp lên máy chủ (mã lỗi: ' . (int)$file['error'] . ').';
        } else {
            $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $error = 'Định dạng tệp không được hỗ trợ. Hiện tại hệ thống hỗ trợ định dạng tệp .csv (chuẩn UTF-8).';
            } elseif ((int)$file['size'] > CANDIDATE_MAX_FILE_SIZE) {
                $error = 'Dung lượng tệp vượt quá giới hạn cho phép (tối đa 10 MB).';
            } else {
                try {
                    $rawRows = candidate_import_parse_csv((string)$file['tmp_name']);
                    $previewResult = candidate_import_validate_batch($pdo, $rawRows);

                    $batchId = bin2hex(random_bytes(16));
                    $confirmToken = bin2hex(random_bytes(32));

                    $_SESSION['candidate_import_batches'][$batchId] = [
                        'normalized_rows' => $previewResult['normalized_rows'],
                        'total_rows' => $previewResult['total_rows'],
                        'can_import' => $previewResult['can_import'],
                        'confirm_token' => $confirmToken,
                        'created_at' => time(),
                    ];

                    $previewResult['batch_id'] = $batchId;
                    $previewResult['confirm_token'] = $confirmToken;
                } catch (InvalidArgumentException $e) {
                    $error = $e->getMessage();
                } catch (Throwable $e) {
                    error_log('Candidate import parse error: ' . $e->getMessage());
                    $error = 'Không thể xử lý tệp dữ liệu. Vui lòng kiểm tra lại cấu trúc tệp.';
                }
            }
        }
    } elseif ($action === 'confirm') {
        // BƯỚC 3: XÁC NHẬN IMPORT VÀO DATABASE
        $bId = trim((string)($_POST['batch_id'] ?? ''));
        $cToken = trim((string)($_POST['confirm_token'] ?? ''));

        $batch = $_SESSION['candidate_import_batches'][$bId] ?? null;

        if (!$batch || !is_array($batch)) {
            $error = 'Phiên làm việc nhập dữ liệu đã hết hạn hoặc không tồn tại. Vui lòng tải lại tệp.';
        } elseif (!hash_equals((string)$batch['confirm_token'], $cToken)) {
            $error = 'Mã xác thực phiên nhập dữ liệu không hợp lệ.';
        } elseif (empty($batch['can_import'])) {
            $error = 'Tệp dữ liệu vẫn còn lỗi và không thể nhập vào hệ thống.';
        } else {
            try {
                $importedCount = candidate_import_execute($pdo, $batch['normalized_rows']);
                unset($_SESSION['candidate_import_batches'][$bId]);
                header('Location: ' . url('/candidates/index.php?imported=' . $importedCount));
                exit;
            } catch (Throwable $e) {
                error_log('Candidate import execute error: ' . $e->getMessage());
                $error = 'Đã xảy ra lỗi trong quá trình ghi dữ liệu. Toàn bộ đợt nhập đã được hoàn tác an toàn.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Nhập danh sách thí sinh';
$activePage = 'candidates';

admin_layout_start('Nhập danh sách thí sinh', 'candidates', ['Quản lý thí sinh' => url('/candidates/index.php'), 'Import thí sinh' => '']);
?>
  <!-- Tiêu đề & Nút quay lại -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">
        Nhập danh sách thí sinh/học sinh
      </h1>
      <p class="text-muted mb-0">Hỗ trợ nhập dữ liệu hàng loạt từ tệp bảng tính CSV (chuẩn UTF-8).</p>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-primary" href="<?= e(url('/candidates/template.php')) ?>">
        Tải file mẫu CSV
      </a>
      <a class="btn btn-outline-secondary" href="<?= e(url('/candidates/index.php')) ?>">
        Quay lại danh sách
      </a>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
      <div class="fw-semibold mb-1">Thông báo lỗi:</div>
      <div><?= e($error) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Card hướng dẫn quy định cấu trúc tệp -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
    <div class="card-header bg-white border-bottom py-3 px-4">
      <h6 class="mb-0 fw-bold text-dark">
        Quy định cấu trúc tệp và định dạng dữ liệu
      </h6>
    </div>
    <div class="card-body p-4 small">
      <div class="row g-3">
        <div class="col-md-6">
          <ul class="mb-0 ps-3 text-secondary">
            <li class="mb-1"><strong>Định dạng chấp nhận:</strong> Tệp bảng tính <code>.csv</code> (mã hóa UTF-8).</li>
            <li class="mb-1"><strong>Giới hạn:</strong> Dung lượng tối đa <strong>10 MB</strong>, tối đa <strong>5.000 dòng</strong> dữ liệu mỗi lần nhập.</li>
            <li class="mb-1"><strong>Dòng tiêu đề (Header):</strong> Dòng đầu tiên bắt buộc phải chứa đúng 10 cột theo thứ tự:
              <br><code class="text-primary">national_id, full_name, date_of_birth, gender, address, phone, email, middle_school_code, eligible, status</code>
            </li>
            <li class="mb-1"><strong>national_id (Bắt buộc):</strong> Số CMND/CCCD/Định danh cá nhân, không trùng lặp trong tệp và chưa tồn tại trong hệ thống.</li>
            <li class="mb-1"><strong>full_name (Bắt buộc):</strong> Họ và tên đầy đủ của thí sinh.</li>
          </ul>
        </div>
        <div class="col-md-6">
          <ul class="mb-0 ps-3 text-secondary">
            <li class="mb-1"><strong>date_of_birth:</strong> Định dạng chuẩn <code>YYYY-MM-DD</code> (ví dụ: <code>2009-05-15</code>).</li>
            <li class="mb-1"><strong>middle_school_code:</strong> Mã trường THCS (<code>school_code</code>) đang hoạt động trong hệ thống.</li>
            <li class="mb-1"><strong>eligible (Bắt buộc):</strong> Đủ điều kiện dự thi, chỉ nhận giá trị <code>1</code> (Có) hoặc <code>0</code> (Không).</li>
            <li class="mb-1"><strong>status (Bắt buộc):</strong> Trạng thái hồ sơ, chỉ nhận <code>active</code> (Hoạt động) hoặc <code>inactive</code> (Không hoạt động).</li>
            <li class="mb-1"><strong>Nguyên tắc giao dịch:</strong> Quá trình nhập thực hiện trong 1 giao dịch an toàn (Transaction), nếu có bất kỳ lỗi nào sẽ hoàn tác toàn bộ dữ liệu.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <?php if (!$previewResult): ?>
    <!-- BƯỚC 1: FORM TẢI LÊN TỆP -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
      <div class="card-body p-4 p-md-5">
        <h5 class="fw-bold mb-3">Bước 1: Chọn tệp dữ liệu cần nhập</h5>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="upload">
          <div class="mb-4">
            <label for="import_file" class="form-label fw-semibold">Chọn tệp (.csv) từ máy tính:</label>
            <input type="file" class="form-control form-control-lg" id="import_file" name="import_file" accept=".csv" required>
            <div class="form-text mt-2 text-muted">
              Khuyến nghị: Tải tệp mẫu <a href="<?= e(url('/candidates/template.php')) ?>" class="text-primary fw-medium">mau_import_thi_sinh.csv</a> để đối chiếu cấu trúc dữ liệu trước khi tải lên.
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-lg px-4">
            Kiểm tra & Xem trước dữ liệu
          </button>
        </form>
      </div>
    </div>
  <?php else: ?>
    <!-- BƯỚC 2: KẾT QUẢ KIỂM TRA & XEM TRƯỚC -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
      <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
          <h5 class="fw-bold mb-0">Bước 2: Kết quả kiểm tra dữ liệu</h5>
          <a href="<?= e(url('/candidates/import.php')) ?>" class="btn btn-sm btn-outline-secondary">
            Tải lên tệp khác
          </a>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="p-3 bg-light rounded-3 text-center border">
              <div class="text-muted small fw-semibold">Tổng số dòng dữ liệu</div>
              <div class="fs-3 fw-bold text-dark"><?= (int)$previewResult['total_rows'] ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 bg-success bg-opacity-10 rounded-3 text-center border border-success border-opacity-25">
              <div class="text-success small fw-semibold">Dòng hợp lệ</div>
              <div class="fs-3 fw-bold text-success"><?= (int)$previewResult['valid_rows'] ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 <?= $previewResult['error_rows'] > 0 ? 'bg-danger bg-opacity-10 border border-danger border-opacity-25' : 'bg-light border' ?> rounded-3 text-center">
              <div class="<?= $previewResult['error_rows'] > 0 ? 'text-danger' : 'text-muted' ?> small fw-semibold">Dòng phát hiện lỗi</div>
              <div class="fs-3 fw-bold <?= $previewResult['error_rows'] > 0 ? 'text-danger' : 'text-muted' ?>">
                <?= (int)$previewResult['error_rows'] ?>
              </div>
            </div>
          </div>
        </div>

        <?php if (!empty($previewResult['errors'])): ?>
          <!-- Danh sách lỗi chi tiết -->
          <div class="alert alert-danger mb-4">
            <h6 class="fw-bold mb-2">
              Phát hiện <?= count($previewResult['errors']) ?> lỗi cần khắc phục:
            </h6>
            <div class="table-responsive bg-white rounded-3 mt-2 border">
              <table class="table table-sm table-hover mb-0">
                <thead class="table-light small">
                  <tr>
                    <th style="width: 80px;">Dòng</th>
                    <th style="width: 160px;">Cột</th>
                    <th style="width: 200px;">Giá trị nhập</th>
                    <th>Lý do lỗi</th>
                  </tr>
                </thead>
                <tbody class="small">
                  <?php foreach ($previewResult['errors'] as $err): ?>
                    <tr>
                      <td class="fw-bold text-danger">Dòng <?= (int)$err['line'] ?></td>
                      <td class="font-monospace fw-semibold"><?= e($err['column']) ?></td>
                      <td class="text-truncate" style="max-width: 200px;"><?= e($err['value'] !== '' ? $err['value'] : '(trống)') ?></td>
                      <td class="text-danger"><?= e($err['reason']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="small mt-2 text-muted">
              * Toàn bộ tệp phải hoàn toàn hợp lệ trước khi được phép ghi vào cơ sở dữ liệu.
            </div>
          </div>
        <?php else: ?>
          <div class="alert alert-success mb-4">
            <div>Tất cả <strong><?= (int)$previewResult['total_rows'] ?></strong> dòng dữ liệu đều hợp lệ và sẵn sàng nhập vào hệ thống!</div>
          </div>
        <?php endif; ?>

        <!-- Bảng xem trước tối đa 50 dòng -->
        <h6 class="fw-bold mb-2 text-secondary">
          Dữ liệu xem trước (tối đa 50 dòng đầu tiên):
        </h6>
        <div class="table-responsive border rounded-3 mb-4">
          <table class="table table-sm table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;">Dòng</th>
                <th>CCCD/CMND</th>
                <th>Họ và tên</th>
                <th>Ngày sinh</th>
                <th>Mã trường THCS</th>
                <th>Đủ điều kiện</th>
                <th>Trạng thái</th>
                <th class="text-center">Kiểm tra</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($previewResult['preview_data'] as $pRow): ?>
                <tr class="<?= !$pRow['is_valid'] ? 'table-danger' : '' ?>">
                  <td class="text-muted"><?= (int)$pRow['_line_number'] ?></td>
                  <td class="font-monospace fw-semibold"><?= e($pRow['national_id']) ?></td>
                  <td><?= e($pRow['full_name']) ?></td>
                  <td><?= e($pRow['date_of_birth'] ?? '-') ?></td>
                  <td><?= e($pRow['middle_school_code'] !== '' ? $pRow['middle_school_code'] : '-') ?></td>
                  <td><?= (int)$pRow['eligible'] === 1 ? '<span class="badge bg-success">Có</span>' : '<span class="badge bg-secondary">Không</span>' ?></td>
                  <td><?= $pRow['status'] === 'active' ? '<span class="badge bg-success">Hoạt động</span>' : '<span class="badge bg-secondary">Không HĐ</span>' ?></td>
                  <td class="text-center">
                    <?php if ($pRow['is_valid']): ?>
                      <span class="badge bg-success-subtle text-success border border-success-subtle">Hợp lệ</span>
                    <?php else: ?>
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Lỗi</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- BƯỚC 3: FORM XÁC NHẬN IMPORT -->
        <?php if ($previewResult['can_import']): ?>
          <form method="post" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="confirm">
            <input type="hidden" name="batch_id" value="<?= e($previewResult['batch_id']) ?>">
            <input type="hidden" name="confirm_token" value="<?= e($previewResult['confirm_token']) ?>">
            <button type="submit" class="btn btn-success btn-lg px-4 fw-medium">
              Xác nhận nhập <?= (int)$previewResult['total_rows'] ?> thí sinh vào hệ thống
            </button>
            <a href="<?= e(url('/candidates/import.php')) ?>" class="btn btn-outline-secondary btn-lg">Hủy bỏ</a>
          </form>
        <?php else: ?>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary btn-lg" disabled>
              Không thể nhập do còn lỗi
            </button>
            <a href="<?= e(url('/candidates/import.php')) ?>" class="btn btn-primary btn-lg">
              Tải lại tệp sau khi sửa
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
<?php
admin_layout_end();
