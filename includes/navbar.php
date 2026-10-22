<?php
/**
 * Navigation Bar Component - Public & Candidate Navigation
 */
$activePage = $activePage ?? 'home';
$isHome = ($activePage === 'home');
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal_helper.php';
$user = auth_user();

// Safe role badge label
$roleLabel = '';
if ($user) {
    if (auth_has_role('so_gdtd')) {
        $roleLabel = 'Sở GD&ĐT';
    } elseif (auth_has_role('admin')) {
        $roleLabel = 'Quản trị viên';
    } elseif (auth_has_role('candidate')) {
        $roleLabel = 'Thí sinh';
    } elseif (auth_is_teacher()) {
        $roleLabel = 'Giáo viên';
    } else {
        $roleLabel = (string)($user['role'] ?? 'Người dùng');
    }
}

// Current URI for active state resolution fallback
$requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');

// Active state detection
$isHomeActive = $isHome || ($activePage === 'home');

// Candidate active states
$isAspirationsActive = ($activePage === 'aspirations' || strpos($requestUri, '/aspirations') !== false);
$isAdmissionResultsActive = ($activePage === 'admission-results' || strpos($requestUri, '/admission/results') !== false);
$isEnrollmentActive = ($activePage === 'enrollment-confirm' || strpos($requestUri, '/enrollment/confirm') !== false);
$isAppealsTrackActive = ($activePage === 'appeals-track' || strpos($requestUri, '/appeals/track') !== false);

// Candidate permissions check
$isCandidate = auth_has_role('candidate');
$showCandidateLinks = $user && ($isCandidate || auth_has_permission('aspiration.manage') || auth_has_permission('admission.view'));

// Nav link class helpers
$navLinkClass = static function(bool $active): string {
    return $active
        ? 'tw-text-primary tw-font-bold tw-transition-colors tw-duration-200 tw-no-underline'
        : 'tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline';
};
?>
<nav class="public-navbar tw-fixed tw-top-0 tw-w-full tw-z-[1000] tw-bg-white/90 tw-backdrop-blur-xl tw-shadow-sm tw-border-b tw-border-slate-200/80 tw-h-20">
  <div class="tw-flex tw-justify-between tw-items-center tw-h-full tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-max-w-7xl tw-mx-auto tw-font-headline tw-antialiased">
    <a href="<?= url('/') ?>" class="public-nav-brand tw-flex tw-items-center tw-gap-2.5 sm:tw-gap-3 tw-no-underline">
      <img src="<?= asset('img/graduate.png') ?>" alt="Logo" class="tw-w-10 tw-h-10 sm:tw-w-12 sm:tw-h-12 tw-object-contain">
      <div class="tw-flex tw-flex-col">
        <span class="tw-text-[9px] sm:tw-text-[10px] tw-font-bold tw-text-slate-500 tw-uppercase tw-tracking-widest tw-leading-tight">SỞ GIÁO DỤC VÀ ĐÀO TẠO</span>
        <span class="tw-text-base sm:tw-text-lg md:tw-text-xl tw-font-extrabold tw-tracking-tight tw-text-primary tw-leading-tight">TUYỂN SINH LỚP 10</span>
      </div>
    </a>

    <!-- Desktop Navigation Links -->
    <div class="public-nav-links tw-hidden md:tw-flex tw-items-center tw-gap-6 lg:tw-gap-8 tw-font-semibold tw-text-sm">
      <?php if ($showCandidateLinks && !$isHome): ?>
        <a class="<?= $navLinkClass($isHomeActive) ?>" href="<?= url('/') ?>">Trang chủ</a>
        <a class="<?= $navLinkClass($isAspirationsActive) ?>" href="<?= e(url('/aspirations/index.php')) ?>">Nguyện vọng</a>
        <a class="<?= $navLinkClass($isAdmissionResultsActive) ?>" href="<?= e(url('/admission/results.php')) ?>">Kết quả xét tuyển</a>
        <a class="<?= $navLinkClass($isEnrollmentActive) ?>" href="<?= e(url('/enrollment/confirm.php')) ?>">Nhập học</a>
        <a class="<?= $navLinkClass($isAppealsTrackActive) ?>" href="<?= e(url('/appeals/track.php')) ?>">Phúc khảo</a>
      <?php else: ?>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="<?= url('/#calendar') ?>">Lịch tuyển sinh</a>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="<?= url('/#guides') ?>">Hướng dẫn</a>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="<?= url('/#targets') ?>">Chỉ tiêu</a>
        <a class="tw-text-slate-600 hover:tw-text-primary tw-transition-colors tw-duration-200 tw-no-underline" href="<?= url('/#schools') ?>">Trường học</a>
      <?php endif; ?>
    </div>

    <!-- User Actions / Portal links -->
    <div class="public-nav-actions tw-flex tw-items-center tw-gap-2 sm:tw-gap-3">
      <?php if ($user): ?>
        <?php if ($roleLabel !== ''): ?>
          <span class="tw-hidden xl:tw-inline-flex tw-items-center tw-px-2.5 tw-py-0.5 tw-rounded-full tw-text-xs tw-font-bold tw-bg-blue-50 tw-text-primary tw-border tw-border-blue-200">
            <?= e($roleLabel) ?>
          </span>
        <?php endif; ?>
        <?php if (auth_is_admin()): ?>
          <a class="btn btn-primary btn-sm px-2.5 sm:px-3 rounded-pill fw-bold" href="<?= e(url('/admin/index.php')) ?>">
            <i class="fas fa-tachometer-alt me-1"></i> Quản trị
          </a>
        <?php elseif (auth_is_teacher() || auth_has_role('teacher')): ?>
          <a class="btn btn-success btn-sm px-2.5 sm:px-3 rounded-pill fw-bold" href="<?= e(url('/teacher/index.php')) ?>">
            <i class="fas fa-chalkboard-teacher me-1"></i> Cổng giáo viên
          </a>
        <?php elseif ($isCandidate): ?>
          <a class="btn btn-primary btn-sm px-2.5 sm:px-3 rounded-pill fw-bold" href="<?= e(url('/#candidate-portal-dashboard')) ?>">
            <i class="fas fa-user-graduate me-1"></i> Cổng thí sinh
          </a>
        <?php endif; ?>
        <a class="tw-hidden sm:tw-inline-flex btn btn-outline-primary btn-sm px-3 rounded-pill" href="<?= e(url('/auth/change-password.php')) ?>">Đổi mật khẩu</a>
        <a class="btn btn-outline-danger btn-sm px-2.5 sm:px-3 rounded-pill" href="<?= e(url('/auth/logout.php')) ?>">Đăng xuất</a>
      <?php else: ?>
        <a class="btn btn-primary btn-sm px-3 rounded-pill" href="<?= e(url('/auth/login.php')) ?>">Đăng nhập</a>
      <?php endif; ?>

      <?php if ($activePage !== 'ket-qua'): ?>
        <a href="<?= url('/ket-qua.php') ?>" class="tw-hidden sm:tw-inline-flex tw-items-center tw-justify-center tw-text-center tw-bg-amber-600 hover:tw-bg-amber-700 tw-text-white tw-px-3.5 tw-py-1.5 tw-rounded-full tw-font-semibold tw-text-sm tw-transition-all tw-no-underline tw-shadow-sm">
          Tra cứu
        </a>
      <?php endif; ?>

      <!-- Mobile Hamburger Toggle Button -->
      <button id="mobileNavToggle" type="button" class="md:tw-hidden tw-inline-flex tw-items-center tw-justify-center tw-p-1.5 tw-rounded-lg tw-text-slate-700 hover:tw-text-primary hover:tw-bg-slate-100 tw-transition-colors tw-border-0 tw-bg-transparent tw-outline-none" aria-label="Mở menu" aria-controls="mobileNavMenu" aria-expanded="false">
        <span class="material-symbols-outlined tw-text-2xl" id="mobileNavIcon">menu</span>
      </button>
    </div>
  </div>

  <!-- Mobile Collapsible Menu -->
  <div id="mobileNavMenu" class="tw-hidden md:tw-hidden tw-w-full tw-bg-white/98 tw-backdrop-blur-xl tw-border-b tw-border-slate-200 tw-shadow-xl tw-px-6 tw-py-4 tw-space-y-3 tw-font-semibold tw-text-sm">
    <?php if ($showCandidateLinks && !$isHome): ?>
      <a class="tw-block tw-py-2 <?= $navLinkClass($isHomeActive) ?>" href="<?= url('/') ?>">Trang chủ</a>
      <a class="tw-block tw-py-2 <?= $navLinkClass($isAspirationsActive) ?>" href="<?= e(url('/aspirations/index.php')) ?>">Đăng ký nguyện vọng</a>
      <a class="tw-block tw-py-2 <?= $navLinkClass($isAdmissionResultsActive) ?>" href="<?= e(url('/admission/results.php')) ?>">Kết quả xét tuyển</a>
      <a class="tw-block tw-py-2 <?= $navLinkClass($isEnrollmentActive) ?>" href="<?= e(url('/enrollment/confirm.php')) ?>">Xác nhận nhập học</a>
      <a class="tw-block tw-py-2 <?= $navLinkClass($isAppealsTrackActive) ?>" href="<?= e(url('/appeals/track.php')) ?>">Theo dõi phúc khảo</a>
    <?php else: ?>
      <a class="tw-block tw-py-2 tw-text-slate-700 hover:tw-text-primary tw-no-underline" href="<?= url('/#calendar') ?>">Lịch tuyển sinh</a>
      <a class="tw-block tw-py-2 tw-text-slate-700 hover:tw-text-primary tw-no-underline" href="<?= url('/#guides') ?>">Hướng dẫn thao tác</a>
      <a class="tw-block tw-py-2 tw-text-slate-700 hover:tw-text-primary tw-no-underline" href="<?= url('/#targets') ?>">Chỉ tiêu tuyển sinh</a>
      <a class="tw-block tw-py-2 tw-text-slate-700 hover:tw-text-primary tw-no-underline" href="<?= url('/#schools') ?>">Mạng lưới trường học</a>
    <?php endif; ?>
    <a class="tw-block tw-py-2 tw-text-amber-700 hover:tw-text-amber-800 tw-font-bold tw-no-underline" href="<?= url('/ket-qua.php') ?>">
      <span class="material-symbols-outlined tw-text-base tw-align-middle me-1">search</span> Tra cứu kết quả
    </a>

    <?php if ($user): ?>
      <div class="tw-pt-2 tw-border-t tw-border-slate-100 tw-space-y-2">
        <?php if (auth_is_admin()): ?>
          <a class="tw-block tw-py-1.5 tw-text-primary tw-font-bold tw-no-underline" href="<?= url('/admin/index.php') ?>">
            <i class="fas fa-tachometer-alt me-1"></i> Vào trang Quản trị
          </a>
        <?php elseif (auth_is_teacher() || auth_has_role('teacher')): ?>
          <a class="tw-block tw-py-1.5 tw-text-success tw-font-bold tw-no-underline" href="<?= url('/teacher/index.php') ?>">
            <i class="fas fa-chalkboard-teacher me-1"></i> Vào Cổng giáo viên
          </a>
        <?php elseif ($isCandidate): ?>
          <a class="tw-block tw-py-1.5 tw-text-primary tw-font-bold tw-no-underline" href="<?= url('/#candidate-portal-dashboard') ?>">
            <i class="fas fa-user-graduate me-1"></i> Vào Cổng thí sinh
          </a>
        <?php endif; ?>
        <a class="tw-block tw-py-1.5 tw-text-slate-600 hover:tw-text-primary tw-no-underline" href="<?= url('/auth/change-password.php') ?>">
          Đổi mật khẩu
        </a>
        <a class="tw-block tw-py-1.5 tw-text-danger tw-no-underline" href="<?= url('/auth/logout.php') ?>">
          Đăng xuất
        </a>
      </div>
    <?php endif; ?>
  </div>
</nav>

<script>
  (function() {
    const toggle = document.getElementById('mobileNavToggle');
    const menu = document.getElementById('mobileNavMenu');
    const icon = document.getElementById('mobileNavIcon');
    if (!toggle || !menu) return;
    const closeMenu = function() {
      menu.classList.add('tw-hidden');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Mở menu');
      if (icon) icon.textContent = 'menu';
    };
    toggle.addEventListener('click', function(e) {
      e.stopPropagation();
      const isHidden = menu.classList.contains('tw-hidden');
      if (isHidden) {
        menu.classList.remove('tw-hidden');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Đóng menu');
        if (icon) icon.textContent = 'close';
      } else {
        closeMenu();
      }
    });
    // Auto-close on link click
    menu.querySelectorAll('a').forEach(function(link) {
      link.addEventListener('click', function() {
        closeMenu();
      });
    });
    // Close on outside click
    document.addEventListener('click', function(e) {
      if (!menu.contains(e.target) && !toggle.contains(e.target) && !menu.classList.contains('tw-hidden')) {
        closeMenu();
      }
    });
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && !menu.classList.contains('tw-hidden')) {
        closeMenu();
        toggle.focus();
      }
    });
    window.matchMedia('(min-width: 1200px)').addEventListener('change', closeMenu);
  })();
</script>
