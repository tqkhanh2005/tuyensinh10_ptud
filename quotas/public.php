<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/quota_workflow_service.php';

$pdo = db();
$periodId = (int)($_GET['period_id'] ?? 0);
$search = is_scalar($_GET['q'] ?? '') ? trim((string)$_GET['q']) : '';
$area = is_scalar($_GET['area'] ?? '') ? trim((string)$_GET['area']) : '';
$error = '';

try {
    $allRows = quota_public_rows($pdo, $periodId);
    $rows = quota_public_rows($pdo, $periodId, $search);
    if ($area !== '') {
        $rows = array_values(array_filter($rows, static fn(array $row): bool => (string)($row['admission_area'] ?? '') === $area));
    }
} catch (Throwable $e) {
    error_log('Public quotas: ' . $e->getMessage());
    $allRows = [];
    $rows = [];
    $error = 'Không thể tải chỉ tiêu. Vui lòng thử lại sau.';
}

$areas = [];
foreach ($allRows as $row) {
    if (!empty($row['admission_area'])) $areas[(string)$row['admission_area']] = true;
}
ksort($areas);
$totalQuota = array_sum(array_map(static fn(array $row): int => (int)$row['quota'], $rows));
?>
<?php require_once __DIR__ . '/../includes/workflow_layout.php'; workflow_layout_start('Chỉ tiêu tuyển sinh', 'quotas'); ?>
<link rel="stylesheet" href="<?= e(url('/quotas/quotas.css')) ?>">
<section class="quota-page">
    <div class="quota-page-heading">
        <div>
            <span class="quota-eyebrow">Kỳ tuyển sinh lớp 10</span>
            <h1>Chỉ tiêu tuyển sinh tại TP.HCM</h1>
            <?php if ($rows): ?><p class="quota-period"><?= e($rows[0]['period_name']) ?> · <?= e($rows[0]['academic_year']) ?></p><?php endif; ?>
        </div>
        <?php if ($rows): ?>
        <div class="quota-summary"><strong><?= number_format($totalQuota, 0, ',', '.') ?></strong><span>thí sinh được tuyển</span></div>
        <?php endif; ?>
    </div>

    <form method="get" class="quota-search-panel">
        <input type="hidden" name="period_id" value="<?= $periodId ?>">
        <div class="quota-search-field">
            <label for="public-quota-search">Tìm kiếm trường</label>
            <div class="quota-search-input-wrap"><span class="material-icons" aria-hidden="true">search</span><input id="public-quota-search" name="q" value="<?= e($search) ?>" placeholder="Nhập mã hoặc tên trường THPT"></div>
        </div>
        <div class="quota-area-field">
            <label for="public-quota-area">Khu vực</label>
            <select id="public-quota-area" name="area">
                <option value="">Tất cả khu vực</option>
                <?php foreach (array_keys($areas) as $value): ?><option value="<?= e($value) ?>" <?= $area === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Xem kết quả</button>
        <?php if ($search !== '' || $area !== ''): ?><a class="btn btn-outline-secondary" href="<?= e(url('/quotas/public.php' . ($periodId ? '?period_id=' . $periodId : ''))) ?>">Xóa bộ lọc</a><?php endif; ?>
    </form>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
    <?php elseif (!$rows): ?>
        <div class="quota-empty"><span class="material-icons" aria-hidden="true">search_off</span><h2><?= $search !== '' || $area !== '' ? 'Không tìm thấy trường phù hợp' : 'Chưa có chỉ tiêu tuyển sinh được công bố' ?></h2><p><?= $search !== '' || $area !== '' ? 'Hãy thử tên trường, mã trường hoặc khu vực khác.' : 'Thông tin sẽ được hiển thị khi nhà trường công bố chỉ tiêu chính thức.' ?></p></div>
    <?php else: ?>
        <div class="quota-result-meta"><strong><?= count($rows) ?></strong> trường THPT TP.HCM</div>
        <div class="quota-card-grid">
            <?php foreach ($rows as $row): ?>
            <article class="quota-school-card">
                <div class="quota-school-code"><?= e((string)$row['school_code']) ?></div>
                <h2><?= e((string)$row['name']) ?></h2>
                <div class="quota-school-area"><span class="material-icons" aria-hidden="true">location_on</span><?= e((string)($row['district'] ?: ($row['admission_area'] ?? 'TP.HCM'))) ?></div>
                <div class="quota-number"><strong><?= number_format((int)$row['quota'], 0, ',', '.') ?></strong><span>số thí sinh tuyển</span></div>
                <div class="quota-status"><span></span>Đã công bố</div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php workflow_layout_end(); ?>
