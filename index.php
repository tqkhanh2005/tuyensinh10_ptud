<?php
/**
 * Trang chủ Tuyển sinh Lớp 10 - TP Cần Thơ
 */
require_once __DIR__ . '/includes/config.php';

$pageTitle = 'TUYỂN SINH LỚP 10';
$activePage = 'home';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';

// Các phân đoạn trang chủ theo bố cục rõ ràng:
// 1. Giới thiệu ngắn
require __DIR__ . '/views/home/hero.php';
// Bảng thông tin thí sinh (chỉ hiển thị khi thí sinh đăng nhập)
require __DIR__ . '/views/candidate/home_dashboard.php';
// 2. Mốc tuyển sinh
require __DIR__ . '/views/home/timeline.php';
// 3. Hướng dẫn thao tác
require __DIR__ . '/views/home/guides.php';
// 4. Chỉ tiêu tuyển sinh & Danh sách trường
require __DIR__ . '/views/home/targets.php';
require __DIR__ . '/views/home/schools.php';
require __DIR__ . '/views/home/video.php';

require __DIR__ . '/includes/footer.php';
require __DIR__ . '/includes/scripts.php';
require __DIR__ . '/views/home/home_scripts.php';
?>
</body>
</html>
