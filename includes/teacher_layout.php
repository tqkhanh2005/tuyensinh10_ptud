<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal_helper.php';

/**
 * Khởi đầu giao diện Khu vực Giáo viên (THCS & THPT)
 *
 * @param string $pageTitle Tiêu đề trang
 * @param string $activePage Trang hiện tại để highlight sidebar
 * @param array $breadcrumb Danh sách đường dẫn breadcrumb (label => url)
 */
function teacher_layout_start(string $pageTitle = 'Cổng thông tin Giáo viên', string $activePage = 'teacher-dashboard', array $breadcrumb = []): void
{
    require_teacher_access();

    // Thí sinh không được phép truy cập khu vực giáo viên
    if (auth_has_role('candidate') && !auth_is_admin()) {
        http_response_code(403);
        exit('403 Forbidden - Khu vực chỉ dành cho Cán bộ - Giáo viên.');
    }

    $user = auth_user();
    $teacherInfo = auth_get_teacher_info();
    $schoolLevel = auth_teacher_level();
    $schoolName = $teacherInfo['school_name'] ?? $teacherInfo['workplace'] ?? 'Trường học';
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($pageTitle) ?> - CỔNG GIÁO VIÊN</title>
  <link rel="icon" type="image/png" href="<?= asset('img/graduate.png') ?>" />

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Be_Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <!-- Bootstrap CSS & FontAwesome -->
  <link href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet" />
  <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link href="<?= asset('tailwind.css') ?>" rel="stylesheet" />
  <link href="<?= asset('app.css') ?>" rel="stylesheet" />

  <style>
    :root {
      --ts-teacher-sidebar-w: 260px;
    }
    body {
      font-family: 'Be Vietnam Pro', sans-serif;
      background-color: #f8fafc;
      color: #1e293b;
      margin: 0;
      padding: 0;
    }
    .teacher-layout-wrapper {
      display: flex;
      min-height: 100vh;
      background-color: #f8fafc;
    }
    .teacher-sidebar {
      width: var(--ts-teacher-sidebar-w);
      min-width: var(--ts-teacher-sidebar-w);
      max-width: var(--ts-teacher-sidebar-w);
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1040;
      background-color: #ffffff;
      transition: transform 0.25s ease-in-out;
    }
    .sidebar-logo {
      width: 40px;
      height: 40px;
      object-fit: contain;
    }
    .sidebar-brand-sub {
      font-size: 0.65rem;
      letter-spacing: 0.08em;
      color: #64748b;
      line-height: 1.2;
    }
    .sidebar-brand-title {
      font-size: 0.95rem;
      letter-spacing: -0.02em;
      line-height: 1.2;
    }
    .sidebar-icon-wrap {
      width: 28px;
      height: 28px;
      font-size: 0.95rem;
      color: #64748b;
      transition: color 0.2s ease;
    }
    .sidebar-nav-link {
      color: #475569;
      font-size: 0.875rem;
      transition: all 0.2s ease;
      text-decoration: none !important;
    }
    .sidebar-nav-link:hover {
      background-color: #f1f5f9;
      color: #0f172a;
    }
    .sidebar-nav-link.active-teacher {
      background-color: #f0fdf4;
      color: #15803d !important;
      font-weight: 700 !important;
    }
    .sidebar-nav-link.active-teacher .sidebar-icon-wrap {
      color: #15803d;
    }
    .sidebar-avatar {
      width: 36px;
      height: 36px;
      font-size: 0.875rem;
    }
    .teacher-main-wrapper {
      margin-left: var(--ts-teacher-sidebar-w);
      width: calc(100% - var(--ts-teacher-sidebar-w));
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .portal-topbar {
      height: 64px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid #e2e8f0;
      position: sticky;
      top: 0;
      z-index: 1030;
      padding: 0 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .portal-content-body {
      padding: 1.75rem 1.5rem;
      flex-grow: 1;
    }
    @media (max-width: 991.98px) {
      .teacher-sidebar {
        transform: translateX(-100%);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
      }
      .teacher-sidebar.show {
        transform: translateX(0);
      }
      .teacher-main-wrapper {
        margin-left: 0;
        width: 100%;
      }
      .sidebar-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.5);
        z-index: 1035;
        display: none;
      }
      .sidebar-backdrop.show {
        display: block;
      }
    }
  </style>
<link href="<?= asset('portal-responsive.css') ?>" rel="stylesheet" />
  <script src="<?= asset('portal-ui.js') ?>" defer></script>
</head>
<body>
<div class="teacher-layout-wrapper">
  <!-- Backdrop for mobile -->
  <div class="sidebar-backdrop" id="teacherSidebarBackdrop"></div>

  <!-- Sidebar Component -->
  <?php require __DIR__ . '/teacher_sidebar.php'; ?>

  <!-- Main Content Wrapper -->
  <div class="teacher-main-wrapper">
    <!-- Topbar -->
    <header class="portal-topbar">
      <div class="d-flex align-items-center gap-3">
        <button type="button" class="btn btn-sm btn-light d-lg-none" id="teacherSidebarToggleBtn" aria-label="Mở menu">
          <i class="fas fa-bars"></i>
        </button>
        <div>
          <h1 class="h5 fw-bold mb-0 text-dark"><?= e($pageTitle) ?></h1>
          <?php if (!empty($breadcrumb)): ?>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb mb-0 small text-muted" style="font-size: 0.75rem;">
                <li class="breadcrumb-item"><a href="<?= url('/teacher/index.php') ?>" class="text-decoration-none text-muted">Giáo viên</a></li>
                <?php foreach ($breadcrumb as $name => $link): ?>
                  <?php if (!empty($link)): ?>
                    <li class="breadcrumb-item"><a href="<?= e($link) ?>" class="text-decoration-none text-muted"><?= e($name) ?></a></li>
                  <?php else: ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($name) ?></li>
                  <?php endif; ?>
                <?php endforeach; ?>
              </ol>
            </nav>
          <?php endif; ?>
        </div>
      </div>

      <div class="d-flex align-items-center gap-3">
        <span class="badge <?= $schoolLevel === 'THCS' ? 'bg-info bg-opacity-10 text-info border border-info' : 'bg-primary bg-opacity-10 text-primary border border-primary' ?> px-3 py-1.5 rounded-pill d-none d-md-inline-flex align-items-center gap-1.5 fw-bold">
          <i class="fas fa-school"></i> <?= e($schoolName) ?>
        </span>
        <div class="dropdown">
          <button class="btn btn-sm btn-light border rounded-pill d-flex align-items-center gap-2 px-2.5 py-1 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="badge bg-success text-white rounded-circle p-1 d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 0.65rem;">
              <i class="fas fa-chalkboard-teacher"></i>
            </span>
            <span class="fw-semibold small text-dark d-none d-md-inline"><?= e($user['full_name'] ?? 'Giáo viên') ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2 py-2">
            <li class="px-3 py-1">
              <div class="small fw-bold text-dark"><?= e($user['full_name'] ?? '') ?></div>
              <div class="text-muted" style="font-size: 0.75rem;">Cán bộ - Giáo viên</div>
              <div class="text-primary small fw-semibold text-truncate" style="max-width: 180px;"><?= e($schoolName) ?></div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item small" href="<?= url('/auth/change-password.php') ?>"><i class="fas fa-key me-2 text-muted"></i>Đổi mật khẩu</a></li>
            <li><a class="dropdown-item small text-danger" href="<?= url('/auth/logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
          </ul>
        </div>
      </div>
    </header>

    <!-- Content Body -->
    <main class="portal-content-body">
    <?php
}

/**
 * Kết thúc giao diện Khu vực Giáo viên
 */
function teacher_layout_end(): void
{
    ?>
    </main>
  </div>
</div>

<!-- Scripts -->
<script src="<?= asset('vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
    <?php
}
