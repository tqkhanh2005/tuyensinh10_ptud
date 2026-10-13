<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/candidate_service.php';
require_permission('adjustment.submit');
$u=auth_user();
$candidate=candidate_find_by_user((int)$u['id']);
if(!$candidate){http_response_code(404);exit('Không tìm thấy hồ sơ.');}

$tamperId = filter_input(INPUT_GET, 'candidate_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'candidate_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($tamperId && $tamperId !== (int)$candidate['id']) {
    http_response_code(403);
    exit('403 Forbidden: Bạn không có quyền gửi yêu cầu điều chỉnh cho thí sinh khác.');
}

$data=$_POST;$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){require_csrf();$new=['national_id'=>trim((string)($_POST['national_id']??'')),'full_name'=>trim((string)($_POST['full_name']??'')),'date_of_birth'=>trim((string)($_POST['date_of_birth']??'')),'gender'=>trim((string)($_POST['gender']??'')),'address'=>trim((string)($_POST['address']??'')),'phone'=>trim((string)($_POST['phone']??'')),'email'=>trim((string)($_POST['email']??'')),'middle_school_id'=>(int)($_POST['middle_school_id']??0)];
$reason=trim((string)($_POST['reason']??''));[$_, $errors]=candidate_validate($new);if($reason==='')$errors['reason']='Lý do không được để trống.';
if(!$errors){try{$old=['national_id'=>$candidate['national_id'],'full_name'=>$candidate['full_name'],'date_of_birth'=>$candidate['date_of_birth'],'gender'=>$candidate['gender'],'address'=>$candidate['address'],'phone'=>$candidate['phone'],'email'=>$candidate['email'],'middle_school_id'=>$candidate['middle_school_id']];adjustment_submit((int)$candidate['id'],(int)$u['id'],$old,$new,$reason,null);header('Location: '.url('/'));exit;}catch(Throwable $e){$errors['general']='Gửi yêu cầu thất bại, vui lòng thử lại.';}}}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Điều chỉnh hồ sơ</title></head><body><h1>Yêu cầu điều chỉnh hồ sơ</h1>
<?php foreach($errors as $x):?><p><?=e($x)?></p><?php endforeach;?><form method="post"><?=csrf_field()?>
<input type="text" name="national_id" required value="<?=e($data['national_id']??$candidate['national_id'])?>"><br>
<input name="full_name" required value="<?=e($data['full_name']??$candidate['full_name'])?>"><br>
<input type="date" name="date_of_birth" value="<?=e($data['date_of_birth']??$candidate['date_of_birth'])?>"><br>
<input name="gender" value="<?=e($data['gender']??$candidate['gender'])?>"><br>
<input name="address" value="<?=e($data['address']??$candidate['address'])?>"><br>
<input name="phone" value="<?=e($data['phone']??$candidate['phone'])?>"><br>
<input type="email" name="email" value="<?=e($data['email']??$candidate['email'])?>"><br>
<input type="number" name="middle_school_id" min="0" value="<?=e((string)($data['middle_school_id']??$candidate['middle_school_id']))?>"><br>
<textarea name="reason" required placeholder="Lý do điều chỉnh"><?=e($data['reason']??'')?></textarea><br>
<button>Gửi yêu cầu</button><a href="<?=e(url('/'))?>"> Hủy</a></form>
<p>[MISSING SPECIFICATION] Quy tắc định dạng/dung lượng file minh chứng chưa được định nghĩa đủ trong tài liệu; UI này chưa nhận file để tránh tự bịa giới hạn.</p></body></html>