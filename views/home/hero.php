<?php
/**
 * Home View: Hero Section
 */
$seasonLabel = '2026 - 2027';
try {
    $pStmt = db()->query("
        SELECT academic_year
        FROM admission_periods
        WHERE status = 'published'
          AND name NOT LIKE 'BATCH%'
          AND name NOT LIKE 'Batch%'
          AND name NOT LIKE 'G1_%'
        ORDER BY id DESC LIMIT 1
    ");
    $dbP = $pStmt ? $pStmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($dbP && !empty($dbP['academic_year'])) {
        $seasonLabel = $dbP['academic_year'];
    }
} catch (Throwable $e) {
    // fallback gracefully
}
?>
<!-- Hero Section -->
<section id="hero" class="hero-section tw-relative tw-flex tw-items-center tw-pt-20 tw-overflow-hidden">
  <div class="tw-absolute tw-inset-0 tw-z-0">
    <img alt="Hero background" class="tw-w-full tw-h-full tw-object-cover tw-brightness-90" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD6QFlgcJs7Wo9IWXdb8btg2BN_4Dd39YdyRvpPSOQuA8goD3lmgej21ELtwGMKwn14TpFl-lC7tKkRSn59_4j1HACRnx1Mk4261Terxl8N5lluzKlPRBTNDtu9IV6GwjYecEcnfUC5m5zIqwVDYrhOFQatXYO6jNkrPPM2xeAoQCIvK2PN9PGYTy6LEnZtm30t-6Llzxj4K0GLHRQL8JYn8cXzqFJUjqtenYuaiVgUuB0ixLuchbQYOQvgjb66NzMV_qkT8X0buPIb">
    <div class="tw-absolute tw-inset-0 tw-bg-black/35"></div>
  </div>
  <div class="tw-relative tw-z-10 tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-w-full tw-text-center">
    <div class="tw-max-w-4xl tw-mx-auto">
      <span class="tw-inline-block tw-px-4 tw-py-1.5 tw-rounded-full tw-bg-tertiary-container tw-text-on-tertiary-container tw-font-headline tw-font-bold tw-text-xs tw-uppercase tw-tracking-widest tw-mb-6">
        Tuyển Sinh <?= e($seasonLabel) ?>
      </span>
      <h1 class="tw-text-3xl sm:tw-text-5xl md:tw-text-6xl lg:tw-text-7xl tw-font-headline tw-font-extrabold tw-text-white tw-leading-[1.15] tw-mb-8 tw-tracking-tight tw-drop-shadow-lg">
        Thắp sáng con đường <span class="tw-text-tertiary-container">Tri thức</span> của bạn.
      </h1>
      <div class="tw-flex tw-flex-wrap tw-justify-center tw-gap-4">
        <a href="<?= url('/ket-qua.php') ?>" class="tw-bg-secondary tw-text-on-secondary tw-px-7 sm:tw-px-8 tw-py-3.5 sm:tw-py-4 tw-rounded-xl tw-font-headline tw-font-bold tw-text-base sm:tw-text-lg tw-flex tw-items-center tw-gap-2 hover:tw-scale-105 tw-transition-all tw-shadow-xl tw-no-underline">
          Tra cứu kết quả <span class="material-symbols-outlined">search</span>
        </a>
        <a href="#calendar" class="tw-bg-white/20 tw-backdrop-blur-md tw-text-white tw-border tw-border-white/40 tw-px-7 sm:tw-px-8 tw-py-3.5 sm:tw-py-4 tw-rounded-xl tw-font-headline tw-font-bold tw-text-base sm:tw-text-lg hover:tw-bg-white/30 tw-transition-all tw-no-underline">
          Xem lịch tuyển sinh
        </a>
      </div>
    </div>
  </div>
</section>
