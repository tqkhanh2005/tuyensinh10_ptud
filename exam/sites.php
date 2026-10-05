<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_permission('exam.manage');
$pdo = db();
$periodId = (int)($_GET['period_id'] ?? $_POST['period_id'] ?? 0);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        create_exam_site($pdo, $_POST);
        header('Location: ' . url('/exam/sites.php?period_id=' . $periodId));
        exit;
    } catch (Throwable $e) {
        $error = 'Không thể tạo điểm thi. Kiểm tra dữ liệu hoặc mã trùng.';
    }
}
$sites = exam_sites($pdo, $periodId);
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Điểm thi</title>
</head>

<body>
    <h1>Điểm thi</h1><?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="period_id" value="<?= $periodId ?>">
        Mã <input name="site_code" required> Tên <input name="name" required> Địa chỉ <input name="address">
        Sức chứa <input type="number" min="0" name="capacity" required><button>Thêm điểm thi</button></form>
    <table border="1">
        <tr>
            <th>Mã</th>
            <th>Tên</th>
            <th>Sức chứa</th>
            <th>Phòng</th>
        </tr>
        <?php foreach ($sites as $s): ?><tr>
                <td><?= e($s['site_code']) ?></td>
                <td><?= e($s['name']) ?></td>
                <td><?= e((string)$s['capacity']) ?></td>
                <td><a href="<?= e(url('/exam/rooms.php?site_id=' . (int)$s['id'])) ?>">Quản lý phòng</a></td>
            </tr><?php endforeach; ?>
    </table>
</body>

</html>