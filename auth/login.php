<?php
/**
 * Trang Đăng nhập Hệ thống Tuyển sinh 10 (Học sinh / Quản trị)
 */
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Đăng nhập hệ thống - TUYỂN SINH LỚP 10';
$activePage = 'login';
$bodyClass = 'tw-bg-background tw-font-body tw-text-on-surface';

$loginError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'student';
    $captchaText = strtoupper(trim($_POST['captchaText'] ?? ''));
    $captchaToken = trim($_POST['captchaToken'] ?? '');

    $validCaptcha = false;
    if (!empty($captchaToken) && isset($_SESSION['captchas'][$captchaToken])) {
        $saved = $_SESSION['captchas'][$captchaToken];
        if ($saved['expires'] >= time() && strtoupper($saved['code']) === $captchaText) {
            $validCaptcha = true;
        }
        unset($_SESSION['captchas'][$captchaToken]);
    }

    if (!$validCaptcha) {
        $loginError = 'Mã xác nhận không chính xác hoặc đã hết hạn. Vui lòng thử lại.';
    } else {
        $loginError = 'Tài khoản hoặc mật khẩu không chính xác.';
    }
}

$customStyles = '
<style>
  html, body {
    margin: 0 !important;
    padding: 0 !important;
    background-color: #ffffff;
  }
  .academic-overlay {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.85) 0%, rgba(224, 235, 255, 0.7) 100%);
    pointer-events: none;
  }
  .glass-card {
    background: rgba(255, 255, 255, 0.98) !important;
    backdrop-filter: blur(15px) !important;
    border: 1px solid rgba(255, 255, 255, 1) !important;
    border-radius: 3rem !important;
  }
  .tab-active {
    color: #0052CC !important;
    border-bottom: 3px solid #0052CC !important;
  }
  .campus-bg {
    background-image: url(https://lh3.googleusercontent.com/aida-public/AB6AXuAUj-4Wy4eFl5LzVrgUWK0RUAZB86fExS583t5V2TfhQpQTlppHTu4a-Pv_4DxMiB5NiacnDskktMaKPX4Vn_KarsFFTrfGB54Nqar-GOfsX66wD0vXwe2AyPZrmlZKvR_zRKNB_GJl7hSDt3u3pFyK__7PC_F3M1ItmjhzJd4v-H3G-mWrdR4hNPlOra9nWpCDoW-3VNVGzbGA-Aj3y-TQy6lr2dSk--r-42B58Vf97sMoYtGbDvW6NrxSEMnmJt95KXKKuzxmKzqE);
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
  }
  .hero-text-shadow {
    text-shadow: 0 2px 10px rgba(0, 82, 204, 0.1);
  }
  .tab-pane.active { display: block !important; }
  .tab-pane { display: none; }
</style>
';

require __DIR__ . '/../includes/header.php';
?>

<!-- Top Navigation Bar -->
<nav class="tw-fixed tw-top-0 tw-w-full tw-z-50 tw-bg-white/80 tw-backdrop-blur-md tw-border-b tw-border-outline-variant/50">
  <div class="tw-flex tw-justify-between tw-items-center tw-px-4 sm:tw-px-8 tw-py-4 tw-max-w-7xl tw-mx-auto tw-font-headline">
    <a href="<?= url('/') ?>" class="tw-flex tw-items-center tw-gap-3 tw-no-underline">
      <img src="<?= asset('img/graduate.png') ?>" alt="Logo" class="tw-w-12 tw-h-12 tw-object-contain">
      <div class="tw-flex tw-flex-col">
        <span class="tw-text-[10px] tw-font-bold tw-text-slate-500 tw-uppercase tw-tracking-widest tw-leading-tight">SỞ GIÁO DỤC VÀ ĐÀO TẠO</span>
        <span class="tw-text-xl tw-font-extrabold tw-tracking-tight tw-text-primary tw-leading-tight">TUYỂN SINH LỚP 10</span>
      </div>
    </a>
    <div>
      <a href="<?= url('/') ?>" class="tw-text-slate-600 hover:tw-text-primary tw-text-sm tw-font-semibold tw-no-underline">
        ← Trang chủ
      </a>
    </div>
  </div>
</nav>

<!-- Main Content Canvas -->
<main class="tw-min-h-screen tw-w-full tw-relative tw-flex tw-items-center tw-justify-center campus-bg tw-pt-24 tw-pb-12">
  <div class="tw-absolute tw-inset-0 academic-overlay"></div>
  
  <div class="tw-w-full tw-max-w-7xl tw-px-10 tw-grid md:tw-grid-cols-2 tw-gap-16 lg:tw-gap-24 tw-items-center tw-relative tw-z-10">       
    
    <!-- Branding Column -->
    <div class="tw-hidden md:tw-block tw-space-y-12">
      <div class="tw-space-y-8">
        <span class="tw-inline-block tw-py-2.5 tw-px-6 tw-rounded-full tw-bg-primary/10 tw-border tw-border-primary/20 tw-text-primary tw-text-[11px] tw-font-black tw-font-label tw-uppercase tw-tracking-widest">
          Hệ thống Tuyển sinh Lớp 10
        </span>
        <h1 class="tw-text-5xl lg:tw-text-6xl tw-font-extrabold tw-font-headline tw-leading-[1.15] tw-tracking-tight tw-text-on-surface hero-text-shadow">
          Kiến tạo <span class="tw-text-primary tw-italic">Tương lai</span> <br/>với giáo dục ưu tú.
        </h1>
        <p class="tw-text-xl tw-text-on-surface-variant tw-max-w-xl tw-leading-relaxed tw-font-medium">
          Tra cứu điểm số, đăng ký nguyện vọng và theo dõi kết quả tuyển sinh lớp 10 trên một nền tảng thống nhất, minh bạch và nhanh chóng.
        </p>
      </div>
      <div class="tw-grid tw-grid-cols-2 tw-gap-8 tw-max-w-lg">
        <div class="tw-p-8 tw-rounded-[2.5rem] tw-bg-white tw-shadow-sm tw-border tw-border-outline-variant/50 hover:tw-shadow-md tw-transition-shadow">
          <span class="material-symbols-outlined tw-text-secondary tw-mb-4 tw-text-4xl">history_edu</span>
          <p class="tw-font-bold tw-text-on-surface tw-text-lg">Hồ sơ Trực tuyến</p>
          <p class="tw-text-sm tw-text-on-surface-variant tw-mt-2 tw-leading-relaxed">Đăng ký nguyện vọng 24/7 mọi lúc mọi nơi.</p>
        </div>
        <div class="tw-p-8 tw-rounded-[2.5rem] tw-bg-white tw-shadow-sm tw-border tw-border-outline-variant/50 hover:tw-shadow-md tw-transition-shadow">
          <span class="material-symbols-outlined tw-text-accent tw-mb-4 tw-text-4xl">analytics</span>
          <p class="tw-font-bold tw-text-on-surface tw-text-lg">Dữ liệu Nhất quán</p>
          <p class="tw-text-sm tw-text-on-surface-variant tw-mt-2 tw-leading-relaxed">Kết quả thi được đồng bộ chính xác.</p>
        </div>
      </div>
    </div>

    <!-- Login Card Column -->
    <div class="tw-flex tw-justify-center md:tw-justify-end">
      <div class="tw-w-full tw-max-w-[480px] glass-card tw-p-6 sm:tw-p-10 md:tw-p-14 tw-shadow-[0_40px_80px_rgba(0,82,204,0.15)]">
        <div class="tw-mb-10">
          <h2 class="tw-text-3xl tw-font-extrabold tw-font-headline tw-text-on-surface tw-mb-3 tw-tracking-tight">Chào mừng trở lại</h2>
          <p class="tw-text-on-surface-variant tw-text-base tw-font-medium">Vui lòng chọn phương thức đăng nhập để tiếp tục.</p>
        </div>

        <?php if ($loginError): ?>
          <div class="tw-mb-6 tw-p-4 tw-rounded-2xl tw-bg-red-50 tw-text-red-700 tw-text-sm tw-font-medium tw-border tw-border-red-100">
            <?= e($loginError) ?>
          </div>
        <?php endif; ?>

         <!-- Tabs -->
         <div class="tw-flex tw-mb-12 tw-border-b tw-border-outline-variant" id="loginTabs">
           <button class="tw-flex-1 tw-pb-4 tw-text-sm tw-font-bold tw-font-label tw-transition-all tw-bg-transparent tw-border-0 tw-outline-none tab-active" data-target="student">
             Học sinh
           </button>
           <button class="tw-flex-1 tw-pb-4 tw-text-sm tw-font-bold tw-font-label tw-transition-all tw-bg-transparent tw-border-0 tw-outline-none tw-text-on-surface-variant hover:tw-text-primary" data-target="admin">
             Quản trị
           </button>
         </div>

        <div class="tw-tab-content">
            <!-- Student Password Form -->
            <div class="tab-pane active" id="student">
              <form class="tw-space-y-8" method="POST" action="<?= url('/auth/login') ?>">
                <input type="hidden" name="mode" value="student" />
                <div class="tw-space-y-3">
                  <label class="tw-text-[11px] tw-font-extrabold tw-text-on-surface-variant tw-font-label tw-uppercase tw-tracking-[0.15em] tw-px-1">CCCD</label>
                  <div class="tw-relative tw-group">
                    <span class="material-symbols-outlined tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-on-surface-variant/40 group-focus-within:tw-text-primary tw-transition-colors">badge</span>
                    <input name="cccd" class="tw-w-full tw-pl-12 tw-pr-4 tw-py-4 tw-rounded-[1.25rem] tw-bg-surface/50 tw-border-2 tw-border-transparent focus:tw-border-primary/20 focus:tw-ring-0 focus:tw-bg-white tw-text-on-surface tw-font-semibold tw-transition-all tw-placeholder:text-on-surface-variant/40" placeholder="Nhập 12 chữ số CCCD" type="text" required />
                  </div>
                </div>
                <div class="tw-space-y-3">
                  <label class="tw-text-[11px] tw-font-extrabold tw-text-on-surface-variant tw-font-label tw-uppercase tw-tracking-[0.15em] tw-px-1">Mật khẩu</label>
                  <div class="tw-relative tw-group">
                    <span class="material-symbols-outlined tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-on-surface-variant/40 group-focus-within:tw-text-primary tw-transition-colors">lock</span>
                    <input name="password" class="tw-w-full tw-pl-12 tw-pr-14 tw-py-4 tw-rounded-[1.25rem] tw-bg-surface/50 tw-border-2 tw-border-transparent focus:tw-border-primary/20 focus:tw-ring-0 focus:tw-bg-white tw-text-on-surface tw-font-semibold tw-transition-all tw-placeholder:text-on-surface-variant/40" placeholder="••••••••" type="password" required />
                    <button type="button" class="js-toggle-password tw-absolute tw-right-4 tw-top-1/2 tw--translate-y-1/2 tw-flex tw-h-9 tw-w-9 tw-items-center tw-justify-center tw-rounded-full tw-border-0 tw-bg-transparent tw-text-on-surface-variant/50 hover:tw-bg-primary/10 hover:tw-text-primary focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-primary/30 tw-transition-all" data-password-toggle="password" aria-label="Hien mat khau" title="Hien mat khau">
                      <span class="material-symbols-outlined tw-text-[20px]">visibility</span>
                    </button>
                  </div>
                </div>
                
                <div class="tw-space-y-3" id="captcha-section-student">
                  <label class="tw-text-[11px] tw-font-extrabold tw-text-on-surface-variant tw-font-label tw-uppercase tw-tracking-[0.15em] tw-px-1">Mã xác nhận</label>
                  <div class="tw-flex tw-items-center tw-gap-2 tw-w-full">
                    <input type="text" name="captchaText" class="tw-text-center md:tw-text-left tw-w-full sm:tw-w-24 md:tw-flex-1 tw-min-w-0 tw-pl-0 tw-pr-0 md:tw-pl-3 md:tw-pr-3 tw-py-2 tw-rounded-[1.25rem] tw-bg-white tw-border-2 tw-border-outline-variant focus:tw-border-primary/20 focus:tw-ring-0 sm:tw-text-center tw-text-on-surface tw-font-semibold tw-transition-all tw-placeholder:text-on-surface-variant/40 tw-text-sm" placeholder="Mã xác nhận" maxlength="6" autocomplete="off" required />
                    <div id="captcha-image-student" class="tw-rounded-xl tw-overflow-hidden tw-flex-shrink-0"></div>
                    <div class="tw-flex tw-flex-col tw-items-center">
                      <div id="captcha-timer-student" class="tw-text-[0.65rem] tw-leading-none tw-text-on-surface-variant/60"></div>
                      <button type="button" class="js-captcha-reload tw-flex-shrink-0 tw-h-10 tw-w-10 tw-rounded-full tw-border tw-border-outline-variant tw-bg-transparent tw-flex tw-items-center tw-justify-center hover:tw-bg-primary/10 tw-transition-all" title="Đổi mã">
                        <span class="material-symbols-outlined tw-text-on-surface-variant">refresh</span>
                      </button>
                    </div>
                  </div>
                  <input type="hidden" name="captchaToken" id="captcha-token-student" value="" />
                </div>
                
                <div class="tw-pt-6">
                  <button class="tw-w-full tw-py-5 tw-rounded-full tw-bg-primary tw-text-on-primary tw-font-bold tw-font-label tw-text-lg tw-shadow-xl tw-shadow-primary/25 hover:tw-shadow-primary/40 hover:tw--translate-y-1 active:tw-translate-y-0 active:tw-scale-[0.98] tw-transition-all tw-border-0" type="submit">Đăng nhập học sinh</button>
                </div>
              </form>
           </div>

           <!-- Admin Password Form -->
           <div class="tab-pane" id="admin">
             <form class="tw-space-y-8" method="POST" action="<?= url('/auth/login') ?>">
               <input type="hidden" name="mode" value="admin" />
               <div class="tw-space-y-3">
                 <label class="tw-text-[11px] tw-font-extrabold tw-text-on-surface-variant tw-font-label tw-uppercase tw-tracking-[0.15em] tw-px-1">Tên tài khoản</label>
                 <div class="tw-relative tw-group">
                   <span class="material-symbols-outlined tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-on-surface-variant/40 group-focus-within:tw-text-primary tw-transition-colors">person</span>
                   <input name="username" class="tw-w-full tw-pl-12 tw-pr-4 tw-py-4 tw-rounded-[1.25rem] tw-bg-surface/50 tw-border-2 tw-border-transparent focus:tw-border-primary/20 focus:tw-ring-0 focus:tw-bg-white tw-text-on-surface tw-font-semibold tw-transition-all tw-placeholder:text-on-surface-variant/40" placeholder="Tài khoản quản trị" type="text" required />
                 </div>
               </div>
               <div class="tw-space-y-3">
                 <label class="tw-text-[11px] tw-font-extrabold tw-text-on-surface-variant tw-font-label tw-uppercase tw-tracking-[0.15em] tw-px-1">Mật khẩu</label>
                 <div class="tw-relative tw-group">
                   <span class="material-symbols-outlined tw-absolute tw-left-4 tw-top-1/2 tw--translate-y-1/2 tw-text-on-surface-variant/40 group-focus-within:tw-text-primary tw-transition-colors">lock</span>
                   <input name="password" class="tw-w-full tw-pl-12 tw-pr-14 tw-py-4 tw-rounded-[1.25rem] tw-bg-surface/50 tw-border-2 tw-border-transparent focus:tw-border-primary/20 focus:tw-ring-0 focus:tw-bg-white tw-text-on-surface tw-font-semibold tw-transition-all tw-placeholder:text-on-surface-variant/40" placeholder="••••••••" type="password" required />
                   <button type="button" class="js-toggle-password tw-absolute tw-right-4 tw-top-1/2 tw--translate-y-1/2 tw-flex tw-h-9 tw-w-9 tw-items-center tw-justify-center tw-rounded-full tw-border-0 tw-bg-transparent tw-text-on-surface-variant/50 hover:tw-bg-primary/10 hover:tw-text-primary focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-primary/30 tw-transition-all" data-password-toggle="password" aria-label="Hien mat khau" title="Hien mat khau">
                     <span class="material-symbols-outlined tw-text-[20px]">visibility</span>
                   </button>
                 </div>
               </div>
               
               <div class="tw-space-y-3" id="captcha-section-admin">
                 <label class="tw-text-[11px] tw-font-extrabold tw-text-on-surface-variant tw-font-label tw-uppercase tw-tracking-[0.15em] tw-px-1">Mã xác nhận</label>
                 <div class="tw-flex tw-items-center tw-gap-2 tw-w-full">
                   <input type="text" name="captchaText" class="tw-text-center md:tw-text-left tw-w-full sm:tw-w-24 md:tw-flex-1 tw-min-w-0 tw-pl-0 tw-pr-0 md:tw-pl-3 md:tw-pr-3 tw-py-2 tw-rounded-[1.25rem] tw-bg-white tw-border-2 tw-border-outline-variant focus:tw-border-primary/20 focus:tw-ring-0 sm:tw-text-center tw-text-on-surface tw-font-semibold tw-transition-all tw-placeholder:text-on-surface-variant/40 tw-text-sm" placeholder="Mã xác nhận" maxlength="6" autocomplete="off" required />
                   <div id="captcha-image-admin" class="tw-rounded-xl tw-overflow-hidden tw-flex-shrink-0"></div>
                   <div class="tw-flex tw-flex-col tw-items-center">
                     <div id="captcha-timer-admin" class="tw-text-[0.65rem] tw-leading-none tw-text-on-surface-variant/60"></div>
                     <button type="button" class="js-captcha-reload tw-flex-shrink-0 tw-h-10 tw-w-10 tw-rounded-full tw-border tw-border-outline-variant tw-bg-transparent tw-flex tw-items-center tw-justify-center hover:tw-bg-primary/10 tw-transition-all" title="Đổi mã">
                       <span class="material-symbols-outlined tw-text-on-surface-variant">refresh</span>
                     </button>
                   </div>
                 </div>
                 <input type="hidden" name="captchaToken" id="captcha-token-admin" value="" />
               </div>
               
               <div class="tw-pt-6">
                 <button class="tw-w-full tw-py-5 tw-rounded-full tw-bg-primary tw-text-on-primary tw-font-bold tw-font-label tw-text-lg tw-shadow-xl tw-shadow-primary/25 hover:tw-shadow-primary/40 hover:tw--translate-y-1 active:tw-translate-y-0 active:tw-scale-[0.98] tw-transition-all tw-border-0" type="submit">Đăng nhập quản trị</button>
               </div>
             </form>
           </div>
        </div>

        <div class="tw-mt-12 tw-text-center">
          <a class="tw-text-sm tw-font-bold tw-text-primary hover:tw-text-primary-dim tw-transition-all tw-decoration-primary/30 hover:tw-underline tw-no-underline" href="#">Bạn gặp khó khăn khi đăng nhập?</a>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require __DIR__ . '/../includes/scripts.php'; ?>
<script>
  var captchaTimerInterval = null;
  function startCaptchaTimerLogin() {
    if (captchaTimerInterval) clearInterval(captchaTimerInterval);
    var remaining = 120;
    var studentEl = document.getElementById('captcha-timer-student');
    var adminEl = document.getElementById('captcha-timer-admin');
    function tick() {
      remaining--;
      if (remaining <= 0) {
        clearInterval(captchaTimerInterval);
        captchaTimerInterval = null;
        if (studentEl) studentEl.textContent = 'Hết hạn';
        if (adminEl) adminEl.textContent = 'Hết hạn';
        loadCaptchaLogin();
      } else {
        var text = remaining + 's';
        if (studentEl) studentEl.textContent = text;
        if (adminEl) adminEl.textContent = text;
      }
    }
    var text = '120s';
    if (studentEl) studentEl.textContent = text;
    if (adminEl) adminEl.textContent = text;
    captchaTimerInterval = setInterval(tick, 1000);
  }

  function loadCaptchaLogin() {
    fetch('<?= url('/captcha/image') ?>', { cache: 'no-store' })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var studentImg = document.getElementById('captcha-image-student');
        var adminImg = document.getElementById('captcha-image-admin');
        var studentToken = document.getElementById('captcha-token-student');
        var adminToken = document.getElementById('captcha-token-admin');
        if (studentImg) studentImg.innerHTML = data.image;
        if (adminImg) adminImg.innerHTML = data.image;
        if (studentToken) studentToken.value = data.token;
        if (adminToken) adminToken.value = data.token;
        startCaptchaTimerLogin();
      });
  }

  document.addEventListener('DOMContentLoaded', loadCaptchaLogin);
  document.querySelectorAll('.js-captcha-reload').forEach(function(btn) {
    btn.addEventListener('click', loadCaptchaLogin);
  });

  // Tab switching
  document.querySelectorAll('#loginTabs button').forEach(button => {
    button.addEventListener('click', function() {
      document.querySelectorAll('#loginTabs button').forEach(btn => {
        btn.classList.remove('tab-active');
        btn.classList.add('tw-text-on-surface-variant', 'hover:tw-text-primary');
      });
      this.classList.add('tab-active');
      this.classList.remove('tw-text-on-surface-variant', 'hover:tw-text-primary');
      const target = this.getAttribute('data-target');
      document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
      document.getElementById(target).classList.add('active');
    });
  });

  // Password toggle
  document.querySelectorAll('.js-toggle-password').forEach(toggle => {
    toggle.addEventListener('click', function() {
      const form = toggle.closest('form');
      const targetName = toggle.getAttribute('data-password-toggle');
      const passwordInput = form ? form.querySelector('input[name="' + targetName + '"]') : null;
      const icon = toggle.querySelector('.material-symbols-outlined');
      if (!passwordInput || !icon) return;
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      icon.textContent = isHidden ? 'visibility_off' : 'visibility';
      toggle.setAttribute('aria-label', isHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
      toggle.setAttribute('title', isHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    });
  });
</script>
</body>
</html>
