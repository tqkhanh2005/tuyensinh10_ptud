<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal_helper.php';

$activePage = $activePage ?? '';
$user = auth_user();
$teacherInfo = auth_get_teacher_info();
$schoolLevel = auth_teacher_level();
$schoolName = $teacherInfo['school_name'] ?? $teacherInfo['workplace'] ?? 'Trường THCS / THPT';
$reqUri = (string)($_SERVER['REQUEST_URI'] ?? '');

$menuItems = [
    [
        'id' => 'teacher-dashboard',
        'title' => 'Tổng quan',
        'url' => url('/teacher/index.php'),
        'icon' => 'fas fa-home',
        'active' => ($activePage === 'teacher-dashboard' && (strpos($reqUri, '/teacher/index.php') !== false || substr($reqUri, -8) === '/teacher' || substr($reqUri, -9) === '/teacher/')),
    ],
    [
        'id' => 'candidates',
        'title' => 'Quản lý thí sinh',
        'url' => url('/teacher/students.php'),
        'icon' => 'fas fa-user-graduate',
        'active' => ($activePage === 'candidates' || strpos($reqUri, '/teacher/students.php') !== false),
    ],
    [
        'id' => 'aspirations',
        'title' => 'Nguyện vọng đăng ký',
        'url' => url('/teacher/aspirations.php'),
        'icon' => 'fas fa-list-ol',
        'active' => ($activePage === 'aspirations' || strpos($reqUri, '/teacher/aspirations.php') !== false),
    ],
    [
        'id' => 'adjustments',
        'title' => 'Yêu cầu điều chỉnh',
        'url' => url('/teacher/adjustments.php'),
        'icon' => 'fas fa-user-edit',
        'active' => ($activePage === 'adjustments' || strpos($reqUri, '/teacher/adjustments.php') !== false),
    ],
    [
        'id' => 'scores',
        'title' => 'Nhập điểm',
        'url' => url('/teacher/scores.php'),
        'icon' => 'fas fa-file-signature',
        'active' => ($activePage === 'scores' || strpos($reqUri, '/teacher/scores.php') !== false),
    ],
    [
        'id' => 'appeals',
        'title' => 'Phúc khảo',
        'url' => url('/teacher/appeals.php'),
        'icon' => 'fas fa-balance-scale',
        'active' => ($activePage === 'appeals' || strpos($reqUri, '/teacher/appeals.php') !== false),
    ],
    [
        'id' => 'admitted',
        'title' => 'Danh sách trúng tuyển',
        'url' => url('/teacher/admitted.php'),
        'icon' => 'fas fa-award',
        'active' => ($activePage === 'admitted' || strpos($reqUri, '/teacher/admitted.php') !== false),
    ],
    [
        'id' => 'quotas',
        'title' => 'Chỉ tiêu tuyển sinh',
        'url' => url('/teacher/quotas.php'),
        'icon' => 'fas fa-bullseye',
        'active' => ($activePage === 'quotas' || strpos($reqUri, '/teacher/quotas.php') !== false),
    ],
    [
        'id' => 'statistics',
        'title' => 'Thống kê - báo cáo',
        'url' => url('/teacher/statistics.php'),
        'icon' => 'fas fa-chart-pie',
        'active' => ($activePage === 'statistics' || strpos($reqUri, '/teacher/statistics.php') !== false),
    ],
    [
        'id' => 'settings',
        'title' => 'Cài đặt',
        'url' => url('/auth/change-password.php'),
        'icon' => 'fas fa-cog',
        'active' => ($activePage === 'settings' || strpos($reqUri, '/auth/change-password') !== false),
    ],
];
?>
<aside id="teacherSidebar" class="teacher-sidebar d-flex flex-column flex-shrink-0 bg-white border-end shadow-sm">
  <!-- Brand / Header -->
  <div class="sidebar-header px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
    <a href="<?= url('/teacher/index.php') ?>" class="d-flex align-items-center gap-3 text-decoration-none">
      <img src="<?= asset('img/graduate.png') ?>" alt="Logo" class="sidebar-logo">
      <div class="d-flex flex-column">
        <span class="sidebar-brand-sub fw-bold text-uppercase">HỆ THỐNG TUYỂN SINH</span>
        <span class="sidebar-brand-title fw-bold text-success">CỔNG GIÁO VIÊN</span>
      </div>
    </a>
    <button type="button" class="btn btn-sm btn-light d-lg-none" id="teacherSidebarCloseBtn" aria-label="Đóng menu">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <!-- School / Level info banner -->
  <div class="px-3 py-2 bg-light border-bottom">
    <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
      <span class="badge <?= $schoolLevel === 'THCS' ? 'bg-info bg-opacity-10 text-info' : 'bg-primary bg-opacity-10 text-primary' ?> fw-bold px-2 py-0.5 rounded-pill small">
        <i class="fas fa-graduation-cap me-1"></i>Trường <?= e($schoolLevel) ?>
      </span>
      <span class="text-muted small" style="font-size: 0.75rem;">Giáo viên</span>
    </div>
    <div class="fw-bold text-dark text-truncate small" title="<?= e($schoolName) ?>">
      <i class="fas fa-school text-muted me-1"></i><?= e($schoolName) ?>
    </div>
  </div>

  <!-- Navigation Menu -->
  <div class="sidebar-menu flex-grow-1 overflow-auto py-3 px-3">
    <div class="nav flex-column gap-1">
      <?php foreach ($menuItems as $item): ?>
        <a href="<?= e($item['url']) ?>" 
           class="nav-link sidebar-nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 fw-semibold <?= $item['active'] ? 'active-teacher' : '' ?>">
          <div class="sidebar-icon-wrap d-flex align-items-center justify-content-center">
            <i class="<?= e($item['icon']) ?>"></i>
          </div>
          <span class="sidebar-nav-text flex-grow-1"><?= e($item['title']) ?></span>
          <?php if ($item['active']): ?>
            <i class="fas fa-chevron-right small text-success opacity-75"></i>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Footer Profile & Logout -->
  <div class="sidebar-footer p-3 border-top bg-light">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
      <div class="d-flex align-items-center gap-2 overflow-hidden">
        <div class="sidebar-avatar rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold">
          <?= e(mb_strtoupper(mb_substr($user['full_name'] ?? 'G', 0, 1))) ?>
        </div>
        <div class="overflow-hidden">
          <div class="fw-bold text-dark text-truncate small" title="<?= e($user['full_name'] ?? '') ?>">
            <?= e($user['full_name'] ?? 'Giáo viên') ?>
          </div>
          <div class="text-muted text-truncate" style="font-size: 0.75rem;">
            <?= e($teacherInfo['staff_role'] ?? 'Cán bộ - Giáo viên') ?>
          </div>
        </div>
      </div>
      <a href="<?= url('/auth/logout.php') ?>" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center" title="Đăng xuất">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
    <div class="pt-2 border-top text-center">
      <a href="<?= url('/') ?>" target="_blank" class="small text-muted text-decoration-none hover-primary">
        <i class="fas fa-external-link-alt me-1"></i>Xem Cổng thí sinh
      </a>
    </div>
  </div>
</aside>
