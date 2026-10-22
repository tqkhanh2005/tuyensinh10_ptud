<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';

require_permission('school.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Method Not Allowed');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    http_response_code(404);
    exit('Không tìm thấy trường học');
}

$pdo = db();
try {
    $existing = school_find($pdo, (int)$id);
} catch (Throwable $e) {
    error_log('School find error: ' . $e->getMessage());
    http_response_code(500);
    exit('Không thể tải thông tin trường học. Vui lòng thử lại sau.');
}

if (!$existing) {
    http_response_code(404);
    exit('Không tìm thấy trường học');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        school_delete($pdo, (int)$id);
        header('Location: ' . url('/schools/index.php?deleted=1'));
        exit;
    } catch (PDOException $e) {
        error_log('School delete PDO error: ' . $e->getMessage());
        $error = 'Không thể xóa trường đang có dữ liệu liên quan (chỉ tiêu, nguyện vọng, phòng thi hoặc thí sinh).';
    } catch (Throwable $e) {
        error_log('School delete error: ' . $e->getMessage());
        $error = 'Không thể xóa trường học. Vui lòng thử lại sau.';
    }
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Xóa trường học';
$activePage = 'schools';

admin_layout_start('Xóa trường học', 'schools', ['Quản lý trường học' => url('/schools/index.php'), 'Xóa trường' => '']);
?>
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
        <h1 class="h4 fw-bold mb-3 text-danger">
          Xác nhận xóa trường học
        </h1>
        <p class="text-muted mb-4">
          Bạn có chắc chắn muốn xóa trường <strong><?= e($existing['name']) ?></strong> (Mã trường: <span class="font-monospace fw-semibold"><?= e($existing['school_code']) ?></span>) khỏi hệ thống tuyển sinh?
        </p>
        <?php if ($error): ?>
          <div class="alert alert-danger mb-4"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$existing['id'] ?>">
          <div class="d-flex gap-2">
            <button class="btn btn-danger fw-medium" type="submit">
              Xác nhận xóa
            </button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/schools/index.php')) ?>">Hủy</a>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php
admin_layout_end();
