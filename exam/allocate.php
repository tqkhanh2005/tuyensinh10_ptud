<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_permission('exam.allocate');
$pdo = db();
$periodId = (int)($_GET['period_id'] ?? $_POST['period_id'] ?? 0);
$error = '';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        $assignments = (array)($_POST['site'] ?? []);
        if (($_POST['mode'] ?? '') === 'auto') $assignments = allocation_preview($pdo, $periodId);
        save_allocations($pdo, $periodId, $assignments);
        $message = 'Phân bố thí sinh thành công.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
$candidates = candidate_rows($pdo, $periodId);
$sites = exam_sites($pdo, $periodId);
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Phân bố thí sinh</title>
</head>

<body>
    <h1>Phân bố thí sinh vào điểm thi</h1><?php if ($message): ?><p><?= e($message) ?></p><?php endif; ?><?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="period_id" value="<?= $periodId ?>">
        <button name="mode" value="auto">Phân bố tự động</button>
        <table border="1">
            <tr>
                <th>SBD</th>
                <th>Thí sinh</th>
                <th>Điểm thi</th>
            </tr>
            <?php foreach ($candidates as $c): ?><tr>
                    <td><?= e((string)$c['candidate_code']) ?></td>
                    <td><?= e($c['full_name']) ?></td>
                    <td>
                        <select name="site[<?= $c['id'] ?>]">
                            <option value="">--</option><?php foreach ($sites as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ((int)($c['allocated_site_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e((string)$s['capacity']) ?>)</option><?php endforeach; ?>
                        </select>
                    </td>
                </tr><?php endforeach; ?>
        </table><button name="mode" value="manual">Xác nhận phân bố thủ công</button>
    </form>
</body>

</html>