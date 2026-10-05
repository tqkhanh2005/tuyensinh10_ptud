<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/staff_service.php';
require_permission('staff.manage');

$staff = staff_list();
$pageTitle = 'Quản lý cán bộ - giáo viên - TUYỂN SINH LỚP 10';
$activePage = 'staff';
$bodyClass = 'tw-bg-background tw-font-body tw-text-on-surface';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 fw-bold mb-1">Quản lý cán bộ - giáo viên</h1><p class="text-muted mb-0">Danh sách cán bộ - giáo viên được quản lý trong hệ thống.</p></div>
    <?php if (auth_has_permission('staff.create')): ?><a class="btn btn-primary" href="<?= e(url('/staff/create.php')) ?>">+ Thêm cán bộ - giáo viên</a><?php endif; ?>
  </div>
  <div class="table-responsive bg-white rounded-4 shadow-sm">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Họ tên</th><th>Đơn vị công tác</th><th>Điện thoại</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($staff as $row): ?>
        <tr>
          <td><?= e($row['full_name']) ?></td><td><?= e($row['workplace']) ?></td><td><?= e($row['phone'] ?? '') ?></td><td><?= e($row['email'] ?? '') ?></td><td><?= e($row['staff_role']) ?></td>
          <td><?= (int)$row['active'] === 1 ? 'Hoạt động' : 'Không hoạt động' ?></td>
          <td class="text-nowrap">
            <?php if ((int)$row['active'] === 1 && auth_has_permission('staff.update')): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/staff/edit.php?id=' . (int)$row['id'])) ?>">Sửa</a><?php endif; ?>
            <?php if ((int)$row['active'] === 1 && auth_has_permission('staff.delete')): ?><a class="btn btn-sm btn-outline-danger" href="<?= e(url('/staff/delete.php?id=' . (int)$row['id'])) ?>">Xóa</a><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$staff): ?><tr><td colspan="7" class="text-center py-5 text-muted">Chưa có cán bộ - giáo viên.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; require __DIR__ . '/../includes/scripts.php'; ?>
</body></html>
