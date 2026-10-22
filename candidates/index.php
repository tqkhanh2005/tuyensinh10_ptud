<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/candidate_service.php';

require_permission('candidate.manage');

$q = trim((string)($_GET['q'] ?? ''));
$middleSchoolId = filter_input(INPUT_GET, 'middle_school_id', FILTER_VALIDATE_INT) ?: 0;
$status = trim((string)($_GET['status'] ?? ''));
$eligible = trim((string)($_GET['eligible'] ?? ''));

$middleSchools = [];
$rows = [];
$error = '';
try {
    $middleSchools = middle_schools_active_list();
    $rows = candidate_list([
        'q' => $q,
        'middle_school_id' => $middleSchoolId,
        'status' => $status,
        'eligible' => $eligible,
    ]);
} catch (Throwable $e) {
    error_log('Candidate list error: ' . $e->getMessage());
    $error = 'Không thể tải danh sách thí sinh/học sinh. Vui lòng thử lại sau.';
}

require_once __DIR__ . '/../includes/admin_layout.php';

$pageTitle = 'Quản lý thí sinh';
$activePage = 'candidates';

admin_layout_start('Quản lý thí sinh', 'candidates', ['Quản lý thí sinh' => '']);
?>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Quản lý thí sinh/học sinh</h1>
      <p class="text-muted mb-0">Quản lý hồ sơ, điều kiện dự thi và trạng thái của thí sinh.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <?php if (auth_has_permission('candidate.create')): ?>
        <a class="btn btn-outline-primary" href="<?= e(url('/candidates/template.php')) ?>">
          Tải file mẫu
        </a>
        <a class="btn btn-outline-success" href="<?= e(url('/candidates/import.php')) ?>">
          Nhập danh sách CSV
        </a>
        <a class="btn btn-primary fw-medium" href="<?= e(url('/candidates/create.php')) ?>">
          Thêm thí sinh
        </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger mb-4"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      Thêm thí sinh/học sinh thành công.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  <?php elseif (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      Cập nhật thông tin thí sinh/học sinh thành công.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      Đã chuyển thí sinh/học sinh sang trạng thái không hoạt động.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  <?php elseif (isset($_GET['imported'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      Nhập dữ liệu thành công. Đã thêm <strong><?= (int)$_GET['imported'] ?></strong> thí sinh/học sinh mới vào hệ thống.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  <?php elseif (isset($_GET['delete_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
      Không thể xóa thí sinh/học sinh. Hồ sơ có thể đang có dữ liệu liên quan.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  <?php endif; ?>

  <!-- Bộ lọc tìm kiếm -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
    <div class="card-body p-3 p-md-4">
      <form method="get" action="<?= e(url('/candidates/index.php')) ?>" class="row g-3">
        <div class="col-md-6 col-lg-3">
          <label for="q" class="form-label small fw-semibold text-muted mb-2">Từ khóa tìm kiếm</label>
          <input type="text" class="form-control mb-0" id="q" name="q" placeholder="CCCD, họ tên, email, SĐT..." value="<?= e($q) ?>">
        </div>
        <div class="col-md-6 col-lg-3">
          <label for="middle_school_id" class="form-label small fw-semibold text-muted mb-2">Trường THCS</label>
          <select class="form-select mb-0" id="middle_school_id" name="middle_school_id">
            <option value="">Tất cả trường THCS</option>
            <?php foreach ($middleSchools as $sch): ?>
              <option value="<?= (int)$sch['id'] ?>" <?= $middleSchoolId === (int)$sch['id'] ? 'selected' : '' ?>>
                <?= e($sch['name']) ?> (<?= e($sch['school_code']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4 col-lg-2">
          <label for="status" class="form-label small fw-semibold text-muted mb-2">Trạng thái</label>
          <select class="form-select mb-0" id="status" name="status">
            <option value="">Tất cả trạng thái</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Hoạt động</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Không hoạt động</option>
          </select>
        </div>
        <div class="col-md-4 col-lg-2">
          <label for="eligible" class="form-label small fw-semibold text-muted mb-2">Đủ điều kiện dự thi</label>
          <select class="form-select mb-0" id="eligible" name="eligible">
            <option value="">Tất cả</option>
            <option value="1" <?= $eligible === '1' ? 'selected' : '' ?>>Có</option>
            <option value="0" <?= $eligible === '0' ? 'selected' : '' ?>>Không</option>
          </select>
        </div>
        <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary border border-primary fw-medium px-3 mt-0 mb-0">Tìm kiếm</button>
          <?php if ($q !== '' || $middleSchoolId > 0 || $status !== '' || $eligible !== ''): ?>
            <a class="btn btn-outline-secondary mt-0 mb-0" href="<?= e(url('/candidates/index.php')) ?>">Đặt lại</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
      <h5 class="mb-0 fw-bold text-dark">
        Danh sách thí sinh/học sinh
      </h5>
      <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">
        Tổng số: <?= count($rows) ?>
      </span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 portal-data-table">
        <thead class="table-light">
          <tr>
            <th style="width: 16%;">CCCD/CMND</th>
            <th style="width: 26%;">Họ và tên</th>
            <th style="width: 22%;">Trường THCS</th>
            <th class="text-center" style="width: 10%;">Đủ điều kiện</th>
            <th style="width: 12%;">Trạng thái</th>
            <th class="text-end" style="width: 14%;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php $isActive = ($row['status'] ?? '') === 'active'; ?>
            <tr>
              <td class="font-monospace fw-semibold"><?= e($row['national_id']) ?></td>
              <td>
                <div class="fw-semibold text-dark"><?= e($row['full_name']) ?></div>
                <?php if (!empty($row['email'])): ?>
                  <div class="small text-muted"><?= e($row['email']) ?></div>
                <?php endif; ?>
              </td>
              <td class="text-muted"><?= e($row['middle_school_name'] ?? 'Chưa cập nhật') ?></td>
              <td class="text-center">
                <?php if ((int) $row['eligible'] === 1): ?>
                  <span class="badge bg-success bg-opacity-10 text-success rounded-pill">Có</span>
                <?php else: ?>
                  <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill">Không</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge <?= $isActive ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' ?> rounded-pill">
                  <?= $isActive ? 'Hoạt động' : 'Không hoạt động' ?>
                </span>
              </td>
              <td class="text-end text-nowrap">
                <?php if ($isActive && auth_has_permission('candidate.update')): ?>
                  <a class="btn btn-sm btn-outline-primary me-1 px-2.5" href="<?= e(url('/candidates/edit.php?id=' . (int) $row['id'])) ?>">
                    Sửa
                  </a>
                <?php endif; ?>
                <?php if ($isActive && auth_has_permission('candidate.delete')): ?>
                  <form method="post" action="<?= e(url('/candidates/delete.php')) ?>" class="d-inline" onsubmit="return confirm('Xác nhận chuyển thí sinh/học sinh sang trạng thái không hoạt động?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger px-2.5">
                      Xóa
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <h6 class="fw-bold text-secondary mb-1">Chưa có thí sinh/học sinh</h6>
                <p class="small mb-0">Hãy thêm hồ sơ mới để bắt đầu quản lý.</p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php
admin_layout_end();
