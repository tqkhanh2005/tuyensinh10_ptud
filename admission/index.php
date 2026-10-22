<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_permission('admission.manage');

$pdo = db();
$periods = admission_period_list($pdo);

// Thống kê nhanh
$totalPeriods = count($periods);
$publishedCount = count(array_filter($periods, fn($p) => ($p['status'] ?? '') === 'published'));
$draftCount = count(array_filter($periods, fn($p) => ($p['status'] ?? '') === 'draft'));
$closedCount = count(array_filter($periods, fn($p) => ($p['status'] ?? '') === 'closed'));

$statusBadges = [
    'published' => [
        'label' => 'Đang công bố',
        'badge' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
        'icon' => 'fas fa-check-circle',
    ],
    'draft' => [
        'label' => 'Bản nháp',
        'badge' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
        'icon' => 'fas fa-edit',
    ],
    'closed' => [
        'label' => 'Đã đóng',
        'badge' => 'bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25',
        'icon' => 'fas fa-lock',
    ],
];

admin_layout_start(
    'Kỳ tuyển sinh & Kế hoạch',
    'admission',
    ['Xét tuyển' => '']
);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Kỳ tuyển sinh & Kế hoạch</h1>
    <p class="text-muted mb-0">Quản lý các kỳ tuyển sinh, phân bổ chỉ tiêu và tiến trình xét tuyển vào lớp 10.</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-primary fw-semibold" href="<?= e(url('/admission/calculate.php')) ?>">
      <i class="fas fa-play me-1"></i> Chạy xét tuyển
    </a>
    <a class="btn btn-primary fw-semibold" href="<?= e(url('/admission/create.php')) ?>">
      <i class="fas fa-plus me-1"></i> Tạo kỳ/kế hoạch mới
    </a>
  </div>
</div>

<!-- Thống kê nhanh (KPI cards) -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="fas fa-calendar-alt fs-5"></i>
        </div>
        <div>
          <div class="text-muted small fw-semibold">Tổng số kỳ</div>
          <div class="fs-4 fw-bold text-dark"><?= $totalPeriods ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="fas fa-bullhorn fs-5"></i>
        </div>
        <div>
          <div class="text-muted small fw-semibold">Đang công bố</div>
          <div class="fs-4 fw-bold text-success"><?= $publishedCount ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="fas fa-edit fs-5"></i>
        </div>
        <div>
          <div class="text-muted small fw-semibold">Bản nháp</div>
          <div class="fs-4 fw-bold text-secondary"><?= $draftCount ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 bg-dark bg-opacity-10 text-dark p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="fas fa-lock fs-5"></i>
        </div>
        <div>
          <div class="text-muted small fw-semibold">Đã đóng</div>
          <div class="fs-4 fw-bold text-dark"><?= $closedCount ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Danh sách kỳ tuyển sinh -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
  <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h6 class="mb-0 fw-bold text-dark">Danh sách kỳ tuyển sinh</h6>
    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1.5 fw-semibold">
      <?= $totalPeriods ?> kỳ
    </span>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Tên kỳ tuyển sinh</th>
          <th style="min-width: 110px;">Năm học</th>
          <th>Phương thức</th>
          <th style="min-width: 220px;">Thời gian đăng ký</th>
          <th style="min-width: 130px;">Trạng thái</th>
          <th class="text-end" style="min-width: 260px;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($periods as $p): ?>
          <?php
            $stKey = (string)($p['status'] ?? 'draft');
            $stMeta = $statusBadges[$stKey] ?? [
                'label' => $stKey,
                'badge' => 'bg-light text-dark',
                'icon' => 'fas fa-question-circle',
            ];
          ?>
          <tr>
            <td>
              <div class="fw-bold text-dark"><?= e($p['name']) ?></div>
              <?php $rowConfig=period_configuration($pdo,(int)$p['id']); if($rowConfig): ?><span class="badge bg-light text-dark border my-1"><?=$rowConfig['settings']['mode']==='simulation'?'MÔ PHỎNG':'CHÍNH THỨC'?></span><?php endif;?>
              <?php if (!empty($p['plan_content'])): ?>
                <div class="text-muted small text-truncate" style="max-width: 320px;" title="<?= e($p['plan_content']) ?>">
                  <?= e(mb_strimwidth($p['plan_content'], 0, 70, '...')) ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1 fw-semibold">
                <?= e($p['academic_year']) ?>
              </span>
            </td>
            <td class="text-muted">
              <?= e($p['admission_method'] ?: 'Thi tuyển') ?>
            </td>
            <td class="text-muted small">
              <?php if (!empty($p['registration_start']) || !empty($p['registration_end'])): ?>
                <div class="d-flex align-items-center gap-1.5">
                  <i class="far fa-clock text-secondary"></i>
                  <span><?= e($p['registration_start'] ? date('d/m/Y H:i', strtotime((string)$p['registration_start'])) : '—') ?></span>
                  <span>&rarr;</span>
                  <span><?= e($p['registration_end'] ? date('d/m/Y H:i', strtotime((string)$p['registration_end'])) : '—') ?></span>
                </div>
              <?php else: ?>
                <span class="text-muted fst-italic">Chưa thiết lập</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $stMeta['badge'] ?> rounded-pill px-2.5 py-1 fw-semibold">
                <i class="<?= $stMeta['icon'] ?> me-1 small"></i>
                <?= e($stMeta['label']) ?>
              </span>
            </td>
            <td class="text-end">
              <div class="d-inline-flex gap-1.5 flex-wrap justify-content-end">
                <a class="btn btn-sm btn-outline-secondary" href="<?=e(url('/admission/manage.php?id='.(int)$p['id']))?>">Cấu hình / trạng thái</a>
                <a class="btn btn-sm btn-outline-primary px-2.5 fw-semibold"
                   href="<?= e(url('/quotas/index.php?period_id=' . (int)$p['id'])) ?>"
                   title="Quản lý chỉ tiêu cho kỳ này">
                  <i class="fas fa-chart-pie me-1"></i> Quản lý chỉ tiêu
                </a>
                <a class="btn btn-sm btn-outline-success px-2.5 fw-semibold"
                   href="<?= e(url('/admission/calculate.php?period_id=' . (int)$p['id'])) ?>"
                   title="Thực hiện xét tuyển">
                  <i class="fas fa-play me-1"></i> Chạy xét tuyển
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($periods)): ?>
          <tr>
            <td colspan="6" class="text-center py-5">
              <div class="py-4">
                <div class="text-muted mb-3">
                  <i class="far fa-calendar-times fa-3x text-secondary"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Chưa có kỳ tuyển sinh nào</h5>
                <p class="text-muted mb-3">Bắt đầu bằng việc khởi tạo một kỳ hoặc kế hoạch tuyển sinh mới.</p>
                <a href="<?= e(url('/admission/create.php')) ?>" class="btn btn-primary fw-semibold">
                  <i class="fas fa-plus me-1"></i> Tạo kỳ/kế hoạch mới
                </a>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
admin_layout_end();
