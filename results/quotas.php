<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/publication_service.php';
require_permission('quota.view');

$pdo = db();
$error = null;
$rows = [];
$filterRows = [];
$schoolId = isset($_GET['school_id']) && (int)$_GET['school_id'] > 0 ? (int)$_GET['school_id'] : null;
$area = trim((string)($_GET['area'] ?? ''));
$area = $area !== '' ? $area : null;

try {
    $currentPeriodId = current_admission_period_id($pdo);
    $filterRows = published_quota_list($pdo, $currentPeriodId);
    $rows = published_quota_list($pdo, $currentPeriodId, $schoolId, $area);
} catch (RuntimeException $e) {
    if ($e->getMessage() === '[MISSING CURRENT ADMISSION PERIOD]') {
        $error = 'Chưa có kỳ tuyển sinh được công bố. Vui lòng theo dõi thông báo của hệ thống.';
    } else {
        throw $e;
    }
}

$schools = [];
$areas = [];
foreach ($filterRows as $row) {
    if (!empty($row['school_id']) && !empty($row['school_name'])) {
        $schools[(int)$row['school_id']] = $row['school_name'];
    }
    if (!empty($row['admission_area'])) {
        $areas[(string)$row['admission_area']] = true;
    }
}

// Sắp xếp danh sách trường và khu vực theo thứ tự chữ cái
asort($schools);
ksort($areas);

require_once __DIR__ . '/../includes/workflow_layout.php';
workflow_layout_start('Xem chỉ tiêu tuyển sinh', 'quotas');
?>
<h1>Xem chỉ tiêu tuyển sinh</h1>

<?php if ($error !== null): ?>
    <p class="error"><?= e($error) ?></p>
<?php else: ?>
    <!-- Form tìm kiếm / lọc -->
    <form method="get" style="margin-bottom: 20px;">
        <label>Trường
            <select name="school_id">
                <option value="">Tất cả</option>
                <?php foreach ($schools as $id => $name): ?>
                    <option value="<?= e((string)$id) ?>" <?= ($schoolId === $id ? 'selected' : '') ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Khu vực
            <select name="area">
                <option value="">Tất cả</option>
                <?php foreach (array_keys($areas) as $value): ?>
                    <option value="<?= e($value) ?>" <?= ($area === $value ? 'selected' : '') ?>><?= e($value) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Lọc</button>
    </form>

    <!-- Bảng thông tin các trường THPT tại TP. Hồ Chí Minh -->
    <div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <h3 style="margin-top: 0; color: #333;">Chỉ tiêu tuyển sinh các trường THPT tại TP. Hồ Chí Minh</h3>
        <table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse; background: #fff;">
            <thead>
                <tr style="background-color: #e9ecef;">
                    <th>STT</th>
                    <th>Tên trường THPT</th>
                    <th>Khu vực / Quận</th>
                    <th>Số lượng tuyển sinh dự kiến (Chỉ tiêu)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>THPT chuyên Lê Hồng Phong</td>
                    <td>Quận 5</td>
                    <td>Khoảng 900 - 1.000 học sinh</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>THPT chuyên Trần Đại Nghĩa</td>
                    <td>Quận 1</td>
                    <td>Khoảng 500 - 600 học sinh</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>THPT Nguyễn Thị Minh Khai</td>
                    <td>Quận 3</td>
                    <td>Khoảng 750 - 850 học sinh</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>THPT Bùi Thị Xuân</td>
                    <td>Quận 1</td>
                    <td>Khoảng 700 - 800 học sinh</td>
                </tr>
                <tr>
                    <td>5</td>
                    <td>THPT Gia Định</td>
                    <td>Quận Bình Thạnh</td>
                    <td>Khoảng 800 - 900 học sinh</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Bảng hiển thị danh sách chi tiết từ hệ thống -->
    <h3>Danh sách chi tiết từ hệ thống</h3>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>Mã trường</th>
                <th>Trường</th>
                <th>Khu vực</th>
                <th>Chỉ tiêu</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Không tìm thấy dữ liệu chỉ tiêu tuyển sinh phù hợp.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['school_code'] ?? '') ?></td>
                        <td><?= e($r['school_name'] ?? '') ?></td>
                        <td><?= e($r['admission_area'] ?? '') ?></td>
                        <td><?= e((string)($r['quota'] ?? '')) ?></td>
                        <td><?= e($r['status'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php workflow_layout_end(); ?>