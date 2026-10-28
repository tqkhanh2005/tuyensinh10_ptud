<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/council_service.php';
require_permission('council.manage');
$pdo=db();
$periods=$pdo->query('SELECT id,name FROM admission_periods ORDER BY id DESC')->fetchAll();
$periodId=(int)($_GET['period_id']??($periods[0]['id']??0));
$type=is_string($_GET['type']??null)?$_GET['type']:null;
$error='';
try{
    $councils=council_list($pdo,$periodId,$type);
    $memberGroups=[];
    if($councils){
        $ids=array_column($councils,'id');
        $query=$pdo->prepare('SELECT cm.*,s.full_name,s.workplace FROM council_members cm JOIN staff s ON s.id=cm.staff_id WHERE cm.council_id IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY cm.id');
        $query->execute($ids);
        foreach($query as $member)$memberGroups[(int)$member['council_id']][]=$member;
    }
}catch(Throwable $e){
    error_log('Council list error: ' . $e->getMessage());
    $councils=[];
    $error='Không thể tải danh sách hội đồng thi. Vui lòng thử lại sau.';
}
require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Quản lý hội đồng thi';
$activePage = 'councils';

admin_layout_start('Quản lý hội đồng thi', 'councils', ['Quản lý kỳ thi' => '']);
?>
<?php if(!empty($_SESSION['council_created'])): unset($_SESSION['council_created']); ?><div class="alert alert-success" role="status">Thành lập hội đồng thành công. Bạn có thể mở phần Phân công để kiểm tra thành viên và nhiệm vụ.</div><?php endif; ?>
<?php if(isset($_SESSION['council_cancelled'])):?><div class="alert alert-success" role="status">Đã hủy hội đồng <?=e($_SESSION['council_cancelled'])?>. Lịch sử được giữ nguyên.</div><?php unset($_SESSION['council_cancelled']);endif;?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Thành lập hội đồng</h1>
    <p class="text-muted mb-0">Quản lý hội đồng theo kỳ tuyển sinh.</p>
  </div>
  <a class="btn btn-primary fw-medium px-3" href="<?= e(url('/councils/create.php?period_id=' . $periodId)) ?>">
    Thành lập hội đồng
  </a>
</div>
<div class="mb-3"><a class="btn btn-outline-primary mb-2" href="<?=e(url('/exam/organization.php?period_id='.$periodId))?>">Tổ chức thi</a><form method="get"><label class="form-label" for="council-period">Kỳ tuyển sinh</label><select id="council-period" class="form-select" name="period_id" onchange="this.form.submit()"><?php foreach($periods as $p):?><option value="<?=$p['id']?>" <?=$p['id']==$periodId?'selected':''?>><?=e($p['name'])?></option><?php endforeach;?></select><noscript><button>Xem</button></noscript></form></div>
<?php if ($error): ?>
  <div class="alert alert-danger mb-4"><?= e($error) ?></div>
<?php endif; ?>
<div class="table-responsive bg-white rounded-4 shadow-sm">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Loại</th>
        <th>Tên hội đồng</th>
        <th>Điểm thi</th>
        <th>Môn phụ trách</th>
        <th>Thời gian</th>
        <th>Trạng thái</th><th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($councils as $c): ?>
        <tr>
          <td><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?= e(council_type_label($c['council_type'])) ?></span></td>
          <td class="fw-semibold text-dark"><?= e($c['name']) ?><details class="mt-2"><summary>Phân công (<?=count($memberGroups[(int)$c['id']]??[])?> thành viên)</summary><ul class="mt-2 mb-0"><?php foreach($memberGroups[(int)$c['id']]??[] as $member):?><li><?=e($member['full_name'])?> · <?=e($member['role_name']?:'Chưa phân vai')?><?php if($member['task_name']):?> — <?=e($member['task_name'])?><?php endif;?></li><?php endforeach;?></ul><?php if($c['location']):?><p class="small mt-2">Địa điểm: <?=e($c['location'])?></p><?php endif;?></details></td>
          <td class="text-muted"><?= e($c['site_name'] ?? 'Chưa cập nhật') ?></td>
          <td class="text-muted"><?= e($c['subjects'] ?? 'Chưa cập nhật') ?></td>
          <td class="text-muted small"><?= e(trim(($c['start_at'] ?? '') . ' - ' . ($c['end_at'] ?? ''), ' -') ?: 'Chưa cập nhật') ?></td>
          <td><span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill"><?= e(['draft'=>'Bản nháp','established'=>'Đã thành lập','cancelled'=>'Đã hủy'][$c['status']]??$c['status']) ?></span></td><td><?php if($c['status']!=='cancelled'):?><a class="btn btn-outline-danger btn-sm" href="<?=e(url('/councils/cancel.php?id='.(int)$c['id']))?>">Hủy hội đồng</a><?php else:?><span class="text-muted">Đã hủy</span><?php endif;?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$councils): ?>
        <tr>
          <td colspan="7" class="text-center py-5 text-muted">Chưa có hội đồng trong kỳ này.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php
admin_layout_end();
