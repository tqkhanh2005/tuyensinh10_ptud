<?php
/**
 * PHP Built-in Server Router
 * Usage: php -S localhost:4000 router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// Nếu là file tĩnh thực tế trong public/ hoặc root (như ảnh, css, js, font)
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// Kiểm tra thư mục public/
$publicFile = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    $ext = pathinfo($publicFile, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'pdf' => 'application/pdf',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];
    $contentType = $mimes[$ext] ?? mime_content_type($publicFile);
    header("Content-Type: $contentType");
    readfile($publicFile);
    exit;
}

// Định tuyến các URL
if ($uri === '/' || $uri === '/index.php' || $uri === '/index.html') {
    require __DIR__ . '/index.php';
    exit;
}

if ($uri === '/ket-qua' || $uri === '/ket-qua.php') {
    require __DIR__ . '/ket-qua.php';
    exit;
}

if ($uri === '/auth/login' || $uri === '/auth/login.php' || $uri === '/login' || $uri === '/login.php') {
    require __DIR__ . '/auth/login.php';
    exit;
}

if ($uri === '/captcha/image' || $uri === '/api/captcha.php') {
    require __DIR__ . '/api/captcha.php';
    exit;
}

// 404 Not Found
http_response_code(404);
echo "404 Not Found: " . htmlspecialchars($uri);
