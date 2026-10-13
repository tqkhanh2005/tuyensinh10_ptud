<?php
/**
 * Trang Tra cứu kết quả Tuyển sinh 10
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/publication_service.php';

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
        try {
            $pdo = db();
            $candidateStmt = $pdo->prepare(
                'SELECT id, candidate_code, national_id, full_name, date_of_birth, gender,
                        exam_number, exam_site_name, exam_room_name, exam_schedule
                 FROM candidates
                 WHERE national_id = :identifier
                    OR candidate_code = :identifier
                    OR exam_number = :identifier
                 LIMIT 1'
            );
            $candidateStmt->execute(['identifier' => $cccd]);
            $candidate = $candidateStmt->fetch(PDO::FETCH_ASSOC);

            if (!$candidate) {
                $searchError = 'Không tìm thấy hồ sơ phù hợp với thông tin tra cứu.';
            } else {
                $periodId = current_admission_period_id($pdo);
                $examRows = exam_results_published($pdo, $periodId)
                    ? public_exam_results($pdo, $periodId, (int)$candidate['id'])
                    : [];
                $admissionRows = admission_results_published($pdo, $periodId)
                    ? public_admission_results($pdo, $periodId, (int)$candidate['id'])
                    : [];

                if (!$examRows && !$admissionRows) {
                    $searchError = 'Kết quả của kỳ tuyển sinh hiện chưa được công bố.';
                } else {
                    $searchResult = [
                        'candidate' => $candidate,
                        'period_id' => $periodId,
                        'exam_rows' => $examRows,
                        'admission_rows' => $admissionRows,
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log('Public result lookup failed: ' . $e->getMessage());
            $searchError = 'Không thể thực hiện tra cứu lúc này. Vui lòng thử lại sau.';
        } catch (RuntimeException $e) {
            $searchError = 'Kết quả của kỳ tuyển sinh hiện chưa được công bố.';
        }
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main class="container py-5" style="margin-top: 5rem;">
  <div class="row justify-content-center">
    <div class="col-lg-9 col-xl-8">

      <!-- Header tiêu đề trang -->
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 72px; height: 72px;">
          <i class="fas fa-graduation-cap fa-2x"></i>
        </div>
        <h1 class="h2 fw-bold text-dark mb-2">Tra cứu Kết quả Tuyển sinh Lớp 10</h1>
        <p class="text-muted mb-0">Hệ thống tra cứu điểm thi và kết quả xét tuyển chính thức của Sở Giáo dục và Đào tạo</p>
      </div>

      <!-- Thông báo lỗi (nếu có) -->
      <?php if ($searchError): ?>
        <div class="alert alert-danger bg-danger bg-opacity-10 border-0 text-danger rounded-4 p-3 d-flex align-items-center gap-3 mb-4 shadow-sm" role="alert">
          <i class="fas fa-exclamation-circle fs-3 flex-shrink-0"></i>
          <div>
            <div class="fw-bold">Tra cứu không thành công</div>
            <div class="small"><?= e($searchError) ?></div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Card Form tra cứu -->
      <div class="card border-0 shadow-sm rounded-4 bg-white p-4 p-md-5 mb-4">
        <form method="post" action="<?= url('/ket-qua.php') ?>">
          <!-- Nhập SBD hoặc CCCD -->
          <div class="mb-4">
            <label for="input-cccd" class="form-label fw-bold text-dark mb-2">
              <i class="fas fa-id-card me-2 text-primary"></i>Số báo danh hoặc CCCD / Mã định danh cá nhân <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-lg">
              <span class="input-group-text bg-light border-end-0 text-muted">
                <i class="fas fa-search"></i>
              </span>
              <input
                type="text"
                id="input-cccd"
                name="cccd"
                class="form-control border-start-0 ps-0 fs-6"
                placeholder="Nhập Số báo danh (VD: 010234) hoặc 12 số CCCD"
                pattern="[a-zA-Z0-9]+"
                title="Chỉ chấp nhận chữ cái và số"
                value="<?= e($_POST['cccd'] ?? '') ?>"
                autocomplete="off"
                required />
            </div>
            <div class="form-text text-muted small mt-2">
              <i class="fas fa-info-circle me-1"></i>Thí sinh có thể tra cứu bằng <strong>Số báo danh</strong> ghi trên Thẻ dự thi hoặc số <strong>CCCD / Mã định danh</strong> đã đăng ký.
            </div>
          </div>

          <!-- Mã xác nhận CAPTCHA -->
          <div class="mb-4 p-3 rounded-4 bg-light border" id="captcha-section">
            <label class="form-label fw-bold text-dark small mb-2 d-flex justify-content-between align-items-center">
              <span><i class="fas fa-shield-alt me-1 text-primary"></i>Mã xác nhận bảo mật <span class="text-danger">*</span></span>
              <span class="text-muted fw-normal">Hiệu lực: <span id="captcha-timer" class="fw-semibold text-primary">120s</span></span>
            </label>
            <div class="row g-2 align-items-center">
              <div class="col-sm-5">
                <input
                  type="text"
                  name="captchaText"
                  class="form-control form-control-lg fs-6 text-center text-uppercase fw-bold"
                  placeholder="Nhập 6 ký tự"
                  maxlength="6"
                  autocomplete="off"
                  required />
              </div>
              <div class="col-sm-4 text-center">
                <div id="captcha-image" class="rounded overflow-hidden d-inline-block border bg-white shadow-sm" style="min-width: 130px; height: 42px; line-height: 42px;"></div>
              </div>
              <div class="col-sm-3 text-end">
                <button type="button" id="captcha-reload" class="btn btn-outline-secondary w-100 py-2 d-flex align-items-center justify-content-center gap-1" title="Lấy mã xác nhận mới">
                  <i class="fas fa-sync-alt"></i>
                  <span class="small">Đổi mã</span>
                </button>
              </div>
            </div>
            <input type="hidden" name="captchaToken" id="captcha-token" value="" />
          </div>

          <!-- Nút hành động Tra cứu -->
          <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold fs-6 shadow-sm">
            <i class="fas fa-search me-2"></i>Tra cứu kết quả
          </button>
        </form>
      </div>

      <!-- Kết quả tra cứu (khi có $searchResult) -->
      <?php if ($searchResult): ?>
        <?php
          $candidate = $searchResult['candidate'];
          $admissionRows = $searchResult['admission_rows'];
          $admittedRow = null;
          foreach ($admissionRows as $admissionRow) {
              if (($admissionRow['result_status'] ?? '') === 'admitted') {
                  $admittedRow = $admissionRow;
                  break;
              }
          }
          $resultLabel = $admittedRow
              ? 'TRÚNG TUYỂN NGUYỆN VỌNG ' . (int)$admittedRow['priority_order']
              : ($admissionRows ? 'CHƯA TRÚNG TUYỂN' : 'ĐIỂM THI ĐÃ CÔNG BỐ');
        ?>
        <div class="card border-0 shadow rounded-4 overflow-hidden bg-white mb-5">
          <div class="card-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
              <i class="fas fa-id-card me-2"></i>KẾT QUẢ TUYỂN SINH LỚP 10
            </h5>
            <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-bold">Chính thức</span>
          </div>
          <div class="card-body p-4 p-md-5">
            <!-- Thông tin cá nhân thí sinh -->
            <div class="row g-3 mb-4 p-3 rounded-4 bg-light">
              <div class="col-md-6">
                <div class="text-muted small">Họ và tên thí sinh</div>
                <div class="fw-bold fs-5 text-dark"><?= e($candidate['full_name']) ?></div>
                <div class="text-muted small mt-1">Mã thí sinh: <span class="fw-semibold text-dark"><?= e($candidate['candidate_code']) ?></span></div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted small">Số báo danh</div>
                <div class="fw-bold fs-5 text-primary"><?= e($candidate['exam_number'] ?: 'Chưa cấp') ?></div>
                <div class="text-muted small mt-1">Hội đồng thi:</div>
                <div class="small fw-semibold text-truncate text-dark" title="<?= e($candidate['exam_site_name']) ?>"><?= e($candidate['exam_site_name'] ?: 'Chưa phân bổ') ?></div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted small">Số CCCD / Định danh</div>
                <div class="fw-bold fs-6 text-dark"><?= e($candidate['national_id']) ?></div>
                <div class="text-muted small mt-1">Ngày sinh: <span class="fw-semibold text-dark"><?= e($candidate['date_of_birth'] ?: 'Chưa cập nhật') ?></span></div>
              </div>
            </div>
            
            <!-- Bảng điểm chi tiết -->
            <?php if ($searchResult['exam_rows']): ?>
            <div class="mb-4">
              <h6 class="fw-bold text-dark mb-3"><i class="fas fa-table me-2 text-primary"></i>Điểm các môn thi</h6>
              <div class="table-responsive">
                <table class="table table-bordered text-center align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="py-3">Mã môn</th>
                      <th class="py-3">Môn thi</th>
                      <th class="py-3 bg-primary bg-opacity-10 text-primary">Điểm</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($searchResult['exam_rows'] as $examRow): ?>
                      <tr class="fw-bold fs-6">
                        <td class="text-dark py-3"><?= e($examRow['subject_code']) ?></td>
                        <td class="text-dark py-3"><?= e($examRow['subject_name']) ?></td>
                        <td class="fs-5 text-primary bg-primary bg-opacity-10 py-3"><?= number_format((float)$examRow['total_score'], 2) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <?php endif; ?>
            
            <!-- Kết quả xét tuyển nguyện vọng -->
            <?php if ($admissionRows): ?>
            <div class="p-4 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25">
              <div class="d-flex align-items-center gap-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle flex-shrink-0" style="width: 48px; height: 48px;">
                  <i class="fas fa-check fs-4"></i>
                </div>
                <div>
                  <div class="fw-bold text-success fs-5">KẾT QUẢ XÉT TUYỂN: <?= e($resultLabel) ?></div>
                  <?php if ($admittedRow): ?>
                    <div class="text-dark mt-1">
                      Trường trúng tuyển: <strong><?= e($admittedRow['school_name']) ?></strong>
                      — Điểm xét tuyển: <strong><?= number_format((float)$admittedRow['admission_score'], 2) ?></strong>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      <?php elseif (!$searchError): ?>
        <!-- Hướng dẫn tra cứu (Empty State ban đầu khi chưa nhập) -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-5">
          <h6 class="fw-bold text-dark mb-3"><i class="fas fa-question-circle me-2 text-primary"></i>Hướng dẫn tra cứu kết quả thi</h6>
          <div class="row g-3">
            <div class="col-md-4">
              <div class="d-flex gap-3 align-items-start">
                <div class="badge bg-primary rounded-circle p-2 px-2.5">1</div>
                <div>
                  <div class="fw-bold small text-dark">Nhập SBD hoặc CCCD</div>
                  <div class="text-muted small">Điền chính xác số báo danh hoặc 12 chữ số CCCD.</div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="d-flex gap-3 align-items-start">
                <div class="badge bg-primary rounded-circle p-2 px-2.5">2</div>
                <div>
                  <div class="fw-bold small text-dark">Nhập Mã xác nhận</div>
                  <div class="text-muted small">Nhập 6 ký tự hiển thị trên hình ảnh bảo mật.</div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="d-flex gap-3 align-items-start">
                <div class="badge bg-primary rounded-circle p-2 px-2.5">3</div>
                <div>
                  <div class="fw-bold small text-dark">Xem kết quả</div>
                  <div class="text-muted small">Bấm Tra cứu để hiển thị bảng điểm và nguyện vọng.</div>
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
