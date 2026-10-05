<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/candidate_service.php';
require_permission('candidate.create');$errors=[];$data=$_POST;
if($_SERVER['REQUEST_METHOD']==='POST'){require_csrf();[$data,$errors]=candidate_validate($_POST);if(!$errors&&candidate_duplicate($data['national_id']))$errors['national_id']='Thí sinh đã tồn tại trong hệ thống.';if(!$errors){try{candidate_create($data);header('Location: '.url('/candidates/index.php'));exit;}catch(Throwable $e){$errors['general']='Không thể lưu dữ liệu, vui lòng thử lại sau.';}}}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Thêm thí sinh</title></head><body><h1>Thêm thí sinh/Học sinh</h1>
<?php foreach($errors as $x):?><p><?=e($x)?></p><?php endforeach;?><form method="post"><?=csrf_field()?><label>CCCD/CMND <input name="national_id" required value="<?=e($data['national_id']??'')?>"></label><br>
<label>Họ tên <input name="full_name" required value="<?=e($data['full_name']??'')?>"></label><br>
<label>Ngày sinh <input type="date" name="date_of_birth" value="<?=e($data['date_of_birth']??'')?>"></label><br>
<label>Giới tính <input name="gender" value="<?=e($data['gender']??'')?>"></label><br>
<label>Địa chỉ <input name="address" value="<?=e($data['address']??'')?>"></label><br>
<label>Điện thoại <input name="phone" value="<?=e($data['phone']??'')?>"></label><br>
<label>Email <input type="email" name="email" value="<?=e($data['email']??'')?>"></label><br>
<label>Mã trường THCS <input type="number" name="middle_school_id" min="0" value="<?=e((string)($data['middle_school_id']??''))?>"></label><br>
<label>Đủ điều kiện dự thi <select name="eligible"><option value="1" <?=((int)($data['eligible']??1)===1?'selected':'')?>>Có</option><option value="0" <?=((int)($data['eligible']??1)===0?'selected':'')?>>Không</option></select></label><br><button>Lưu</button> <a href="<?=e(url('/candidates/index.php'))?>">Hủy</a></form>
</body></html>