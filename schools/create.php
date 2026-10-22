<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';

require_permission('school.manage');

$pdo = db();
$errors = [];
$data = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $errors = school_validate($data);

    if (!$errors) {
        try {
            school_create($pdo, $data);
            header('Location: ' . url('/schools/index.php'));
            exit;
        } catch (PDOException $e) {
            error_log('School create PDO error: ' . $e->getMessage());
            $errors['general'] = 'Không thể lưu dữ liệu. Vui lòng kiểm tra thông tin trùng lặp hoặc thử lại sau.';
        } catch (Throwable $e) {
            error_log('School create error: ' . $e->getMessage());
            $errors['general'] = 'Không thể lưu dữ liệu. Vui lòng thử lại sau.';
        }
    }
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Thêm trường học';
$activePage = 'schools';

admin_layout_start('Thêm trường học', 'schools', ['Quản lý trường học' => url('/schools/index.php'), 'Thêm trường' => '']);
?>
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
        <div class="mb-4">
          <h1 class="h3 fw-bold text-dark mb-1">Thêm trường THCS, THPT</h1>
          <p class="text-muted mb-0">Nhập thông tin trường học mới để đưa vào hệ thống tuyển sinh.</p>
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

        <form method="post">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small" for="school_code">Mã trường <span class="text-danger">*</span></label>
              <input class="form-control" id="school_code" name="school_code" value="<?= e($data['school_code'] ?? '') ?>" placeholder="VD: THPT01" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small" for="name">Tên trường <span class="text-danger">*</span></label>
              <input class="form-control" id="name" name="name" value="<?= e($data['name'] ?? '') ?>" placeholder="VD: THPT Châu Văn Liêm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small" for="level">Cấp học <span class="text-danger">*</span></label>
              <select class="form-select" id="level" name="level" required>
                <option value="THCS" <?= ($data['level'] ?? '') === 'THCS' ? 'selected' : '' ?>>THCS</option>
                <option value="THPT" <?= ($data['level'] ?? '') === 'THPT' ? 'selected' : '' ?>>THPT</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small" for="admission_area">Khu vực tuyển sinh</label>
              <input class="form-control" id="admission_area" name="admission_area" value="<?= e($data['admission_area'] ?? '') ?>" placeholder="VD: Khu vực 1">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small" for="district">Quận/Huyện</label>
              <input class="form-control" id="district" name="district" value="<?= e($data['district'] ?? '') ?>" placeholder="VD: Ninh Kiều">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small" for="phone">Số điện thoại</label>
              <input class="form-control" id="phone" name="phone" value="<?= e($data['phone'] ?? '') ?>" placeholder="VD: 02923821000">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small" for="email">Email</label>
              <input class="form-control" type="email" id="email" name="email" value="<?= e($data['email'] ?? '') ?>" placeholder="VD: truonghoc@example.com">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small" for="address">Địa chỉ</label>
              <input class="form-control" id="address" name="address" value="<?= e($data['address'] ?? '') ?>" placeholder="VD: 58 Ngô Quyền, Tân An, Ninh Kiều">
            </div>
          </div>

          <div class="mt-4 pt-2 d-flex gap-2">
            <button class="btn btn-primary px-4 fw-medium" type="submit">
              Lưu thông tin
            </button>
            <a class="btn btn-outline-secondary px-4" href="<?= e(url('/schools/index.php')) ?>">Hủy</a>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php
admin_layout_end();

