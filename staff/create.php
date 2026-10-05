<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/staff_service.php';
require_permission('staff.create');

$errors = [];
$data = ['full_name'=>'','workplace'=>'','phone'=>'','email'=>'','staff_role'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) $errors['form'] = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    [$data, $validation] = staff_validate($_POST);
    $errors = array_merge($errors, $validation);
    if (!$errors && staff_has_duplicate($data)) $errors['form'] = 'Cán bộ - giáo viên đã tồn tại.';
    if (!$errors) {
        staff_create($data);
        header('Location: ' . url('/staff/index.php?created=1'));
        exit;
    }
}
$pageTitle='Thêm cán bộ - giáo viên - TUYỂN SINH LỚP 10'; $activePage='staff'; $bodyClass='tw-bg-background tw-font-body tw-text-on-surface';
require __DIR__ . '/../includes/header.php'; require __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-5"><div class="row justify-content-center"><div class="col-lg-8"><div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
<h1 class="h3 fw-bold mb-4">Thêm cán bộ - giáo viên</h1>
<?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="row g-3"><div class="col-md-6"><label class="form-label">Họ tên *</label><input class="form-control" name="full_name" value="<?= e($data['full_name']) ?>" required></div>
<div class="col-md-6"><label class="form-label">Đơn vị công tác *</label><input class="form-control" name="workplace" value="<?= e($data['workplace']) ?>" required></div>
<div class="col-md-6"><label class="form-label">Số điện thoại</label><input class="form-control" name="phone" value="<?= e($data['phone']) ?>"></div>
<div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= e($data['email']) ?>"></div>
<div class="col-12"><label class="form-label">Vai trò *</label><input class="form-control" name="staff_role" value="<?= e($data['staff_role']) ?>" required></div></div>
<div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit">Lưu thông tin</button><a class="btn btn-outline-secondary" href="<?= e(url('/staff/index.php')) ?>">Hủy</a></div>
</form></div></div></div></main>
<?php require __DIR__ . '/../includes/footer.php'; require __DIR__ . '/../includes/scripts.php'; ?></body></html>
