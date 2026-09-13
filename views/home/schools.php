<?php
/**
 * Home View: Schools Network Section
 */
$schools = require __DIR__ . '/../../data/schools.php';
$thptSchools = $schools['thpt'];
$thcsSchools = $schools['thcs'];
?>
<!-- School Network -->
<section id="schools" class="tw-py-24 tw-px-8 tw-max-w-7xl tw-mx-auto tw-bg-surface">
  <div class="tw-text-center tw-mb-12">
    <h2 class="tw-text-4xl tw-font-headline tw-font-extrabold tw-text-on-surface tw-mb-4">Mạng lưới trường học</h2>        
    <p class="tw-text-on-surface-variant">Khám phá các cơ sở đào tạo hiện đại của chúng tôi trên toàn quốc.</p>
  </div>

  <!-- Search and Tabs for Network -->
  <div class="tw-max-w-4xl tw-mx-auto tw-mb-16 tw-space-y-8">
    <div class="tw-relative">
      <span class="material-symbols-outlined tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-outline">search</span>     
      <input id="schoolSearch" class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3.5 tw-rounded-full tw-border tw-border-outline-variant tw-bg-surface-container-lowest focus:tw-ring-2 focus:tw-ring-primary/20 tw-transition-all tw-outline-none" placeholder="Tìm kiếm tên cơ sở hoặc địa chỉ..." type="text">
    </div>
    <div class="tw-flex tw-justify-center tw-border-b tw-border-surface-container-high">
      <div class="tw-flex tw-gap-12" id="schoolTabs">
        <button class="tab-btn active tw-pb-4 tw-text-primary tw-font-bold tw-border-b-4 tw-border-primary tw-px-2 tw-transition-all tw-bg-transparent tw-border-0 tw-outline-none" onmousedown="this.blur()" data-target="thpt-content">Khối THPT</button>  
        <button class="tab-btn tw-pb-4 tw-text-on-surface-variant tw-font-medium hover:tw-text-primary tw-px-2 tw-transition-all tw-bg-transparent tw-border-0 tw-outline-none" onmousedown="this.blur()" data-target="thcs-content">Khối THCS</button>
      </div>
    </div>
  </div>

  <div class="tab-content" id="schoolTabsContent">
    <!-- Khối THPT -->
    <div class="tab-pane fade show active" id="thpt-content" role="tabpanel">
      <div id="thptContainer" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-8">
        <?php foreach ($thptSchools as $idx => $school): ?>
          <div class="school-item tw-bg-surface-container-lowest tw-rounded-2xl tw-overflow-hidden tw-shadow-md hover:tw-shadow-2xl hover:tw--translate-y-2 tw-transition-all tw-border tw-border-surface-container-high reveal <?= $idx >= 3 ? 'hidden-item' : '' ?>" data-name="<?= e($school['data_name']) ?>" data-address="<?= e($school['data_address']) ?>">
            <div class="tw-h-48 tw-relative tw-overflow-hidden tw-bg-slate-200">
              <img alt="<?= e($school['name']) ?>" class="tw-w-full tw-h-full tw-object-cover" src="<?= e($school['image']) ?>">
              <div class="tw-absolute tw-top-4 tw-right-4 tw-bg-white/90 tw-px-3 tw-py-1 tw-rounded-full tw-text-[10px] tw-font-bold tw-text-primary">
                <?= e($school['badge'] ?: 'Cơ sở THPT') ?>
              </div>
            </div>
            <div class="tw-p-6">
              <h3 class="tw-text-xl tw-font-bold tw-mb-4"><?= e($school['name']) ?></h3>
              <div class="tw-space-y-3 tw-mb-6 tw-text-sm tw-text-on-surface-variant">
                <?php if (!empty($school['address'])): ?>
                  <div class="tw-flex tw-gap-2">
                    <span class="material-symbols-outlined tw-text-primary tw-text-lg">location_on</span>
                    <span><?= e($school['address']) ?></span>
                  </div>
                <?php endif; ?>
                <?php if (!empty($school['phone'])): ?>
                  <div class="tw-flex tw-gap-2">
                    <span class="material-symbols-outlined tw-text-primary tw-text-lg">call</span>
                    <span><?= e($school['phone']) ?></span>
                  </div>
                <?php endif; ?>
                <?php if (!empty($school['website'])): ?>
                  <div class="tw-flex tw-gap-2">
                    <span class="material-symbols-outlined tw-text-primary tw-text-lg">language</span>
                    <a href="<?= e($school['website']['url']) ?>" target="_blank" class="tw-text-primary hover:tw-underline tw-cursor-pointer"><?= e($school['website']['text']) ?></a>
                  </div>
                <?php endif; ?>
                <?php if (!empty($school['zalo'])): ?>
                  <div class="tw-flex tw-gap-2">
                    <span class="material-symbols-outlined tw-text-primary tw-text-lg">chat</span>
                    <a href="<?= e($school['zalo']) ?>" target="_blank" class="tw-text-primary hover:tw-underline tw-cursor-pointer">Zalo hỗ trợ</a>
                  </div>
                <?php endif; ?>
              </div>
              <?php if (!empty($school['video'])): ?>
                <button type="button" class="js-change-video tw-w-full tw-bg-primary/10 tw-text-primary tw-py-3 tw-rounded-lg tw-font-bold hover:tw-bg-primary hover:tw-text-white tw-transition-all" data-youtube-link="<?= e($school['video']['url']) ?>" data-school-name="<?= e($school['video']['school_name']) ?>">Giới thiệu</button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="tw-flex tw-justify-center tw-mt-12">
        <button id="btnMoreThpt" class="tw-bg-white hover:tw-bg-slate-50 tw-text-primary tw-border tw-border-primary/20 hover:tw-border-primary tw-px-8 tw-py-3.5 tw-rounded-xl tw-font-headline tw-font-bold tw-text-sm tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-cursor-pointer">
          Xem thêm cơ sở
        </button>
      </div>
    </div>

    <!-- Khối THCS -->
    <div class="tab-pane fade" id="thcs-content" role="tabpanel">
      <div id="thcsContainer" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-8">
        <?php foreach ($thcsSchools as $idx => $school): ?>
          <div class="school-item tw-bg-surface-container-lowest tw-rounded-2xl tw-overflow-hidden tw-shadow-md hover:tw-shadow-2xl hover:tw--translate-y-2 tw-transition-all tw-border tw-border-surface-container-high reveal <?= $idx >= 3 ? 'hidden-item' : '' ?>" data-name="<?= e($school['data_name']) ?>" data-address="<?= e($school['data_address']) ?>">
            <div class="tw-h-48 tw-relative tw-overflow-hidden tw-bg-slate-200">
              <img alt="<?= e($school['name']) ?>" class="tw-w-full tw-h-full tw-object-cover" src="<?= e($school['image']) ?>">
              <div class="tw-absolute tw-top-4 tw-right-4 tw-bg-white/90 tw-px-3 tw-py-1 tw-rounded-full tw-text-[10px] tw-font-bold tw-text-primary">
                <?= e($school['badge'] ?: 'Cơ sở THCS') ?>
              </div>
            </div>
            <div class="tw-p-6">
              <h3 class="tw-text-xl tw-font-bold tw-mb-4"><?= e($school['name']) ?></h3>
              <div class="tw-space-y-3 tw-mb-6 tw-text-sm tw-text-on-surface-variant">
                <?php if (!empty($school['address'])): ?>
                  <div class="tw-flex tw-gap-2">
                    <span class="material-symbols-outlined tw-text-primary tw-text-lg">location_on</span>
                    <span><?= e($school['address']) ?></span>
                  </div>
                <?php endif; ?>
                <?php if (!empty($school['phone'])): ?>
                  <div class="tw-flex tw-gap-2">
                    <span class="material-symbols-outlined tw-text-primary tw-text-lg">call</span>
                    <span><?= e($school['phone']) ?></span>
                  </div>
                <?php endif; ?>
                <?php if (!empty($school['website'])): ?>
                  <div class="tw-flex tw-gap-2">
                    <span class="material-symbols-outlined tw-text-primary tw-text-lg">language</span>
                    <a href="<?= e($school['website']['url']) ?>" target="_blank" class="tw-text-primary hover:tw-underline tw-cursor-pointer"><?= e($school['website']['text']) ?></a>
                  </div>
                <?php endif; ?>
                <?php if (!empty($school['zalo'])): ?>
                  <div class="tw-flex tw-gap-2">
                    <span class="material-symbols-outlined tw-text-primary tw-text-lg">chat</span>
                    <a href="<?= e($school['zalo']) ?>" target="_blank" class="tw-text-primary hover:tw-underline tw-cursor-pointer">Zalo hỗ trợ</a>
                  </div>
                <?php endif; ?>
              </div>
              <?php if (!empty($school['video'])): ?>
                <button type="button" class="js-change-video tw-w-full tw-bg-primary/10 tw-text-primary tw-py-3 tw-rounded-lg tw-font-bold hover:tw-bg-primary hover:tw-text-white tw-transition-all" data-youtube-link="<?= e($school['video']['url']) ?>" data-school-name="<?= e($school['video']['school_name']) ?>">Giới thiệu</button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="tw-flex tw-justify-center tw-mt-12">
        <button id="btnMoreThcs" class="tw-bg-white hover:tw-bg-slate-50 tw-text-primary tw-border tw-border-primary/20 hover:tw-border-primary tw-px-8 tw-py-3.5 tw-rounded-xl tw-font-headline tw-font-bold tw-text-sm tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-cursor-pointer">
          Xem thêm cơ sở
        </button>
      </div>
    </div>
  </div>
</section>
