document.addEventListener('click', async event => {
    const button = event.target.closest('.grv-affiliate-copy');
    if (!button) return;
    const cell = button.closest('td');
    const input = cell.querySelector('.grv-affiliate-copy-value');
    const status = cell.querySelector('.grv-copy-status');
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(input.value);
        } else {
            input.focus(); input.select();
            if (!document.execCommand('copy')) throw new Error('copy');
        }
        status.textContent = 'Link copiado!';
    } catch (_) {
        input.focus(); input.select();
        status.textContent = 'Selecionei o link. Use Ctrl+C para copiar.';
    }
});
