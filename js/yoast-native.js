/* global YoastSEO, grvYoastNative */
(function ($) {
    'use strict';
    let registered = false;
    const escape = value => $('<span>').text(value).html();
    function rows(value) {
        return value.split(/\r\n|\r|\n/).map(line => line.trim()).filter(Boolean).map(line => {
            const colon = line.indexOf(':');
            return colon < 0 ? {label: '', text: line} : {label: line.slice(0, colon).trim(), text: line.slice(colon + 1).trim()};
        });
    }
    function nativeContent() {
        const wrapper = $('<div>').html(grvYoastNative.content);
        ['bullets', 'specs'].forEach(field => {
            const input = $('#grv-' + field);
            if (!input.length) return;
            const items = rows(input.val());
            let html = '';
            if (field === 'bullets') {
                html = '<ul>' + items.map(row => '<li>' + escape((row.label ? row.label + ': ' : '') + row.text) + '</li>').join('') + '</ul>';
            } else if (items.length) {
                let groups = {'': items};
                if (items.length > 25) {
                    groups = {};
                    items.forEach(row => {
                        const label = row.label.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                        const group = /tela|resolucao|exibicao/.test(label) ? 'Tela e imagem' : /memoria|ram|disco|armazenamento/.test(label) ? 'Memória e armazenamento' : /cpu|processador|grafico|video/.test(label) ? 'Processador e gráficos' : /conect|comunicacao|bateria|pilha|energia|celula/.test(label) ? 'Conexões e energia' : 'Características gerais';
                        (groups[group] || (groups[group] = [])).push(row);
                    });
                }
                html = '<h2>Informações do produto</h2>' + Object.entries(groups).map(([label, values]) => (label ? '<h3>' + escape(label) + '</h3>' : '') + '<table class="specs"><tbody>' + values.map(row => '<tr><th>' + escape(row.label || '—') + '</th><td>' + escape(row.text) + '</td></tr>').join('') + '</tbody></table>').join('');
            }
            let target = wrapper.find('[data-grv-analysis="' + field + '"]');
            if (!target.length) target = $('<div>').attr('data-grv-analysis', field).appendTo(wrapper);
            target.html(html);
        });
        const affiliate = $('#grv-affiliate_url');
        const offer = affiliate.length && affiliate.val() ? affiliate : $('#grv-url');
        if (offer.length) {
            wrapper.find('a[rel*="sponsored"]').remove();
            if (/^https?:\/\//i.test(offer.val())) $('<a>', {href: offer.val(), rel: 'nofollow sponsored noopener noreferrer', text: 'Ver oferta na loja'}).appendTo(wrapper);
        }
        return wrapper.html();
    }
    function register() {
        if (registered || typeof YoastSEO === 'undefined' || !YoastSEO.app) return;
        registered = true;
        YoastSEO.app.registerPlugin('grvNativeFields', {status: 'ready'});
        YoastSEO.app.registerModification('content', function (content) {
            return content + nativeContent();
        }, 'grvNativeFields', 10);
    }
    $(window).on('YoastSEO:ready', register);
    register();
    let refreshTimer;
    $(document).on('input change', '#grv-bullets, #grv-specs, #grv-url, #grv-affiliate_url', function () {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(function () {
            if (registered && typeof YoastSEO.app.refresh === 'function') YoastSEO.app.refresh();
        }, 350);
    });
})(jQuery);
