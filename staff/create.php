<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/staff_service.php';
require_permission('staff.create');

$errors = [];
$data = [
    'full_name' => '',
    'workplace' => '',
    'school_id' => null,
    'phone' => '',
    'email' => '',
    'staff_role' => '',
    'create_account' => false,
    'username' => '',
    'password' => '',
];

$activeSchools = staff_active_schools();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    [$data, $validation] = staff_validate($_POST);
    $errors = array_merge($errors, $validation);

    $createAccount = !empty($_POST['create_account']);
    $data['create_account'] = $createAccount;
    $data['username'] = trim((string)($_POST['username'] ?? ''));
    $data['password'] = (string)($_POST['password'] ?? '');

    $accountData = [];
    if ($createAccount) {
        [$accountData, $accValidation] = staff_validate_account($_POST);
        $errors = array_merge($errors, $accValidation);
        if (empty($data['school_id'])) {
            $errors['school_id'] = 'Bắt buộc phải chọn trường công tác khi cấp tài khoản đăng nhập Cổng Giáo viên.';
        }
    }

    if (!$errors) {
        try {
            if ($createAccount) {
                staff_create_with_account($data, $accountData);
                header('Location: ' . url('/staff/index.php?created=1&account=1'));
                exit;
            } else {
                if (staff_has_duplicate($data)) {
                    $errors['form'] = 'Cán bộ - giáo viên đã tồn tại.';
                } else {
                    staff_create($data);
                    header('Location: ' . url('/staff/index.php?created=1'));
                    exit;
                }
            }
        } catch (RuntimeException | InvalidArgumentException $e) {
            $errors['form'] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Staff create error: ' . $e->getMessage());
            $errors['form'] = 'Không thể thêm cán bộ - giáo viên. Vui lòng thử lại sau.';
        }
    }
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Thêm cán bộ - giáo viên';
$activePage = 'staff';

admin_layout_start('Thêm cán bộ - giáo viên', 'staff', ['Quản lý giáo viên' => url('/staff/index.php'), 'Thêm giáo viên' => '']);
?>
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
          <div>
            <h1 class="h3 fw-bold mb-1">Thêm cán bộ - giáo viên</h1>
            <p class="text-muted small mb-0">Tạo hồ sơ cán bộ - giáo viên và tùy chọn cấp tài khoản truy cập Teacher Portal.</p>
          </div>
          <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/staff/index.php')) ?>">Quay lại</a>
        </div>

        <?php if ($errors): ?>
          <div class="alert alert-danger rounded-3">
            <div class="fw-bold mb-1">Vui lòng kiểm tra lại thông tin:</div>
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" id="staffCreateForm">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" id="workplace_hidden" name="workplace" value="<?= e($data['workplace']) ?>">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="full_name">Họ tên *</label>
              <input class="form-control" id="full_name" name="full_name" value="<?= e($data['full_name']) ?>" required placeholder="Ví dụ: Nguyễn Văn A">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" for="school_id">Trường công tác *</label>
              <select class="form-select <?= isset($errors['school_id']) ? 'is-invalid' : '' ?>" id="school_id" name="school_id" onchange="handleSchoolChange(this)">
                <option value="">-- Chọn trường công tác --</option>
                <?php foreach ($activeSchools as $sch): ?>
                  <option value="<?= (int)$sch['id'] ?>" data-name="<?= e($sch['name']) ?>" <?= ((int)($data['school_id'] ?? 0) === (int)$sch['id']) ? 'selected' : '' ?>>
                    [<?= e($sch['level']) ?>] <?= e($sch['name']) ?> (<?= e($sch['school_code']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" for="phone">Số điện thoại</label>
              <input class="form-control" id="phone" name="phone" value="<?= e($data['phone']) ?>" placeholder="Ví dụ: 0912345678">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" for="email">Email</label>
              <input class="form-control" type="email" id="email" name="email" value="<?= e($data['email']) ?>" placeholder="Ví dụ: giaovien@truong.edu.vn">
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold" for="staff_role">Vai trò nghiệp vụ *</label>
              <input class="form-control" id="staff_role" name="staff_role" value="<?= e($data['staff_role']) ?>" required placeholder="Ví dụ: Giáo viên, Giáo viên chủ nhiệm, Cán bộ khảo thí">
            </div>
          </div>

          <!-- Section Cấp tài khoản -->
          <div class="border rounded-3 p-3 bg-light mt-4">
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" role="switch" id="create_account" name="create_account" value="1" <?= $data['create_account'] ? 'checked' : '' ?> onchange="toggleAccountFields(this.checked)">
              <label class="form-check-label fw-bold text-primary" for="create_account">
                Cấp tài khoản đăng nhập Cổng Giáo viên (Teacher Portal)
              </label>
            </div>
            <div class="small text-muted mt-1 ps-4">
              Tạo tài khoản người dùng và tự động gán quyền Giáo viên vào trường công tác đã chọn.
            </div>

            <div id="account_section" class="mt-3 pt-3 border-top" style="<?= $data['create_account'] ? '' : 'display: none;' ?>">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold" for="username">Tên đăng nhập *</label>
                  <input class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" id="username" name="username" value="<?= e($data['username']) ?>" placeholder="Ví dụ: gv_nguyenvana">
                  <div class="form-text small">Từ 3-50 ký tự (chữ, số, _, -, .)</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" for="password">Mật khẩu khởi tạo *</label>
                  <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" placeholder="Tối thiểu 6 ký tự">
                  <div class="form-text small">Mật khẩu sẽ được mã hóa an toàn bằng thuật toán chuẩn.</div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary px-4 fw-medium" type="submit">Lưu thông tin</button>
            <a class="btn btn-outline-secondary px-3" href="<?= e(url('/staff/index.php')) ?>">Hủy</a>
          </div>
        </form>
      </div>
    </div>
  </div>

<script>
function toggleAccountFields(isChecked) {
  const section = document.getElementById('account_section');
  const userField = document.getElementById('username');
  const passField = document.getElementById('password');
  if (isChecked) {
    section.style.display = 'block';
    userField.setAttribute('required', 'required');
    passField.setAttribute('required', 'required');
  } else {
    section.style.display = 'none';
    userField.removeAttribute('required');
    passField.removeAttribute('required');
  }
}

function handleSchoolChange(selectEl) {
  const selectedOption = selectEl.options[selectEl.selectedIndex];
  const schoolName = selectedOption ? (selectedOption.getAttribute('data-name') || '') : '';
  const hiddenField = document.getElementById('workplace_hidden');
  if (hiddenField) {
    hiddenField.value = schoolName;
  }
}

// Khởi tạo trạng thái ban đầu
document.addEventListener('DOMContentLoaded', function() {
  const chk = document.getElementById('create_account');
  if (chk) {
    toggleAccountFields(chk.checked);
  }
  const sch = document.getElementById('school_id');
  if (sch) {
    handleSchoolChange(sch);
  }
});
</script>
<?php
admin_layout_end();
