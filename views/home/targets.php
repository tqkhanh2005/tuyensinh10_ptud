<?php
/**
 * Home View: Admissions Quotas Section
 */
$quotas = require __DIR__ . '/../../data/quotas.php';
?>
<!-- Admissions Quotas -->
<section id="targets" class="tw-bg-surface-container-low tw-py-24">
  <div class="tw-max-w-7xl tw-mx-auto tw-px-8">
    <div class="tw-text-center tw-mb-12">
      <h2 class="tw-text-4xl tw-font-headline tw-font-extrabold tw-text-on-surface tw-mb-2">Chỉ tiêu tuyển sinh</h2>
      <p class="tw-text-on-surface-variant">Thông tin chi tiết về số lượng tuyển sinh năm học 2026.</p>     
    </div>

    <!-- Search Bar for Quotas -->
    <div class="tw-max-w-2xl tw-mx-auto tw-mb-12">
      <div class="tw-relative tw-group">
        <span class="material-symbols-outlined tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-outline">search</span>     
        <input id="quotaSearch" class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3.5 tw-rounded-full tw-border tw-border-outline-variant tw-bg-white focus:tw-ring-2 focus:tw-ring-primary/20 focus:tw-border-primary tw-transition-all tw-shadow-sm tw-outline-none" placeholder="Tìm kiếm theo tên trường học..." type="text">
      </div>
    </div>
    
    <div id="quotaContainer" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6 tw-mb-12">
      <?php foreach ($quotas as $idx => $quota): ?>
        <div class="quota-item tw-bg-surface-container-lowest tw-p-6 tw-rounded-2xl tw-shadow-md tw-border tw-border-outline-variant/20 hover:tw-border-primary/30 hover:tw-shadow-xl hover:tw--translate-y-1 tw-transition-all reveal <?= $idx >= 6 ? 'hidden-item' : '' ?>" data-name="<?= e($quota['data_name']) ?>">
          <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
            <h4 class="tw-text-lg tw-font-headline tw-font-bold tw-text-on-surface tw-leading-tight tw-pr-2"><?= e($quota['name']) ?></h4>
            <?php if (!empty($quota['code'])): ?>
              <span class="<?= e($quota['code_class'] ?: 'tw-bg-surface-container-high tw-text-on-surface-variant') ?> tw-text-[10px] tw-px-2 tw-py-1 tw-rounded tw-font-bold tw-uppercase tw-shrink-0"><?= e($quota['code']) ?></span>
            <?php endif; ?>
          </div>
          <div class="tw-space-y-3">
            <?php foreach ($quota['stats'] as $st): ?>
              <div class="tw-flex tw-justify-between tw-text-xs tw-font-semibold">
                <span class="tw-text-on-surface-variant tw-uppercase tw-tracking-tighter"><?= e($st['label']) ?></span>
                <span class="tw-text-on-surface"><?= e($st['value']) ?></span>
              </div>
            <?php endforeach; ?>
            
            <div class="tw-w-full tw-h-2 tw-bg-surface-container tw-rounded-full tw-overflow-hidden">
              <div class="tw-h-full tw-bg-primary" style="width: <?= $quota['percentage'] ?>%"></div>
            </div>
            
            <div class="tw-flex tw-items-center tw-justify-between tw-pt-1">
              <div class="tw-flex tw-flex-col">
                <?php if ($quota['status']): ?>
                  <span class="tw-text-[11px] tw-font-bold tw-text-error tw-flex tw-items-center tw-gap-1">
                    <span class="tw-w-1.5 tw-h-1.5 tw-rounded-full tw-bg-error tw-animate-pulse"></span>
                    <?= e($quota['status']) ?>
                  </span>
                <?php endif; ?>
                <?php if ($quota['remaining']): ?>
                  <span class="tw-text-[10px] tw-text-on-surface-variant tw-font-medium tw-mt-0.5">
                    <?= e($quota['remaining']) ?>
                  </span>
                <?php endif; ?>
              </div>
              <a href="<?= e($quota['detail_url'] ?: '#') ?>" target="_blank" class="tw-text-primary tw-text-[11px] tw-font-bold hover:tw-underline tw-flex tw-items-center tw-gap-0.5 tw-no-underline">
                Chi tiết <span class="material-symbols-outlined tw-text-sm">chevron_right</span>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Show more button -->
    <div class="tw-flex tw-justify-center">
      <button id="btnMoreQuotas" class="tw-bg-white hover:tw-bg-slate-50 tw-text-primary tw-border tw-border-primary/20 hover:tw-border-primary tw-px-8 tw-py-3.5 tw-rounded-xl tw-font-headline tw-font-bold tw-text-sm tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-cursor-pointer">
        Xem thêm chỉ tiêu
      </button>
    </div>
  </div>
</section>
