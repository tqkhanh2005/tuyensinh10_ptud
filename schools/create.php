<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';
require_permission('school.manage');
$pdo = db(); $errors=[]; $data=$_POST;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    $errors = school_validate($data);
    if (!$errors) {
        try { school_create($pdo,$data); header('Location: '.url('/schools/index.php')); exit; }
        catch (PDOException $e) { $errors['general']='Không thể lưu dữ liệu. Vui lòng kiểm tra thông tin trùng lặp hoặc thử lại sau.'; }
    }
}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Thêm trường</title></head><body>
<h1>Thêm trường THCS, THPT</h1>
<?php if ($errors): ?><ul><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<label>Mã trường <input name="school_code" value="<?= e($data['school_code']??'') ?>" required></label><br>
<label>Tên trường <input name="name" value="<?= e($data['name']??'') ?>" required></label><br>
<label>Cấp học <select name="level"><option>THCS</option><option>THPT</option></select></label><br>
<label>Địa chỉ <input name="address" value="<?= e($data['address']??'') ?>"></label><br>
<label>Quận/Huyện <input name="district" value="<?= e($data['district']??'') ?>"></label><br>
<label>Khu vực tuyển sinh <input name="admission_area" value="<?= e($data['admission_area']??'') ?>"></label><br>
<label>Số điện thoại <input name="phone" value="<?= e($data['phone']??'') ?>"></label><br>
<label>Email <input type="email" name="email" value="<?= e($data['email']??'') ?>"></label><br>
<button type="submit">Lưu</button> <a href="<?= e(url('/schools/index.php')) ?>">Hủy</a>
</form></body></html>
