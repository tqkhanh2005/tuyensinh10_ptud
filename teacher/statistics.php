<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_helper.php';
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

$summary = [
    'total_candidates' => 0,
    'eligible_count' => 0,
    'ineligible_count' => 0,
    'total_admitted' => 0,
    'total_confirmed' => 0,
    'quota' => 0,
];

$academicBreakdown = [];
$schoolBreakdown = [];

try {
    if ($schoolLevel === 'THCS' && $schoolId > 0) {
        $cStmt = $pdo->prepare('SELECT COUNT(*) FROM candidates WHERE middle_school_id = :sid AND status = "active"');
        $cStmt->execute(['sid' => $schoolId]);
        $summary['total_candidates'] = (int)$cStmt->fetchColumn();

        $eStmt = $pdo->prepare('SELECT COUNT(*) FROM candidates WHERE middle_school_id = :sid AND status = "active" AND eligible = 1');
        $eStmt->execute(['sid' => $schoolId]);
        $summary['eligible_count'] = (int)$eStmt->fetchColumn();
        $summary['ineligible_count'] = max(0, $summary['total_candidates'] - $summary['eligible_count']);

        // Phân loại tiến độ tuyển sinh
        $acStmt = $pdo->prepare('
            SELECT 
                CASE 
                    WHEN admission_status = "enrolled" THEN "Đã nhập học"
                    WHEN admission_status = "admitted" THEN "Đã trúng tuyển"
                    ELSE "Đang chờ xét tuyển"
                END AS grade,
                COUNT(*) AS cnt 
            FROM candidates 
            WHERE middle_school_id = :sid AND status = "active"
            GROUP BY admission_status
        ');
        $acStmt->execute(['sid' => $schoolId]);
        $academicBreakdown = $acStmt->fetchAll(PDO::FETCH_ASSOC);

        // Trường THPT được học sinh trường này đăng ký nhiều nhất
        $schStmt = $pdo->prepare('
            SELECT s.name AS school_name, COUNT(*) AS count_aspirations
            FROM aspirations a
            JOIN candidates c ON c.id = a.candidate_id
            JOIN schools s ON s.id = a.school_id
            WHERE c.middle_school_id = :sid
            GROUP BY s.id
            ORDER BY count_aspirations DESC
            LIMIT 10
        ');
        $schStmt->execute(['sid' => $schoolId]);
        $schoolBreakdown = $schStmt->fetchAll(PDO::FETCH_ASSOC);

        // Số lượng đỗ
        $admStmt = $pdo->prepare('
            SELECT COUNT(*) 
            FROM admission_results ar
            JOIN candidates c ON c.id = ar.candidate_id
            WHERE c.middle_school_id = :sid AND ar.result_status = "admitted"
        ');
        $admStmt->execute(['sid' => $schoolId]);
        $summary['total_admitted'] = (int)$admStmt->fetchColumn();

    } elseif ($schoolLevel === 'THPT' && $schoolId > 0) {
        // Chỉ tiêu
        $qStmt = $pdo->prepare('SELECT COALESCE(SUM(quota), 0) FROM admission_quotas WHERE school_id = :sid');
        $qStmt->execute(['sid' => $schoolId]);
        $summary['quota'] = (int)$qStmt->fetchColumn();

        // Thí sinh đăng ký NV
        $cStmt = $pdo->prepare('SELECT COUNT(DISTINCT candidate_id) FROM aspirations WHERE school_id = :sid');
        $cStmt->execute(['sid' => $schoolId]);
        $summary['total_candidates'] = (int)$cStmt->fetchColumn();

        // Trúng tuyển
        $admStmt = $pdo->prepare('SELECT COUNT(*) FROM admission_results WHERE school_id = :sid AND result_status = "admitted"');
        $admStmt->execute(['sid' => $schoolId]);
        $summary['total_admitted'] = (int)$admStmt->fetchColumn();

        // Nhập học
        $cfStmt = $pdo->prepare('SELECT COUNT(*) FROM admission_results WHERE school_id = :sid AND result_status = "admitted" AND enrollment_status = "confirmed"');
        $cfStmt->execute(['sid' => $schoolId]);
        $summary['total_confirmed'] = (int)$cfStmt->fetchColumn();

        // Phân bố NV1, NV2, NV3
        $acStmt = $pdo->prepare('
            SELECT CONCAT("Nguyện vọng ", priority_order) AS grade, COUNT(*) AS cnt 
            FROM aspirations 
            WHERE school_id = :sid 
            GROUP BY priority_order
        ');
        $acStmt->execute(['sid' => $schoolId]);
        $academicBreakdown = $acStmt->fetchAll(PDO::FETCH_ASSOC);

        // Các trường THCS nguồn có nhiều thí sinh đăng ký vào trường này nhất
        $schStmt = $pdo->prepare('
            SELECT ms.name AS school_name, COUNT(*) AS count_aspirations
            FROM aspirations a
            JOIN candidates c ON c.id = a.candidate_id
            JOIN schools ms ON ms.id = c.middle_school_id
            WHERE a.school_id = :sid
            GROUP BY ms.id
            ORDER BY count_aspirations DESC
            LIMIT 10
        ');
        $schStmt->execute(['sid' => $schoolId]);
        $schoolBreakdown = $schStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('Teacher Statistics Error: ' . $e->getMessage());
}

teacher_layout_start('Báo cáo thống kê', 'statistics', ['Thống kê - báo cáo' => '']);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="h4 fw-bold text-dark mb-1">
      <i class="fas fa-chart-pie text-success me-2"></i>Thống Kê - Báo Cáo Tuyển Sinh
    </h1>
    <p class="text-muted small mb-0">
      Số liệu phân tích chi tiết phục vụ công tác quản lý và theo dõi tuyển sinh của <strong><?= e($schoolName) ?></strong>.
    </p>
  </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-primary border-4">
      <div class="small text-muted fw-semibold mb-1">
        <?= $schoolLevel === 'THCS' ? 'Tổng học sinh lớp 9' : 'Tổng thí sinh đăng ký' ?>
      </div>
      <div class="h3 fw-bold text-dark mb-0"><?= number_format($summary['total_candidates']) ?></div>
      <div class="small text-muted">Hồ sơ trong hệ thống</div>
    </div>
  </div>

  <?php if ($schoolLevel === 'THCS'): ?>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-success border-4">
        <div class="small text-muted fw-semibold mb-1">Đủ điều kiện dự thi</div>
        <div class="h3 fw-bold text-success mb-0"><?= number_format($summary['eligible_count']) ?></div>
        <div class="small text-muted">
          <?= $summary['total_candidates'] > 0 ? round(($summary['eligible_count'] / $summary['total_candidates']) * 100, 1) : 0 ?>% tỷ lệ đạt
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-success border-4">
        <div class="small text-muted fw-semibold mb-1">Chỉ tiêu tuyển sinh</div>
        <div class="h3 fw-bold text-success mb-0"><?= number_format($summary['quota']) ?></div>
        <div class="small text-muted">Sở GD&ĐT giao</div>
      </div>
    </div>
  <?php endif; ?>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-info border-4">
      <div class="small text-muted fw-semibold mb-1">Trúng tuyển lớp 10</div>
      <div class="h3 fw-bold text-info mb-0"><?= number_format($summary['total_admitted']) ?></div>
      <div class="small text-muted">Đạt điểm chuẩn vào trường</div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border-start border-warning border-4">
      <div class="small text-muted fw-semibold mb-1">
        <?= $schoolLevel === 'THCS' ? 'Chưa đủ điều kiện' : 'Đã xác nhận nhập học' ?>
      </div>
      <div class="h3 fw-bold text-warning mb-0">
        <?= $schoolLevel === 'THCS' ? number_format($summary['ineligible_count']) : number_format($summary['total_confirmed']) ?>
      </div>
      <div class="small text-muted">
        <?= $schoolLevel === 'THCS' ? 'Cần rà soát bổ sung' : 'Đã nộp hồ sơ nhập học' ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- Cột 1: Phân bố số liệu -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
      <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="fw-bold mb-0 text-dark">
          <?= $schoolLevel === 'THCS' ? 'Tiến độ Tuyển sinh của Học sinh' : 'Cơ cấu Nguyện vọng Đăng ký' ?>
        </h6>
      </div>
      <div class="card-body p-4">
        <?php if (!empty($academicBreakdown)): ?>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
            <?php foreach ($academicBreakdown as $ab): ?>
              <?php
                $pct = $summary['total_candidates'] > 0 ? round(((int)$ab['cnt'] / $summary['total_candidates']) * 100, 1) : 0;
              ?>
              <li>
                <div class="d-flex justify-content-between align-items-center mb-1 small">
                  <span class="fw-bold text-dark"><?= e((string)$ab['grade']) ?></span>
                  <span class="text-muted fw-semibold"><?= number_format((int)$ab['cnt']) ?> học sinh (<?= $pct ?>%)</span>
                </div>
                <div class="progress" style="height: 8px;">
                  <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="text-center py-4 text-muted">Chưa có dữ liệu thống kê phân bố.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Cột 2: Phân bố liên kết trường -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
      <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="fw-bold mb-0 text-dark">
          <?= $schoolLevel === 'THCS' ? 'Top Trường THPT Học sinh Lựa chọn Nhiều Nhất' : 'Top Trường THCS Nguồn Có Nhiều Thí sinh Đăng ký' ?>
        </h6>
      </div>
      <div class="card-body p-4">
        <?php if (!empty($schoolBreakdown)): ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 small">
              <thead class="table-light">
                <tr>
                  <th>Tên trường</th>
                  <th class="text-end">Số lượt đăng ký</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($schoolBreakdown as $sb): ?>
                  <tr>
                    <td class="fw-medium text-dark"><?= e((string)$sb['school_name']) ?></td>
                    <td class="text-end font-monospace fw-bold text-primary">
                      <?= number_format((int)$sb['count_aspirations']) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-4 text-muted">Chưa có dữ liệu nguyện vọng liên kết trường.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
teacher_layout_end();
