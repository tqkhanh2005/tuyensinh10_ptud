(() => {
  const form = document.getElementById('council-form');
  const box = document.getElementById('members');
  const type = document.getElementById('council_type');
  const staff = JSON.parse(document.getElementById('council-staff').textContent);
  const saved = JSON.parse(document.getElementById('council-members').textContent);
  let dirty = saved.length > 0, submitting = false, counter = 0;
  const roles = ['ra đề', 'phản biện', 'thẩm định'];
  function field(parent, label, element) {
    const col = document.createElement('div'); col.className = 'col-md-3';
    const title = document.createElement('label'); title.className = 'form-label'; title.textContent = label;
    element.id = `council-field-${counter++}`; title.htmlFor = element.id;
    element.className = element.tagName === 'SELECT' ? 'form-select' : 'form-control';
    col.append(title, element); parent.append(col); return element;
  }
  function addMember(data = {}) {
    const row = document.createElement('div'); row.className = 'member-row';
    const person = document.createElement('select'); person.name = 'staff_id[]'; person.required = true;
    person.add(new Option('-- Chọn cán bộ --', ''));
    staff.filter(s => s.active === 1 || String(s.id) === String(data.staff_id)).forEach(s => person.add(new Option(s.name + (s.active ? '' : ' (ngừng hoạt động)'), String(s.id))));
    if (data.staff_id && !staff.some(s => String(s.id) === String(data.staff_id))) person.add(new Option(`Cán bộ không còn tồn tại (#${data.staff_id})`, String(data.staff_id)));
    person.value = String(data.staff_id || ''); field(row, 'Cán bộ', person);
    const roleBox = document.createElement('div'); roleBox.className = 'col-md-3';
    const label = document.createElement('label'); label.className = 'form-label'; label.textContent = 'Vai trò';
    const text = document.createElement('input'); text.className = 'form-control'; text.maxLength = 100; text.value = data.role_name || '';
    text.id = `council-field-${counter++}`;
    const choice = document.createElement('select'); choice.className = 'form-select'; choice.id = `council-field-${counter++}`;
    choice.add(new Option('-- Chọn vai trò --', '')); roles.forEach(r => choice.add(new Option(r, r)));
    const normalized = (data.role_name || '').trim().toLocaleLowerCase('vi');
    if (normalized && !roles.includes(normalized)) choice.add(new Option(`Vai trò không hợp lệ: ${data.role_name}`, normalized));
    choice.value = normalized;
    roleBox.append(label, text, choice); row.append(roleBox);
    const task = document.createElement('input'); task.name = 'task_name[]'; task.maxLength = 255; task.value = data.task_name || ''; field(row, 'Nhiệm vụ', task);
    const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-outline-danger'; remove.textContent = 'Xóa dòng';
    const actions = document.createElement('div'); actions.className = 'member-actions'; actions.append(remove); row.append(actions);
    remove.addEventListener('click', () => { if (box.children.length === 1) { alert('Cần giữ ít nhất một thành viên.'); return; } row.remove(); dirty = true; });
    row.syncRole = () => {
      const question = type.value === 'question';
      text.hidden = question; text.disabled = question; text.name = question ? '' : 'role_name[]';
      choice.hidden = !question; choice.disabled = !question; choice.required = question; choice.name = question ? 'role_name[]' : '';
      label.htmlFor = question ? choice.id : text.id;
    };
    box.append(row); row.syncRole(); person.focus();
  }
  function sync() {
    const invigilation = type.value === 'invigilation';
    document.getElementById('site_wrap').hidden = !invigilation;
    document.getElementById('subject_wrap').hidden = invigilation;
    const site = form.querySelector('[name="exam_site_id"]'); site.disabled = !invigilation; site.required = invigilation;
    const subjects = form.querySelector('[name="exam_subject_ids[]"]'); const hasSubjects = subjects.dataset.hasSubjects !== '0';
    subjects.disabled = invigilation || !hasSubjects; subjects.required = !invigilation && hasSubjects;
    form.querySelector('button[type="submit"]').disabled = !invigilation && !hasSubjects;
    const multiple = type.value === 'grading';
    if (!multiple && subjects.selectedOptions.length > 1) subjects.value = '';
    subjects.multiple = multiple; subjects.size = multiple ? 4 : 1;
    [...box.children].forEach(row => row.syncRole());
  }
  (saved.length ? saved : [{}]).forEach(addMember); sync();
  document.getElementById('add_member').addEventListener('click', () => { addMember(); dirty = true; });
  type.addEventListener('change', sync);
  form.addEventListener('input', () => { dirty = true; });
  form.addEventListener('change', () => { dirty = true; });
  form.addEventListener('submit', event => {
    if (submitting) { event.preventDefault(); return; }
    const people = [...box.querySelectorAll('[name="staff_id[]"]')].map(input => input.value);
    if (new Set(people).size !== people.length) { event.preventDefault(); alert('Không được trùng thành viên trong cùng hội đồng.'); return; }
    submitting = true;
  });
  window.addEventListener('beforeunload', event => { if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; } });
})();
