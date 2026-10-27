const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const elements = {};
for (const id of ['confirm-publication', 'open-publication', 'cancel-publication', 'form']) {
    elements[id] = { events: {}, dataset: {}, addEventListener(name, fn) { this.events[name] = fn; } };
}
const dialog = elements['confirm-publication'];
dialog.querySelector = () => elements.form;
dialog.showModal = () => { dialog.open = true; };
dialog.close = () => { dialog.open = false; dialog.events.close(); };
elements['open-publication'].focus = () => { elements['open-publication'].focused = true; };
vm.runInNewContext(fs.readFileSync('publication/exam.js', 'utf8'), { document: { getElementById: id => elements[id] } });
elements['open-publication'].events.click();
assert.equal(dialog.open, true);
elements['cancel-publication'].events.click();
assert.equal(dialog.open, false);
assert.equal(elements['open-publication'].focused, true);
assert.equal(dialog.dataset.submitting, undefined);
let prevented = 0;
elements.form.events.submit({ preventDefault() { prevented++; } });
elements.form.events.submit({ preventDefault() { prevented++; } });
assert.equal(prevented, 1);
console.log('Passed 5 publication dialog checks.');
