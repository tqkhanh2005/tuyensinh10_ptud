<?php
/**
 * Footer Component
 */
?>
<footer class="tw-w-full tw-bg-slate-50 dark:tw-bg-slate-900 tw-py-12 tw-font-body tw-text-sm tw-border-t tw-border-slate-200">
  <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-8 tw-px-8 tw-max-w-7xl tw-mx-auto">
    <div class="md:tw-col-span-1">
      <div class="tw-text-xl tw-font-bold tw-text-primary dark:tw-text-blue-300 tw-mb-6">TUYỂN SINH LỚP 10</div>
      <p class="tw-text-slate-500 dark:tw-text-slate-400 tw-mb-6 tw-leading-relaxed">
        © 2026 Sở Giáo dục và Đào tạo Cần Thơ.
        <br>Kiến tạo tương lai bền vững.
      </p>
      <div class="tw-flex tw-gap-4 tw-text-slate-400">
        <span class="material-symbols-outlined tw-cursor-pointer hover:tw-text-primary tw-transition-colors">facebook</span>    
        <span class="material-symbols-outlined tw-cursor-pointer hover:tw-text-primary tw-transition-colors">language</span>    
        <span class="material-symbols-outlined tw-cursor-pointer hover:tw-text-primary tw-transition-colors">alternate_email</span>
      </div>
    </div>
    <div>
      <h5 class="tw-text-slate-900 dark:tw-text-white tw-font-bold tw-mb-6">Liên kết nhanh</h5>
      <ul class="tw-space-y-4 tw-text-slate-500 dark:tw-text-slate-400 tw-list-none tw-p-0">
        <li><a class="hover:tw-underline tw-transition-all tw-no-underline tw-text-inherit" href="#">Chính sách bảo mật</a></li>
        <li><a class="hover:tw-underline tw-transition-all tw-no-underline tw-text-inherit" href="#">Điều khoản sử dụng</a></li>
        <li><a class="hover:tw-underline tw-transition-all tw-no-underline tw-text-inherit" href="<?= url('/#targets') ?>">Tuyển sinh</a></li>
      </ul>
    </div>
    <div>
      <h5 class="tw-text-slate-900 dark:tw-text-white tw-font-bold tw-mb-6">Hỗ trợ</h5>
      <ul class="tw-space-y-4 tw-text-slate-500 dark:tw-text-slate-400 tw-list-none tw-p-0">
        <li><a class="hover:tw-underline tw-transition-all tw-no-underline tw-text-inherit" href="<?= url('/#guides') ?>">Hỏi đáp (FAQ)</a></li>
        <li><a class="hover:tw-underline tw-transition-all tw-no-underline tw-text-inherit" href="<?= url('/#schools') ?>">Liên hệ</a></li>
        <li><a class="hover:tw-underline tw-transition-all tw-no-underline tw-text-inherit" href="<?= url('/#targets') ?>">Mạng lưới cơ sở</a></li>
      </ul>
    </div>
  </div>
</footer>

<!-- Global Confirm Modal -->
<div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold text-dark" id="globalConfirmTitle">Xác nhận</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center pt-3 pb-4">
        <div class="mb-3">
          <i class="fas fa-question-circle text-warning fs-1"></i>
        </div>
        <p class="mb-0 text-secondary" id="globalConfirmMessage">Bạn có chắc chắn không?</p>
      </div>
      <div class="modal-footer border-0 pt-0 d-flex justify-content-center gap-2">
        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
        <button type="button" class="btn btn-primary px-4" id="globalConfirmBtn">Đồng ý</button>
      </div>
    </div>
  </div>
</div>
