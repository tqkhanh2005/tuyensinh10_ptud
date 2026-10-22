<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/admin_layout.php';
require_once __DIR__.'/../includes/admission_period_configuration.php';
require_once __DIR__.'/../includes/audit_labels.php';
require_permission('admission.manage');
if(!auth_is_admin()){http_response_code(403);exit('403 Forbidden');}
$pdo=db();$periods=$pdo->query('SELECT id,name FROM admission_periods ORDER BY id DESC')->fetchAll();$pid=(int)($_GET['period_id']??($periods[0]['id']??0));$action=trim((string)($_GET['action']??''));
$params=[$pid];$where='ev.admission_period_id=?';if($action!==''){$where.=' AND ev.action=?';$params[]=$action;}
$q=$pdo->prepare('SELECT COUNT(*) FROM admission_period_events ev WHERE '.$where);$q->execute($params);$total=(int)$q->fetchColumn();$pageCount=max(1,(int)ceil($total/50));$page=min($pageCount,max(1,(int)($_GET['page']??1)));$offset=($page-1)*50;
$q=$pdo->prepare('SELECT ev.*,u.full_name actor_name FROM admission_period_events ev LEFT JOIN users u ON u.id=ev.actor_user_id WHERE '.$where.' ORDER BY ev.id DESC LIMIT 50 OFFSET '.$offset);$q->execute($params);$rows=$q->fetchAll();
$q=$pdo->prepare('SELECT DISTINCT action FROM admission_period_events WHERE admission_period_id=? ORDER BY action');$q->execute([$pid]);$actions=$q->fetchAll(PDO::FETCH_COLUMN);$pagePath='/admin/audit.php';
admin_layout_start('Nhật ký kỳ tuyển sinh','audit');
?><h1 class="h3">Nhật ký kỳ tuyển sinh</h1><p>Tra cứu người thực hiện, thời điểm và chi tiết các mốc của kỳ. Nhật ký không thay thế lịch sử điểm và phúc khảo tại từng hồ sơ.</p>
<form method="get" class="row g-2 mb-3"><div class="col-md-6"><label for="period">Kỳ tuyển sinh</label><select id="period" name="period_id" class="form-select"><?php foreach($periods as $p):?><option value="<?=$p['id']?>" <?=$pid===(int)$p['id']?'selected':''?>><?=e($p['name'])?></option><?php endforeach;?></select></div><div class="col-md-4"><label for="action">Thao tác</label><select id="action" name="action" class="form-select"><option value="">Tất cả thao tác</option><?php foreach($actions as $a):?><option value="<?=e($a)?>" <?=$action===$a?'selected':''?>><?=e(period_event_label($a))?></option><?php endforeach;?></select></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">Lọc</button></div></form>
<p><?=$total?> bản ghi theo bộ lọc · Giờ Việt Nam</p><?php require __DIR__.'/../includes/list_pagination.php';?>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Thời điểm</th><th>Người thực hiện</th><th>Thao tác</th><th>Chi tiết</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td class="text-nowrap"><?=e($r['created_at'])?></td><td><?=e($r['actor_name']??($r['actor_user_id']===null?'Hệ thống':'Tài khoản không còn tồn tại'))?></td><td class="text-break"><?=e(period_event_label($r['action']))?></td><td><details><summary>Xem chi tiết</summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere;max-width:35rem"><?=e(json_encode(json_decode($r['detail'],true),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></pre></details></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="4">Chưa có nhật ký phù hợp.</td></tr><?php endif;?></tbody></table></div><?php admin_layout_end(); ?>
