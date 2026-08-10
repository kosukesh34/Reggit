declare const liff: { openWindow?: (opts: { url: string; external: boolean }) => void };

export function isExternalHref(href: string): boolean {
    return href !== '#' && (href.startsWith('http://') || href.startsWith('https://'));
}

export function openBuilderHref(href: string, isExternal: boolean): void {
    if (href === '') {
        return;
    }
    if (href.startsWith('#')) {
        window.location.hash = href;
        return;
    }
    if (
        isExternal
        && window.__IS_LIFF__
        && typeof liff !== 'undefined'
        && typeof liff.openWindow === 'function'
    ) {
        liff.openWindow({ url: href, external: false });
        return;
    }
    window.location.href = href;
}

const NAV_SELECTOR = [
    '.builder-btn',
    '.builder-nav',
    '.builder-promo-banner--link',
    '.builder-collection__item',
    '.builder-editorial-card--link',
].join(', ');

/** 外部リンクのみ intercept。hash は href のままブラウザ遷移。 */
export function handleBuilderNavClick(e: MouseEvent): void {
    const target = e.target;
    if (!(target instanceof Element)) {
        return;
    }
    const btn = target.closest(NAV_SELECTOR);
    if (btn === null) {
        return;
    }
    const href = btn.getAttribute('href');
    if (href === null || href === '') {
        return;
    }
    if (btn.getAttribute('data-builder-external') === '1' || isExternalHref(href)) {
        e.preventDefault();
        openBuilderHref(href, true);
    }
}
