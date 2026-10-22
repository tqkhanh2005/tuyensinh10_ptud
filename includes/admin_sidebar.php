<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal_helper.php';

$activePage = $activePage ?? '';
$user = auth_user();
$roleLabel = auth_has_role('so_gdtd') ? 'Cán bộ Sở GD&ĐT' : (auth_has_role('admin') ? 'Quản trị viên hệ thống' : 'Quản trị');
$roleCode = auth_has_role('so_gdtd') ? 'Sở GD&ĐT' : (auth_has_role('admin') ? 'Admin' : 'Quản trị');
$reqUri = (string)($_SERVER['REQUEST_URI'] ?? '');

$menuGroups = [
    [
        'title' => 'TỔNG QUAN',
        'items' => [
            [
                'id' => 'admin-dashboard',
                'title' => 'Tổng quan',
                'url' => url('/admin/index.php'),
                'active' => ($activePage === 'admin-dashboard' || (strpos($reqUri, '/admin') !== false && strpos($reqUri, '/admission') === false)),
            ],
        ],
    ],
    [
        'title' => 'DỮ LIỆU CƠ SỞ',
        'items' => [
            [
                'id' => 'candidates',
                'title' => 'Quản lý thí sinh',
                'url' => url('/candidates/index.php'),
                'permission' => 'candidate.manage',
                'active' => ($activePage === 'candidates' || strpos($reqUri, '/candidates') !== false),
            ],
            [
                'id' => 'schools',
                'title' => 'Quản lý trường học',
                'url' => url('/schools/index.php'),
                'permission' => 'school.manage',
                'active' => ($activePage === 'schools' || strpos($reqUri, '/schools') !== false),
            ],
            [
                'id' => 'adjustments',
                'title' => 'Duyệt điều chỉnh hồ sơ',
                'url' => url('/adjustments/index.php'),
                'permission' => 'adjustment.review',
                'active' => ($activePage === 'adjustments' || strpos($reqUri, '/adjustments') !== false),
            ],
            [
                'id' => 'staff',
                'title' => 'Quản lý giáo viên',
                'url' => url('/staff/index.php'),
                'permission' => 'staff.manage',
                'active' => ($activePage === 'staff' || strpos($reqUri, '/staff') !== false),
            ],
        ],
    ],
    [
        'title' => 'KỲ THI',
        'items' => [
            [
                'id' => 'councils',
                'title' => 'Quản lý kỳ thi',
                'url' => url('/councils/index.php'),
                'permission' => 'council.manage',
                'active' => ($activePage === 'councils' || strpos($reqUri, '/councils') !== false || strpos($reqUri, '/exam/rooms') !== false || strpos($reqUri, '/exam/sites') !== false || strpos($reqUri, '/exam/allocate') !== false || strpos($reqUri, '/exam/organization') !== false || strpos($reqUri, '/exam/invigilators') !== false),
            ],
            [
                'id' => 'scores',
                'title' => 'Nhập điểm',
                'url' => url('/exam/grading.php'),
                'permission' => 'score.enter',
                'active' => ($activePage === 'scores' || strpos($reqUri, '/exam/scores') !== false || strpos($reqUri, '/exam/grading') !== false || strpos($reqUri, '/exam/papers') !== false),
            ],
            [
                'id' => 'appeals',
                'title' => 'Phúc khảo',
                'url' => url('/appeals/update_result.php'),
                'permission' => 'appeal.manage',
                'active' => ($activePage === 'appeals' || strpos($reqUri, '/appeals') !== false),
            ],
        ],
    ],
    [
        'title' => 'TUYỂN SINH',
        'items' => [
            [
                'id' => 'admission',
                'title' => 'Xét tuyển',
                'url' => url('/admission/index.php'),
                'permission' => 'admission.manage',
                'active' => ($activePage === 'admission' || $activePage === 'quotas' || (strpos($reqUri, '/admission') !== false && strpos($reqUri, '/admission/results') === false) || strpos($reqUri, '/quotas') !== false),
            ],
            [
                'id' => 'admitted',
                'title' => 'Theo dõi nhập học',
                'url' => url('/admitted/index.php'),
                'permission' => 'admitted.view',
                'active' => ($activePage === 'admitted' || strpos($reqUri, '/admitted') !== false),
            ],
            [
                'id' => 'statistics',
                'title' => 'Thống kê - báo cáo',
                'url' => url('/statistics/index.php'),
                'permission' => 'statistics.view',
                'active' => ($activePage === 'statistics' || strpos($reqUri, '/statistics') !== false),
            ],
            [
                'id' => 'audit',
                'title' => 'Nhật ký kỳ tuyển sinh',
                'url' => url('/admin/audit.php'),
                'permission' => 'admission.manage',
                'active' => ($activePage === 'audit'),
            ],
        ],
    ],
    [
        'title' => 'CÀI ĐẶT',
        'items' => [
            [
                'id' => 'change-password',
                'title' => 'Đổi mật khẩu',
                'url' => url('/auth/change-password.php'),
                'active' => ($activePage === 'change-password' || strpos($reqUri, '/auth/change-password') !== false),
            ],
            [
                'id' => 'public-site',
                'title' => 'Xem trang công khai',
                'url' => url('/'),
                'target' => '_blank',
                'active' => false,
            ],
            [
                'id' => 'logout',
                'title' => 'Đăng xuất',
                'url' => url('/auth/logout.php'),
                'active' => false,
            ],
        ],
    ],
];
?>
<aside id="adminSidebar" class="admin-sidebar d-flex flex-column flex-shrink-0 bg-white border-end shadow-sm">
  <!-- Brand / Header -->
  <div class="sidebar-header px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
    <a href="<?= url('/admin/index.php') ?>" class="d-flex align-items-center gap-3 text-decoration-none">
      <img src="<?= asset('img/graduate.png') ?>" alt="Logo" class="sidebar-logo">
      <div class="d-flex flex-column">
        <span class="sidebar-brand-sub fw-bold text-uppercase">SỞ GD&ĐT TP.HCM</span>
        <span class="sidebar-brand-title fw-bold text-primary">HỆ THỐNG QUẢN TRỊ</span>
        <span class="visually-hidden">QUẢN TRỊ VIÊN</span>
      </div>
    </a>
    <button type="button" class="btn btn-sm btn-outline-secondary d-lg-none py-0 px-2" id="adminSidebarCloseBtn" aria-label="Đóng menu">
      Đóng
    </button>
  </div>

  <!-- Role Badge banner -->
  <div class="px-4 py-2 bg-light border-bottom d-flex align-items-center justify-content-between small">
    <span class="text-muted fw-semibold">Vai trò:</span>
    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2.5 py-1 rounded-pill">
      <?= e($roleLabel) ?>
    </span>
  </div>

  <!-- Navigation Menu -->
  <div class="sidebar-menu flex-grow-1 overflow-auto py-2 px-3">
    <div class="nav flex-column gap-1">
      <?php foreach ($menuGroups as $group): ?>
        <?php
          $visibleItems = array_filter($group['items'], function($item) {
              if (isset($item['permission'])) {
                  return auth_has_permission($item['permission']);
              }
              return true;
          });
          if (empty($visibleItems)) continue;
        ?>
        <div class="sidebar-section-title">
          <?= e($group['title']) ?>
          <?php if ($group['title'] === 'CÀI ĐẶT'): ?>
            <span class="visually-hidden">Cài đặt</span>
          <?php endif; ?>
        </div>
        <?php foreach ($visibleItems as $item): ?>
          <a href="<?= e($item['url']) ?>" 
             <?= !empty($item['target']) ? 'target="' . e($item['target']) . '"' : '' ?>
             class="sidebar-nav-link <?= $item['active'] ? 'active' : '' ?>">
            <?= e($item['title']) ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Footer Profile & Logout -->
  <div class="sidebar-footer p-3 border-top bg-light">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
      <div class="d-flex align-items-center gap-2 overflow-hidden">
        <div class="sidebar-avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold">
          <?= e(mb_strtoupper(mb_substr($user['full_name'] ?? ($roleLabel[0] ?? 'A'), 0, 1))) ?>
        </div>
        <div class="overflow-hidden">
          <div class="fw-bold text-dark text-truncate small" title="<?= e($user['full_name'] ?? '') ?>">
            <?= e($user['full_name'] ?? $roleLabel) ?>
          </div>
          <div class="text-muted text-truncate" style="font-size: 0.75rem;">
            <?= e($roleLabel) ?>
          </div>
        </div>
      </div>
      <a href="<?= url('/auth/logout.php') ?>" class="btn btn-sm btn-outline-danger px-2 py-0.5 small" title="Đăng xuất">
        Đăng xuất
      </a>
    </div>
    <div class="pt-2 border-top text-center">
      <a href="<?= url('/') ?>" target="_blank" class="small text-muted text-decoration-none">
        Xem trang công khai
      </a>
    </div>
  </div>
</aside>
