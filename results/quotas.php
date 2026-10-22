<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/publication_service.php';
require_permission('quota.view');

$pdo=db();
$error=null;
$rows=[];
$filterRows=[];
$schoolId=isset($_GET['school_id']) && (int)$_GET['school_id']>0 ? (int)$_GET['school_id'] : null;
$area=trim((string)($_GET['area']??''));
$area=$area!==''?$area:null;

try {
    $currentPeriodId=current_admission_period_id($pdo);
    $filterRows=published_quota_list($pdo,$currentPeriodId);
    $rows=published_quota_list($pdo,$currentPeriodId,$schoolId,$area);
} catch (RuntimeException $e) {
    if($e->getMessage()==='[MISSING CURRENT ADMISSION PERIOD]') {
        $error='Chưa có kỳ tuyển sinh được công bố. Vui lòng theo dõi thông báo của hệ thống.';
    } else {
        throw $e;
    }
}

$schools=[];
$areas=[];
foreach($filterRows as $row){
    $schools[(int)$row['school_id']]=$row['school_name'];
    if(($row['admission_area']??'')!=='') $areas[(string)$row['admission_area']]=true;
}
?><?php require_once __DIR__ . '/../includes/workflow_layout.php'; workflow_layout_start('Xem chỉ tiêu tuyển sinh', 'quotas'); ?>
<h1>Xem chỉ tiêu tuyển sinh</h1>
<?php if($error!==null): ?>
<p><?=e($error)?></p>
<?php else: ?>
<form method="get">
    <label>Trường
        <select name="school_id">
            <option value="">Tất cả</option>
            <?php foreach($schools as $id=>$name): ?>
                <option value="<?=e((string)$id)?>" <?=($schoolId===$id?'selected':'')?>><?=e($name)?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Khu vực
        <select name="area">
            <option value="">Tất cả</option>
            <?php foreach(array_keys($areas) as $value): ?>
                <option value="<?=e($value)?>" <?=($area===$value?'selected':'')?>><?=e($value)?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Lọc</button>
</form>
<table border="1" cellpadding="8">
<tr><th>Mã trường</th><th>Trường</th><th>Khu vực</th><th>Chỉ tiêu</th><th>Trạng thái</th></tr>
<?php foreach($rows as $r): ?>
<tr><td><?=e($r['school_code'])?></td><td><?=e($r['school_name'])?></td><td><?=e($r['admission_area']??'')?></td><td><?=e($r['quota'])?></td><td><?=e($r['status'])?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php workflow_layout_end(); ?>
