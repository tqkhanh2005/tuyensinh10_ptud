<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/candidate_service.php';
require_once __DIR__ . '/../includes/exam_service.php';
require_permission('exam_card.view');
$u = auth_user();
$candRow = candidate_find_by_user((int)$u['id']);
$candId = $candRow ? (int)$candRow['id'] : 0;

// Chống IDOR: không cho phép xem thẻ dự thi thí sinh khác qua query string
$tamperId = filter_input(INPUT_GET, 'candidate_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($tamperId && $tamperId !== $candId) {
    http_response_code(403);
    exit('403 Forbidden: Bạn không có quyền truy cập thẻ dự thi của thí sinh khác.');
}

try {
    $candidate = exam_card_for_user(db(), (int)$u['id']);
} catch (Throwable $e) {
    error_log('exam card query failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Không thể tạo thẻ dự thi, vui lòng thử lại.');
}
if (!$candidate) {
    http_response_code(404);
    exit('Bạn chưa được phân công phòng thi hoặc cấp số báo danh.');
}
if (trim((string)$candidate['exam_number']) === '' || trim((string)$candidate['exam_site_name']) === '' || trim((string)$candidate['exam_room_name']) === '' || trim((string)$candidate['exam_schedule']) === '') { ?>
    <!doctype html>
    <html lang="vi">
    <meta charset="utf-8">
    <p>Bạn chưa được phân công phòng thi hoặc cấp số báo danh.</p>

    </html><?php exit;
}
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Thẻ dự thi</title>
</head>

<body>
    <h1>THẺ DỰ THI</h1>
    <p>Họ tên: <?= e($candidate['full_name']) ?></p>
    <p>SBD: <?= e($candidate['exam_number']) ?></p>
    <p>Điểm thi: <?= e($candidate['exam_site_name']) ?></p>
    <p>Phòng thi: <?= e($candidate['exam_room_name']) ?></p>
    <p>Giờ thi: <?= e($candidate['exam_schedule']) ?></p>
    <button onclick="window.print()">In / Tải thẻ</button>
</body>

</html>