<?php
/**
 * Home View: Guides & Form Downloads Section
 */
?>
<!-- Instructions Section -->
<section id="guides" class="tw-py-24 tw-px-8 tw-max-w-7xl tw-mx-auto tw-bg-surface">
  <div class="tw-bg-surface-container-lowest tw-rounded-[3rem] tw-p-12 md:tw-p-20 tw-shadow-sm tw-relative tw-overflow-hidden tw-border tw-border-surface-container-high">
    <div class="tw-absolute tw-top-0 tw-right-0 tw-w-64 tw-h-64 tw-bg-primary/5 tw-rounded-full tw--mr-32 tw--mt-32"></div>
    <h2 class="tw-text-3xl tw-font-headline tw-font-bold tw-mb-12 tw-text-center tw-text-on-surface">Hướng dẫn thao tác hệ thống</h2>
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-8 tw-relative tw-z-10">
      <div class="tw-flex tw-flex-col tw-items-center tw-text-center">
        <div class="tw-text-5xl tw-font-headline tw-font-black tw-text-primary/20 tw-mb-6">01</div>
        <h4 class="tw-text-xl tw-font-bold tw-mb-4 tw-text-on-surface">Kiểm tra dữ liệu</h4>
        <p class="tw-text-on-surface-variant">Đăng nhập để kiểm tra thông tin cá nhân và học bạ do THCS nhập lên hệ thống.</p>
      </div>
      <div class="tw-flex tw-flex-col tw-items-center tw-text-center">
        <div class="tw-text-5xl tw-font-headline tw-font-black tw-text-primary/20 tw-mb-6">02</div>
        <h4 class="tw-text-xl tw-font-bold tw-mb-4 tw-text-on-surface">Yêu cầu chỉnh sửa</h4>
        <p class="tw-text-on-surface-variant">Nếu có sai sót, gửi yêu cầu để THCS cập nhật lại dữ liệu trước khi Sở khóa cổng THCS.</p>
      </div>
      <div class="tw-flex tw-flex-col tw-items-center tw-text-center">
        <div class="tw-text-5xl tw-font-headline tw-font-black tw-text-primary/20 tw-mb-6">03</div>
        <h4 class="tw-text-xl tw-font-bold tw-mb-4 tw-text-on-surface">Đăng ký nguyện vọng</h4>
        <p class="tw-text-on-surface-variant">Khi Sở mở cổng, đăng ký NV1-NV4 và gửi hồ sơ để trường THPT tiếp nhận.</p>
      </div>
      <div class="tw-flex tw-flex-col tw-items-center tw-text-center">
        <div class="tw-text-5xl tw-font-headline tw-font-black tw-text-primary/20 tw-mb-6">04</div>
        <h4 class="tw-text-xl tw-font-bold tw-mb-4 tw-text-on-surface">Theo dõi duyệt hồ sơ</h4>
        <p class="tw-text-on-surface-variant">Theo dõi phản hồi từ THPT; nếu bị yêu cầu bổ sung/chỉnh sửa thì cập nhật và gửi lại hồ sơ.</p>
      </div>
      <div class="tw-flex tw-flex-col tw-items-center tw-text-center">
        <div class="tw-text-5xl tw-font-headline tw-font-black tw-text-primary/20 tw-mb-6">05</div>
        <h4 class="tw-text-xl tw-font-bold tw-mb-4 tw-text-on-surface">Kết quả và nhập học</h4>
        <p class="tw-text-on-surface-variant">Tra cứu kết quả, xác nhận nhập học trực tuyến; nếu có đợt bổ sung thì tiếp tục cập nhật NV3, NV4.</p>
      </div>
    </div>
    <div class="tw-mt-16 tw-flex tw-flex-col md:tw-flex-row tw-items-center tw-justify-center tw-gap-4">
      <a href="https://docs.google.com/document/d/1Tg2biqfZdYr0k4moD8qP1NrdOjCuxY5VvZVR9muu_kU/edit?usp=sharing" target="_blank" class="tw-w-full md:tw-w-auto tw-bg-surface-container-low tw-border tw-border-outline-variant/30 tw-px-10 tw-py-4 tw-rounded-2xl tw-font-bold tw-flex tw-items-center tw-justify-center tw-gap-3 hover:tw-bg-surface-container tw-transition-colors tw-no-underline">
        <span class="material-symbols-outlined tw-text-primary">description</span>
        Tài liệu hướng dẫn
      </a>
      <a href="<?= asset('tuyensinh/dondangkyduthi10.pdf') ?>" target="_blank" class="tw-w-full md:tw-w-auto tw-bg-surface-container-low tw-border tw-border-outline-variant/30 tw-px-10 tw-py-4 tw-rounded-2xl tw-font-bold tw-flex tw-items-center tw-justify-center tw-gap-3 hover:tw-bg-surface-container tw-transition-colors tw-no-underline">
        <span class="material-symbols-outlined tw-text-primary">download</span>
        Tải biểu mẫu (PDF)
      </a>
    </div>
  </div>
</section>
