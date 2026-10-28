<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/council_service.php';
require_once __DIR__.'/../includes/admin_layout.php';
require_permission('council.manage');
if(!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true)){http_response_code(405);header('Allow: GET, POST');exit;}
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
if(!$id || $id<1){http_response_code(404);exit('Không tìm thấy hội đồng.');}
$pdo=db();$error='';$c=council_find($pdo,$id);
if(!$c){http_response_code(404);exit('Không tìm thấy hội đồng.');}
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    try{
        if(($_POST['action']??'')!=='cancel')throw new InvalidArgumentException('Thao tác không hợp lệ.');
        $period=council_cancel($pdo,$id);$_SESSION['council_cancelled']=$c['name'];
        header('Location: '.url('/councils/index.php?period_id='.$period));exit;
    }catch(InvalidArgumentException $e){$error=$e->getMessage();}
    catch(Throwable $e){error_log('Council cancel: '.$e->getMessage());$error='Không thể hủy hội đồng. Dữ liệu chưa thay đổi, vui lòng thử lại.';}
}
$back=url('/councils/index.php?period_id='.$c['admission_period_id']);
admin_layout_start('Hủy hội đồng','councils',['Hội đồng'=>$back,'Hủy hội đồng'=>'']);
?>
<div class="card p-4 mx-auto" style="max-width:760px">
<h1 class="h4">Xác nhận hủy hội đồng</h1>
<?php if($error):?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif;?>
<p>Bạn muốn hủy <strong><?=e($c['name'])?></strong> (<?=e(council_type_label($c['council_type']))?>)?</p>
<p>Thông tin hội đồng, môn phụ trách và phân công thành viên vẫn được giữ để tra cứu lịch sử. Hội đồng đã hủy không được dùng cho phân công mới.</p>
<ul><?php foreach($c['members'] as $member):?><li><?=e($member['full_name'])?> · <?=e($member['role_name']?:'Chưa phân vai')?></li><?php endforeach;?></ul>
<?php if($c['status']==='cancelled'):?><p class="alert alert-info">Hội đồng này đã hủy.</p><?php else:?>
<form method="post"><?=csrf_field()?><button class="btn btn-danger" name="action" value="cancel">Xác nhận hủy hội đồng</button> <a class="btn btn-outline-secondary" href="<?=e($back)?>">Quay lại, giữ hội đồng</a></form>
<?php endif;?>
</div>
<?php admin_layout_end(); ?>
