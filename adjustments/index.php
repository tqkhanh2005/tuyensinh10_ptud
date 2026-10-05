<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/candidate_service.php';
require_permission('adjustment.review');$rows=adjustment_list('pending');
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Duyệt điều chỉnh hồ sơ</title></head><body><h1>Yêu cầu điều chỉnh hồ sơ</h1>
<table border="1" cellpadding="8"><tr><th>ID</th><th>Thí sinh</th><th>Thông tin mới</th><th>Lý do</th><th>Thao tác</th></tr>
<?php foreach($rows as $r):$new=json_decode($r['new_data'],true);?><tr><td><?=$r['id']?></td><td><?=e($r['full_name'])?></td><td><pre><?=e(json_encode($new,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))?></pre></td><td><?=e($r['reason'])?></td><td>
<form method="post" action="<?=e(url('/adjustments/review.php'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="action" value="approve"><button>Duyệt</button></form>
<form method="post" action="<?=e(url('/adjustments/review.php'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="action" value="reject"><input name="reason" required placeholder="Lý do từ chối"><button>Từ chối</button></form>
</td></tr><?php endforeach;?></table></body></html>