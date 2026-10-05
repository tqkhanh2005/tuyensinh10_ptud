<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
$pdo=db();
$periodId=(int)($_GET['period_id']??0);
if($periodId){
 $stmt=$pdo->prepare('SELECT s.school_code,s.name,s.admission_area,aq.quota FROM admission_quotas aq JOIN schools s ON s.id=aq.school_id WHERE aq.admission_period_id=:id AND aq.published_at IS NOT NULL AND s.level="THPT" AND s.active=1 ORDER BY s.name');
 $stmt->execute(['id'=>$periodId]); $rows=$stmt->fetchAll();
}else{$rows=[];}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Chỉ tiêu tuyển sinh</title></head><body>
<h1>Chỉ tiêu tuyển sinh</h1>
<table border="1" cellpadding="8"><tr><th>Mã trường</th><th>Tên trường</th><th>Khu vực</th><th>Chỉ tiêu</th></tr>
<?php foreach($rows as $r):?><tr><td><?=e($r['school_code'])?></td><td><?=e($r['name'])?></td><td><?=e($r['admission_area']??'')?></td><td><?=e((string)$r['quota'])?></td></tr><?php endforeach;?>
</table></body></html>
