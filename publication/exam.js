const publicationDialog = document.getElementById('confirm-publication');
const publicationButton = document.getElementById('open-publication');
publicationButton?.addEventListener('click', () => publicationDialog.showModal());
document.getElementById('cancel-publication')?.addEventListener('click', () => publicationDialog.close());
publicationDialog?.addEventListener('close', () => publicationButton.focus());
publicationDialog?.querySelector('form').addEventListener('submit', event => {
    if (publicationDialog.dataset.submitting) event.preventDefault();
    else publicationDialog.dataset.submitting = '1';
});
