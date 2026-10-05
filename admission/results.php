<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/candidate_service.php';
require_once __DIR__.'/../includes/admission_service.php';
require_permission('admission.view');
$u=auth_user();$candidate=candidate_find_by_user((int)$u['id']);
if(!$candidate){http_response_code(404);exit('Không tìm thấy hồ sơ.');}
$pdo=db();$periods=$pdo->query('SELECT * FROM admission_periods ORDER BY academic_year DESC,id DESC')->fetchAll();
$periodId=(int)($_GET['period_id']??($periods[0]['id']??0));
$rows=$periodId?candidate_admission_result($pdo,(int)$candidate['id'],$periodId):[];
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Kết quả xét tuyển</title></head><body><h1>Kết quả xét tuyển</h1>
<form method="get"><select name="period_id"><?php foreach($periods as $p):?><option value="<?=$p['id']?>" <?=$p['id']==$periodId?'selected':''?>><?=e($p['name'].' - '.$p['academic_year'])?></option><?php endforeach;?></select><button>Xem</button></form>
<?php if(!$rows):?><p>Kết quả xét tuyển chưa được cập nhật.</p><?php else:?><table border="1" cellpadding="6"><tr><th>Nguyện vọng</th><th>Trường</th><th>Tổng điểm</th><th>Điểm ưu tiên</th><th>Điểm xét tuyển</th><th>Trạng thái</th></tr><?php foreach($rows as $r):?><tr><td><?=e((string)$r['priority_order'])?></td><td><?=e(($r['school_code']??'').' - '.($r['school_name']??''))?></td><td><?=e((string)$r['score_total'])?></td><td><?=e((string)$r['priority_points'])?></td><td><?=e((string)$r['admission_score'])?></td><td><?=e($r['result_status'])?></td></tr><?php endforeach;?></table><?php endif;?></body></html>
