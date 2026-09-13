<?php
/**
 * Trang chủ Tuyển sinh Lớp 10 - TP Cần Thơ
 */
require_once __DIR__ . '/includes/config.php';

$pageTitle = 'TUYỂN SINH LỚP 10 - TP CẦN THƠ';
$activePage = 'home';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';

// Các phân đoạn trang chủ
require __DIR__ . '/views/home/hero.php';
require __DIR__ . '/views/home/timeline.php';
require __DIR__ . '/views/home/targets.php';
require __DIR__ . '/views/home/schools.php';
require __DIR__ . '/views/home/video.php';
require __DIR__ . '/views/home/guides.php';

require __DIR__ . '/includes/footer.php';
require __DIR__ . '/includes/scripts.php';
require __DIR__ . '/views/home/home_scripts.php';
?>
</body>
</html>
