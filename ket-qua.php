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
        $searchError = 'Mã xác nhận không chính xác hoặc đã hết hạn. Vui lòng nhập lại mã mới.';
    } elseif (empty($cccd)) {
        $searchError = 'Vui lòng nhập Số báo danh hoặc CCCD/Định danh cá nhân.';
    } else {
        // Dữ liệu tra cứu mẫu hiển thị sinh động kết quả thi
        $searchResult = [
            'name' => 'NGUYỄN VĂN AN',
            'dob' => '15/08/2011',
            'gender' => 'Nam',
            'sbd' => '010234',
            'cccd' => $cccd,
            'school_origin' => 'THCS Đoàn Thị Điểm',
            'exam_location' => 'Hội đồng thi THPT Chuyên Lý Tự Trọng',
            'van' => 8.50,
            'toan' => 9.25,
            'anh' => 8.75,
            'chuyen' => 8.00,
            'uu_tien' => 0.0,
            'tong_diem' => 42.50,
            'status' => 'TRÚNG TUYỂN NGUYỆN VỌNG 1',
            'target_school' => 'Trường THPT Chuyên Lý Tự Trọng',
            'target_class' => 'Chuyên Toán - Tin'
        ];
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main class="container py-5" style="margin-top: 5rem;">
  <div class="row justify-content-center">
    <div class="col-lg-8">

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
       
      <form method="post" action="<?= url('/ket-qua.php') ?>">
        <div class="mx-auto" style="max-width: 700px;">
          <div class="d-flex flex-column flex-lg-row gap-3">
            <input
              type="text"
              name="cccd"
              class="form-control form-control-lg flex-grow-1 text-center"
              style="margin-bottom: 0; font-size: 1rem;"
              placeholder="Nhập Số báo danh hoặc CCCD (VD: 010234 hoặc 012345678901)"
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

      <?php if ($searchResult): ?>
        <div class="card border-0 shadow-sm rounded-4 mt-5 overflow-hidden">
          <div class="card-header bg-primary text-white py-3 px-4">
            <h5 class="mb-0 fw-bold"><i class="fas fa-id-card me-2"></i>KẾT QUẢ THI TUYỂN SINH LỚP 10 NĂM HỌC 2026 - 2027</h5>
          </div>
          <div class="card-body p-4">
            <div class="row g-3 mb-4">
              <div class="col-sm-6">
                <div class="text-muted small">Họ và tên thí sinh</div>
                <div class="fw-bold fs-5 text-dark"><?= e($searchResult['name']) ?></div>
                <div class="text-muted small mt-1">Trường THCS: <?= e($searchResult['school_origin']) ?></div>
              </div>
              <div class="col-sm-3">
                <div class="text-muted small">Số báo danh</div>
                <div class="fw-bold fs-5 text-primary"><?= e($searchResult['sbd']) ?></div>
              </div>
              <div class="col-sm-3">
                <div class="text-muted small">Số CCCD / Định danh</div>
                <div class="fw-bold fs-6 text-dark"><?= e($searchResult['cccd']) ?></div>
              </div>
            </div>
            
            <div class="table-responsive mb-4">
              <table class="table table-bordered text-center align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Ngữ văn</th>
                    <th>Toán</th>
                    <th>Tiếng Anh</th>
                    <th>Điểm Chuyên</th>
                    <th>Điểm Ưu tiên</th>
                    <th>Tổng điểm XT</th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="fw-bold fs-6">
                    <td class="text-primary"><?= number_format($searchResult['van'], 2) ?></td>
                    <td class="text-primary"><?= number_format($searchResult['toan'], 2) ?></td>
                    <td class="text-primary"><?= number_format($searchResult['anh'], 2) ?></td>
                    <td><?= number_format($searchResult['chuyen'], 2) ?></td>
                    <td><?= number_format($searchResult['uu_tien'], 2) ?></td>
                    <td class="fs-5 text-danger"><?= number_format($searchResult['tong_diem'], 2) ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div class="p-3 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25">
              <div class="d-flex align-items-center gap-3">
                <i class="fas fa-check-circle text-success fs-3"></i>
                <div>
                  <div class="fw-bold text-success fs-6">KẾT QUẢ XÉT TUYỂN: <?= e($searchResult['status']) ?></div>
                  <div class="text-dark small mt-0.5">Trường: <strong><?= e($searchResult['target_school']) ?></strong> — Lớp: <strong><?= e($searchResult['target_class']) ?></strong></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

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
    fetch('<?= url('/api/captcha.php') ?>', { cache: 'no-store' })
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
