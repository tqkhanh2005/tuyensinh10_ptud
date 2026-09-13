<?php
/**
 * Home View: Client-side Interactive Scripts
 */
?>
<script>
  let currentYoutubeUrl = "";

  function loadYoutube() {
    if (!currentYoutubeUrl) return;

    const container = document.getElementById('youtube-container');
    const placeholder = document.getElementById('youtube-placeholder');
    const iframe = document.getElementById('youtube-iframe');
    
    let embedUrl = currentYoutubeUrl;
    if (embedUrl.includes('watch?v=')) {
      embedUrl = embedUrl.replace('watch?v=', 'embed/');
    }
    if (!embedUrl.includes('autoplay=1')) {
      embedUrl += (embedUrl.includes('?') ? '&' : '?') + 'autoplay=1';
    }

    iframe.src = embedUrl;
    placeholder.classList.add('tw-hidden');
    container.classList.remove('tw-hidden');
  }

  function changeVideo(url, name) {
    currentYoutubeUrl = url;
    document.getElementById('video-title').innerText = name;

    const videoSection = document.getElementById('video-section');
    const container = document.getElementById('youtube-container');
    const placeholder = document.getElementById('youtube-placeholder');
    const iframe = document.getElementById('youtube-iframe');

    videoSection.classList.remove('tw-hidden');
    iframe.src = '';
    container.classList.add('tw-hidden');
    placeholder.classList.remove('tw-hidden');

    // Scroll to video section
    videoSection.scrollIntoView({ behavior: 'smooth' });
  }

  document.querySelectorAll('.js-change-video').forEach((button) => {
    button.addEventListener('click', () => {
      changeVideo(button.dataset.youtubeLink || '', button.dataset.schoolName || '');
    });
  });

  // Responsive Limits
  function getLimits() {
    const isDesktop = window.innerWidth >= 1024;
    return {
      quota: isDesktop ? 6 : 4,
      school: isDesktop ? 3 : 2
    };
  }

  // Search logic
  function setupSearch(inputId, itemClass, containerId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('input', function() {
      const limits = getLimits();
      const limit = containerId.includes('quota') ? limits.quota : limits.school;
      const filter = this.value.trim().toLowerCase();
      const items = document.querySelectorAll('#' + containerId + ' .' + itemClass);
      const btnMore = document.querySelector('#' + containerId).parentElement.querySelector('button[id^="btnMore"]');
      
      if (!filter) {
        // Restore initial limit if no filter
        items.forEach((item, idx) => {
          if (idx < limit) {
            item.classList.remove('hidden-item');
          } else {
            item.classList.add('hidden-item');
          }
        });
        if (btnMore) {
          btnMore.parentElement.style.display = 'flex';
          btnMore.innerText = (containerId.includes('quota') ? 'Xem thêm chỉ tiêu' : 'Xem thêm cơ sở');
        }
      } else {
        // Searching ignores limit
        items.forEach(item => {
          const text = item.innerText.toLowerCase();
          if (text.includes(filter)) {
            item.classList.remove('hidden-item');
          } else {
            item.classList.add('hidden-item');
          }
        });
        if (btnMore) {
          btnMore.parentElement.style.display = 'none';
        }
      }
    });
  }

  setupSearch('quotaSearch', 'quota-item', 'quotaContainer');
  setupSearch('schoolSearch', 'school-item', 'thptContainer');
  setupSearch('schoolSearch', 'school-item', 'thcsContainer');

  // Show more logic with staggered animation
  function setupShowMore(btnId, containerId, itemClass) {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    
    let expanded = false;
    btn.addEventListener('click', function() {
      const limits = getLimits();
      const limit = containerId.includes('quota') ? limits.quota : limits.school;
      const items = Array.from(document.querySelectorAll('#' + containerId + ' .' + itemClass));
      expanded = !expanded;
      
      if (expanded) {
        let delay = 0;
        items.forEach((item, idx) => {
          if (idx >= limit) {
            setTimeout(() => {
              item.classList.remove('hidden-item');
              item.classList.add('animate-fade-in');
              if (typeof initReveal === 'function') initReveal();
            }, delay);
            delay += 70;
          }
        });
      } else {
        items.forEach((item, idx) => {
          if (idx >= limit) {
            item.classList.add('hidden-item');
            item.classList.remove('animate-fade-in');
          }
        });
        document.getElementById(containerId).scrollIntoView({ behavior: 'smooth' });
      }
      
      btn.innerText = expanded ? 'Thu gọn' : (btnId.includes('Quotas') ? 'Xem thêm chỉ tiêu' : 'Xem thêm cơ sở');
    });
  }

  setupShowMore('btnMoreQuotas', 'quotaContainer', 'quota-item');
  setupShowMore('btnMoreThpt', 'thptContainer', 'school-item');
  setupShowMore('btnMoreThcs', 'thcsContainer', 'school-item');

  function applyInitialLimits() {
    const limits = getLimits();
    
    document.querySelectorAll('.quota-item').forEach((item, idx) => {
      if (idx >= limits.quota) item.classList.add('hidden-item');
      else item.classList.remove('hidden-item');
    });
    
    ['thptContainer', 'thcsContainer'].forEach(contId => {
       const items = document.querySelectorAll('#' + contId + ' .school-item');
       items.forEach((item, idx) => {
          if (idx >= limits.school) item.classList.add('hidden-item');
          else item.classList.remove('hidden-item');
       });
    });
  }

  // Initialize limits
  applyInitialLimits();
  window.addEventListener('resize', applyInitialLimits);

  // Custom Tab switching for Network
  document.querySelectorAll('#schoolTabs button').forEach(button => {
    button.addEventListener('click', function() {
      this.blur();
      document.querySelectorAll('#schoolTabs button').forEach(btn => {
        btn.classList.remove('active', 'tw-text-primary', 'tw-border-b-4', 'tw-border-primary');
        btn.classList.add('tw-text-on-surface-variant', 'tw-font-medium');
      });
      this.classList.add('active', 'tw-text-primary', 'tw-border-b-4', 'tw-border-primary');
      this.classList.remove('tw-text-on-surface-variant', 'tw-font-medium');
      this.classList.add('tw-font-bold');
      
      // Toggle tab content visibility
      const targetId = this.getAttribute('data-target');
      document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('show', 'active');
      });
      document.getElementById(targetId).classList.add('show', 'active');
      
      document.getElementById('schoolSearch').value = '';
      const limits = getLimits();
      document.querySelectorAll('.school-item').forEach((item) => {
         const parentId = item.parentElement.id;
         const itemsInThisContainer = document.querySelectorAll('#' + parentId + ' .school-item');
         const itemIdx = Array.from(itemsInThisContainer).indexOf(item);
         if (itemIdx < limits.school) item.classList.remove('hidden-item');
         else item.classList.add('hidden-item');
      });
      
      const btnThpt = document.getElementById('btnMoreThpt');
      const btnThcs = document.getElementById('btnMoreThcs');
      if (btnThpt) btnThpt.innerText = 'Xem thêm cơ sở';
      if (btnThcs) btnThcs.innerText = 'Xem thêm cơ sở';
    });
  });
  
  const videoTitleEl = document.getElementById('video-title');
  if (videoTitleEl) {
    videoTitleEl.innerText = "Trường THPT chuyên Lý Tự Trọng";
  }

  // Scroll reveal initialization
  const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('reveal-visible');
        try {
          if (entry.target.closest && entry.target.closest('#calendar')) {
            const p = entry.target.querySelector(':scope > p');
            if (p) p.remove();
          }
        } catch (e) {}
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);
  
  function initReveal() {
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
  }
  
  // Initialize on load
  initReveal();
  
  // Re-initialize when "Show More" is clicked or tabs changed
  document.querySelectorAll('button[id^="btnMore"], #schoolTabs button').forEach(btn => {
     btn.addEventListener('click', () => {
        setTimeout(initReveal, 100);
     });
  });
</script>
