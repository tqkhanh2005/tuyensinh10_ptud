(() => {
  const form = document.getElementById('quota-form');
  if (!form) return;
  const rows = [...document.querySelectorAll('[data-quota-row]')];
  const search = document.getElementById('quota-search');
  const status = document.getElementById('quota-status');
  const size = document.getElementById('quota-page-size');
  const previous = document.getElementById('quota-prev');
  const next = document.getElementById('quota-next');
  const normalize = value => value.toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
  let page = 1;
  let dirty = form.dataset.dirty === '1';
  let submitting = false;
  const warning = 'Có thay đổi chưa lưu, bạn có chắc muốn thoát?';
  const render = () => {
    const found = rows.filter(row => normalize(row.dataset.search).includes(normalize(search.value.trim())) && (!status.value || row.dataset.status === status.value));
    const pages = Math.max(1, Math.ceil(found.length / Number(size.value)));
    page = Math.min(page, pages);
    rows.forEach(row => { row.hidden = true; });
    found.slice((page - 1) * Number(size.value), page * Number(size.value)).forEach(row => { row.hidden = false; });
    previous.disabled = page <= 1;
    next.disabled = page >= pages;
    document.getElementById('quota-empty').hidden = found.length > 0;
    document.getElementById('quota-page-info').textContent = `${found.length} trường · Trang ${page}/${pages}`;
  };
  [search, status, size].forEach(input => input.addEventListener('input', () => { page = 1; render(); }));
  previous.addEventListener('click', () => { page--; render(); });
  next.addEventListener('click', () => { page++; render(); });
  form.addEventListener('input', () => { dirty = true; });
  form.addEventListener('change', () => { dirty = true; });
  form.addEventListener('submit', event => {
    if (event.submitter?.value === 'import' && !document.getElementById('quota-excel').files.length) {
      event.preventDefault(); alert('Vui lòng chọn file Excel .xlsx.'); return;
    }
    submitting = true;
  });
  document.querySelectorAll('.quota-error-link').forEach(link => link.addEventListener('click', event => {
    event.preventDefault(); search.value = ''; status.value = '';
    const row = document.getElementById(link.hash.slice(1));
    if (!row) return;
    page = Math.floor(rows.indexOf(row) / Number(size.value)) + 1; render();
    row.querySelector('input').focus();
  }));
  document.addEventListener('click', event => {
    const link = event.target.closest('a[href]');
    if (!link || link.id === 'quota-template' || link.getAttribute('href').startsWith('#') || !dirty) return;
    if (!confirm(warning)) event.preventDefault();
    else dirty = false;
  });
  window.addEventListener('beforeunload', event => { if (dirty && !submitting) { event.preventDefault(); event.returnValue = warning; } });
  render();
  if (document.querySelector('.table-danger[data-quota-row]')) {
    page = Math.floor(rows.findIndex(row => row.classList.contains('table-danger')) / Number(size.value)) + 1; render();
  }
  const confirmation = document.getElementById('quota-confirm');
  if (confirmation) bootstrap.Modal.getOrCreateInstance(confirmation).show();
})();
