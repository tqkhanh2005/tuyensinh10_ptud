<?php
declare(strict_types=1);

// Workflow guide only. The administration must announce the dates for each season.
$stages = [
    ['Rà soát hồ sơ học sinh', 'Kiểm tra thông tin học sinh do trường THCS cung cấp và gửi yêu cầu chỉnh sửa nếu cần.'],
    ['Đăng ký nguyện vọng', 'Đăng ký nguyện vọng trong thời gian hệ thống mở cổng.'],
    ['Tham dự kỳ thi', 'Theo dõi thông tin số báo danh, điểm thi và phòng thi trên hồ sơ cá nhân.'],
    ['Tra cứu kết quả và phúc khảo', 'Theo dõi kết quả được công bố và thời gian tiếp nhận yêu cầu phúc khảo.'],
    ['Xác nhận nhập học', 'Xác nhận nhập học theo kết quả xét tuyển và thông báo của kỳ tuyển sinh.'],
];
$items = [];
foreach ($stages as $index => [$title, $description]) {
    $items[] = [
        'index' => $index + 1, 'is_reverse' => $index % 2 === 1,
        'date' => 'Theo thông báo kỳ tuyển sinh', 'icon' => 'calendar_month',
        'event_badge' => 'BƯỚC ' . ($index + 1), 'title' => $title, 'desc' => $description,
    ];
}
return $items;
