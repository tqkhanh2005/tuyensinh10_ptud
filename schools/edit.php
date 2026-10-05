<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';
require_permission('school.manage');
$pdo=db(); $id=(int)($_GET['id']??0); $school=school_find($pdo,$id);
if (!$school) { http_response_code(404); exit('Không tìm thấy trường.'); }
$errors=[]; $data=$_POST ?: $school;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf(); $errors=school_validate($data,true);
    if (!$errors) {
        try { school_update($pdo,$id,$data); header('Location: '.url('/schools/index.php')); exit; }
        catch(PDOException $e) { $errors['general']='Không thể cập nhật dữ liệu. Vui lòng kiểm tra thông tin trùng lặp hoặc thử lại sau.'; }
    }
}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Sửa trường</title></head><body>
<h1>Cập nhật thông tin trường</h1>
<?php if($errors): ?><ul><?php foreach($errors as $err): ?><li><?=e($err)?></li><?php endforeach;?></ul><?php endif;?>
<form method="post"><?=csrf_field()?>
<input type="hidden" name="id" value="<?=$id?>">
<label>Mã trường <input name="school_code" value="<?=e($data['school_code'])?>" required></label><br>
<label>Tên trường <input name="name" value="<?=e($data['name'])?>" required></label><br>
<label>Cấp học <select name="level"><option <?=($data['level']==='THCS'?'selected':'')?>>THCS</option><option <?=($data['level']==='THPT'?'selected':'')?>>THPT</option></select></label><br>
<label>Địa chỉ <input name="address" value="<?=e($data['address']??'')?>"></label><br>
<label>Quận/Huyện <input name="district" value="<?=e($data['district']??'')?>"></label><br>
<label>Khu vực tuyển sinh <input name="admission_area" value="<?=e($data['admission_area']??'')?>"></label><br>
<label>Số điện thoại <input name="phone" value="<?=e($data['phone']??'')?>"></label><br>
<label>Email <input name="email" value="<?=e($data['email']??'')?>"></label><br>
<button>Lưu thay đổi</button> <a href="<?=e(url('/schools/index.php'))?>">Hủy</a>
</form></body></html>
