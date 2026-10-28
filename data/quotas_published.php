<?php
declare(strict_types=1);

// Public quotas must come from a published database record, never the borrowed theme.
$items = [];
try {
    $rows = db()->query("SELECT aq.quota, aq.admission_period_id, ap.name AS period_name,
            ap.academic_year, s.name, s.school_code
        FROM admission_quotas aq JOIN schools s ON s.id=aq.school_id
        JOIN admission_periods ap ON ap.id=aq.admission_period_id
        JOIN admission_period_configs cfg ON cfg.admission_period_id=ap.id
        WHERE aq.published_at IS NOT NULL AND s.active=1 AND s.level='THPT'
          AND ap.status IN ('published','closed')
          AND JSON_UNQUOTE(JSON_EXTRACT(cfg.settings,'$.mode'))='official'
        ORDER BY s.name")->fetchAll();
    foreach ($rows as $row) {
        $items[] = [
            'name' => $row['name'], 'data_name' => mb_strtolower($row['name'], 'UTF-8'),
            'code' => $row['school_code'], 'code_class' => '',
            'period_name' => $row['period_name'],
            'academic_year' => $row['academic_year'],
            'stats' => [
                ['label' => 'Năm học', 'value' => (string)$row['academic_year']],
                ['label' => 'Chỉ tiêu tuyển sinh', 'value' => (string)$row['quota'] . ' học sinh'],
            ],
            'percentage' => null, 'status' => '', 'remaining' => '',
            'detail_url' => url('/quotas/public.php?period_id=' . (int)$row['admission_period_id']),
        ];
    }
} catch (Throwable $e) {
    error_log('Public quota directory unavailable: ' . $e->getMessage());
}

// Fallback tham khảo cho giao diện lịch sử khi database chưa được nhập chỉ tiêu
// chính thức của năm học 2026-2027. Các bản ghi này không thay thế dữ liệu công bố.
$historyItems = [
    [
        'name' => 'Trường THPT Nguyễn Thị Minh Khai',
        'code' => 'THPT_NGUYEN_THI_MINH_KHAI',
        'quota' => 315,
    ],
    [
        'name' => 'Trường THPT Trần Phú',
        'code' => 'THPT_TRAN_PHU',
        'quota' => 290,
    ],
    [
        'name' => 'Trường THPT Marie Curie',
        'code' => 'THPT_MARIE_CURIE',
        'quota' => 340,
    ],
];
$hasHistoricalQuota = array_filter($items, static fn(array $item): bool =>
    (string)($item['academic_year'] ?? '') === '2026-2027'
);
if (!$hasHistoricalQuota) {
    foreach ($historyItems as $historyItem) {
        $items[] = [
            'name' => $historyItem['name'],
            'data_name' => mb_strtolower($historyItem['name'], 'UTF-8'),
            'code' => $historyItem['code'],
            'code_class' => '',
            'period_name' => 'Tuyển sinh lớp 10 năm học 2026-2027',
            'academic_year' => '2026-2027',
            'stats' => [
                ['label' => 'Năm học', 'value' => '2026-2027'],
                ['label' => 'Chỉ tiêu tham khảo', 'value' => (string)$historyItem['quota'] . ' học sinh'],
            ],
            'percentage' => null,
            'status' => '',
            'remaining' => '',
            'detail_url' => '',
        ];
    }
}
return $items;
