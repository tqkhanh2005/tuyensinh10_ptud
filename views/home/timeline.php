<?php
/**
 * Home View: Admissions Timeline Section
 */
$timeline = require __DIR__ . '/../../data/timeline.php';
$seasonTitle = 'Lịch trình Tuyển Sinh 2026 - 2027';
try {
    $pStmt = db()->query("
        SELECT academic_year, name
        FROM admission_periods
        WHERE status = 'published'
          AND name NOT LIKE 'BATCH%'
          AND name NOT LIKE 'Batch%'
          AND name NOT LIKE 'G1_%'
        ORDER BY id DESC LIMIT 1
    ");
    $dbP = $pStmt ? $pStmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($dbP && !empty($dbP['name'])) {
        $seasonTitle = $dbP['name'];
    } elseif ($dbP && !empty($dbP['academic_year'])) {
        $seasonTitle = 'Lịch trình Tuyển Sinh ' . $dbP['academic_year'];
    }
} catch (Throwable $e) {
    // fallback gracefully
}
?>
<!-- Admissions Timeline -->
<section id="calendar" class="tw-py-20 tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-max-w-5xl tw-mx-auto tw-overflow-hidden tw-bg-surface">
  <div class="tw-text-center tw-mb-14">
    <span class="tw-text-xs tw-font-bold tw-text-primary tw-uppercase tw-tracking-widest tw-mb-2 tw-block">Mốc thời gian quan trọng</span>
    <h2 class="tw-text-3xl sm:tw-text-4xl tw-font-headline tw-font-extrabold tw-text-on-surface tw-mb-3">
      <?= e($seasonTitle) ?>
    </h2>
    <div class="tw-w-20 tw-h-1.5 tw-bg-primary tw-mx-auto tw-rounded-full"></div>
  </div>
  <div class="tw-relative timeline-line">
    <?php foreach ($timeline as $event): ?>
      <?php if (!$event['is_reverse']): ?>
        <div class="tw-mb-10 tw-flex tw-flex-col md:tw-flex-row tw-items-center tw-justify-between tw-w-full">
          <div class="tw-order-1 tw-w-full md:tw-w-5/12 tw-hidden md:tw-block tw-text-right tw-pr-8">
            <p class="tw-text-primary tw-font-bold tw-text-base md:tw-text-lg mb-0"><?= e($event['date']) ?></p>
          </div>
          <div class="tw-z-20 tw-flex tw-items-center tw-order-1 tw-bg-primary tw-shadow-md tw-w-11 tw-h-11 tw-rounded-full tw-border-4 tw-border-white tw-shrink-0">
            <span class="tw-mx-auto tw-font-semibold tw-text-base tw-text-white material-symbols-outlined">
              <?= e($event['icon']) ?>
            </span>
          </div>
          <div class="tw-order-1 tw-bg-white tw-rounded-2xl tw-shadow-sm tw-border tw-border-slate-200/80 tw-w-full md:tw-w-5/12 tw-px-5 tw-py-5 tw-mt-3 md:tw-mt-0 tw-group hover:tw-shadow-md hover:tw--translate-y-0.5 tw-transition-all tw-relative tw-z-30 reveal">
            <div class="md:tw-hidden tw-mb-2">
              <p class="tw-text-primary tw-font-bold tw-text-sm mb-0"><?= e($event['date']) ?></p>
            </div>
            <span class="tw-text-[11px] tw-font-bold tw-text-primary tw-uppercase tw-tracking-wider tw-mb-1 tw-block"><?= e($event['event_badge']) ?></span>
            <h3 class="tw-text-base md:tw-text-lg tw-font-bold tw-text-on-surface mb-2"><?= e($event['title']) ?></h3>
            <p class="tw-text-slate-600 tw-text-sm tw-leading-relaxed mb-0">
              <?= $event['desc'] ?>
            </p>
          </div>
        </div>
      <?php else: ?>
        <div class="tw-mb-10 tw-flex tw-flex-col md:tw-flex-row md:tw-flex-row-reverse tw-items-center tw-justify-between tw-w-full">
          <div class="tw-order-1 tw-w-full md:tw-w-5/12 tw-hidden md:tw-block tw-pl-8">
            <p class="tw-text-primary tw-font-bold tw-text-base md:tw-text-lg mb-0"><?= e($event['date']) ?></p>
          </div>
          <div class="tw-z-20 tw-flex tw-items-center tw-order-1 tw-bg-primary tw-shadow-md tw-w-11 tw-h-11 tw-rounded-full tw-border-4 tw-border-white tw-shrink-0">
            <span class="tw-mx-auto tw-font-semibold tw-text-base tw-text-white material-symbols-outlined">
              <?= e($event['icon']) ?>
            </span>
          </div>
          <div class="tw-order-1 tw-bg-white tw-rounded-2xl tw-shadow-sm tw-border tw-border-slate-200/80 tw-w-full md:tw-w-5/12 tw-px-5 tw-py-5 tw-mt-3 md:tw-mt-0 tw-group hover:tw-shadow-md hover:tw--translate-y-0.5 tw-transition-all tw-relative tw-z-30 reveal">
            <div class="md:tw-hidden tw-mb-2">
              <p class="tw-text-primary tw-font-bold tw-text-sm mb-0"><?= e($event['date']) ?></p>
            </div>
            <span class="tw-text-[11px] tw-font-bold tw-text-primary tw-uppercase tw-tracking-wider tw-mb-1 tw-block"><?= e($event['event_badge']) ?></span>
            <h3 class="tw-text-base md:tw-text-lg tw-font-bold tw-text-on-surface mb-2"><?= e($event['title']) ?></h3>
            <p class="tw-text-slate-600 tw-text-sm tw-leading-relaxed mb-0">
              <?= $event['desc'] ?>
            </p>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</section>
