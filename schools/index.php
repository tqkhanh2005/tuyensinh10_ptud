<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';

require_permission('school.manage');
$pdo = db();
$schools = school_list($pdo, trim((string)($_GET['q'] ?? '')), (string)($_GET['level'] ?? ''), trim((string)($_GET['area'] ?? '')));
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Quản lý trường</title>
<meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body>
<h1>Quản lý trường THCS, THPT</h1>
<p><a href="<?= e(url('/schools/create.php')) ?>">Thêm trường</a></p>
<form method="get">
<input name="q" placeholder="Tìm kiếm..." value="<?= e((string)($_GET['q'] ?? '')) ?>">
<select name="level"><option value="">Tất cả cấp học</option>
<option value="THCS" <?= ($_GET['level'] ?? '')==='THCS'?'selected':'' ?>>THCS</option>
<option value="THPT" <?= ($_GET['level'] ?? '')==='THPT'?'selected':'' ?>>THPT</option></select>
<button>Tìm</button>
</form>
<table border="1" cellpadding="8" cellspacing="0">
<tr><th>Mã trường</th><th>Tên trường</th><th>Cấp học</th><th>Quận/Huyện</th><th>Khu vực</th><th>Thao tác</th></tr>
<?php foreach ($schools as $school): ?>
<tr>
<td><?= e($school['school_code']) ?></td><td><?= e($school['name']) ?></td><td><?= e($school['level']) ?></td>
<td><?= e($school['district'] ?? '') ?></td><td><?= e($school['admission_area'] ?? '') ?></td>
<td><a href="<?= e(url('/schools/edit.php?id='.(int)$school['id'])) ?>">Sửa</a>
<form method="post" action="<?= e(url('/schools/delete.php')) ?>" style="display:inline">
<?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$school['id'] ?>">
<button type="submit" onclick="return confirm('Xác nhận xóa trường?')">Xóa</button></form></td>
</tr>
<?php endforeach; ?>
</table>
</body></html>
