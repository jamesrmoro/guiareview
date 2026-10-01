/* global grvWidgets */
(() => {
    const storage = {
        get(key, session = false) { try { return (session ? sessionStorage : localStorage).getItem(key); } catch (_) { return null; } },
        set(key, value, session = false) { try { (session ? sessionStorage : localStorage).setItem(key, value); } catch (_) {} }
    };
    const notice = document.querySelector('.cookie-notice');
    if (notice && !storage.get('cookie-guiareview')) notice.hidden = false;
    notice?.querySelector('.accept')?.addEventListener('click', () => {
        storage.set('cookie-guiareview', 'aceito'); notice.hidden = true;
    });
    const modal = document.getElementById('grvAdModal');
    const banner = document.getElementById('footer-banner');
    const shown = new Set();
    let priorFocus;
    function event(ad, kind) {
        const data = new URLSearchParams({action: 'grv_ad_event', ad: ad.id, event: kind, token: ad.token});
        fetch(grvWidgets.endpoint, {method: 'POST', body: data, credentials: 'same-origin', keepalive: true}).catch(() => {});
    }
    function impression(ad, placement) {
        const key = ad.id + ':' + placement;
        if (!shown.has(key) && document.visibilityState !== 'hidden') { shown.add(key); event(ad, 'views'); }
    }
    function observe(element, ad, placement) {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(entries => {
                if (entries.some(entry => entry.isIntersecting && entry.intersectionRatio >= 0.5)) { impression(ad, placement); observer.disconnect(); }
            }, {threshold: 0.5});
            observer.observe(element);
        } else { impression(ad, placement); }
    }
    function populate(element, ad) {
        const link = element.querySelector('[data-ad-link]');
        link.href = ad.url;
        element.querySelector('[data-ad-title]').textContent = ad.title;
        element.querySelector('[data-ad-cta]').textContent = ad.cta;
        const image = element.querySelector('[data-ad-image]');
        image.src = ad.image; image.alt = ad.title;
        link.addEventListener('click', () => event(ad, 'clicks'));
        link.addEventListener('auxclick', e => { if (e.button === 1) event(ad, 'clicks'); });
    }
    modal?.querySelector('[data-ad-close]')?.addEventListener('click', () => modal.close());
    modal?.addEventListener('click', e => { if (e.target === modal) modal.close(); });
    modal?.addEventListener('close', () => { storage.set('grv-ad-dismissed', '1', true); priorFocus?.focus(); });
    document.getElementById('toggle-banner')?.addEventListener('click', () => {
        const collapsed = banner.classList.toggle('collapsed');
        document.getElementById('toggle-banner').setAttribute('aria-expanded', String(!collapsed));
        storage.set('bannerCollapsed', collapsed ? '1' : '0');
    });
    let variants = {};
    try { variants = JSON.parse(storage.get('grv-ad-variants', true) || '{}'); } catch (_) {}
    fetch(grvWidgets.endpoint, {method: 'POST', credentials: 'same-origin', body: new URLSearchParams({action: 'grv_get_ads', variants: JSON.stringify(variants)})})
        .then(response => response.json()).then(response => {
            if (!response.success) return;
            const {modal: popup, footer} = response.data;
            [popup, footer].filter(Boolean).forEach(ad => { variants[ad.campaign + '|' + ad.position] = ad.id; });
            storage.set('grv-ad-variants', JSON.stringify(variants), true);
            if (footer && banner && !(grvWidgets.product && matchMedia('(max-width: 768px)').matches)) {
                populate(banner, footer); banner.hidden = false;
                if (storage.get('bannerCollapsed') === '1') { banner.classList.add('collapsed'); document.getElementById('toggle-banner').setAttribute('aria-expanded', 'false'); }
                observe(banner.querySelector('[data-ad-link]'), footer, 'footer');
            }
            if (popup && modal && !storage.get('grv-ad-dismissed', true)) {
                populate(modal, popup);
                setTimeout(() => {
                    if (popup.expires && popup.expires * 1000 <= Date.now()) return;
                    if (document.visibilityState === 'hidden' || document.querySelector('dialog[open], .grv-search-modal-bg.active')) return;
                    priorFocus = document.activeElement; modal.showModal(); observe(modal.querySelector('[data-ad-link]'), popup, 'modal');
                }, 2500);
            }
            setInterval(() => {
                if (footer?.expires && footer.expires * 1000 <= Date.now()) banner.hidden = true;
                if (popup?.expires && popup.expires * 1000 <= Date.now() && modal.open) modal.close();
            }, 30000);
        }).catch(() => {});
    const install = document.getElementById('installApp-bottom');
    if (install && !document.cookie.split(';').some(value => value.trim().startsWith('appDismissed='))) {
        install.style.display = 'flex'; document.body.classList.add('resize-app');
        document.getElementById('close-app')?.addEventListener('click', () => {
            install.style.display = 'none'; document.body.classList.remove('resize-app');
            document.cookie = 'appDismissed=true; max-age=604800; path=/; SameSite=Lax';
        });
    }
})();
