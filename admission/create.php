<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';
require_once __DIR__ . '/../includes/admin_layout.php';

require_permission('admission.manage');

$pdo = db();
$errors = [];
$data = [
    'name' => '',
    'academic_year' => '',
    'admission_method' => 'Thi tuyển',
    'status' => 'draft',
    'registration_start' => '',
    'registration_end' => '',
    'plan_content' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $data = array_merge($data, $_POST);

    $name = trim((string)($data['name'] ?? ''));
    $academicYear = trim((string)($data['academic_year'] ?? ''));
    $admissionMethod = trim((string)($data['admission_method'] ?? ''));
    $status = (string)($data['status'] ?? 'draft');
    $planContent = trim((string)($data['plan_content'] ?? ''));
    $regStart = trim((string)($data['registration_start'] ?? ''));
    $regEnd = trim((string)($data['registration_end'] ?? ''));

    if ($name === '') {
        $errors['name'] = 'Tên kế hoạch tuyển sinh là bắt buộc.';
    }

    if ($academicYear === '') {
        $errors['academic_year'] = 'Năm học là bắt buộc (ví dụ: 2026-2027).';
    }

    if (!in_array($status, ['draft', 'published', 'closed'], true)) {
        $errors['status'] = 'Trạng thái kế hoạch không hợp lệ.';
    }

    if ($regStart !== '' && $regEnd !== '' && $regStart > $regEnd) {
        $errors['registration_end'] = 'Thời gian kết thúc đăng ký phải sau thời gian bắt đầu đăng ký.';
    }

    if (empty($errors)) {
        try {
            admission_period_create($pdo, [
                'name' => $name,
                'academic_year' => $academicYear,
                'admission_method' => $admissionMethod !== '' ? $admissionMethod : null,
                'plan_content' => $planContent !== '' ? $planContent : null,
                'registration_start' => $regStart !== '' ? $regStart : null,
                'registration_end' => $regEnd !== '' ? $regEnd : null,
                'status' => $status,
            ]);

            header('Location: ' . url('/admission/index.php'));
            exit;
        } catch (Throwable $e) {
            error_log('admission_period_create error: ' . $e->getMessage());
            $errors['general'] = 'Không thể lưu kế hoạch tuyển sinh. Vui lòng kiểm tra lại thông tin và thử lại.';
        }
    }
}

admin_layout_start(
    'Thiết lập kế hoạch tuyển sinh',
    'admission',
    [
        'Xét tuyển' => url('/admission/index.php'),
        'Thiết lập kế hoạch' => '',
    ]
);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Thiết lập kế hoạch tuyển sinh</h1>
    <p class="text-muted mb-0">Khởi tạo kế hoạch và kỳ tuyển sinh vào lớp 10 mới cho năm học.</p>
  </div>
  <a class="btn btn-outline-secondary" href="<?= e(url('/admission/index.php')) ?>">
    <i class="fas fa-arrow-left me-1.5"></i> Quay lại danh sách
  </a>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-2 mb-2 fw-bold">
      <i class="fas fa-exclamation-triangle"></i> Vui lòng sửa các lỗi sau để tiếp tục:
    </div>
    <ul class="mb-0 ps-3">
      <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
      <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
  </div>
<?php endif; ?>

<form method="post" action="<?= e(url('/admission/create.php')) ?>">
  <?= csrf_field() ?>

  <!-- 1. Thông tin kế hoạch -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center gap-2">
      <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
        <i class="fas fa-calendar-check small"></i>
      </div>
      <h6 class="mb-0 fw-bold text-dark">1. Thông tin kế hoạch</h6>
    </div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">
            Tên kế hoạch tuyển sinh <span class="text-danger">*</span>
          </label>
          <input type="text"
                 name="name"
                 class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                 required
                 value="<?= e((string)($data['name'] ?? '')) ?>"
                 placeholder="Ví dụ: Kỳ thi tuyển sinh vào lớp 10 THPT năm học 2026-2027" />
          <?php if (isset($errors['name'])): ?>
            <div class="invalid-feedback"><?= e($errors['name']) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">
            Năm học <span class="text-danger">*</span>
          </label>
          <input type="text"
                 name="academic_year"
                 class="form-control <?= isset($errors['academic_year']) ? 'is-invalid' : '' ?>"
                 required
                 value="<?= e((string)($data['academic_year'] ?? '')) ?>"
                 placeholder="Ví dụ: 2026-2027" />
          <?php if (isset($errors['academic_year'])): ?>
            <div class="invalid-feedback"><?= e($errors['academic_year']) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Phương thức tuyển sinh</label>
          <input type="text"
                 name="admission_method"
                 list="methodList"
                 class="form-control"
                 value="<?= e((string)($data['admission_method'] ?? 'Thi tuyển')) ?>"
                 placeholder="Ví dụ: Thi tuyển" />
          <datalist id="methodList">
            <option value="Thi tuyển">
            <option value="Xét tuyển">
            <option value="Kết hợp thi tuyển và xét tuyển">
          </datalist>
          <div class="form-text">Có thể chọn từ gợi ý hoặc tự nhập phương thức.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Trạng thái kế hoạch</label>
          <select name="status" class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>">
            <option value="draft" <?= ($data['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Bản nháp</option>
            <option value="published" <?= ($data['status'] ?? '') === 'published' ? 'selected' : '' ?>>Đang công bố</option>
            <option value="closed" <?= ($data['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Đã đóng</option>
          </select>
          <?php if (isset($errors['status'])): ?>
            <div class="invalid-feedback"><?= e($errors['status']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Thời gian đăng ký -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center gap-2">
      <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
        <i class="far fa-clock small"></i>
      </div>
      <h6 class="mb-0 fw-bold text-dark">2. Thời gian đăng ký</h6>
    </div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Bắt đầu đăng ký</label>
          <input type="datetime-local"
                 name="registration_start"
                 class="form-control <?= isset($errors['registration_start']) ? 'is-invalid' : '' ?>"
                 value="<?= e((string)($data['registration_start'] ?? '')) ?>" />
          <?php if (isset($errors['registration_start'])): ?>
            <div class="invalid-feedback"><?= e($errors['registration_start']) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Kết thúc đăng ký</label>
          <input type="datetime-local"
                 name="registration_end"
                 class="form-control <?= isset($errors['registration_end']) ? 'is-invalid' : '' ?>"
                 value="<?= e((string)($data['registration_end'] ?? '')) ?>" />
          <?php if (isset($errors['registration_end'])): ?>
            <div class="invalid-feedback"><?= e($errors['registration_end']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Nội dung kế hoạch -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center gap-2">
      <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
        <i class="far fa-file-alt small"></i>
      </div>
      <h6 class="mb-0 fw-bold text-dark">3. Nội dung kế hoạch</h6>
    </div>
    <div class="card-body p-4">
      <label class="form-label small fw-semibold text-secondary">Nội dung chi tiết</label>
      <textarea name="plan_content"
                class="form-control"
                rows="6"
                placeholder="Nhập nội dung kế hoạch tuyển sinh, quy chế, đối tượng, phân bổ chỉ tiêu tổng quát hoặc ghi chú..."><?= e((string)($data['plan_content'] ?? '')) ?></textarea>
    </div>
  </div>

  <!-- Form Actions -->
  <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center">
      <a href="<?= e(url('/admission/index.php')) ?>" class="btn btn-outline-secondary px-3">
        <i class="fas fa-times me-1.5"></i> Hủy / Quay lại danh sách
      </a>
      <button type="submit" class="btn btn-primary px-4 fw-semibold">
        <i class="fas fa-save me-1.5"></i> Lưu kế hoạch
      </button>
    </div>
  </div>
</form>

<?php
admin_layout_end();
