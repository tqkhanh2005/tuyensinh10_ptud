<?php
/**
 * Home View: Admissions Timeline Section
 */
$timeline = require __DIR__ . '/../../data/timeline.php';
?>
<!-- Admissions Timeline -->
<section id="calendar" class="tw-py-24 tw-px-8 tw-max-w-5xl tw-mx-auto tw-overflow-hidden tw-bg-surface">
  <div class="tw-text-center tw-mb-16">
    <h2 class="tw-text-4xl tw-font-headline tw-font-extrabold tw-text-on-surface tw-mb-4">
      Tuyển Sinh 2026 - 2027
    </h2>       
    <div class="tw-w-24 tw-h-1.5 tw-bg-primary tw-mx-auto tw-rounded-full"></div>
  </div>
  <div class="tw-relative timeline-line">
    <?php foreach ($timeline as $event): ?>
      <?php if (!$event['is_reverse']): ?>
        <div class="tw-mb-12 tw-flex tw-flex-col md:tw-flex-row tw-items-center tw-justify-between tw-w-full">
          <div class="tw-order-1 tw-w-full md:tw-w-5/12 tw-hidden md:tw-block tw-text-right tw-pr-8">
            <p class="tw-text-primary tw-font-bold tw-text-lg"><?= e($event['date']) ?></p>
          </div>
          <div class="tw-z-20 tw-flex tw-items-center tw-order-1 tw-bg-primary tw-shadow-xl tw-w-12 tw-h-12 tw-rounded-full tw-border-4 tw-border-white">  
            <span class="tw-mx-auto tw-font-semibold tw-text-lg tw-text-white material-symbols-outlined">
              <?= e($event['icon']) ?>
            </span>
          </div>
          <div class="tw-order-1 tw-bg-surface-container-lowest tw-rounded-2xl tw-shadow-md tw-border tw-border-surface-container-high tw-w-full md:tw-w-5/12 tw-px-6 tw-py-6 tw-mt-4 md:tw-mt-0 tw-group hover:tw-shadow-xl hover:tw--translate-y-1 tw-transition-all tw-relative tw-z-30 reveal">
            <div class="md:tw-hidden tw-mb-2">
              <p class="tw-text-primary tw-font-bold tw-text-sm"><?= e($event['date']) ?></p>
            </div>
            <span class="tw-text-xs tw-font-bold tw-text-primary tw-uppercase tw-tracking-wider tw-mb-1 tw-block"><?= e($event['event_badge']) ?></span>
            <h3 class="tw-text-lg md:tw-text-xl tw-font-bold tw-text-on-surface"><?= e($event['title']) ?></h3>
            <p class="tw-text-on-surface-variant tw-text-sm tw-mt-2 tw-leading-relaxed">
              <?= $event['desc'] ?>
            </p>
          </div>
        </div>
      <?php else: ?>
        <div class="tw-mb-12 tw-flex tw-flex-col md:tw-flex-row md:tw-flex-row-reverse tw-items-center tw-justify-between tw-w-full">
          <div class="tw-order-1 tw-w-full md:tw-w-5/12 tw-hidden md:tw-block tw-pl-8">
            <p class="tw-text-primary tw-font-bold tw-text-lg"><?= e($event['date']) ?></p>
          </div>
          <div class="tw-z-20 tw-flex tw-items-center tw-order-1 tw-bg-primary tw-shadow-xl tw-w-12 tw-h-12 tw-rounded-full tw-border-4 tw-border-white">  
            <span class="tw-mx-auto tw-font-semibold tw-text-lg tw-text-white material-symbols-outlined">
              <?= e($event['icon']) ?>
            </span>
          </div>
          <div class="tw-order-1 tw-bg-surface-container-lowest tw-rounded-2xl tw-shadow-md tw-border tw-border-surface-container-high tw-w-full md:tw-w-5/12 tw-px-6 tw-py-6 tw-mt-4 md:tw-mt-0 tw-group hover:tw-shadow-xl hover:tw--translate-y-1 tw-transition-all tw-relative tw-z-30 reveal">
            <div class="md:tw-hidden tw-mb-2">
              <p class="tw-text-primary tw-font-bold tw-text-sm"><?= e($event['date']) ?></p>
            </div>
            <span class="tw-text-xs tw-font-bold tw-text-primary tw-uppercase tw-tracking-wider tw-mb-1 tw-block"><?= e($event['event_badge']) ?></span>
            <h3 class="tw-text-lg md:tw-text-xl tw-font-bold tw-text-on-surface"><?= e($event['title']) ?></h3>
            <p class="tw-text-on-surface-variant tw-text-sm tw-mt-2 tw-leading-relaxed">
              <?= $event['desc'] ?>
            </p>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</section>
