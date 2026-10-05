<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/staff_service.php';
require_permission('staff.delete');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(404); exit('Không tìm thấy cán bộ - giáo viên'); }
$existing = staff_find((int)$id);
if (!$existing) { http_response_code(404); exit('Không tìm thấy cán bộ - giáo viên'); }
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    } else {
        try {
            staff_deactivate((int)$id);
            header('Location: ' . url('/staff/index.php?deleted=1'));
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}
$pageTitle='Xóa cán bộ - giáo viên - TUYỂN SINH LỚP 10'; $activePage='staff'; $bodyClass='tw-bg-background tw-font-body tw-text-on-surface';
require __DIR__ . '/../includes/header.php'; require __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-5"><div class="row justify-content-center"><div class="col-lg-6"><div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
<h1 class="h4 fw-bold">Xác nhận xóa cán bộ - giáo viên</h1><p>Bạn có chắc muốn xóa <strong><?= e($existing['full_name']) ?></strong> khỏi danh sách hoạt động?</p>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-danger" type="submit">Xác nhận xóa</button> <a class="btn btn-outline-secondary" href="<?= e(url('/staff/index.php')) ?>">Hủy</a></form>
</div></div></div></main>
<?php require __DIR__ . '/../includes/footer.php'; require __DIR__ . '/../includes/scripts.php'; ?></body></html>
