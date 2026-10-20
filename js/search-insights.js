document.getElementById('grv-copy-searches')?.addEventListener('click', async event => {
    const button = event.currentTarget;
    const status = document.getElementById('grv-copy-search-status');
    button.disabled = true;
    status.textContent = 'Preparando tabela…';
    try {
        const response = await fetch(button.dataset.url, {credentials: 'same-origin'});
        if (!response.ok || !response.headers.get('content-type')?.includes('text/tab-separated-values')) throw new Error('export');
        const text = await response.text();
        try {
            await navigator.clipboard.writeText(text);
        } catch (_) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.append(textarea);
            textarea.select();
            const copied = document.execCommand('copy');
            textarea.remove();
            if (!copied) throw new Error('clipboard');
        }
        status.textContent = 'Tabela copiada. Cole no Excel ou em uma planilha.';
    } catch (_) {
        status.textContent = 'Não foi possível copiar. Use Exportar CSV.';
    } finally {
        button.disabled = false;
    }
});
