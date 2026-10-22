<?php
/**
 * Phân đoạn Dashboard Thí sinh trên Trang chủ khi Thí sinh đã đăng nhập
 */
declare(strict_types=1);

if (!auth_check() || !auth_has_role('candidate')) {
    return;
}

require_once __DIR__ . '/../../includes/portal_helper.php';
require_once __DIR__ . '/../../includes/candidate_service.php';

$candInfo = auth_get_candidate_info();
if (!$candInfo) {
    return;
}
require_once __DIR__.'/../../includes/exam_service.php';
$homeExamCard=exam_card_for_user(db(),(int)auth_user()['user_id']);
$candInfo['exam_number']=$homeExamCard['exam_number'] ?? null;
$homeExamConfig=isset($homeExamCard['admission_period_id'])?period_configuration(db(),(int)$homeExamCard['admission_period_id']):null;
?>
<!-- Candidate Portal Dashboard Section for Logged-in Candidate -->
<section id="candidate-portal-dashboard" class="tw-py-8 tw-bg-gradient-to-b tw-from-blue-50/50 tw-to-white tw-border-b tw-border-slate-200/80">
  <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
    <?php if($homeExamConfig && $homeExamConfig['settings']['mode']==='simulation'):?><div class="alert alert-warning">MÔ PHỎNG — SBD và phiếu dự thi dưới đây phục vụ dự án, không phải thông báo tuyển sinh chính thức.</div><?php endif;?>
    
    <!-- Welcome Card -->
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-sm tw-border tw-border-blue-100 tw-p-5 sm:tw-p-6 tw-mb-6">
      <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4">
        <div class="tw-flex tw-items-center tw-gap-3.5">
          <div class="tw-w-12 tw-h-12 tw-rounded-full tw-bg-primary/10 tw-text-primary tw-flex tw-items-center tw-justify-center tw-shrink-0">
            <span class="material-symbols-outlined tw-text-2xl">school</span>
          </div>
          <div>
            <div class="tw-flex tw-items-center tw-gap-2 tw-flex-wrap">
              <span class="tw-text-xs tw-font-bold tw-text-primary tw-bg-blue-50 tw-px-2 tw-py-0.5 tw-rounded-full tw-border tw-border-blue-200">
                THÍ SINH
              </span>
              <h2 class="tw-text-lg sm:tw-text-xl tw-font-bold tw-text-slate-800 tw-mb-0">
                Xin chào, <?= e($candInfo['full_name'] ?? 'Thí sinh') ?>
              </h2>
            </div>
            <div class="tw-text-xs sm:tw-text-sm tw-text-slate-500 tw-mt-0.5">
              CCCD: <span class="tw-font-mono tw-font-semibold tw-text-slate-700"><?= e($candInfo['national_id'] ?? '---') ?></span>
              <?php if (!empty($candInfo['exam_number'])): ?>
                &bull; SBD: <span class="tw-font-mono tw-font-bold tw-text-primary"><?= e($candInfo['exam_number']) ?></span>
              <?php endif; ?>
              <?php if (!empty($candInfo['middle_school_name'])): ?>
                &bull; Trường: <span class="tw-font-medium tw-text-slate-700"><?= e($candInfo['middle_school_name']) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="tw-flex tw-items-center tw-gap-2 tw-flex-wrap">
          <?php if ((int)($candInfo['eligible'] ?? 1) === 1): ?>
            <span class="tw-inline-flex tw-items-center tw-gap-1 tw-text-xs tw-font-semibold tw-bg-emerald-50 tw-text-emerald-700 tw-px-3 tw-py-1 tw-rounded-full tw-border tw-border-emerald-200">
              <span class="material-symbols-outlined tw-text-sm">check_circle</span> Đủ điều kiện dự thi
            </span>
          <?php else: ?>
            <span class="tw-inline-flex tw-items-center tw-gap-1 tw-text-xs tw-font-semibold tw-bg-rose-50 tw-text-rose-700 tw-px-3 tw-py-1 tw-rounded-full tw-border tw-border-rose-200">
              <span class="material-symbols-outlined tw-text-sm">cancel</span> Chưa đủ điều kiện
            </span>
          <?php endif; ?>

          <?php 
            $statusBadge = $candInfo['admission_status'] ?? 'not_processed';
            if ($statusBadge === 'admitted'): ?>
            <span class="tw-inline-flex tw-items-center tw-gap-1 tw-text-xs tw-font-bold tw-bg-amber-50 tw-text-amber-700 tw-px-3 tw-py-1 tw-rounded-full tw-border tw-border-amber-200">
              <span class="material-symbols-outlined tw-text-sm">stars</span> Trúng tuyển
            </span>
          <?php elseif ($statusBadge === 'enrolled'): ?>
            <span class="tw-inline-flex tw-items-center tw-gap-1 tw-text-xs tw-font-bold tw-bg-blue-50 tw-text-blue-700 tw-px-3 tw-py-1 tw-rounded-full tw-border tw-border-blue-200">
              <span class="material-symbols-outlined tw-text-sm">verified</span> Đã nhập học
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Quick Navigation Hub -->
    <div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-4 tw-gap-3 sm:tw-gap-4">
      
      <!-- 1. Nguyện vọng -->
      <a href="<?= e(url('/aspirations/index.php')) ?>" class="tw-bg-white hover:tw-bg-blue-50/50 tw-p-4 tw-rounded-xl tw-border tw-border-slate-200/90 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-group tw-no-underline">
        <div class="tw-flex tw-items-center tw-gap-3">
          <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-bg-blue-100 tw-text-primary tw-flex tw-items-center tw-justify-center tw-shrink-0 group-hover:tw-scale-110 tw-transition-transform">
            <span class="material-symbols-outlined tw-text-xl">format_list_numbered</span>
          </div>
          <div class="tw-min-w-0">
            <div class="tw-font-bold tw-text-slate-800 tw-text-sm group-hover:tw-text-primary tw-transition-colors">Nguyện vọng</div>
            <div class="tw-text-xs tw-text-slate-500 tw-truncate">Đăng ký & điều chỉnh</div>
          </div>
        </div>
      </a>

      <!-- 2. Thẻ dự thi -->
      <a href="<?= e(url('/exam/card.php')) ?>" class="tw-bg-white hover:tw-bg-emerald-50/50 tw-p-4 tw-rounded-xl tw-border tw-border-slate-200/90 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-group tw-no-underline">
        <div class="tw-flex tw-items-center tw-gap-3">
          <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-bg-emerald-100 tw-text-emerald-700 tw-flex tw-items-center tw-justify-center tw-shrink-0 group-hover:tw-scale-110 tw-transition-transform">
            <span class="material-symbols-outlined tw-text-xl">badge</span>
          </div>
          <div class="tw-min-w-0">
            <div class="tw-font-bold tw-text-slate-800 tw-text-sm group-hover:tw-text-emerald-700 tw-transition-colors">Thẻ dự thi</div>
            <div class="tw-text-xs tw-text-slate-500 tw-truncate">Phòng thi & lịch thi</div>
          </div>
        </div>
      </a>

      <!-- 3. Điểm thi & Kết quả -->
      <a href="<?= e(url('/admission/results.php')) ?>" class="tw-bg-white hover:tw-bg-amber-50/50 tw-p-4 tw-rounded-xl tw-border tw-border-slate-200/90 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-group tw-no-underline">
        <div class="tw-flex tw-items-center tw-gap-3">
          <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-bg-amber-100 tw-text-amber-800 tw-flex tw-items-center tw-justify-center tw-shrink-0 group-hover:tw-scale-110 tw-transition-transform">
            <span class="material-symbols-outlined tw-text-xl">award_star</span>
          </div>
          <div class="tw-min-w-0">
            <div class="tw-font-bold tw-text-slate-800 tw-text-sm group-hover:tw-text-amber-800 tw-transition-colors">Kết quả tuyển sinh</div>
            <div class="tw-text-xs tw-text-slate-500 tw-truncate">Xem điểm & trúng tuyển</div>
          </div>
        </div>
      </a>

      <!-- 4. Điều chỉnh hồ sơ -->
      <a href="<?= e(url('/adjustments/create.php')) ?>" class="tw-bg-white hover:tw-bg-purple-50/50 tw-p-4 tw-rounded-xl tw-border tw-border-slate-200/90 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-group tw-no-underline">
        <div class="tw-flex tw-items-center tw-gap-3">
          <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-bg-purple-100 tw-text-purple-700 tw-flex tw-items-center tw-justify-center tw-shrink-0 group-hover:tw-scale-110 tw-transition-transform">
            <span class="material-symbols-outlined tw-text-xl">edit_document</span>
          </div>
          <div class="tw-min-w-0">
            <div class="tw-font-bold tw-text-slate-800 tw-text-sm group-hover:tw-text-purple-700 tw-transition-colors">Điều chỉnh hồ sơ</div>
            <div class="tw-text-xs tw-text-slate-500 tw-truncate">Gửi yêu cầu chỉnh sửa</div>
          </div>
        </div>
      </a>

    </div>

  </div>
</section>
