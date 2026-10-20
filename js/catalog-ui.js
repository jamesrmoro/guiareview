(() => {
    const nav = document.getElementById('grvCategoryNav');
    if (nav) {
        const more = nav.querySelector('.grv-nav-more');
        const overflow = document.getElementById('grvMoreCategories');
        const mainItems = Array.from(nav.children).filter(item => item !== more);
        const moreItems = Array.from(overflow.children);
        const fitNavigation = () => {
            mainItems.forEach(item => { item.hidden = false; });
            for (let index = mainItems.length - 1; index >= 0 && nav.scrollWidth > nav.clientWidth; index--) {
                mainItems[index].hidden = true;
            }
            const visible = new Set(mainItems.filter(item => !item.hidden).map(item => item.dataset.categoryKey));
            moreItems.forEach(item => { item.hidden = visible.has(item.dataset.categoryKey); });
            document.getElementById('grvMoreCount').textContent = moreItems.filter(item => !item.hidden).length + ' categorias';
        };
        fitNavigation();
        if ('ResizeObserver' in window) new ResizeObserver(fitNavigation).observe(nav);
        else window.addEventListener('resize', fitNavigation);
        nav.addEventListener('toggle', event => {
            const dropdown = event.target;
            if (!dropdown.open) return;
            nav.querySelectorAll('details[open]').forEach(other => {
                if (other !== dropdown && !other.contains(dropdown) && !dropdown.contains(other)) other.open = false;
            });
        }, true);
        document.addEventListener('click', event => {
            if (!nav.contains(event.target)) nav.querySelectorAll('details[open]').forEach(item => { item.open = false; });
        });
        nav.addEventListener('keydown', event => {
            if (event.key !== 'Escape') return;
            const dropdown = event.target.closest('details[open]');
            if (dropdown) { dropdown.open = false; dropdown.querySelector('summary').focus(); event.stopPropagation(); }
        });
    }

    const drawer = document.getElementById('grvDrawer');
    const categorySearch = document.getElementById('grvCategorySearch');
    const categoryRows = Array.from(document.querySelectorAll('#grvDrawerCategories > li'));
    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR').trim();
    categorySearch?.addEventListener('input', () => {
        const query = normalize(categorySearch.value);
        categoryRows.forEach(row => { row.hidden = !normalize(row.querySelector('[data-category-name]').dataset.categoryName).includes(query); });
        document.getElementById('grvCategoryEmpty').hidden = categoryRows.some(row => !row.hidden);
    });
    document.querySelector('[data-open-category-drawer]')?.addEventListener('click', () => {
        nav.querySelectorAll('details[open]').forEach(item => { item.open = false; });
        drawer.showModal();
        document.getElementById('grvMenuToggle').setAttribute('aria-expanded', 'true');
        categorySearch.focus();
    });
    drawer?.addEventListener('close', () => {
        categorySearch.value = '';
        categoryRows.forEach(row => { row.hidden = false; });
        document.getElementById('grvCategoryEmpty').hidden = true;
    });

    const description = document.getElementById('grvProductDetails');
    const toggle = document.querySelector('.grv-description-toggle');
    if (description && toggle) {
        const setExpanded = expanded => {
            description.classList.toggle('is-collapsed', !expanded);
            toggle.setAttribute('aria-expanded', String(expanded));
            toggle.querySelector('span').textContent = expanded ? 'Ver menos informações' : 'Ver mais informações';
        };
        const measure = () => {
            const limit = parseFloat(getComputedStyle(description).getPropertyValue('--description-height'));
            toggle.hidden = description.scrollHeight <= limit;
            description.classList.toggle('is-collapsed', !toggle.hidden && toggle.getAttribute('aria-expanded') !== 'true');
        };
        measure();
        window.addEventListener('load', measure, {once: true});
        window.addEventListener('resize', measure);
        toggle.addEventListener('click', () => {
            const expand = toggle.getAttribute('aria-expanded') !== 'true';
            setExpanded(expand);
            if (!expand) toggle.scrollIntoView({block: 'nearest'});
        });
        // A keyboard user can reach links in the full description without clipping.
        description.addEventListener('focusin', event => {
            if (description.classList.contains('is-collapsed') && event.target.getBoundingClientRect().bottom > description.getBoundingClientRect().bottom - 60) setExpanded(true);
        });
    }

    document.querySelectorAll('[data-copy-url]').forEach(button => {
        button.addEventListener('click', async () => {
            const status = button.parentElement.querySelector('[role="status"]');
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(button.dataset.copyUrl);
                } else {
                    const input = document.createElement('textarea');
                    input.value = button.dataset.copyUrl;
                    input.style.cssText = 'position:fixed;left:-9999px;top:0';
                    document.body.append(input);
                    input.select();
                    const copied = document.execCommand('copy');
                    input.remove();
                    button.focus();
                    if (!copied) throw new Error('copy');
                }
                status.textContent = 'Link copiado!';
                button.title = 'Link copiado!';
            } catch (_) {
                status.textContent = 'Não foi possível copiar. Copie o endereço na barra do navegador.';
            }
        });
    });
})();
