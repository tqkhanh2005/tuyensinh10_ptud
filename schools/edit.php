<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_edit_service.php';

require_permission('school.manage');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    http_response_code(404);
    exit('Không tìm thấy trường.');
}

try {
    $pdo = db();
    $school = school_find($pdo, (int)$id);
} catch (Throwable $e) {
    error_log('School find error: ' . $e->getMessage());
    http_response_code(500);
    exit('Không thể tải thông tin trường. Vui lòng thử lại sau.');
}

if (!$school) {
    http_response_code(404);
    exit('Không tìm thấy trường.');
}

$errors = [];
$data = $school;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $data = school_batch_rows([$_POST])[0];
    try {
        $errors = school_edit_save($pdo, (int)$id, $data);
        if (!$errors) {
            header('Location: ' . url('/schools/index.php?updated=1'));
            exit;
        }
    } catch (OutOfBoundsException $e) {
        http_response_code(404);
        $errors['general'] = $e->getMessage();
    } catch (Throwable $e) {
        error_log('School update error: ' . $e->getMessage());
        $errors['general'] = 'Không thể cập nhật dữ liệu. Thay đổi chưa được lưu. Vui lòng thử lại sau.';
    }
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Sửa trường học';
$activePage = 'schools';

admin_layout_start('Sửa trường học', 'schools', ['Quản lý trường học' => url('/schools/index.php'), 'Sửa trường' => '']);
?>
<link rel="stylesheet" href="<?= e(url('/schools/schools.css')) ?>">
<section class="schools-page schools-edit" aria-label="Quản lý trường học">
  <div class="row justify-content-center">
    <div class="col-12 schools-edit-container">
      <div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
        <div class="mb-4 position-relative pe-4">
          <a class="btn-close position-absolute top-0 end-0" href="<?= e(url('/schools/index.php')) ?>" aria-label="Đóng form, không lưu thay đổi"></a>
          <h1 class="h3 fw-bold text-dark mb-1">Cập nhật thông tin trường</h1>
          <p class="text-muted mb-0">Chỉnh sửa thông tin trường THCS, THPT tại TP.HCM. Cả 5 thông tin đều bắt buộc.</p>
          <p class="text-muted mt-2 mb-0">Mã trường: <strong><?= e($school['school_code']) ?></strong></p>
        </div>

        <?php if ($errors): ?>
          <div class="alert alert-danger mb-4" role="alert">
            <div class="fw-semibold mb-1">Đã xảy ra lỗi, vui lòng kiểm tra lại:</div>
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/schools/edit.php?id=' . (int)$id)) ?>" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small" for="name">Tên trường <span class="text-danger">*</span></label>
              <input class="form-control" id="name" name="name" maxlength="150" value="<?= e($data['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small" for="level">Cấp học <span class="text-danger">*</span></label>
              <select class="form-select" id="level" name="level" required>
                <option value="">Chọn cấp học</option>
                <option value="THCS" <?= ($data['level'] ?? '') === 'THCS' ? 'selected' : '' ?>>THCS</option>
                <option value="THPT" <?= ($data['level'] ?? '') === 'THPT' ? 'selected' : '' ?>>THPT</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small" for="phone">Số điện thoại <span class="text-danger">*</span></label>
              <input class="form-control" type="tel" id="phone" name="phone" maxlength="30" value="<?= e($data['phone'] ?? '') ?>" required aria-describedby="phone-help">
              <div class="form-text" id="phone-help">Dùng đầu số 0 hoặc +84.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small" for="email">Email liên hệ <span class="text-danger">*</span></label>
              <input class="form-control" type="email" id="email" name="email" maxlength="150" value="<?= e($data['email'] ?? '') ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold text-secondary small" for="address">Địa chỉ <span class="text-danger">*</span></label>
              <input class="form-control" id="address" name="address" maxlength="255" value="<?= e($data['address'] ?? '') ?>" required>
            </div>
          </div>

          <div class="schools-form-actions">
            <button class="btn btn-primary px-4 fw-medium" type="submit">
              Xác nhận
            </button>
            <a class="btn btn-outline-secondary px-4" href="<?= e(url('/schools/index.php')) ?>">Hủy</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
<?php
admin_layout_end();

