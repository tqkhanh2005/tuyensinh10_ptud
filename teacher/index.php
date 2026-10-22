<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_helper.php';
require_once __DIR__ . '/../includes/teacher_layout.php';

require_teacher_access();

// Thí sinh không được phép truy cập khu vực giáo viên
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
require_once __DIR__.'/../includes/teacher_reporting_service.php';
$periods=$pdo->query('SELECT id,name FROM admission_periods ORDER BY (status="published") DESC,id DESC')->fetchAll();$periodId=(int)($_GET['period_id']??($periods[0]['id']??0));
$schoolStats = [
    'students_total' => 0,
    'students_eligible' => 0,
    'adjustments_pending' => 0,
    'school_quota' => 0,
];

try {
    if ($schoolLevel === 'THCS' && $schoolId > 0) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM candidates WHERE middle_school_id = :sid AND status = "active"');
        $st->execute(['sid' => $schoolId]);
        $schoolStats['students_total'] = (int)$st->fetchColumn();

        $stE = $pdo->prepare('SELECT COUNT(*) FROM candidates WHERE middle_school_id = :sid AND status = "active" AND eligible = 1');
        $stE->execute(['sid' => $schoolId]);
        $schoolStats['students_eligible'] = (int)$stE->fetchColumn();

        $adjSt = $pdo->prepare('
            SELECT COUNT(*) 
            FROM adjustment_requests ar 
            JOIN candidates c ON c.id = ar.candidate_id 
            WHERE c.middle_school_id = :sid AND ar.status = "pending"
        ');
        $adjSt->execute(['sid' => $schoolId]);
        $schoolStats['adjustments_pending'] = (int)$adjSt->fetchColumn();
    } elseif ($schoolLevel === 'THPT' && $schoolId > 0) {
        $r=teacher_period_report($pdo,$schoolId,'THPT',$periodId)['summary'];
        $schoolStats['students_total']=$r['total_candidates'];$schoolStats['students_eligible']=$r['eligible_count'];$schoolStats['school_quota']=$r['quota'];
        $q=$pdo->prepare('SELECT COUNT(*) FROM adjustment_requests ar WHERE ar.status="pending" AND EXISTS(SELECT 1 FROM aspirations a WHERE a.candidate_id=ar.candidate_id AND a.school_id=? AND a.admission_period_id=?)');$q->execute([$schoolId,$periodId]);$schoolStats['adjustments_pending']=(int)$q->fetchColumn();
    }
} catch (Throwable $e) {
    error_log('Teacher Dashboard Stats Error: ' . $e->getMessage());
}

// Lấy danh sách học sinh tiêu biểu (nếu có school_id thì lấy của trường, nếu không lấy chung)
$students = [];
try {
    if ($schoolId > 0 && $schoolLevel === 'THCS') {
        $stmt = $pdo->prepare('
            SELECT id, national_id, full_name, date_of_birth, gender, eligible, status
            FROM candidates
            WHERE middle_school_id = :sid
            ORDER BY id DESC
            LIMIT 6
        ');
        $stmt->execute(['sid' => $schoolId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($schoolId > 0 && $schoolLevel === 'THPT') {
        $stmt=$pdo->prepare('SELECT c.id,c.national_id,c.full_name,c.date_of_birth,c.gender,c.eligible,c.status FROM candidates c WHERE EXISTS(SELECT 1 FROM aspirations a WHERE a.candidate_id=c.id AND a.school_id=? AND a.admission_period_id=?) ORDER BY c.id DESC LIMIT 6');$stmt->execute([$schoolId,$periodId]);$students=$stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('Teacher Dashboard Students Error: ' . $e->getMessage());
}

teacher_layout_start('Tổng quan Giáo viên', 'teacher-dashboard');
?>

<!-- Welcome Banner -->
<div class="card border-0 shadow-sm rounded-4 mb-4 text-white overflow-hidden" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
  <div class="card-body p-4 p-md-5 position-relative">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <span class="badge bg-white text-success text-wrap mw-100 lh-base px-3 py-1.5 rounded-pill mb-3 fw-semibold">
          <i class="fas fa-chalkboard-teacher me-1.5"></i> CỔNG DÀNH CHO CÁN BỘ - GIÁO VIÊN
        </span>
        <h2 class="h3 fw-bold mb-2">Xin chào, Thầy/Cô <?= e($user['full_name'] ?? 'Giáo viên') ?>!</h2>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
          <span class="badge bg-dark bg-opacity-25 px-2.5 py-1 rounded-pill small">
            <i class="fas fa-school me-1"></i> <?= e($schoolName) ?>
          </span>
          <span class="badge bg-dark bg-opacity-25 px-2.5 py-1 rounded-pill small">
            <i class="fas fa-layer-group me-1"></i> Cấp <?= e($schoolLevel) ?>
          </span>
          <?php if (!empty($teacherInfo['staff_role'])): ?>
            <span class="badge bg-white text-success fw-bold px-2.5 py-1 rounded-pill small">
              <?= e($teacherInfo['staff_role']) ?>
            </span>
          <?php endif; ?>
        </div>
        <p class="mb-0 text-white-50 fs-6">
          Hệ thống theo dõi hồ sơ học sinh, tra cứu điều kiện dự thi, đăng ký yêu cầu điều chỉnh và theo dõi kết quả tuyển sinh vào Lớp 10.
        </p>
      </div>
      <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
        <a href="<?= e(url('/teacher/students.php')) ?>" class="btn btn-light fw-bold px-4 py-2.5 rounded-pill shadow-sm me-2 text-success">
          <i class="fas fa-users me-1.5"></i> Danh sách học sinh
        </a>
      </div>
    </div>
  </div>
</div>

<?php if($schoolLevel==='THPT'):?><form method="get" class="mb-3"><label class="form-label w-100">Kỳ tuyển sinh<select class="form-select" name="period_id"><?php foreach($periods as $p):?><option value="<?=$p['id']?>" <?=$periodId===(int)$p['id']?'selected':''?>><?=e($p['name'])?></option><?php endforeach;?></select></label><button class="btn btn-outline-primary">Xem</button></form><?php endif;?>
<?php if(($cfg=period_configuration($pdo,$periodId)) && $cfg['settings']['mode']==='simulation'):?><div class="alert alert-warning">MÔ PHỎNG — Không phải thông tin tuyển sinh chính thức.</div><?php endif;?>
<!-- Stats Row -->
<div class="row g-3 g-md-4 mb-4">
  <div class="col-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
      <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="text-muted fw-semibold small"><?=$schoolLevel==='THPT'?'Hồ sơ đăng ký kỳ này':'Tổng hồ sơ học sinh'?></span>
          <div class="rounded-3 bg-success bg-opacity-10 text-success p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="fas fa-user-graduate fs-5"></i>
          </div>
        </div>
        <div class="h3 fw-bold text-dark mb-1"><?= number_format($schoolStats['students_total']) ?></div>
        <div class="small text-muted">
          <?php if ($schoolId > 0 && $schoolLevel === 'THCS'): ?>
            <span>Thuộc trường THCS của bạn</span>
          <?php elseif ($schoolId > 0 && $schoolLevel === 'THPT'): ?>
            <span>Đăng ký xét tuyển vào trường</span>
          <?php else: ?>
            <span>Tổng trên toàn hệ thống</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
      <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="text-muted fw-semibold small">Đủ điều kiện dự thi</span>
          <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="fas fa-check-circle fs-5"></i>
          </div>
        </div>
        <div class="h3 fw-bold text-dark mb-1"><?= number_format($schoolStats['students_eligible']) ?></div>
        <div class="small text-muted">
          <span class="text-success fw-semibold"><?= $schoolStats['students_total'] > 0 ? round(($schoolStats['students_eligible'] / $schoolStats['students_total']) * 100, 1) : 0 ?>%</span> hồ sơ hợp lệ
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
      <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="text-muted fw-semibold small">Yêu cầu điều chỉnh</span>
          <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="fas fa-user-edit fs-5"></i>
          </div>
        </div>
        <div class="h3 fw-bold text-dark mb-1"><?= number_format($schoolStats['adjustments_pending']) ?></div>
        <div class="small text-muted">
          <a href="<?= e(url('/teacher/adjustments.php')) ?>" class="text-decoration-none text-warning fw-semibold">
            Xem yêu cầu chờ duyệt <i class="fas fa-arrow-right ms-1 small"></i>
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
      <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="text-muted fw-semibold small">Chỉ tiêu trường</span>
          <div class="rounded-3 bg-info bg-opacity-10 text-info p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="fas fa-bullseye fs-5"></i>
          </div>
        </div>
        <div class="h3 fw-bold text-dark mb-1">
          <?= $schoolStats['school_quota'] > 0 ? number_format($schoolStats['school_quota']) : 'Xem chỉ tiêu' ?>
        </div>
        <div class="small text-muted">
          <a href="<?= e(url('/teacher/quotas.php')) ?>" class="text-decoration-none text-info fw-semibold">
            Bảng chỉ tiêu THPT <i class="fas fa-arrow-right ms-1 small"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Teacher Core Functions -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <!-- Phân hệ chức năng dành cho giáo viên -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
      <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
        <h5 class="h6 fw-bold mb-0 text-dark">
          <i class="fas fa-tasks text-success me-2"></i>Nghiệp vụ Giáo viên & Nhà trường
        </h5>
        <span class="badge bg-light text-secondary rounded-pill small">Hỗ trợ kỳ thi</span>
      </div>
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-6">
            <a href="<?= e(url('/teacher/students.php')) ?>" class="card p-3 border rounded-3 text-decoration-none text-dark h-100 hover-shadow transition">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 text-success p-2.5">
                  <i class="fas fa-user-graduate fs-5"></i>
                </div>
                <div>
                  <div class="fw-bold">Hồ sơ học sinh</div>
                  <div class="small text-muted">Kiểm tra thông tin thí sinh, tình trạng dự thi</div>
                </div>
              </div>
            </a>
          </div>

          <div class="col-md-6">
            <a href="<?= e(url('/teacher/adjustments.php')) ?>" class="card p-3 border rounded-3 text-decoration-none text-dark h-100 hover-shadow transition">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-2.5">
                  <i class="fas fa-user-edit fs-5"></i>
                </div>
                <div>
                  <div class="fw-bold">Yêu cầu điều chỉnh hồ sơ</div>
                  <div class="small text-muted">Hỗ trợ học sinh sửa sai sót thông tin</div>
                </div>
              </div>
            </a>
          </div>

          <div class="col-md-6">
            <a href="<?= e(url('/teacher/scores.php')) ?>" class="card p-3 border rounded-3 text-decoration-none text-dark h-100 hover-shadow transition">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2.5">
                  <i class="fas fa-edit fs-5"></i>
                </div>
                <div>
                  <div class="fw-bold">Bảng điểm thi</div>
                  <div class="small text-muted">Theo dõi và tra cứu kết quả thi tuyển sinh</div>
                </div>
              </div>
            </a>
          </div>

          <div class="col-md-6">
            <a href="<?= e(url('/teacher/admitted.php')) ?>" class="card p-3 border rounded-3 text-decoration-none text-dark h-100 hover-shadow transition">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 text-info p-2.5">
                  <i class="fas fa-list-check fs-5"></i>
                </div>
                <div>
                  <div class="fw-bold">Danh sách trúng tuyển</div>
                  <div class="small text-muted">Tra cứu kết quả trúng tuyển và xác nhận nhập học</div>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Bảng danh sách học sinh tiêu biểu -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
      <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
        <h5 class="h6 fw-bold mb-0 text-dark">
          <i class="fas fa-users text-success me-2"></i>Danh sách học sinh cập nhật gần đây
        </h5>
        <a href="<?= e(url('/teacher/students.php')) ?>" class="small text-success text-decoration-none fw-semibold">
          Xem tất cả <i class="fas fa-angle-right ms-1"></i>
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>CCCD/CMND</th>
              <th>Họ và tên</th>
              <th>Ngày sinh</th>
              <th>Giới tính</th>
              <th class="text-center">Đủ ĐK</th>
              <th class="text-end">Trạng thái</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($students as $stu): ?>
              <tr>
                <td class="font-monospace fw-semibold"><?= e($stu['national_id']) ?></td>
                <td class="fw-semibold text-dark"><?= e($stu['full_name']) ?></td>
                <td class="text-muted"><?= e($stu['date_of_birth'] ?? '—') ?></td>
                <td class="text-muted"><?= e($stu['gender'] ?? '—') ?></td>
                <td class="text-center">
                  <?php if ((int)$stu['eligible'] === 1): ?>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill">Đủ ĐK</span>
                  <?php else: ?>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill">Chưa</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <span class="badge <?= $stu['status'] === 'active' ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' ?> rounded-pill">
                    <?= $stu['status'] === 'active' ? 'Hoạt động' : 'Khóa' ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$students): ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">Chưa có dữ liệu học sinh.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Cột hướng dẫn & Thông báo nhà trường -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
      <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-bullhorn text-success me-2"></i>Lưu ý dành cho Giáo viên</h6>
      </div>
      <div class="card-body p-4 small">
        <div class="alert alert-success bg-success bg-opacity-10 border-0 text-dark mb-3">
          <strong>Trách nhiệm trường THCS:</strong> Rà soát kỹ điều kiện tốt nghiệp THCS, thông tin cư trú và diện ưu tiên của học sinh để đảm bảo quyền lợi tuyển sinh.
        </div>
        <div class="alert alert-info bg-info bg-opacity-10 border-0 text-dark mb-0">
          <strong>Trách nhiệm trường THPT:</strong> Theo dõi tiến độ đăng ký nguyện vọng, công tác phân bổ phòng thi và chuẩn bị cơ sở vật chất điểm thi.
        </div>
      </div>
    </div>

    <!-- Lịch trình kỳ thi -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
      <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-calendar-alt text-primary me-2"></i>Mốc thời gian quan trọng</h6>
      </div>
      <div class="card-body p-4 small">
        <div class="timeline-simple">
          <div class="d-flex gap-3 mb-3">
            <span class="badge bg-primary rounded-pill p-1 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">1</span>
            <div>
              <div class="fw-bold text-dark">Kiểm tra thông tin thí sinh</div>
              <div class="text-muted">Hoàn tất kiểm tra hồ sơ trước ngày thi.</div>
            </div>
          </div>
          <div class="d-flex gap-3 mb-3">
            <span class="badge bg-success rounded-pill p-1 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">2</span>
            <div>
              <div class="fw-bold text-dark">Kỳ thi tuyển sinh chính thức</div>
              <div class="text-muted">Tổ chức coi thi tại các điểm thi THPT.</div>
            </div>
          </div>
          <div class="d-flex gap-3">
            <span class="badge bg-secondary rounded-pill p-1 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">3</span>
            <div>
              <div class="fw-bold text-dark">Xét tuyển & Xác nhận nhập học</div>
              <div class="text-muted">Công bố danh sách trúng tuyển đợt 1.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
teacher_layout_end();
