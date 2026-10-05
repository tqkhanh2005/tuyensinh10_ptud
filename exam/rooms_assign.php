<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_permission('exam.allocate');
$pdo = db();
$periodId = (int)($_GET['period_id'] ?? $_POST['period_id'] ?? 0);
$siteId = (int)($_GET['site_id'] ?? $_POST['site_id'] ?? 0);
$error = '';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        $assignments = (array)($_POST['room'] ?? []);
        if (($_POST['mode'] ?? '') === 'auto') $assignments = room_preview($pdo, $periodId, $siteId);
        save_room_assignments($pdo, $periodId, $siteId, $assignments);
        $message = 'Sắp xếp thí sinh vào phòng thi thành công.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
$q = $pdo->prepare("SELECT c.id,c.candidate_code,c.full_name,ra.exam_room_id AS assigned_room_id
                  FROM candidates c
                  JOIN exam_allocations a ON a.candidate_id=c.id
                    AND a.admission_period_id=:p AND a.exam_site_id=:s
                  LEFT JOIN room_assignments ra
                    ON ra.candidate_id=c.id AND ra.admission_period_id=:p2
                  WHERE c.status='active' AND c.eligible=1
                  ORDER BY c.candidate_code,c.id");
$q->execute(['p' => $periodId, 's' => $siteId, 'p2' => $periodId]);
$candidates = $q->fetchAll();
$rooms = exam_rooms($pdo, $siteId);
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Sắp xếp phòng thi</title>
</head>

<body>
    <h1>Sắp xếp thí sinh vào phòng thi</h1><?php if ($message): ?><p><?= e($message) ?></p><?php endif; ?><?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="period_id" value="<?= $periodId ?>"><input type="hidden" name="site_id" value="<?= $siteId ?>">
        <button name="mode" value="auto">Sắp xếp tự động</button>
        <table border="1">
            <tr>
                <th>SBD</th>
                <th>Thí sinh</th>
                <th>Phòng</th>
            </tr>
            <?php foreach ($candidates as $c): ?><tr>
                    <td><?= e($c['candidate_code']) ?></td>
                    <td><?= e($c['full_name']) ?></td>
                    <td>
                        <select name="room[<?= $c['id'] ?>]">
                            <option value="">--</option><?php foreach ($rooms as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= ((int)($c['assigned_room_id'] ?? 0) === (int)$r['id']) ? 'selected' : '' ?>><?= e($r['name']) ?> (<?= e((string)$r['capacity']) ?>)</option><?php endforeach; ?>
                        </select>
                    </td>
                </tr><?php endforeach; ?>
        </table>
        <button name="mode" value="manual">Xác nhận sắp xếp thủ công</button>
    </form>
</body>

</html>