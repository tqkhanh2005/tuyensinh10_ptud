<?php
/**
 * Application Configuration & Helper Functions
 * Cổng thông tin Tuyển sinh Lớp 10 - TP Cần Thơ
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tự động xác định đường dẫn gốc chuẩn xác, không bị lệch khi chạy từ file trong thư mục con (auth/, api/)
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$projRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

if (!empty($docRoot) && strpos($projRoot, $docRoot) === 0) {
    $baseDir = substr($projRoot, strlen($docRoot));
} else {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $clean = preg_replace('#/(auth|api|views|includes)/.*$#', '', $script);
    $baseDir = dirname($clean);
}

$baseDir = rtrim(str_replace('\\', '/', $baseDir), '/');
define('BASE_URL', $baseDir === '/' ? '' : $baseDir);
define('SITE_NAME', 'TUYỂN SINH LỚP 10 - TP CẦN THƠ');

/**
 * Tạo URL tương đối tới thư mục ứng dụng
 */
function url(string $path = ''): string {
    $cleanPath = '/' . ltrim($path, '/');
    return BASE_URL . $cleanPath;
}

/**
 * Đường dẫn tài nguyên tĩnh
 */
function asset(string $path = ''): string {
    return url('public/' . ltrim($path, '/'));
}

/**
 * Xử lý dữ liệu tránh XSS
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
