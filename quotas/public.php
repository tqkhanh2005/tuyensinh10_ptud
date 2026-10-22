<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admission_period_configuration.php';
$pdo=db();
$periodId=(int)($_GET['period_id']??0);
if($periodId && period_publicly_visible($pdo,$periodId)){
 $stmt=$pdo->prepare('SELECT s.school_code,s.name,s.admission_area,aq.quota FROM admission_quotas aq JOIN schools s ON s.id=aq.school_id WHERE aq.admission_period_id=:id AND aq.published_at IS NOT NULL AND s.level="THPT" AND s.active=1 ORDER BY s.name');
 $stmt->execute(['id'=>$periodId]); $rows=$stmt->fetchAll();
}else{$rows=[];}
?>
<?php require_once __DIR__ . '/../includes/workflow_layout.php'; workflow_layout_start('Chỉ tiêu tuyển sinh', 'quotas'); ?>
<h1>Chỉ tiêu tuyển sinh</h1>
<?php if(!$rows):?><p>Chưa có chỉ tiêu chính thức được công bố cho kỳ này.</p><?php endif;?>
<table border="1" cellpadding="8"><tr><th>Mã trường</th><th>Tên trường</th><th>Khu vực</th><th>Chỉ tiêu</th></tr>
<?php foreach($rows as $r):?><tr><td><?=e($r['school_code'])?></td><td><?=e($r['name'])?></td><td><?=e($r['admission_area']??'')?></td><td><?=e((string)$r['quota'])?></td></tr><?php endforeach;?>
</table><?php workflow_layout_end(); ?>
