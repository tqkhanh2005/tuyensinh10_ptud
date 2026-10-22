<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/staff_service.php';

require_auth();
if (!auth_has_permission('staff.update') && !auth_has_permission('staff.manage')) {
    http_response_code(403);
    exit('403 Forbidden');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Không tìm thấy cán bộ - giáo viên.');
}

$staff = staff_find((int)$id);
if (!$staff) {
    http_response_code(404);
    exit('Không tìm thấy cán bộ - giáo viên.');
}

$accountInfo = staff_get_account_info((int)$id);
$errors = [];

// Kiểm tra tính hợp lệ trước khi cho phép đặt lại mật khẩu
if (!$accountInfo) {
    $errors['account'] = 'Cán bộ - giáo viên này chưa được cấp tài khoản người dùng liên kết.';
} elseif (!$accountInfo['is_teacher']) {
    $errors['account'] = 'Chỉ cho phép đặt lại mật khẩu cho tài khoản có vai trò Giáo viên (Teacher Portal).';
} elseif ((int)$staff['active'] !== 1) {
    $errors['account'] = 'Cán bộ - giáo viên đang ở trạng thái không hoạt động.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (empty($errors['account'])) {
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

        if ($password === '') {
            $errors['password'] = 'Vui lòng nhập mật khẩu mới.';
        } elseif (mb_strlen($password) < 6) {
            $errors['password'] = 'Mật khẩu mới phải có độ dài tối thiểu 6 ký tự.';
        }

        if ($passwordConfirm === '') {
            $errors['password_confirm'] = 'Vui lòng xác nhận lại mật khẩu mới.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Mật khẩu xác nhận không khớp.';
        }

        if (!$errors) {
            try {
                staff_reset_password((int)$id, $password);
                header('Location: ' . url('/staff/index.php?reset=1'));
                exit;
            } catch (InvalidArgumentException | RuntimeException $e) {
                $errors['form'] = $e->getMessage();
            } catch (Throwable $e) {
                error_log('Reset password error for staff_id ' . (int)$id);
                $errors['form'] = 'Không thể đặt lại mật khẩu. Vui lòng thử lại sau.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Đặt lại mật khẩu giáo viên';
$activePage = 'staff';

admin_layout_start('Đặt lại mật khẩu giáo viên', 'staff', [
    'Quản lý giáo viên' => url('/staff/index.php'),
    'Đặt lại mật khẩu' => '',
]);
?>
  <div class="row justify-content-center">
    <div class="col-lg-7 col-xl-6">
      <div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
          <div>
            <h1 class="h3 fw-bold mb-1">Đặt lại mật khẩu</h1>
            <p class="text-muted small mb-0">Cấp lại mật khẩu mới cho tài khoản Giáo viên (Teacher Portal).</p>
          </div>
          <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/staff/index.php')) ?>">Quay lại</a>
        </div>

        <?php if (!empty($errors['account'])): ?>
          <div class="alert alert-warning rounded-3 mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i><?= e($errors['account']) ?>
          </div>
          <div class="d-flex justify-content-start">
            <a class="btn btn-outline-secondary" href="<?= e(url('/staff/index.php')) ?>">Quay lại danh sách</a>
          </div>
        <?php else: ?>

          <?php if (!empty($errors['form'])): ?>
            <div class="alert alert-danger rounded-3 mb-4">
              <i class="fas fa-exclamation-circle me-2"></i><?= e($errors['form']) ?>
            </div>
          <?php endif; ?>

          <!-- Thông tin tài khoản -->
          <div class="card border bg-light rounded-3 mb-4">
            <div class="card-body p-3">
              <div class="row g-2 small">
                <div class="col-sm-4 text-muted">Họ tên cán bộ:</div>
                <div class="col-sm-8 fw-bold text-dark"><?= e($staff['full_name']) ?></div>

                <div class="col-sm-4 text-muted">Đơn vị công tác:</div>
                <div class="col-sm-8 text-dark"><?= e($staff['workplace']) ?></div>

                <div class="col-sm-4 text-muted">Tên đăng nhập:</div>
                <div class="col-sm-8 fw-bold text-primary font-monospace"><?= e($accountInfo['username']) ?></div>

                <div class="col-sm-4 text-muted">Phạm vi tài khoản:</div>
                <div class="col-sm-8"><span class="badge bg-success-subtle text-success border border-success-subtle">Teacher Portal</span></div>
              </div>
            </div>
          </div>

          <form method="post" action="<?= e(url('/staff/reset_password.php?id=' . (int)$id)) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$id ?>">

            <div class="mb-3">
              <label class="form-label fw-semibold" for="password">Mật khẩu mới *</label>
              <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required minlength="6" autocomplete="new-password" placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)">
              <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><?= e($errors['password']) ?></div>
              <?php else: ?>
                <div class="form-text small">Mật khẩu tối thiểu 6 ký tự.</div>
              <?php endif; ?>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold" for="password_confirm">Xác nhận mật khẩu mới *</label>
              <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" required minlength="6" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới để xác nhận">
              <?php if (isset($errors['password_confirm'])): ?>
                <div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div>
              <?php endif; ?>
            </div>

            <div class="d-flex gap-2">
              <button class="btn btn-warning fw-semibold px-4" type="submit">
                <i class="fas fa-key me-1"></i>Xác nhận đặt lại mật khẩu
              </button>
              <a class="btn btn-outline-secondary px-3" href="<?= e(url('/staff/index.php')) ?>">Hủy</a>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php
admin_layout_end();
