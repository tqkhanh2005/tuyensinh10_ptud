<?php
/**
 * Trang Tra cứu kết quả Tuyển sinh 10
 */
require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Tra cứu kết quả - TUYỂN SINH LỚP 10';
$activePage = 'ket-qua';
$bodyClass = 'bg-light';

$searchResult = null;
$searchError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cccd = trim($_POST['cccd'] ?? '');
    $captchaText = strtoupper(trim($_POST['captchaText'] ?? ''));
    $captchaToken = trim($_POST['captchaToken'] ?? '');

    // Kiểm tra captcha
    $validCaptcha = false;
    if (!empty($captchaToken) && isset($_SESSION['captchas'][$captchaToken])) {
        $saved = $_SESSION['captchas'][$captchaToken];
        if ($saved['expires'] >= time() && strtoupper($saved['code']) === $captchaText) {
            $validCaptcha = true;
        }
        unset($_SESSION['captchas'][$captchaToken]); // Sử dụng 1 lần
    }

    if (!$validCaptcha) {
        $searchError = 'Mã xác nhận không chính xác hoặc đã hết hạn. Vui lòng thử lại.';
    } elseif (empty($cccd)) {
        $searchError = 'Vui lòng nhập Số báo danh hoặc CCCD/Định danh cá nhân.';
    } else {
        // Dữ liệu tra cứu mẫu (hoặc kết nối CSDL khi có DB)
        $searchError = 'Chưa tìm thấy kết quả cho số báo danh hoặc CCCD: ' . htmlspecialchars($cccd) . '. Vui lòng kiểm tra lại thông tin hoặc liên hệ Hội đồng tuyển sinh.';
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main class="container py-5" style="margin-top: 5rem;">
  <div class="row justify-content-center">
    <div class="col-lg-7">

      <div class="text-center mb-5">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle mb-4 overflow-hidden" style="width: 80px; height: 80px;">
          <div style="margin-right: -6px; margin-bottom: -10px;">
            <i class="fas fa-chalkboard-teacher" style="font-size: 2.4em; color: #0a2a4a;"></i>
          </div>
        </div>
        <h1 class="fw-bold text-ts-primary">Tra cứu kết quả Tuyển sinh 10</h1>
        <p class="text-muted mb-0 mt-2">Vui lòng nhập đúng số báo danh hoặc CCCD/Định danh cá nhân.</p>
      </div>

      <?php if ($searchError): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4 text-center mb-4">
          <i class="fas fa-exclamation-triangle me-2"></i><?= e($searchError) ?>
        </div>
      <?php endif; ?>
       
      <form method="post" action="<?= url('/ket-qua') ?>">
        <div class="mx-auto" style="max-width: 700px;">
          <div class="d-flex flex-column flex-lg-row gap-3">
            <input
              type="text"
              name="cccd"
              class="form-control form-control-lg flex-grow-1 text-center"
              style="margin-bottom: 0; font-size: 1rem;"
              placeholder="Nhập Số báo danh hoặc CCCD/Định danh cá nhân"
              pattern="[a-zA-Z0-9]+"
              title="Chỉ chấp nhận chữ cái và số"
              value="<?= e($_POST['cccd'] ?? '') ?>"
              required />
            <button type="submit" class="btn btn-lg flex-shrink-0" style="background-color: #0a2a4a; border-color: #0a2a4a; color: #fff; margin-top: 0; font-size: 1rem;">
              <i class="fas fa-search me-2"></i>Tra cứu
            </button>
          </div>
          
          <div class="mt-3 p-3 rounded bg-white border" id="captcha-section">
            <div class="d-flex align-items-center gap-2">
              <div class="flex-grow-1">
                <input type="text" name="captchaText" class="form-control mb-0" placeholder="Mã xác nhận" maxlength="6" autocomplete="off" required />
              </div>
              <div id="captcha-image" class="flex-shrink-0 rounded overflow-hidden"></div>
              <div class="d-flex flex-column align-items-center">
                <small id="captcha-timer" class="text-muted" style="font-size:0.65rem;line-height:1"></small>
                <button type="button" id="captcha-reload" class="btn btn-outline-secondary btn-sm flex-shrink-0" title="Đổi mã xác nhận">
                  <i class="fas fa-sync-alt"></i>
                </button>
              </div>
            </div>
            <input type="hidden" name="captchaToken" id="captcha-token" value="" />
          </div>
        </div>
      </form>

    </div>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
<?php require __DIR__ . '/includes/scripts.php'; ?>

<script>
  var captchaTimerRemaining = 120;
  var captchaTimerInterval = null;

  function startCaptchaTimer() {
    if (captchaTimerInterval) clearInterval(captchaTimerInterval);
    captchaTimerRemaining = 120;
    var el = document.getElementById('captcha-timer');
    if (el) el.textContent = '120s';
    captchaTimerInterval = setInterval(function() {
      captchaTimerRemaining--;
      if (captchaTimerRemaining <= 0) {
        clearInterval(captchaTimerInterval);
        captchaTimerInterval = null;
        if (el) el.textContent = 'Hết hạn';
        loadCaptcha();
      } else {
        if (el) el.textContent = captchaTimerRemaining + 's';
      }
    }, 1000);
  }

  function loadCaptcha() {
    fetch('<?= url('/captcha/image') ?>', { cache: 'no-store' })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        document.getElementById('captcha-image').innerHTML = data.image;
        document.getElementById('captcha-token').value = data.token;
        startCaptchaTimer();
      })
      .catch(function() {
        document.getElementById('captcha-section').innerHTML =
          '<div class="text-danger small">Không thể tải mã xác nhận.</div>';
      });
  }

  document.addEventListener('DOMContentLoaded', loadCaptcha);
  document.getElementById('captcha-reload').addEventListener('click', loadCaptcha);
</script>
</body>
</html>
