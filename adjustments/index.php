<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/candidate_service.php';
require_once __DIR__.'/../includes/adjustment_presentation.php';
require_permission('adjustment.review');
$status=(string)($_GET['status']??'pending');
if(!in_array($status,['','pending','approved','rejected'],true)){http_response_code(400);exit('Trạng thái không hợp lệ.');}
$all=adjustment_list($status);$pageCount=max(1,(int)ceil(count($all)/50));$page=min($pageCount,max(1,(int)($_GET['page']??1)));$rows=array_slice($all,($page-1)*50,50);$pagePath='/adjustments/index.php';
require_once __DIR__.'/../includes/workflow_layout.php';workflow_layout_start('Duyệt điều chỉnh hồ sơ','adjustments');
?><h1>Duyệt điều chỉnh hồ sơ</h1><p>Đối chiếu thông tin trước và sau theo đề nghị của giáo viên. Chỉ yêu cầu đang chờ duyệt mới có thể xử lý.</p>
<?php if(isset($_GET['saved'])):?><div class="alert alert-success" role="status">Đã xử lý yêu cầu điều chỉnh hồ sơ.</div><?php endif;?>
<form method="get" class="d-flex flex-wrap gap-2 align-items-end mb-3"><div><label class="form-label" for="status">Trạng thái</label><select id="status" name="status" class="form-select"><?php foreach(['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối',''=>'Tất cả'] as $key=>$label):?><option value="<?=$key?>" <?=$status===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></div><button class="btn btn-outline-primary">Lọc yêu cầu</button></form>
<p><?=count($all)?> yêu cầu theo bộ lọc</p><?php require __DIR__.'/../includes/list_pagination.php';?>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Yêu cầu</th><th>Học sinh</th><th>Thông tin thay đổi</th><th>Lý do</th><th>Xử lý</th></tr></thead><tbody>
<?php foreach($rows as $r):$changes=adjustment_visible_changes(json_decode($r['old_data'],true)??[],json_decode($r['new_data'],true)??[]);?><tr><td>#<?=(int)$r['id']?><div class="small text-muted"><?=e($r['created_at'])?></div></td><td><?=e($r['full_name'])?><div class="small text-muted"><?=e($r['national_id'])?></div></td><td><ul class="ps-3 mb-0"><?php foreach($changes as $change):?><li><strong><?=e($change['label'])?>:</strong> <?=e($change['before']?:'Chưa có')?> → <strong><?=e($change['after']?:'Để trống')?></strong></li><?php endforeach;?></ul><?php if(!$changes):?>Không có thông tin thay đổi.<?php endif;?></td><td><?=e($r['reason'])?></td><td>
<?php if($r['status']==='pending'):?><form method="post" action="<?=e(url('/adjustments/review.php'))?>" class="mb-2"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><input type="hidden" name="action" value="approve"><button class="btn btn-success btn-sm">Duyệt yêu cầu #<?=(int)$r['id']?></button></form><form method="post" action="<?=e(url('/adjustments/review.php'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><input type="hidden" name="action" value="reject"><label class="visually-hidden" for="reason-<?=(int)$r['id']?>">Lý do từ chối #<?=(int)$r['id']?></label><input id="reason-<?=(int)$r['id']?>" class="form-control form-control-sm mb-2" name="reason" required placeholder="Lý do từ chối"><button class="btn btn-outline-danger btn-sm">Từ chối #<?=(int)$r['id']?></button></form>
<?php else:?><?=e(['approved'=>'Đã duyệt','rejected'=>'Đã từ chối'][$r['status']])?><div class="small"><?=e($r['review_reason']??'')?></div><?php endif;?></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="5">Không có yêu cầu phù hợp.</td></tr><?php endif;?></tbody></table></div><?php workflow_layout_end(); ?>
