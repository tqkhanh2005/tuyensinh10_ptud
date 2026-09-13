<?php
/**
 * Home View: YouTube Full-Width Video Section
 */
?>
<!-- Full-width Video Section -->
<section id="video-section" class="tw-w-full tw-bg-black tw-relative tw-aspect-video md:tw-max-h-[700px] tw-overflow-hidden tw-group tw-hidden">
  <div id="youtube-placeholder" class="tw-w-full tw-h-full tw-relative tw-cursor-pointer" onclick="loadYoutube()">
    <img id="video-thumbnail" alt="Thumbnail Video" class="tw-w-full tw-h-full tw-object-cover tw-opacity-60 group-hover:tw-scale-105 tw-transition-transform tw-duration-1000" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBozt-DDFTVExfPEaCSu_nzjgMzfrCLGMkEAiR-yS0AUSLqB3oPbjQNAPTQ1SIZFseqVUOJGIqXvO6kaAwUN6BIdYGOHMjbztHj9N3yLp3BN3c78SDCwtaxeUX33X90vp9bCGIViWRJwzp22ibUgI5XRTbzM3FPSqT2fT0moZhe3cYimA3yHchAfXAJbCCoItpuutyuwQsGToWXWv_iFzQ_edis-UEu6IgiqltNFBHp-jyljVR-3IZg2BOGxgGB4wNPqR6C7uPpYiJy">
    <div class="tw-absolute tw-inset-0 tw-flex tw-items-center tw-justify-center">
      <div class="tw-w-24 tw-h-24 md:tw-w-32 md:tw-h-32 tw-bg-white/20 tw-backdrop-blur-md tw-rounded-full tw-flex tw-items-center tw-justify-center tw-border tw-border-white/40 group-hover:tw-scale-110 tw-transition-transform">
        <div class="tw-w-16 tw-h-16 md:tw-w-20 md:tw-h-20 tw-bg-white tw-rounded-full tw-flex tw-items-center tw-justify-center tw-shadow-2xl">       
          <span class="material-symbols-outlined tw-text-primary tw-text-5xl md:tw-text-6xl" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
        </div>
      </div>
    </div>
    <div class="tw-absolute tw-bottom-8 tw-left-1/2 tw--translate-x-1/2 tw-text-white tw-text-center tw-w-full tw-px-4">
      <h3 id="video-title" class="tw-text-2xl tw-font-headline tw-font-bold tw-mb-2">Trường THPT chuyên Lý Tự Trọng</h3>
      <p class="tw-text-white/80">Khám phá TUYỂN SINH LỚP 10 qua ống kính camera</p>
    </div>
  </div>
  <div id="youtube-container" class="tw-w-full tw-h-full tw-hidden">
    <iframe id="youtube-iframe" class="tw-w-full tw-h-full" src="" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen=""></iframe>
  </div>
</section>
