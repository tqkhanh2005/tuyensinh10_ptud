<?php
declare(strict_types=1);

// Public quotas must come from a published database record, never the borrowed theme.
$items = [];
try {
    $rows = db()->query("SELECT aq.quota,aq.admission_period_id,s.name,s.school_code
        FROM admission_quotas aq JOIN schools s ON s.id=aq.school_id
        JOIN admission_periods ap ON ap.id=aq.admission_period_id
        JOIN admission_period_configs cfg ON cfg.admission_period_id=ap.id
        WHERE aq.published_at IS NOT NULL AND s.active=1 AND s.level='THPT'
          AND ap.status IN ('published','closed')
          AND JSON_UNQUOTE(JSON_EXTRACT(cfg.settings,'$.mode'))='official'
          AND ap.id=(SELECT MAX(aq2.admission_period_id) FROM admission_quotas aq2
              JOIN admission_periods ap2 ON ap2.id=aq2.admission_period_id
              JOIN admission_period_configs cfg2 ON cfg2.admission_period_id=ap2.id
              WHERE aq2.published_at IS NOT NULL AND ap2.status IN ('published','closed')
              AND JSON_UNQUOTE(JSON_EXTRACT(cfg2.settings,'$.mode'))='official')
        ORDER BY s.name")->fetchAll();
    foreach ($rows as $row) {
        $items[] = [
            'name' => $row['name'], 'data_name' => mb_strtolower($row['name'], 'UTF-8'),
            'code' => $row['school_code'], 'code_class' => '',
            'stats' => [['label' => 'Chỉ tiêu công bố', 'value' => (string)$row['quota']]],
            'percentage' => null, 'status' => '', 'remaining' => '',
            'detail_url' => url('/quotas/public.php?period_id=' . (int)$row['admission_period_id']),
        ];
    }
} catch (Throwable $e) {
    error_log('Public quota directory unavailable: ' . $e->getMessage());
}
return $items;
