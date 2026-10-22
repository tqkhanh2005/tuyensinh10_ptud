<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_admission_service.php';

require_permission('school.manage');

$q = trim((string)($_GET['q'] ?? ''));
$level = (string)($_GET['level'] ?? '');
$area = trim((string)($_GET['area'] ?? ''));

$schools = [];
$error = '';
try {
    $pdo = db();
    $schools = school_list($pdo, $q, $level, $area);
} catch (Throwable $e) {
    error_log('School list error: ' . $e->getMessage());
    $error = 'Không thể tải danh sách trường học. Vui lòng thử lại sau.';
}

$success = '';
if (isset($_GET['created'])) {
    $success = 'Thêm trường học mới thành công.';
} elseif (isset($_GET['updated'])) {
    $success = 'Cập nhật thông tin trường học thành công.';
} elseif (isset($_GET['deleted'])) {
    $success = 'Xóa trường học thành công.';
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Quản lý trường học';
$activePage = 'schools';

admin_layout_start('Quản lý trường học', 'schools', ['Quản lý trường học' => '']);
?>
  <!-- Tiêu đề trang & Nút thêm trường -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Quản lý trường học</h1>
      <p class="text-muted mb-0">Danh sách các trường THCS và THPT trong hệ thống tuyển sinh.</p>
    </div>
    <?php if (auth_has_permission('school.manage')): ?>
      <a class="btn btn-primary fw-medium px-3" href="<?= e(url('/schools/create.php')) ?>">
        Thêm trường
      </a>
    <?php endif; ?>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      <?= e($success) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger mb-4"><?= e($error) ?></div>
  <?php endif; ?>

  <!-- Bộ lọc tìm kiếm -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
    <div class="card-body p-3 p-md-4">
      <form method="get" action="<?= e(url('/schools/index.php')) ?>" class="row g-3">
        <div class="col-md-5 col-lg-5">
          <label for="q" class="form-label small fw-semibold text-muted mb-2">Từ khóa tìm kiếm</label>
          <input type="text" class="form-control mb-0" id="q" name="q" placeholder="Mã trường, tên trường, quận/huyện..." value="<?= e($q) ?>">
        </div>
        <div class="col-md-4 col-lg-3">
          <label for="level" class="form-label small fw-semibold text-muted mb-2">Cấp học</label>
          <select class="form-select mb-0" id="level" name="level">
            <option value="">Tất cả cấp học</option>
            <option value="THCS" <?= $level === 'THCS' ? 'selected' : '' ?>>THCS</option>
            <option value="THPT" <?= $level === 'THPT' ? 'selected' : '' ?>>THPT</option>
          </select>
        </div>
        <?php if ($area !== ''): ?>
          <input type="hidden" name="area" value="<?= e($area) ?>">
        <?php endif; ?>
        <div class="col-md-3 col-lg-4 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary border border-primary fw-medium px-4 mt-0 mb-0">Tìm kiếm</button>
          <?php if ($q !== '' || $level !== '' || $area !== ''): ?>
            <a class="btn btn-outline-secondary mt-0 mb-0" href="<?= e(url('/schools/index.php')) ?>">Đặt lại</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Bảng danh sách trường -->
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
      <h5 class="mb-0 fw-bold text-dark">
        Danh sách trường học
      </h5>
      <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">
        Tổng số: <?= count($schools) ?> trường
      </span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 portal-data-table">
        <thead class="table-light">
          <tr>
            <th style="width: 16%;">Mã trường</th>
            <th style="width: 32%;">Tên trường</th>
            <th style="width: 10%;">Cấp học</th>
            <th style="width: 14%;">Địa bàn</th>
            <th style="width: 12%;">Khu vực</th>
            <th class="text-end" style="width: 16%;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($schools as $school): ?>
            <tr>
              <td class="fw-bold text-dark font-monospace"><?= e($school['school_code']) ?></td>
              <td>
                <div class="fw-semibold text-dark"><?= e($school['name']) ?></div>
                <?php if (!empty($school['address'])): ?>
                  <div class="text-muted small"><?= e($school['address']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge <?= $school['level'] === 'THPT' ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-info bg-opacity-10 text-info border border-info border-opacity-25' ?> px-2 py-1 rounded-pill small fw-semibold">
                  <?= e($school['level']) ?>
                </span>
              </td>
              <td class="text-muted"><?= e($school['district'] ?? '') ?></td>
              <td class="text-muted"><?= e($school['admission_area'] ?? '') ?></td>
              <td class="text-end text-nowrap">
                <?php if (auth_has_permission('school.manage')): ?>
                  <a class="btn btn-sm btn-outline-primary me-1 px-2.5" href="<?= e(url('/schools/edit.php?id=' . (int)$school['id'])) ?>">
                    Sửa
                  </a>
                  <a class="btn btn-sm btn-outline-danger px-2.5" href="<?= e(url('/schools/delete.php?id=' . (int)$school['id'])) ?>">
                    Xóa
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$schools): ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <div class="py-3">
                  <h6 class="fw-bold text-secondary mb-1">Không tìm thấy trường học nào</h6>
                  <p class="text-muted small mb-0">Thử thay đổi từ khóa tìm kiếm hoặc bộ lọc cấp học.</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php
admin_layout_end();

