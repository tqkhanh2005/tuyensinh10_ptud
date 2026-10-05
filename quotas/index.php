<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';
require_permission('admission.manage');
$pdo = db();
$periodId = (int)($_GET['period_id'] ?? 0);
$period = admission_period_find($pdo, $periodId);
if (!$period) {
    http_response_code(404);
    exit('Không tìm thấy kỳ tuyển sinh.');
}
$rows = quota_rows($pdo, $periodId, trim((string)($_GET['q'] ?? '')), trim((string)($_GET['area'] ?? '')));
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        quota_save_all($pdo, $periodId, (array)($_POST['quota'] ?? []));
        $message = 'Đặt chỉ tiêu thành công.';
        $rows = quota_rows($pdo, $periodId);
    } catch (Throwable $e) {
        $message = 'Không thể lưu chỉ tiêu. Vui lòng kiểm tra dữ liệu và thử lại.';
    }
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Đặt chỉ tiêu</title>
</head>

<body>
    <h1>Đặt chỉ tiêu tuyển sinh</h1>
    <p>Kỳ: <?= e($period['name']) ?> (<?= e($period['academic_year']) ?>)</p>
    <?php if ($message): ?><p><?= e($message) ?></p><?php endif; ?>
    <form method="post"><?= csrf_field() ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>Mã trường</th>
                <th>Tên trường</th>
                <th>Khu vực</th>
                <th>Chỉ tiêu</th>
                <th>Trạng thái</th>
            </tr>
            <?php foreach ($rows as $r): ?><tr>
                    <td><?= e($r['school_code']) ?></td>
                    <td><?= e($r['name']) ?></td>
                    <td><?= e($r['admission_area'] ?? '') ?></td>
                    <td><input type="number" min="0" step="1" name="quota[<?= e((string)$r['school_id']) ?>]" value="<?= e((string)$r['quota']) ?>"></td>
                    <td><?= e($r['quota_status']) ?></td>
                </tr><?php endforeach; ?>
        </table><button>Lưu tất cả</button>
    </form>
</body>

</html>