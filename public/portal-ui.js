/* Keep wide tables inside their own keyboard-accessible scroll region. */
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.workflow-content table').forEach((table) => {
    table.classList.add('table', 'table-hover', 'align-middle');
    table.removeAttribute('border');
    table.removeAttribute('cellpadding');
    if (!table.closest('.table-responsive')) {
      const region = document.createElement('div');
      region.className = 'table-responsive';
      table.before(region);
      region.append(table);
    }
  });
  document.querySelectorAll('.table-responsive').forEach((region) => {
    region.tabIndex = 0;
    region.setAttribute('role', 'region');
    region.setAttribute('aria-label', 'Bảng dữ liệu, cuộn ngang để xem các cột');
    const table = region.querySelector('table');
    if (!table) return;
    const columns = Math.max(0, ...Array.from(table.rows, row => Array.from(row.cells).reduce((sum, cell) => sum + cell.colSpan, 0)));
    if (columns && !table.querySelector('td')) {
      const body = table.tBodies[0] || table.createTBody();
      const cell = body.insertRow().insertCell();
      cell.colSpan = columns;
      cell.className = 'text-center py-4 text-muted';
      cell.textContent = 'Chưa có dữ liệu phù hợp.';
    }
    if (columns >= 4) table.classList.add('portal-data-table');
    if (columns >= 8) table.classList.add('portal-table-wide');
    // Teacher lists mix identifiers, short values and long names. Give each its own width.
    if (table.closest('.teacher-main-wrapper') && columns >= 4 && !table.querySelector('colgroup')) {
      const headers = Array.from(table.querySelectorAll('thead tr:first-child th'));
      if (headers.length !== columns) return;
      const widths = headers.map(header => {
        const label = header.textContent.trim().toLocaleLowerCase('vi');
        if (/lý do|phản hồi|dữ liệu thay đổi/.test(label)) return 260;
        if (/họ và tên|họ tên|học sinh|thí sinh|người gửi/.test(label)) return 210;
        if (/trường|đơn vị/.test(label) && !/mã trường/.test(label)) return 220;
        if (/ngày sinh/.test(label)) return 115;
        if (/giới tính/.test(label)) return 70;
        if (/phòng thi/.test(label)) return 90;
        if (/điều kiện/.test(label)) return 80;
        if (/trạng thái|nhập học/.test(label)) return 110;
        if (/thao tác/.test(label)) return 125;
        if (/điểm các môn/.test(label)) return 240;
        if (/thời gian/.test(label)) return 170;
        return 130;
      });
      const colgroup = document.createElement('colgroup');
      widths.forEach(width => {
        const col = document.createElement('col');
        col.style.width = `${width}px`;
        colgroup.append(col);
      });
      table.prepend(colgroup);
      table.style.minWidth = `${widths.reduce((sum, width) => sum + width, 0)}px`;
    }
  });
  [['adminSidebar', 'adminSidebarToggleBtn', 'adminSidebarCloseBtn', 'adminSidebarBackdrop'],
   ['teacherSidebar', 'teacherSidebarToggleBtn', 'teacherSidebarCloseBtn', 'teacherSidebarBackdrop']].forEach(([sidebarId,toggleId,closeId,backdropId]) => {
    const sidebar = document.getElementById(sidebarId);
    const toggle = document.getElementById(toggleId);
    const close = document.getElementById(closeId);
    const backdrop = document.getElementById(backdropId);
    if (!sidebar || !toggle || !close || !backdrop) return;
    const media = window.matchMedia('(max-width: 991.98px)');
    let previousOverflow = '';
    let open = false;
    toggle.setAttribute('aria-controls', sidebarId);
    const sync = () => {
      const mobileOpen = media.matches && open;
      sidebar.classList.toggle('show', mobileOpen);
      backdrop.classList.toggle('show', mobileOpen);
      sidebar.inert = media.matches && !open;
      toggle.setAttribute('aria-expanded', String(mobileOpen));
    };
    const hide = () => {
      if (!open) return;
      open = false;
      document.body.style.overflow = previousOverflow;
      sync();
      toggle.focus();
    };
    toggle.addEventListener('click', () => {
      previousOverflow = document.body.style.overflow;
      open = true;
      document.body.style.overflow = 'hidden';
      sync();
      close.focus();
    });
    close.addEventListener('click', hide);
    backdrop.addEventListener('click', hide);
    document.addEventListener('keydown', event => {
      if (!open || !media.matches) return;
      if (event.key === 'Escape') { event.preventDefault(); hide(); }
      if (event.key === 'Tab') {
        const targets = Array.from(sidebar.querySelectorAll('a[href],button:not([disabled]),input:not([type=hidden]),select,textarea,[tabindex="0"]')).filter(el => el.getClientRects().length);
        const first = targets[0], last = targets[targets.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
      }
    });
    media.addEventListener('change', () => { if (open) hide(); sync(); });
    sync();
  });
});
