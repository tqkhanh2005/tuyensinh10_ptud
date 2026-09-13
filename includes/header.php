<?php
/**
 * Header Component: Meta tags, Stylesheets, Fonts
 */
$pageTitle = $pageTitle ?? 'TUYỂN SINH LỚP 10 - TP CẦN THƠ';
$pageDescription = $pageDescription ?? 'Trang dành riêng cho học sinh – tra cứu kết quả và đăng ký tuyển sinh.';
$bodyClass = $bodyClass ?? 'tw-bg-surface tw-text-on-surface';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>" />
  <link rel="icon" type="image/png" href="<?= asset('img/graduate.png') ?>" />
  <link rel="apple-touch-icon" href="<?= asset('img/graduate.png') ?>" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Be_Vietnam+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />

  <!-- Bootstrap CSS -->
  <link href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet" />
  <!-- FontAwesome -->
  <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>">
  
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  
  <!-- Tailwind & App CSS -->
  <link href="<?= asset('tailwind.css') ?>" rel="stylesheet" />
  <link href="<?= asset('app.css') ?>" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    :root {
      --ts-primary: #1769aa;
      --ts-secondary: #0d2a52;
      --ts-accent: #fdb813;
      --ts-light: #f7f9fb;
    }
    .text-ts-primary { color: var(--ts-primary); }
    .bg-ts-primary { background-color: var(--ts-primary); }

    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      vertical-align: middle;
    }
    .timeline-line::before {
      content: '';
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      width: 2px;
      height: 100%;
      background: #dfe0ff;
      z-index: 0;
    }
    @media (max-width: 768px) {
      .timeline-line::before {
        left: 50%;
      }
    }
    body { font-family: 'Be Vietnam Pro', sans-serif; background-color: #f8f5ff; color: #272c51; }
    .font-headline { font-family: 'Plus Jakarta Sans', sans-serif; }
    .hero-section { min-height: 85vh; }
    @media (min-width: 1024px) {
      .hero-section { min-height: 90vh; }
    }
    .hidden-item { display: none; }
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @keyframes fadeOutDown {
      from {
        opacity: 1;
        transform: translateY(0);
      }
      to {
        opacity: 0;
        transform: translateY(20px);
      }
    }
    .animate-fade-in {
      animation: fadeInUp 0.4s ease forwards;
    }
    .animate-fade-out {
      animation: fadeOutDown 0.4s ease forwards;
    }
    /* Fix Bootstrap link conflict */
    a.tw-no-underline { text-decoration: none !important; }
    .tw-bg-primary { background-color: #0253cd !important; }
    .tw-text-primary { color: #0253cd !important; }
    .tw-bg-secondary { background-color: #8a4c00 !important; }
    .tw-text-on-secondary { color: #fff0e6 !important; }
    button { 
      outline: none !important;
      border: none !important;
    }
    button:focus, a:focus, .tab-btn:focus, .tab-btn:active, .tab-btn:focus-visible, .tab-btn:focus-within {
      outline: none !important;
      outline: 0 !important;
      box-shadow: none !important;
      -webkit-tap-highlight-color: transparent !important;
      -webkit-user-select: none;
      user-select: none;
    }
    .tab-btn::-moz-focus-inner { border: 0 !important; }
    .reveal {
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .reveal.reveal-visible {
      opacity: 1;
      transform: translateY(0);
    }
  </style>
  <?= $customStyles ?? '' ?>
</head>
<body class="<?= e($bodyClass) ?>">
