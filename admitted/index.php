<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/publication_service.php';
require_once __DIR__.'/../includes/enrollment_export.php';
require_once __DIR__.'/../includes/portal_helper.php';
require_permission('admitted.view');
if(!auth_is_admin()){http_response_code(403);exit('Danh sách toàn kỳ chỉ dành cho quản trị. Giáo viên xem danh sách của trường tại khu vực giáo viên.');}
$pdo=db();$periods=$pdo->query('SELECT * FROM admission_periods ORDER BY (status="published") DESC,id DESC')->fetchAll();
$pid=(int)($_GET['period_id']??($periods[0]['id']??0));$schoolId=isset($_GET['school_id']) && $_GET['school_id']!==''?(int)$_GET['school_id']:null;
$enroll=($_GET['enrollment_status']??'')!==''?(string)$_GET['enrollment_status']:null;$error='';$rows=[];$all=[];
$p=null;foreach($periods as $item)if((int)$item['id']===$pid)$p=$item;
$cfg=period_configuration($pdo,$pid);$simulation=($cfg['settings']['mode']??'simulation')==='simulation';$end=$p['enrollment_end']??null;
try{
    if(!$p)throw new RuntimeException('Vui lòng chọn kỳ tuyển sinh.');
    $all=admitted_list($pdo,$pid,$schoolId);
    if($enroll!==null && !in_array($enroll,['pending','confirmed','expired'],true))throw new InvalidArgumentException('Trạng thái không hợp lệ.');
    $rows=array_values(array_filter($all,static fn($r)=>$enroll===null || ($enroll==='expired'?($r['enrollment_status']==='pending' && $end && $end<date('Y-m-d H:i:s')):$r['enrollment_status']===$enroll)));
    if(($_GET['download']??'')!=='')enrollment_roster_download((string)$_GET['download'],$pid,$rows,$end,$simulation);
}catch(Throwable $e){$error=$e->getMessage();$rows=[];if(isset($_GET['download']))http_response_code(400);}
$total=count($all);$confirmed=count(array_filter($all,static fn($r)=>$r['enrollment_status']==='confirmed'));$pending=$total-$confirmed;
$pageCount=max(1,(int)ceil(count($rows)/50));$page=min($pageCount,max(1,(int)($_GET['page']??1)));$shown=array_slice($rows,($page-1)*50,50);$pagePath='/admitted/index.php';
$schools=$pdo->query('SELECT id,name FROM schools WHERE level="THPT" ORDER BY name')->fetchAll();
$exportUrl=static fn($format)=>'?'.http_build_query(['period_id'=>$pid,'school_id'=>$schoolId??'','enrollment_status'=>$enroll??'','download'=>$format]);
require_once __DIR__.'/../includes/workflow_layout.php';workflow_layout_start('Theo dõi xác nhận nhập học','admitted');
?><h1>Theo dõi xác nhận nhập học</h1>
<?php if(!isset($_GET['period_id']) && $cfg && $simulation):?><div class="alert alert-warning">MÔ PHỎNG — Không phải kết quả tuyển sinh chính thức.</div><?php endif;?>
<form method="get" class="row g-2 mb-3"><div class="col-md-4"><label for="period">Kỳ tuyển sinh</label><select id="period" name="period_id" class="form-select"><?php foreach($periods as $item):?><option value="<?=$item['id']?>" <?=$pid===(int)$item['id']?'selected':''?>><?=e($item['name'].' — '.$item['academic_year'])?></option><?php endforeach;?></select></div><div class="col-md-4"><label for="school">Trường THPT</label><select id="school" name="school_id" class="form-select"><option value="">Tất cả trường</option><?php foreach($schools as $s):?><option value="<?=$s['id']?>" <?=$schoolId===(int)$s['id']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></div><div class="col-md-3"><label for="status">Trạng thái</label><select id="status" name="enrollment_status" class="form-select"><option value="">Tất cả trạng thái</option><?php foreach(['pending'=>'Chưa xác nhận','confirmed'=>'Đã xác nhận','expired'=>'Quá hạn chưa xác nhận'] as $key=>$label):?><option value="<?=$key?>" <?=$enroll===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></div><div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary">Lọc</button></div></form>
<?php if($error):?><div class="alert alert-info"><?=e($error)?></div><?php else:?>
<p>Trúng tuyển: <strong><?=$total?></strong> · Đã xác nhận: <strong><?=$confirmed?></strong> · Chưa xác nhận: <strong><?=$pending?></strong> · Quá hạn chưa xác nhận: <strong><?=$end && $end<date('Y-m-d H:i:s')?$pending:0?></strong></p>
<p>Thời hạn: <?=e($p['enrollment_start']??'Chưa thiết lập')?> đến <?=e($end??'Chưa thiết lập')?> (giờ Việt Nam). Trạng thái quá hạn không tự động thay đổi kết quả trúng tuyển.</p>
<p><a class="btn btn-outline-primary" href="<?=e($exportUrl('csv'))?>">Tải CSV</a> <a class="btn btn-outline-primary" href="<?=e($exportUrl('xlsx'))?>">Tải XLSX</a> · <?=count($rows)?> học sinh theo bộ lọc</p>
<?php require __DIR__.'/../includes/list_pagination.php';?>
<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>SBD</th><th>Họ tên</th><th>Trường</th><th>NV</th><th>Điểm</th><th>Nhập học</th><th>Thời điểm xác nhận</th></tr></thead><tbody><?php foreach($shown as $r):?><tr><td class="text-nowrap"><?=e($r['candidate_code'])?></td><td><?=e($r['full_name'])?></td><td><?=e($r['school_name'])?></td><td><?=e((string)$r['priority_order'])?></td><td><?=e((string)$r['admission_score'])?></td><td><?=e(enrollment_state_label($r['enrollment_status'],$end))?></td><td><?=e($r['confirmed_at']??'—')?></td></tr><?php endforeach;?><?php if(!$shown):?><tr><td colspan="7">Không có học sinh phù hợp.</td></tr><?php endif;?></tbody></table></div><?php endif;?>
<?php workflow_layout_end(); ?>
