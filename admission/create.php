<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';
require_permission('admission.manage');
$pdo=db(); $errors=[]; $data=$_POST;
if($_SERVER['REQUEST_METHOD']==='POST'){
 require_csrf();
 if(trim((string)($data['name']??''))==='' || trim((string)($data['academic_year']??''))==='') $errors['general']='Tên kỳ tuyển sinh và năm học là bắt buộc.';
 if(!$errors){
  try{admission_period_create($pdo,$data);header('Location: '.url('/admission/index.php'));exit;}
  catch(PDOException $e){$errors['general']='Không thể lưu kế hoạch tuyển sinh.';}
 }
}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Tạo kỳ tuyển sinh</title></head><body>
<h1>Thiết lập kế hoạch tuyển sinh</h1>
<?php if($errors):?><p><?=e(implode(' ',$errors))?></p><?php endif;?>
<form method="post"><?=csrf_field()?>
<label>Tên kế hoạch <input name="name" required value="<?=e($data['name']??'')?>"></label><br>
<label>Năm học <input name="academic_year" required value="<?=e($data['academic_year']??'')?>"></label><br>
<label>Phương thức tuyển sinh <input name="admission_method" value="<?=e($data['admission_method']??'')?>"></label><br>
<label>Nội dung kế hoạch<br><textarea name="plan_content"><?=e($data['plan_content']??'')?></textarea></label><br>
<label>Bắt đầu đăng ký <input type="datetime-local" name="registration_start" value="<?=e($data['registration_start']??'')?>"></label><br>
<label>Kết thúc đăng ký <input type="datetime-local" name="registration_end" value="<?=e($data['registration_end']??'')?>"></label><br>
<label>Trạng thái <select name="status"><option value="draft">draft</option><option value="published">published</option><option value="closed">closed</option></select></label><br>
<button>Lưu</button> <a href="<?=e(url('/admission/index.php'))?>">Hủy</a>
</form></body></html>
