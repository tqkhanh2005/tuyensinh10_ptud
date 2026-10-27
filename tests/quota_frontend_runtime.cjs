const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
class Element {
  constructor(value = '') { this.value = value; this.dataset = {}; this.handlers = {}; this.hidden = false; this.disabled = false; }
  addEventListener(type, handler) { (this.handlers[type] ??= []).push(handler); }
  trigger(type, event = {}) { for (const handler of this.handlers[type] ?? []) handler(event); }
}
const elements = Object.fromEntries(['quota-form','quota-search','quota-status','quota-page-size','quota-prev','quota-next','quota-empty','quota-page-info','quota-excel','quota-confirm'].map(id => [id, new Element()]));
elements['quota-page-size'].value = '20';
elements['quota-form'].dataset.dirty = '0';
elements['quota-excel'].files = [];
const rows = Array.from({length: 45}, (_, index) => {
  const row = new Element();
  row.dataset.search = index === 44 ? 'HCM_45 Trường THPT Thủ Đức' : `HCM_${index + 1} THPT ${index + 1}`;
  row.dataset.status = index < 20 ? 'set' : 'not_set';
  row.input = new Element(index < 20 ? '100' : '');
  return row;
});
const document = new Element();
document.getElementById = id => elements[id];
document.querySelectorAll = selector => selector === '[data-quota-row]' ? rows : [];
document.querySelector = () => null;
const window = new Element();
let alerts = 0, modalOpened = false, leaveApproved = false;
vm.runInNewContext(fs.readFileSync('quotas/quotas.js','utf8'), {
  document, window, alert: () => alerts++, confirm: () => leaveApproved,
  bootstrap: { Modal: { getOrCreateInstance: () => ({show: () => { modalOpened = true; }}) } },
});
let checks = 0;
const check = (condition, message) => { assert.ok(condition, message); console.log(`PASS ${++checks}: ${message}`); };
const visible = () => rows.filter(row => !row.hidden);
check(visible().length === 20 && elements['quota-prev'].disabled, 'First page displays 20 rows');
rows[0].input.value = '123';
elements['quota-form'].trigger('input');
elements['quota-next'].trigger('click');
check(visible().length === 20 && rows[0].hidden, 'Next page changes visible rows');
check(rows[0].input.value === '123' && !rows[0].input.disabled, 'Hidden edits remain enabled and retain values for submission');
elements['quota-next'].trigger('click');
check(visible().length === 5 && elements['quota-next'].disabled, 'Final page and next button boundaries');
elements['quota-status'].value = 'not_set'; elements['quota-status'].trigger('input');
check(visible().length === 20 && visible().every(row => row.dataset.status === 'not_set'), 'Status filter uses saved state and resets pagination');
elements['quota-search'].value = 'thu duc'; elements['quota-search'].trigger('input');
check(visible().length === 1 && visible()[0] === rows[44], 'Vietnamese search supports accents and đ');
elements['quota-search'].value = 'no match'; elements['quota-search'].trigger('input');
check(visible().length === 0 && !elements['quota-empty'].hidden, 'Search empty state');
elements['quota-search'].value = ''; elements['quota-status'].value = ''; elements['quota-page-size'].value = '50'; elements['quota-page-size'].trigger('input');
check(visible().length === 45 && rows[0].input.value === '123', 'Page size and filter changes preserve edits');
let prevented = false;
elements['quota-form'].trigger('submit', {submitter: {value: 'import'}, preventDefault: () => { prevented = true; }});
check(prevented && alerts === 1, 'Empty Excel selection prevents submission');
prevented = false;
window.trigger('beforeunload', {preventDefault: () => { prevented = true; }});
check(prevented, 'Unsaved changes trigger browser exit warning');
const link = {id: '', getAttribute: () => '/admission/index.php'};
prevented = false;
document.trigger('click', {target: {closest: () => link}, preventDefault: () => { prevented = true; }});
check(prevented, 'Declining exit leaves page unchanged');
link.id = 'quota-template'; prevented = false;
document.trigger('click', {target: {closest: () => link}, preventDefault: () => { prevented = true; }});
check(!prevented, 'Template download bypasses leave warning');
check(modalOpened, 'Server confirmation opens Bootstrap modal');
elements['quota-form'].trigger('submit', {submitter: {value: 'preview'}});
prevented = false;
window.trigger('beforeunload', {preventDefault: () => { prevented = true; }});
check(!prevented, 'Workflow submission does not trigger exit warning');
console.log(`PASS: ${checks} frontend behavior checks.`);
