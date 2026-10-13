<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_helper.php';
require_once __DIR__ . '/../includes/candidate_service.php';
require_once __DIR__ . '/../includes/teacher_layout.php';

require_auth();

if (auth_has_role('candidate') && !auth_is_admin()) {
    http_response_code(403);
    exit('403 Forbidden - Khu vực chỉ dành cho Cán bộ - Giáo viên.');
}

$user = auth_user();
$teacherInfo = auth_get_teacher_info();
$schoolLevel = auth_teacher_level();
$schoolName = $teacherInfo['school_name'] ?? $teacherInfo['workplace'] ?? 'Trường THCS / THPT';
$schoolId = (int)($teacherInfo['school_id'] ?? 0);

$pdo = db();
$candidateId = filter_input(INPUT_GET, 'candidate_id', FILTER_VALIDATE_INT) ?: 0;
$action = trim((string)($_GET['action'] ?? ''));

$successMsg = '';
$errorMsg = '';

// Xử lý gửi yêu cầu điều chỉnh mới từ giáo viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'create' || isset($_POST['submit_adjustment']))) {
    require_csrf();
    $targetCandidateId = filter_input(INPUT_POST, 'candidate_id', FILTER_VALIDATE_INT) ?: 0;
    $reason = trim((string)($_POST['reason'] ?? ''));

    if ($targetCandidateId <= 0) {
        $errorMsg = 'Vui lòng chọn học sinh cần điều chỉnh.';
    } elseif ($reason === '') {
        $errorMsg = 'Vui lòng nhập lý do điều chỉnh.';
    } else {
        try {
            $c = candidate_find($targetCandidateId);
            if (!$c) {
                $errorMsg = 'Không tìm thấy hồ sơ học sinh.';
            } elseif ($schoolLevel === 'THCS' && $schoolId > 0 && (int)($c['middle_school_id'] ?? 0) !== $schoolId) {
                $errorMsg = 'Bạn không có quyền gửi yêu cầu điều chỉnh cho học sinh thuộc trường khác.';
            } else {
                $new = [
                    'national_id' => trim((string)($_POST['national_id'] ?? $c['national_id'])),
                    'full_name' => trim((string)($_POST['full_name'] ?? $c['full_name'])),
                    'date_of_birth' => trim((string)($_POST['date_of_birth'] ?? $c['date_of_birth'])),
                    'gender' => trim((string)($_POST['gender'] ?? $c['gender'])),
                    'address' => trim((string)($_POST['address'] ?? $c['address'])),
                    'phone' => trim((string)($_POST['phone'] ?? $c['phone'])),
                    'email' => trim((string)($_POST['email'] ?? $c['email'])),
                    'middle_school_id' => (int)($c['middle_school_id'] ?? 0),
                ];

                $old = [
                    'national_id' => $c['national_id'],
                    'full_name' => $c['full_name'],
                    'date_of_birth' => $c['date_of_birth'],
                    'gender' => $c['gender'],
                    'address' => $c['address'],
                    'phone' => $c['phone'],
                    'email' => $c['email'],
                    'middle_school_id' => $c['middle_school_id'],
                ];

                adjustment_submit($targetCandidateId, (int)$user['id'], $old, $new, $reason, null);
                $successMsg = 'Đã gửi yêu cầu điều chỉnh hồ sơ học sinh ' . $c['full_name'] . ' thành công tới Sở GD&ĐT!';
                $action = '';
            }
        } catch (Throwable $e) {
            error_log('Teacher Adjustment Submit Error: ' . $e->getMessage());
            $errorMsg = 'Gửi yêu cầu thất bại: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách yêu cầu điều chỉnh
$requests = [];
$filterStatus = trim((string)($_GET['status'] ?? ''));

try {
    $where = [];
    $params = [];

    if ($schoolLevel === 'THCS' && $schoolId > 0) {
        $where[] = 'c.middle_school_id = :school_id';
        $params['school_id'] = $schoolId;
    } elseif ($schoolLevel === 'THPT' && $schoolId > 0) {
        $where[] = 'EXISTS (SELECT 1 FROM aspirations asp WHERE asp.candidate_id = c.id AND asp.school_id = :school_id)';
        $params['school_id'] = $schoolId;
    } else {
        $where[] = '1 = 0';
    }

    if ($filterStatus !== '') {
        $where[] = 'a.status = :status';
        $params['status'] = $filterStatus;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("
        SELECT a.*, c.full_name, c.national_id, c.candidate_code, u.full_name AS submitter_name
        FROM adjustment_requests a
        JOIN candidates c ON c.id = a.candidate_id
        LEFT JOIN users u ON u.id = a.submitted_by_user_id
        $whereSql
        ORDER BY a.id DESC
        LIMIT 100
    ");
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Teacher Adjustments Fetch Error: ' . $e->getMessage());
}

// Nếu đang ở mode tạo yêu cầu cho 1 học sinh cụ thể
$selectedCandidate = null;
if ($candidateId > 0) {
    try {
        $selectedCandidate = candidate_find($candidateId);
        if ($selectedCandidate && $schoolLevel === 'THCS' && $schoolId > 0 && (int)($selectedCandidate['middle_school_id'] ?? 0) !== $schoolId) {
            $selectedCandidate = null;
            $errorMsg = 'Bạn không có quyền xem hoặc điều chỉnh hồ sơ học sinh thuộc trường khác.';
        } else {
            $action = 'create';
        }
    } catch (Throwable $e) {
        // ignore
    }
}

teacher_layout_start('Yêu cầu điều chỉnh hồ sơ', 'adjustments', ['Yêu cầu điều chỉnh' => '']);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="h4 fw-bold text-dark mb-1">
      <i class="fas fa-user-edit text-warning me-2"></i>Yêu cầu Điều chỉnh Hồ sơ Thí sinh
    </h1>
    <p class="text-muted small mb-0">
      Gửi đề xuất đính chính sai sót thông tin học sinh tới Sở GD&ĐT và theo dõi tiến độ phê duyệt.
    </p>
  </div>
  <div>
    <?php if ($action !== 'create'): ?>
      <a href="<?= e(url('/teacher/adjustments.php?action=create')) ?>" class="btn btn-warning btn-sm rounded-pill fw-semibold text-dark shadow-sm">
        <i class="fas fa-plus me-1"></i>Tạo yêu cầu điều chỉnh mới
      </a>
    <?php else: ?>
      <a href="<?= e(url('/teacher/adjustments.php')) ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fas fa-arrow-left me-1"></i>Quay lại danh sách yêu cầu
      </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($successMsg): ?>
  <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
    <i class="fas fa-check-circle me-1"></i><?= e($successMsg) ?>
  </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
  <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
    <i class="fas fa-exclamation-circle me-1"></i><?= e($errorMsg) ?>
  </div>
<?php endif; ?>

<?php if ($action === 'create'): ?>
  <!-- Form tạo yêu cầu điều chỉnh mới -->
  <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4">
      <h5 class="h6 fw-bold mb-0 text-dark">
        <i class="fas fa-pen text-warning me-2"></i>Thông tin đề xuất điều chỉnh
      </h5>
    </div>
    <div class="card-body p-4">
      <form method="POST" action="<?= e(url('/teacher/adjustments.php?action=create')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="submit_adjustment" value="1">

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label small fw-bold">Học sinh cần điều chỉnh <span class="text-danger">*</span></label>
            <?php if ($selectedCandidate): ?>
              <input type="hidden" name="candidate_id" value="<?= (int)$selectedCandidate['id'] ?>">
              <div class="form-control bg-light">
                <strong><?= e($selectedCandidate['full_name']) ?></strong> - CCCD: <?= e($selectedCandidate['national_id']) ?>
              </div>
            <?php else: ?>
              <select name="candidate_id" class="form-select" required id="candidateSelect">
                <option value="">-- Chọn học sinh trường bạn --</option>
                <?php
                $schCandStmt = $pdo->prepare('SELECT id, full_name, national_id FROM candidates WHERE middle_school_id = :sid ORDER BY full_name');
                $schCandStmt->execute(['sid' => $schoolId]);
                $cList = $schCandStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($cList as $cl):
                ?>
                  <option value="<?= (int)$cl['id'] ?>">
                    <?= e($cl['full_name']) ?> (CCCD: <?= e($cl['national_id']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Lý do điều chỉnh sai sót <span class="text-danger">*</span></label>
            <input type="text" name="reason" class="form-control" placeholder="Ví dụ: Sai ngày tháng năm sinh so với Giấy khai sinh..." required>
          </div>
        </div>

        <div class="card border rounded-3 p-3 bg-light-subtle mb-4">
          <div class="fw-bold text-dark small mb-3">Thông tin chính xác cần cập nhật:</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small text-muted">Họ và tên mới</label>
              <input type="text" name="full_name" class="form-control form-control-sm" value="<?= e($selectedCandidate['full_name'] ?? '') ?>" placeholder="Để trống nếu không đổi">
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">CCCD/CMND mới</label>
              <input type="text" name="national_id" class="form-control form-control-sm" value="<?= e($selectedCandidate['national_id'] ?? '') ?>" placeholder="Để trống nếu không đổi">
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Ngày sinh mới</label>
              <input type="date" name="date_of_birth" class="form-control form-control-sm" value="<?= e($selectedCandidate['date_of_birth'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Giới tính</label>
              <select name="gender" class="form-select form-select-sm">
                <option value="Nam" <?= ($selectedCandidate['gender'] ?? '') === 'Nam' ? 'selected' : '' ?>>Nam</option>
                <option value="Nữ" <?= ($selectedCandidate['gender'] ?? '') === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Số điện thoại liên hệ</label>
              <input type="text" name="phone" class="form-control form-control-sm" value="<?= e($selectedCandidate['phone'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Email liên hệ</label>
              <input type="email" name="email" class="form-control form-control-sm" value="<?= e($selectedCandidate['email'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label small text-muted">Hộ khẩu thường trú / Địa chỉ cư trú mới</label>
              <input type="text" name="address" class="form-control form-control-sm" value="<?= e($selectedCandidate['address'] ?? '') ?>">
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="<?= e(url('/teacher/adjustments.php')) ?>" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">Hủy</a>
          <button type="submit" class="btn btn-warning btn-sm px-4 rounded-pill fw-bold text-dark shadow-sm">
            <i class="fas fa-paper-plane me-1"></i>Gửi yêu cầu tới Sở GD&ĐT
          </button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- Danh sách yêu cầu đã gửi -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
  <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
    <div class="fw-bold text-dark small">
      Lịch sử yêu cầu điều chỉnh (<?= count($requests) ?> yêu cầu)
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= e(url('/teacher/adjustments.php')) ?>" class="badge <?= $filterStatus === '' ? 'bg-primary' : 'bg-light text-dark' ?> text-decoration-none rounded-pill px-2.5 py-1">Tất cả</a>
      <a href="<?= e(url('/teacher/adjustments.php?status=pending')) ?>" class="badge <?= $filterStatus === 'pending' ? 'bg-warning text-dark' : 'bg-light text-dark' ?> text-decoration-none rounded-pill px-2.5 py-1">Chờ duyệt</a>
      <a href="<?= e(url('/teacher/adjustments.php?status=approved')) ?>" class="badge <?= $filterStatus === 'approved' ? 'bg-success' : 'bg-light text-dark' ?> text-decoration-none rounded-pill px-2.5 py-1">Đã duyệt</a>
      <a href="<?= e(url('/teacher/adjustments.php?status=rejected')) ?>" class="badge <?= $filterStatus === 'rejected' ? 'bg-danger' : 'bg-light text-dark' ?> text-decoration-none rounded-pill px-2.5 py-1">Từ chối</a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light small">
        <tr>
          <th style="white-space: nowrap;">Mã YC</th>
          <th style="white-space: nowrap;">Học sinh</th>
          <th style="white-space: nowrap;">Lý do điều chỉnh</th>
          <th style="white-space: nowrap;">Dữ liệu thay đổi</th>
          <th style="white-space: nowrap;">Người gửi / Ngày gửi</th>
          <th class="text-center" style="white-space: nowrap;">Trạng thái</th>
          <th style="white-space: nowrap;">Phản hồi từ Sở</th>
        </tr>
      </thead>
      <tbody class="small">
        <?php foreach ($requests as $req): ?>
          <?php
            $newData = json_decode($req['new_data'] ?? '{}', true) ?: [];
            $oldData = json_decode($req['old_data'] ?? '{}', true) ?: [];
          ?>
          <tr>
            <td class="font-monospace fw-bold text-muted">#<?= (int)$req['id'] ?></td>
            <td>
              <div class="fw-bold text-dark"><?= e($req['full_name']) ?></div>
              <div class="text-muted small font-monospace">CCCD: <?= e($req['national_id']) ?></div>
            </td>
            <td>
              <div class="text-dark"><?= e($req['reason']) ?></div>
            </td>
            <td>
              <ul class="list-unstyled mb-0 small text-muted">
                <?php foreach ($newData as $k => $v): ?>
                  <?php if (!empty($v) && ($oldData[$k] ?? '') !== $v): ?>
                    <li><strong class="text-dark"><?= e($k) ?>:</strong> <?= e((string)$oldData[$k]) ?> &rarr; <span class="text-success fw-bold"><?= e((string)$v) ?></span></li>
                  <?php endif; ?>
                <?php endforeach; ?>
              </ul>
            </td>
            <td class="text-muted small" style="white-space: nowrap;">
              <div><?= e($req['submitter_name'] ?? 'Giáo viên') ?></div>
              <div class="small opacity-75"><?= e($req['created_at']) ?></div>
            </td>
            <td class="text-center" style="white-space: nowrap;">
              <?php if ($req['status'] === 'pending'): ?>
                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning rounded-pill px-2.5 py-1">
                  <i class="fas fa-clock me-1"></i>Chờ duyệt
                </span>
              <?php elseif ($req['status'] === 'approved'): ?>
                <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-2.5 py-1">
                  <i class="fas fa-check me-1"></i>Đã duyệt
                </span>
              <?php else: ?>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill px-2.5 py-1">
                  <i class="fas fa-times me-1"></i>Từ chối
                </span>
              <?php endif; ?>
            </td>
            <td class="small text-muted">
              <?php if (!empty($req['review_reason'])): ?>
                <div class="text-dark"><?= e($req['review_reason']) ?></div>
                <div class="small opacity-75"><?= e($req['reviewed_at'] ?? '') ?></div>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($requests)): ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">
              Chưa có yêu cầu điều chỉnh nào trong danh sách.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
teacher_layout_end();
