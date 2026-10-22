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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    http_response_code(404);
    exit('Không tìm thấy trường học');
}

try {
    $pdo = db();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (($_POST['action'] ?? '') !== 'delete') {
        http_response_code(400);
        exit('Thao tác không hợp lệ.');
    }
    try {
        school_delete($pdo, (int)$id);
        header('Location: ' . url('/schools/index.php?deleted=1'));
        exit;
    } catch (DomainException $e) {
        $_SESSION['school_delete_error'] = 'Không thể xóa trường đang có dữ liệu liên quan';
    } catch (OutOfBoundsException $e) {
        $_SESSION['school_delete_error'] = $e->getMessage();
    } catch (Throwable $e) {
        error_log('School delete error: ' . $e->getMessage());
        $_SESSION['school_delete_error'] = 'Không thể xóa trường học. Dữ liệu chưa thay đổi. Vui lòng thử lại sau.';
    }
    header('Location: ' . url('/schools/index.php'));
    exit;
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Xóa trường học';
$activePage = 'schools';

admin_layout_start('Xóa trường học', 'schools', ['Quản lý trường học' => url('/schools/index.php'), 'Xóa trường' => '']);
?>
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="bg-white rounded-4 shadow-sm p-4 p-md-5 position-relative" role="dialog" aria-labelledby="delete-title" aria-describedby="delete-description">
        <a class="btn-close position-absolute top-0 end-0 m-3" href="<?= e(url('/schools/index.php')) ?>" aria-label="Đóng hộp thoại, hủy xóa"></a>
        <h1 id="delete-title" class="h4 fw-bold mb-3 text-danger pe-3">
          Xác nhận xóa trường học
        </h1>
        <p id="delete-description" class="text-muted mb-4">
          Bạn có chắc chắn muốn xóa trường <strong><?= e($existing['name']) ?></strong> (Mã trường: <span class="font-monospace fw-semibold"><?= e($existing['school_code']) ?></span>) khỏi hệ thống tuyển sinh?
        </p>
        <p class="small text-muted">Cấp học: <?= e($existing['level']) ?>. Chỉ có thể xóa trường không có dữ liệu liên quan.</p>
        <form method="post" action="<?= e(url('/schools/delete.php?id=' . (int)$existing['id'])) ?>">
          <?= csrf_field() ?>
          <div class="d-flex gap-2">
            <button class="btn btn-danger fw-medium" type="submit" name="action" value="delete">
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
