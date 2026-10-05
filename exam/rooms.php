<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_permission('exam.manage');
$pdo = db();
$siteId = (int)($_GET['site_id'] ?? $_POST['site_id'] ?? 0);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        create_exam_room($pdo, $_POST);
        header('Location: ' . url('/exam/rooms.php?site_id=' . $siteId));
        exit;
    } catch (Throwable $e) {
        $error = 'Không thể tạo phòng thi. Kiểm tra dữ liệu hoặc mã trùng.';
    }
}
$rooms = exam_rooms($pdo, $siteId);
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Phòng thi</title>
</head>

<body>
    <h1>Phòng thi</h1><?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="site_id" value="<?= $siteId ?>">
        Mã <input name="room_code" required> Tên <input name="name" required>
        Sức chứa <input type="number" min="0" name="capacity" required><button>Thêm phòng</button></form>
    <table border="1">
        <tr>
            <th>Mã</th>
            <th>Tên</th>
            <th>Sức chứa</th>
        </tr>
        <?php foreach ($rooms as $r): ?><tr>
                <td><?= e($r['room_code']) ?></td>
                <td><?= e($r['name']) ?></td>
                <td><?= e((string)$r['capacity']) ?></td>
            </tr><?php endforeach; ?>
    </table>
</body>

</html>