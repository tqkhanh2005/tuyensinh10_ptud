<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/staff_service.php';
require_permission('staff.delete');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
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

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_csrf();
  try {
    staff_deactivate((int)$id);
    header('Location: ' . url('/staff/index.php?deleted=1'));
    exit;
  } catch (RuntimeException $e) {
    $error = $e->getMessage();
  } catch (Throwable $e) {
    error_log('Staff delete error: ' . $e->getMessage());
    $error = 'Không thể xóa cán bộ - giáo viên. Vui lòng thử lại sau.';
  }
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Xóa cán bộ - giáo viên';
$activePage = 'staff';

admin_layout_start('Xóa cán bộ - giáo viên', 'staff', ['Quản lý giáo viên' => url('/staff/index.php'), 'Xóa giáo viên' => '']);
?>
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
        <h1 class="h4 fw-bold mb-3">Xác nhận xóa cán bộ - giáo viên</h1>
        <p class="text-muted mb-4">Bạn có chắc muốn xóa <strong><?= e($existing['full_name']) ?></strong> khỏi danh sách hoạt động?</p>
        <?php if ($error): ?>
          <div class="alert alert-danger mb-4"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <div class="d-flex gap-2">
            <button class="btn btn-danger" type="submit">Xác nhận xóa</button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/staff/index.php')) ?>">Hủy</a>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php
admin_layout_end();