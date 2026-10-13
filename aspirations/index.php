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

$periods = admission_period_list($pdo);

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

    try {
        aspiration_save(
            (int)$candidate['id'],
            $periodId,
            (array)($_POST['school_ids'] ?? []),
            (int)$u['id']
        );

        $saved = true;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
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
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Đăng ký nguyện vọng</title>
</head>
<body>

<h1>Đăng ký nguyện vọng</h1>

<?php foreach ($errors as $x): ?>
    <p><?= e($x) ?></p>
<?php endforeach; ?>

<?php if (!empty($saved)): ?>
    <p>Đăng ký nguyện vọng thành công.</p>
<?php endif; ?>

<form method="post">
    <?= csrf_field() ?>

    <select name="period_id">
        <?php foreach ($periods as $p): ?>
            <option
                value="<?= (int)$p['id'] ?>"
                <?= ((int)$p['id'] === $periodId ? 'selected' : '') ?>
            >
                <?= e($p['name'] . ' - ' . $p['academic_year']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <br>

    <?php foreach ($schools as $s): ?>
        <label>
            <input
                type="checkbox"
                name="school_ids[]"
                value="<?= (int)$s['id'] ?>"
                <?= in_array((int)$s['id'], $selected, true) ? 'checked' : '' ?>
            >
            <?= e($s['school_code'] . ' - ' . $s['name']) ?>
        </label>
        <br>
    <?php endforeach; ?>

    <p>
        Thứ tự được xác định theo thứ tự các trường được gửi.
        Không tự đặt giới hạn số nguyện vọng vì đặc tả chưa quy định.
    </p>

    <button type="submit">Xác nhận</button>
</form>

</body>
</html>