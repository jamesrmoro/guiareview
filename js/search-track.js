/* global grvSearchTrack */
document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.getAttribute('role') !== 'search') return;
    const field = form.querySelector('[name="s"]');
    const term = field?.value.trim();
    if (!term) return;
    const data = new URLSearchParams({action: 'grv_record_search', term});
    if (!navigator.sendBeacon || !navigator.sendBeacon(grvSearchTrack.endpoint, data)) {
        fetch(grvSearchTrack.endpoint, {method: 'POST', body: data, credentials: 'same-origin', keepalive: true}).catch(() => {});
    }
}, true);
