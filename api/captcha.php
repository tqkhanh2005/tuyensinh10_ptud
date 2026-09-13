<?php
/**
 * Dynamic Captcha Generator API
 * Returns JSON { "image": "<svg>...</svg>", "token": "..." }
 */
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Tạo token ngẫu nhiên
$token = bin2hex(random_bytes(16));

// Tạo chuỗi mã xác nhận 5-6 ký tự dễ đọc
$chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
$length = 5;
$code = '';
for ($i = 0; $i < $length; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}

// Lưu session để kiểm tra
if (!isset($_SESSION['captchas'])) {
    $_SESSION['captchas'] = [];
}
$_SESSION['captchas'][$token] = [
    'code' => $code,
    'expires' => time() + 120 // Hạn 120s
];

// Dọn dẹp token hết hạn
foreach ($_SESSION['captchas'] as $tok => $data) {
    if ($data['expires'] < time()) {
        unset($_SESSION['captchas'][$tok]);
    }
}

// Tạo SVG captcha đẹp mắt
$width = 130;
$height = 42;
$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
$svg .= '<rect width="100%" height="100%" fill="#f1f5f9" rx="6"/>';

// Đường cong nhiễu
for ($i = 0; $i < 3; $i++) {
    $x1 = random_int(0, 30);
    $y1 = random_int(5, $height - 5);
    $x2 = random_int($width - 30, $width);
    $y2 = random_int(5, $height - 5);
    $cx = random_int(40, $width - 40);
    $cy = random_int(0, $height);
    $color = ['#94a3b8', '#cbd5e1', '#64748b'][random_int(0, 2)];
    $svg .= '<path d="M ' . $x1 . ' ' . $y1 . ' Q ' . $cx . ' ' . $cy . ' ' . $x2 . ' ' . $y2 . '" stroke="' . $color . '" stroke-width="1.5" fill="none" opacity="0.7"/>';
}

// Chấm nhiễu
for ($i = 0; $i < 25; $i++) {
    $rx = random_int(0, $width);
    $ry = random_int(0, $height);
    $svg .= '<circle cx="' . $rx . '" cy="' . $ry . '" r="1" fill="#94a3b8" opacity="0.6"/>';
}

// Ký tự
$colors = ['#1e40af', '#0369a1', '#0f766e', '#4338ca', '#b45309'];
$charWidth = ($width - 20) / $length;

for ($i = 0; $i < $length; $i++) {
    $char = $code[$i];
    $x = 12 + ($i * $charWidth) + random_int(-2, 3);
    $y = 28 + random_int(-3, 3);
    $rot = random_int(-15, 15);
    $c = $colors[$i % count($colors)];
    $svg .= '<text x="' . $x . '" y="' . $y . '" fill="' . $c . '" font-size="20" font-weight="bold" font-family="Arial, sans-serif" transform="rotate(' . $rot . ' ' . $x . ' ' . $y . ')">' . $char . '</text>';
}

$svg .= '</svg>';

echo json_encode([
    'image' => $svg,
    'token' => $token
], JSON_UNESCAPED_UNICODE);
exit;
