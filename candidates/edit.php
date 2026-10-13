<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/candidate_service.php';

require_permission('candidate.update');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    http_response_code(404);
    exit('Không tìm thấy thí sinh.');
}

$candidate = candidate_find((int) $id);
if (!$candidate) {
    http_response_code(404);
    exit('Không tìm thấy thí sinh.');
}

$data = $_POST ?: $candidate;
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    [$data, $errors] = candidate_validate($_POST);
    if (!$errors && candidate_duplicate($data['national_id'], (int) $id)) {
        $errors['national_id'] = 'CCCD/CMND đã tồn tại.';
    }
    if (!$errors) {
        try {
            candidate_update((int) $id, $data);
            header('Location: ' . url('/candidates/index.php?updated=1'));
            exit;
        } catch (Throwable $e) {
            error_log('Candidate update error: ' . $e->getMessage());
            $errors['general'] = 'Không thể cập nhật dữ liệu, vui lòng kiểm tra lại thông tin.';
        }
    }
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Sửa thí sinh';
$activePage = 'candidates';

admin_layout_start('Sửa thí sinh', 'candidates', ['Quản lý thí sinh' => url('/candidates/index.php'), 'Sửa thí sinh' => '']);
?>
  <div class="row justify-content-center">
    <div class="col-xl-9">
      <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <div>
          <h1 class="h3 fw-bold mb-1">Sửa thí sinh/học sinh</h1>
          <p class="text-muted mb-0">Cập nhật thông tin hồ sơ đang được chọn.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= e(url('/candidates/index.php')) ?>">Quay lại</a>
      </div>
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-lg-5">
          <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger"><?= e($errors['general']) ?></div>
          <?php elseif ($errors): ?>
            <div class="alert alert-danger">Vui lòng kiểm tra lại các thông tin chưa hợp lệ.</div>
          <?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="national_id">CCCD/CMND</label>
                <input class="form-control <?= isset($errors['national_id']) ? 'is-invalid' : '' ?>" id="national_id" name="national_id" required value="<?= e($data['national_id'] ?? '') ?>">
                <?php if (isset($errors['national_id'])): ?><div class="invalid-feedback"><?= e($errors['national_id']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="full_name">Họ và tên</label>
                <input class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" id="full_name" name="full_name" required value="<?= e($data['full_name'] ?? '') ?>">
                <?php if (isset($errors['full_name'])): ?><div class="invalid-feedback"><?= e($errors['full_name']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold" for="date_of_birth">Ngày sinh</label>
                <input class="form-control <?= isset($errors['date_of_birth']) ? 'is-invalid' : '' ?>" id="date_of_birth" type="date" name="date_of_birth" value="<?= e($data['date_of_birth'] ?? '') ?>">
                <?php if (isset($errors['date_of_birth'])): ?><div class="invalid-feedback"><?= e($errors['date_of_birth']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold" for="gender">Giới tính</label>
                <input class="form-control" id="gender" name="gender" value="<?= e($data['gender'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold" for="middle_school_id">Mã trường THCS</label>
                <input class="form-control" id="middle_school_id" type="number" name="middle_school_id" min="0" value="<?= e((string) ($data['middle_school_id'] ?? '')) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="phone">Điện thoại</label>
                <input class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" value="<?= e($data['phone'] ?? '') ?>">
                <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="email">Email</label>
                <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" type="email" name="email" value="<?= e($data['email'] ?? '') ?>">
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold" for="address">Địa chỉ</label>
                <input class="form-control" id="address" name="address" value="<?= e($data['address'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="eligible">Đủ điều kiện dự thi</label>
                <select class="form-select" id="eligible" name="eligible">
                  <option value="1" <?= (int) ($data['eligible'] ?? 1) === 1 ? 'selected' : '' ?>>Có</option>
                  <option value="0" <?= (int) ($data['eligible'] ?? 1) === 0 ? 'selected' : '' ?>>Không</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="status">Trạng thái</label>
                <select class="form-select" id="status" name="status">
                  <option value="active" <?= ($data['status'] ?? '') === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                  <option value="inactive" <?= ($data['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Không hoạt động</option>
                </select>
              </div>
            </div>
            <div class="d-flex gap-2 mt-4">
              <button class="btn btn-primary px-4 fw-medium" type="submit">Lưu thay đổi</button>
              <a class="btn btn-outline-secondary px-4" href="<?= e(url('/candidates/index.php')) ?>">Hủy</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php
admin_layout_end();
