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
    const dayMilliseconds = 24 * 60 * 60 * 1000;
    const modalNextKey = 'grv-ad-next-show';
    function modalNextShow() {
        const cookie = document.cookie.split(';').map(value => value.trim()).find(value => value.startsWith(modalNextKey + '='));
        return Math.max(Number(storage.get(modalNextKey)) || 0, Number(cookie?.slice(modalNextKey.length + 1)) || 0);
    }
    function postponeModal(days = 7) {
        const next = Date.now() + days * dayMilliseconds;
        storage.set(modalNextKey, String(next));
        document.cookie = modalNextKey + '=' + next + '; max-age=' + days * 86400 + '; path=/; SameSite=Lax';
    }
    // Preserve a dismissal from the previous session-based behavior.
    if (!modalNextShow() && storage.get('grv-ad-dismissed', true)) postponeModal();
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
        image.src = element === banner ? (ad.thumbnail || ad.image) : ad.image; image.alt = ad.title;
        link.addEventListener('click', () => event(ad, 'clicks'));
        link.addEventListener('auxclick', e => { if (e.button === 1) event(ad, 'clicks'); });
    }
    modal?.querySelector('[data-ad-close]')?.addEventListener('click', () => modal.close());
    modal?.addEventListener('click', e => { if (e.target === modal) modal.close(); });
    modal?.addEventListener('close', () => { priorFocus?.focus(); });
    document.getElementById('toggle-banner')?.addEventListener('click', () => {
        const collapsed = banner.classList.toggle('collapsed');
        document.getElementById('toggle-banner').setAttribute('aria-expanded', String(!collapsed));
        storage.set('bannerCollapsed', collapsed ? '1' : '0');
    });
    let rotation = {};
    try {
        const saved = JSON.parse(storage.get('grv-ad-rotation') || '{}');
        if (saved && typeof saved === 'object' && !Array.isArray(saved)) rotation = saved;
    } catch (_) {}
    const variants = {}, previous = {};
    Object.entries(rotation).forEach(([key, entry]) => {
        if (!entry || !Number.isFinite(entry.id) || !Number.isFinite(entry.until)) return;
        if (entry.until > Date.now()) variants[key] = entry.id;
        else previous[key] = entry.id;
    });
    fetch(grvWidgets.endpoint, {method: 'POST', credentials: 'same-origin', body: new URLSearchParams({action: 'grv_get_ads', variants: JSON.stringify(variants), previous: JSON.stringify(previous)})})
        .then(response => response.json()).then(response => {
            if (!response.success) return;
            const {modal: popup, footer} = response.data;
            [popup, footer].filter(Boolean).forEach(ad => {
                const key = ad.campaign + '|' + ad.position;
                const entry = rotation[key];
                if (!entry || entry.id !== ad.id || entry.until <= Date.now()) {
                    rotation[key] = {id: ad.id, until: Date.now() + (ad.rotation_days ?? 7) * dayMilliseconds};
                }
            });
            storage.set('grv-ad-rotation', JSON.stringify(rotation));
            if (footer && banner && !(grvWidgets.product && matchMedia('(max-width: 768px)').matches)) {
                populate(banner, footer); banner.hidden = false;
                if (storage.get('bannerCollapsed') === '1') { banner.classList.add('collapsed'); document.getElementById('toggle-banner').setAttribute('aria-expanded', 'false'); }
                observe(banner.querySelector('[data-ad-link]'), footer, 'footer');
            }
            if (popup && modal && Date.now() >= modalNextShow()) {
                populate(modal, popup);
                setTimeout(() => {
                    if (Date.now() < modalNextShow()) return;
                    if (popup.expires && popup.expires * 1000 <= Date.now()) return;
                    if (document.visibilityState === 'hidden' || document.querySelector('dialog[open], .grv-search-modal-bg.active')) return;
                    priorFocus = document.activeElement; modal.showModal(); postponeModal(popup.repeat_days ?? 7); observe(modal.querySelector('[data-ad-link]'), popup, 'modal');
                }, 2500);
            }
            setInterval(() => {
                if (footer?.expires && footer.expires * 1000 <= Date.now()) banner.hidden = true;
                if (popup?.expires && popup.expires * 1000 <= Date.now() && modal.open) modal.close();
            }, 30000);
        }).catch(() => {});
})();
