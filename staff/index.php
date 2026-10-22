<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/staff_service.php';
require_permission('staff.manage');

$staff = [];
$error = '';
try {
    $staff = staff_list();
} catch (Throwable $e) {
    error_log('Staff list error: ' . $e->getMessage());
    $error = 'Không thể tải danh sách cán bộ - giáo viên. Vui lòng thử lại sau.';
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Quản lý cán bộ - giáo viên';
$activePage = 'staff';

admin_layout_start('Quản lý cán bộ - giáo viên', 'staff', ['Quản lý giáo viên' => '']);
?>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Quản lý cán bộ - giáo viên</h1>
      <p class="text-muted mb-0">Danh sách cán bộ - giáo viên được quản lý trong hệ thống.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <?php if (auth_has_permission('staff.create')): ?>
        <a class="btn btn-outline-primary" href="<?= e(url('/staff/template.php')) ?>">
          Tải file mẫu
        </a>
        <a class="btn btn-outline-success" href="<?= e(url('/staff/import.php')) ?>">
          Nhập danh sách CSV
        </a>
        <a class="btn btn-primary fw-medium" href="<?= e(url('/staff/create.php')) ?>">
          Thêm cán bộ - giáo viên
        </a>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($error): ?><div class="alert alert-danger mb-4"><?= e($error) ?></div><?php endif; ?>
  <?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      <?= isset($_GET['account']) ? 'Thêm cán bộ - giáo viên và cấp tài khoản Teacher Portal thành công.' : 'Thêm cán bộ - giáo viên thành công.' ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  <?php elseif (isset($_GET['reset'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      Đặt lại mật khẩu tài khoản thành công.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  <?php elseif (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      Cập nhật thông tin cán bộ - giáo viên thành công.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      Đã chuyển cán bộ - giáo viên sang trạng thái không hoạt động.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  <?php elseif (isset($_GET['imported'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      Nhập dữ liệu thành công. Đã thêm <strong><?= (int)$_GET['imported'] ?></strong> cán bộ - giáo viên mới vào hệ thống.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  <?php endif; ?>
  <div class="table-responsive bg-white rounded-4 shadow-sm border">
    <table class="table table-hover align-middle mb-0 portal-data-table" style="min-width: 900px;">
      <thead class="table-light text-secondary small text-uppercase">
        <tr>
          <th style="width: 23%;">Họ tên</th>
          <th style="width: 23%;">Trường/Đơn vị công tác</th>
          <th style="width: 12%;">Vai trò</th>
          <th style="width: 14%;">Tài khoản</th>
          <th style="width: 10%;">Trạng thái</th>
          <th class="text-end" style="width: 18%;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($staff as $row): ?>
        <tr>
          <td class="text-break">
            <div class="fw-semibold text-dark text-break" title="<?= e($row['full_name']) ?>">
              <?= e($row['full_name']) ?>
            </div>
            <?php if (!empty($row['phone']) || !empty($row['email'])): ?>
              <div class="text-muted small text-break" style="font-size: 0.8rem;" title="<?= e(trim(($row['phone'] ?? '') . ' ' . ($row['email'] ?? ''))) ?>">
                <?php if (!empty($row['phone'])): ?>
                  <span class="me-2"><i class="fas fa-phone-alt me-1 text-secondary" style="font-size: 0.75rem;"></i><?= e($row['phone']) ?></span>
                <?php endif; ?>
                <?php if (!empty($row['email'])): ?>
                  <span><i class="fas fa-envelope me-1 text-secondary" style="font-size: 0.75rem;"></i><?= e($row['email']) ?></span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </td>
          <td class="text-break">
            <div class="text-dark text-break" title="<?= e($row['workplace']) ?>">
              <?= e($row['workplace']) ?>
            </div>
          </td>
          <td class="text-break">
            <div class="text-dark text-break" title="<?= e($row['staff_role']) ?>">
              <?= e($row['staff_role']) ?>
            </div>
          </td>
          <td class="text-break">
            <?php if (!empty($row['user_id']) && !empty($row['is_teacher'])): ?>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace text-break d-inline-block" style="max-width: 100%; font-size: 0.8rem;" title="Tài khoản Teacher Portal: <?= e($row['username'] ?? '') ?>">
                <?= e($row['username'] ?? 'teacher') ?>
              </span>
            <?php elseif (!empty($row['user_id'])): ?>
              <span class="badge bg-light text-secondary border font-monospace text-break d-inline-block" style="max-width: 100%; font-size: 0.8rem;" title="Tài khoản: <?= e($row['username'] ?? '') ?>">
                <?= e($row['username'] ?? 'user') ?>
              </span>
            <?php else: ?>
              <span class="text-muted small">Chưa cấp</span>
            <?php endif; ?>
          </td>
          <td class="text-nowrap">
            <?php if ((int)$row['active'] === 1): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
            <?php else: ?>
              <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng HĐ</span>
            <?php endif; ?>
          </td>
          <td class="text-end text-nowrap" style="white-space: nowrap;">
            <div class="portal-table-actions">
              <?php if (!empty($row['user_id']) && !empty($row['is_teacher']) && (int)$row['active'] === 1 && (auth_has_permission('staff.update') || auth_has_permission('staff.manage'))): ?>
                <a class="btn btn-sm btn-outline-warning" href="<?= e(url('/staff/reset_password.php?id=' . (int)$row['id'])) ?>" title="Đặt lại mật khẩu">Đặt lại mật khẩu</a>
              <?php endif; ?>
              <?php if ((int)$row['active'] === 1 && auth_has_permission('staff.update')): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/staff/edit.php?id=' . (int)$row['id'])) ?>" title="Sửa thông tin">Sửa</a>
              <?php endif; ?>
              <?php if ((int)$row['active'] === 1 && auth_has_permission('staff.delete')): ?>
                <a class="btn btn-sm btn-outline-danger" href="<?= e(url('/staff/delete.php?id=' . (int)$row['id'])) ?>" title="Xóa cán bộ">Xóa</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$staff): ?><tr><td colspan="6" class="text-center py-5 text-muted">Chưa có cán bộ - giáo viên.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
<?php
admin_layout_end();
