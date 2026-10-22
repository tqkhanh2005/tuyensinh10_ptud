<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';
require_once __DIR__ . '/../includes/candidate_service.php';

require_permission('aspiration.manage');

$u = auth_user();

$candidate = candidate_find_by_user((int)$u['id']);

if (!$candidate) {
    http_response_code(404);
    exit('Không tìm thấy hồ sơ.');
}

// Chống IDOR: không cho phép xem hoặc sửa nguyện vọng thí sinh khác
$tamperId = filter_input(INPUT_GET, 'candidate_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'candidate_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($tamperId && $tamperId !== (int)$candidate['id']) {
    http_response_code(403);
    exit('403 Forbidden: Bạn không có quyền truy cập hoặc chỉnh sửa nguyện vọng của thí sinh khác.');
}

$pdo = db();

$periods = $pdo->query('SELECT * FROM admission_periods WHERE status IN ("published","closed") ORDER BY (status="published") DESC,id DESC')->fetchAll();

$periodId = (int)(
    $_GET['period_id']
    ?? ($periods[0]['id'] ?? 0)
);

$schools = $pdo->query(
    'SELECT id,school_code,name
     FROM schools
     WHERE level="THPT"
       AND active=1
     ORDER BY name'
)->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $periodId = (int)($_POST['period_id'] ?? 0);
    $orderedSchools = [];
    $gap = false;
    foreach ((array)($_POST['school_ids'] ?? []) as $value) {
        if ($value === '') { $gap = true; continue; }
        if ($gap) $errors[] = 'Vui lòng chọn nguyện vọng liên tiếp, bắt đầu từ NV1.';
        $orderedSchools[] = $value;
    }

    try {
        if ($errors) throw new InvalidArgumentException($errors[0]);
        aspiration_save(
            (int)$candidate['id'],
            $periodId,
            $orderedSchools,
            (int)$u['id']
        );

        $saved = true;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$currentConfiguration=period_configuration($pdo,$periodId);
if($currentConfiguration) {
    $schoolOptions=$pdo->prepare('SELECT s.id,s.school_code,s.name FROM schools s JOIN admission_quotas aq ON aq.school_id=s.id AND aq.admission_period_id=? WHERE s.level="THPT" AND s.active=1 AND aq.status="set" AND aq.quota>0 ORDER BY s.name');
    $schoolOptions->execute([$periodId]);$schools=$schoolOptions->fetchAll();
}
$current = $periodId
    ? aspiration_get(
        (int)$candidate['id'],
        $periodId
    )
    : [];

$selected = array_map(
    'intval',
    array_column($current, 'school_id')
);
$period = null;
foreach ($periods as $item) if ((int)$item['id'] === $periodId) $period = $item;
$canEdit = $period && $period['status'] === 'published' && $candidate['status']==='active' && (int)$candidate['eligible']===1;
$now = new DateTimeImmutable('now');
$configuration=$period ? period_configuration($pdo,$periodId) : null;
if ($configuration) $canEdit=$canEdit && period_registration_error($pdo,$periodId,(int)$candidate['id'])===null;
elseif ($period && ((!empty($period['registration_start']) && $now < new DateTimeImmutable($period['registration_start'])) || (!empty($period['registration_end']) && $now > new DateTimeImmutable($period['registration_end'])))) $canEdit = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors) $selected = array_values(array_filter((array)($_POST['school_ids'] ?? []), 'is_scalar'));
require_once __DIR__ . '/../includes/workflow_layout.php'; workflow_layout_start('Đăng ký nguyện vọng', 'aspirations');
?>
<h1>Đăng ký nguyện vọng</h1>
<?php if($current):?><h2 class="h5">Nguyện vọng đã lưu</h2><ol><?php foreach($current as $wish):?><li><?=e($wish['name'])?></li><?php endforeach;?></ol><?php endif;?>
<p>Chọn trường theo thứ tự ưu tiên. Kiểm tra kỹ NV1, NV2, NV3 trước khi lưu.</p>
<?php foreach (array_unique($errors) as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<?php if (!empty($saved)): ?><div class="alert alert-success">Đăng ký nguyện vọng thành công.</div><?php endif; ?>
<?php if (!$periods): ?><div class="alert alert-info">Chưa có kỳ tuyển sinh được công bố.</div><?php endif; ?>
<?php if (!$schools): ?><div class="alert alert-info">Chưa có danh mục trường THPT. Vui lòng theo dõi thông báo cập nhật.</div><?php endif; ?>
<form method="get" class="mb-3">
<label for="view_period">Kỳ tuyển sinh</label>
<select id="view_period" name="period_id" class="form-select"><?php foreach ($periods as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (int)$item['id']===$periodId?'selected':'' ?>><?= e($item['name'].' · '.$item['academic_year']) ?></option><?php endforeach; ?></select>
<button class="btn btn-outline-primary mt-2" type="submit">Xem nguyện vọng</button>
</form>
<?php if ($period && !$canEdit): ?><div class="alert alert-warning">Hiện không thể thay đổi nguyện vọng của kỳ này. Vui lòng kiểm tra thời hạn đăng ký và trạng thái hồ sơ.</div><?php endif; ?>
<form method="post">
<?= csrf_field() ?><input type="hidden" name="period_id" value="<?= $periodId ?>">
<fieldset <?= (!$canEdit || !$schools)?'disabled':'' ?>>
<div id="aspiration_choices">
<?php for ($index=0;$index<max(3,count($selected));$index++): ?>
<div class="mb-3 aspiration-choice"><label class="form-label" for="nv_<?= $index ?>">Nguyện vọng <?= $index+1 ?></label>
<select class="form-select" id="nv_<?= $index ?>" name="school_ids[]"><option value="">-- Chọn trường --</option>
<?php foreach ($schools as $school): ?><option value="<?= (int)$school['id'] ?>" <?= (int)($selected[$index] ?? 0)===(int)$school['id']?'selected':'' ?>><?= e($school['name'].' · '.$school['school_code']) ?></option><?php endforeach; ?>
</select></div>
<?php endfor; ?>
</div>
<?php if (!$configuration): ?><button class="btn btn-outline-secondary" type="button" id="add_aspiration">Thêm nguyện vọng</button><?php else: ?><p class="small text-muted">Tối đa 3 nguyện vọng thường, không chọn trùng trường.</p><?php endif; ?>
<button class="btn btn-primary" type="submit">Lưu thứ tự nguyện vọng</button>
</fieldset>
</form>
<script>
document.getElementById('add_aspiration')?.addEventListener('click',function(){
 const box=document.getElementById('aspiration_choices');
 const index=box.children.length;
 const row=box.firstElementChild.cloneNode(true);
 row.querySelector('label').textContent='Nguyện vọng '+(index+1);
 row.querySelector('label').htmlFor='nv_'+index;
 row.querySelector('select').id='nv_'+index;
 row.querySelector('select').value='';
 box.appendChild(row);
});
</script>
<?php workflow_layout_end(); ?>
