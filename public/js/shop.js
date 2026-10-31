document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('.copy-contact');
    if (!button) return;
    try {
        await navigator.clipboard.writeText(button.dataset.message);
        button.textContent = button.dataset.copied;
    } catch {
        const textarea = button.parentElement.querySelector('textarea');
        textarea.focus();
        textarea.select();
    }
});
document.addEventListener('click', (event) => {
    const removeButton = event.target.closest('[data-remove-fitment]');
    if (removeButton) {
        removeButton.closest('.fitment-row').remove();
        return;
    }
    const button = event.target.closest('[data-add-fitment]');
    if (!button) return;
    const container = document.getElementById('fitment-rows');
    const template = document.getElementById('fitment-template');
    const index = Number(container.dataset.next);
    if (index >= 30) return;
    container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(index)));
    container.dataset.next = String(index + 1);
});
