<?php
/**
 * Navigation Bar Component
 */
$activePage = $activePage ?? 'home';
$isHome = ($activePage === 'home');
?>
<nav class="tw-fixed tw-top-0 tw-w-full tw-z-[1000] tw-bg-white/80 tw-backdrop-blur-xl tw-shadow-md tw-h-20">
  <div class="tw-flex tw-justify-between tw-items-center tw-h-full tw-px-8 tw-max-w-7xl tw-mx-auto tw-font-headline tw-antialiased">
    <a href="<?= url('/') ?>" class="tw-flex tw-items-center tw-gap-3 tw-no-underline">
      <img src="<?= asset('img/graduate.png') ?>" alt="Logo" class="tw-w-12 tw-h-12 tw-object-contain">
      <div class="tw-flex tw-flex-col">
        <span class="tw-text-[10px] tw-font-bold tw-text-slate-500 tw-uppercase tw-tracking-widest tw-leading-tight">SỞ GIÁO DỤC VÀ ĐÀO TẠO</span>
        <span class="tw-text-lg md:tw-text-xl tw-font-extrabold tw-tracking-tight tw-text-primary tw-leading-tight">TUYỂN SINH LỚP 10</span>
      </div>
    </a>
    
    <div class="tw-hidden md:tw-flex tw-items-center tw-space-x-8 tw-font-semibold tw-text-sm">
      <?php if ($isHome): ?>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="#calendar">Lịch</a>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="#targets">Chỉ tiêu</a>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="#schools">Trường học</a>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="#guides">Hướng dẫn</a>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="#guides">Biểu mẫu</a>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="#schools">Hỗ trợ</a>
      <?php else: ?>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="<?= url('/') ?>">Trang chủ</a>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="<?= url('/#calendar') ?>">Lịch</a>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="<?= url('/#targets') ?>">Chỉ tiêu</a>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="<?= url('/#schools') ?>">Trường học</a>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="<?= url('/#guides') ?>">Hướng dẫn</a>
      <?php endif; ?>
    </div>
    
    <div class="tw-flex tw-items-center tw-gap-4">
      <a class="btn btn-primary btn-sm px-3 rounded-pill" href="<?= url('/auth/login.php') ?>">Đăng nhập</a>
      <?php if ($activePage !== 'ket-qua'): ?>
        <a href="<?= url('/ket-qua.php') ?>" class="tw-hidden md:tw-inline-flex tw-items-center tw-justify-center tw-text-center tw-bg-amber-700 tw-text-white hover:tw-bg-amber-800 tw-px-4 tw-py-[0.3rem] tw-rounded-full tw-font-semibold tw-text-sm tw-leading-[1.6] tw-transition-all tw-no-underline">
          Tra cứu
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>
