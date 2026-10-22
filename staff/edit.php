<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/staff_service.php';
require_permission('staff.update');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Không tìm thấy cán bộ - giáo viên');
}

try {
    $existing = staff_find((int)$id);
} catch (Throwable $e) {
    error_log('Staff find error: ' . $e->getMessage());
    http_response_code(500);
    exit('Không thể tải thông tin cán bộ - giáo viên. Vui lòng thử lại sau.');
}

if (!$existing) {
    http_response_code(404);
    exit('Không tìm thấy cán bộ - giáo viên');
}

$errors = [];
$data = [
    'full_name' => $existing['full_name'],
    'workplace' => $existing['workplace'],
    'phone' => $existing['phone'] ?? '',
    'email' => $existing['email'] ?? '',
    'staff_role' => $existing['staff_role'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    [$data, $validation] = staff_validate($_POST);
    $errors = array_merge($errors, $validation);

    if (!$errors) {
        try {
            if (staff_has_duplicate($data, (int)$id)) {
                $errors['form'] = 'Cán bộ - giáo viên đã tồn tại.';
            } else {
                staff_update((int)$id, $data);
                header('Location: ' . url('/staff/index.php?updated=1'));
                exit;
            }
        } catch (RuntimeException $e) {
            $errors['form'] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Staff update error: ' . $e->getMessage());
            $errors['form'] = 'Không thể cập nhật cán bộ - giáo viên. Vui lòng thử lại sau.';
        }
    }
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Sửa cán bộ - giáo viên';
$activePage = 'staff';

admin_layout_start('Sửa cán bộ - giáo viên', 'staff', ['Quản lý giáo viên' => url('/staff/index.php'), 'Sửa giáo viên' => '']);
?>
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
        <h1 class="h3 fw-bold mb-4">Sửa cán bộ - giáo viên</h1>
        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <?php foreach ($errors as $err): ?>
              <div><?= e($err) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="full_name">Họ tên *</label>
              <input class="form-control" id="full_name" name="full_name" value="<?= e($data['full_name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="workplace">Đơn vị công tác *</label>
              <input class="form-control" id="workplace" name="workplace" value="<?= e($data['workplace']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="phone">Số điện thoại</label>
              <input class="form-control" id="phone" name="phone" value="<?= e($data['phone']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="email">Email</label>
              <input class="form-control" type="email" id="email" name="email" value="<?= e($data['email']) ?>">
            </div>
            <div class="col-12">
              <label class="form-label" for="staff_role">Vai trò *</label>
              <input class="form-control" id="staff_role" name="staff_role" value="<?= e($data['staff_role']) ?>" required>
            </div>
          </div>
          <div class="mt-4 d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit">Cập nhật thông tin</button>
            <?php if (!empty($existing['user_id']) && (int)$existing['active'] === 1 && (auth_has_permission('staff.update') || auth_has_permission('staff.manage'))): ?>
              <a class="btn btn-outline-warning" href="<?= e(url('/staff/reset_password.php?id=' . (int)$id)) ?>">
                <i class="fas fa-key me-1"></i>Đặt lại mật khẩu
              </a>
            <?php endif; ?>
            <a class="btn btn-outline-secondary" href="<?= e(url('/staff/index.php')) ?>">Hủy</a>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php
admin_layout_end();

