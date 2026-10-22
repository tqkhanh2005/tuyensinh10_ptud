<?php
/**
 * Application Configuration & Helper Functions
 * Cổng thông tin Tuyển sinh Lớp 10 - TP.HCM
 */

declare(strict_types=1);
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if(PHP_SAPI!=='cli' && !headers_sent()){
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: SAMEORIGIN');
    if(isset($_SESSION['auth']))header('Cache-Control: private, no-store');
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
define('SITE_NAME', 'TUYỂN SINH LỚP 10 - TP.HCM');

/**
 * Tạo URL tương đối tới thư mục ứng dụng
 */
function url(string $path = ''): string
{
    $cleanPath = '/' . ltrim($path, '/');
    return BASE_URL . $cleanPath;
}

/**
 * Đường dẫn tài nguyên tĩnh
 */
function asset(string $path = ''): string
{
    return url('public/' . ltrim($path, '/'));
}

/**
 * Xử lý dữ liệu tránh XSS
 */
function e(?string $string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
