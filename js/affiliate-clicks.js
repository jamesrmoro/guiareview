/* global grvAffiliateTracking */
(() => {
    function record(event) {
        if (event.defaultPrevented || (event.type === 'click' && event.button !== 0) || (event.type === 'auxclick' && event.button !== 1)) return;
        const link = event.target.closest('a[data-grv-product][data-grv-click-token]');
        if (!link) return;
        const data = new FormData();
        data.append('action', 'grv_affiliate_click');
        data.append('product', link.dataset.grvProduct);
        data.append('token', link.dataset.grvClickToken);
        // The original affiliate href opens normally while this records the click.
        if (typeof fetch === 'function') {
            fetch(grvAffiliateTracking.endpoint, {method: 'POST', body: data, keepalive: true, credentials: 'same-origin'})
                .then(response => response.json())
                .then(result => {
                    const row = link.closest('tr');
                    const counter = row && row.querySelector('.column-grv_clicks');
                    if (counter && result.success && Number.isInteger(result.data.total)) {
                        counter.textContent = new Intl.NumberFormat('pt-BR').format(result.data.total);
                    }
                }).catch(() => {});
        } else if (navigator.sendBeacon) {
            navigator.sendBeacon(grvAffiliateTracking.endpoint, data);
        }
    }
    document.addEventListener('click', record);
    document.addEventListener('auxclick', record);
})();
