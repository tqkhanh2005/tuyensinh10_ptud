<?php
declare(strict_types=1);

// A small directory of schools in Ho Chi Minh City, linked to their official sites.
$makeSchool = static function (string $name, string $level, string $website): array {
    return [
        'name' => $name,
        'data_name' => mb_strtolower($name, 'UTF-8'),
        'data_address' => 'thành phố hồ chí minh',
        'badge' => 'Cơ sở ' . $level,
        'image' => asset('img/graduate.png'),
        'address' => 'Thành phố Hồ Chí Minh',
        'phone' => '',
        'website' => ['url' => $website, 'text' => (string)parse_url($website, PHP_URL_HOST)],
        'zalo' => null,
        'video' => null,
    ];
};
return [
    'thpt' => [
        $makeSchool('Trường THPT Nguyễn Thị Minh Khai', 'THPT', 'https://thptnguyenthiminhkhai.hcm.edu.vn/'),
        $makeSchool('Trường THPT Trần Phú', 'THPT', 'https://thpttranphu.hcm.edu.vn/'),
    ],
    'thcs' => [
        $makeSchool('Trường THCS Nguyễn Du', 'THCS', 'https://thcsnguyenduq1.hcm.edu.vn/'),
        $makeSchool('Trường THCS Lê Quý Đôn', 'THCS', 'https://thcslequydonq3.hcm.edu.vn/'),
    ],
];
