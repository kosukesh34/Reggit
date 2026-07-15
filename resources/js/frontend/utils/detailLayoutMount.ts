type LayoutManagerLike = {
    renderCustomLayout: (el: HTMLElement, tab: string, blocks: unknown[]) => void;
};

/**
 * 詳細ページにデザイナー設定（背景・余白）と before/after ブロックを載せる。
 * data source スロットの位置に bodyHtml を挿入する。
 */
export function mountDetailLayoutChrome(options: {
    section: HTMLElement;
    layoutKey: string;
    dataSource: string;
    bodyHtml: string;
    slotClassName?: string;
}): void {
    const { section, layoutKey, dataSource, bodyHtml } = options;
    const slotClassName = options.slotClassName ?? 'liff-detail-slot';

    delete section.dataset.layoutRendered;
    applyDetailPageSettings(section, layoutKey);

    const layouts = (typeof window !== 'undefined' && window.__LIFF_DETAIL_LAYOUTS__)
        ? window.__LIFF_DETAIL_LAYOUTS__
        : {};
    const blocks = Array.isArray(layouts[layoutKey]) ? layouts[layoutKey] : [];
    const before: unknown[] = [];
    const after: unknown[] = [];
    let foundSlot = false;
    blocks.forEach((b) => {
        if (
            !foundSlot
            && b !== null
            && typeof b === 'object'
            && (b as { type?: string }).type === 'data'
            && (b as { source?: string }).source === dataSource
        ) {
            foundSlot = true;
            return;
        }
        if (foundSlot) {
            after.push(b);
        } else {
            before.push(b);
        }
    });

    section.innerHTML = '';
    const beforeEl = document.createElement('div');
    beforeEl.className = 'liff-detail-chrome liff-detail-chrome--before';
    const bodyEl = document.createElement('div');
    bodyEl.className = slotClassName;
    bodyEl.innerHTML = bodyHtml;
    const afterEl = document.createElement('div');
    afterEl.className = 'liff-detail-chrome liff-detail-chrome--after';
    section.appendChild(beforeEl);
    section.appendChild(bodyEl);
    section.appendChild(afterEl);

    const manager = getLayoutManager();
    if (manager !== null) {
        if (before.length > 0) {
            manager.renderCustomLayout(beforeEl, layoutKey, before);
        }
        if (after.length > 0) {
            manager.renderCustomLayout(afterEl, layoutKey, after);
        }
    }
}

function applyDetailPageSettings(section: HTMLElement, layoutKey: string): void {
    const settings = (typeof window !== 'undefined' && window.__LIFF_TAB_SETTINGS__)
        ? window.__LIFF_TAB_SETTINGS__ as Record<string, Record<string, unknown>>
        : {};
    const detailSettings = settings[layoutKey] || {};
    const bgColor = typeof detailSettings.background_color === 'string' ? detailSettings.background_color : '';
    section.style.background = bgColor || '';
    const rawPad = detailSettings.content_padding;
    const padding = typeof rawPad === 'number' ? rawPad : (typeof rawPad === 'string' ? Number(rawPad) : null);
    if (padding !== null && !Number.isNaN(padding)) {
        section.style.setProperty('--content-padding', `${padding}px`);
        section.style.padding = `${padding}px`;
    }
}

function getLayoutManager(): LayoutManagerLike | null {
    if (typeof window === 'undefined' || !window.app || !window.app.layoutManager) {
        return null;
    }
    return window.app.layoutManager as LayoutManagerLike;
}
