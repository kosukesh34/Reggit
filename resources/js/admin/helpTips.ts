/**
 * 管理画面の補足説明（.form-text）を、項目名の横の「?」ツールチップにまとめる。
 * 警告・状態表示・リンクやコード入り・表やアコーディオン見出しの中の文は、読み飛ばされると困るので表示したまま残す。
 * 残したい説明には data-help-visible を付ける。
 */

type TooltipHost = HTMLElement & { _tooltip?: HTMLElement | null };

const KEEP_VISIBLE_INSIDE = 'summary, td, th, .alert, [data-help-visible]';
const KEEP_VISIBLE_CONTENT = 'a, button, input, select, textarea, strong, code, [id]';
// 「?」を付ける先。説明の直前にある項目名・見出しを、近いまとまりから順に外へ探す。
// ラジオ・チェックボックスの選択肢の label より、項目名（.form-label・legend・見出し）を優先する
const ANCHOR_SCOPE = '.form-group, fieldset, .info-item, .sd-weather-step, .andel-card, .card, section, form';
const ANCHOR_HEADINGS = ['.form-label, legend, h2, h3, h4, .card-title', 'label'];
// どのまとまりにも項目名がない（ページ全体の説明）ときはページの見出しに付ける
const PAGE_TITLE = '.content-header-title-wrap > h2';

function isHelpText(text: HTMLElement): boolean {
    if (text.hidden || text.hasAttribute('role') || text.closest(KEEP_VISIBLE_INSIDE) || text.querySelector(KEEP_VISIBLE_CONTENT)) {
        return false;
    }
    // id は aria-describedby から参照されているだけなら変換してよい（JS が書き換える文は残す）
    return text.id === '' || document.querySelector(`[aria-describedby~="${CSS.escape(text.id)}"]`) !== null;
}

function findAnchor(text: HTMLElement): HTMLElement | null {
    for (let scope = text.parentElement?.closest<HTMLElement>(ANCHOR_SCOPE); scope; scope = scope.parentElement?.closest<HTMLElement>(ANCHOR_SCOPE)) {
        for (const selector of ANCHOR_HEADINGS) {
            const labels = Array.from(scope.querySelectorAll<HTMLElement>(selector))
                .filter((label) => label.compareDocumentPosition(text) & Node.DOCUMENT_POSITION_FOLLOWING && !label.contains(text));
            if (labels.length > 0) return labels[labels.length - 1];
        }
    }

    return document.querySelector<HTMLElement>(PAGE_TITLE);
}

export function convertHelpTexts(root: ParentNode = document): void {
    let count = 0;
    root.querySelectorAll<HTMLElement>('.form-text').forEach((text) => {
        if (!isHelpText(text)) return;
        const message = (text.innerText || text.textContent || '').replace(/[ \t]+/g, ' ').trim();
        if (message === '') return;

        if (text.id === '') text.id = `adminHelp${++count}`;
        text.classList.add('is-help-source');
        const anchor = findAnchor(text);
        const existing = anchor?.querySelector<HTMLButtonElement>(':scope > .admin-help--inline');
        if (existing) {
            existing.dataset.tooltip = `${existing.dataset.tooltip}\n\n${message}`;
            existing.setAttribute('aria-describedby', `${existing.getAttribute('aria-describedby')} ${text.id}`);
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'admin-help admin-help--inline';
        button.textContent = '?';
        button.dataset.tooltip = message;
        button.setAttribute('aria-label', '説明を見る');
        button.setAttribute('aria-describedby', text.id);
        if (anchor) {
            anchor.append(button);
        } else {
            text.before(button);
        }
    });
}

function showTooltip(element: TooltipHost): void {
    if (element._tooltip) element._tooltip.remove();
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.textContent = element.dataset.tooltip || '';
    document.body.appendChild(tooltip);

    const rect = element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    let top = rect.top - tooltipRect.height - 8;
    let left = rect.left;
    if (top < 8) top = rect.bottom + 8;
    if (left + tooltipRect.width > window.innerWidth - 8) {
        left = Math.max(8, window.innerWidth - tooltipRect.width - 8);
    }
    tooltip.style.top = `${top}px`;
    tooltip.style.left = `${left}px`;
    tooltip.classList.add('show');
    element._tooltip = tooltip;
}

function hideTooltip(element: TooltipHost): void {
    element._tooltip?.remove();
    element._tooltip = null;
}

function tooltipHost(target: EventTarget | null): TooltipHost | null {
    return target instanceof Element ? target.closest<HTMLElement>('[data-tooltip]') : null;
}

/** 画面に後から足された要素（条件カードの複製など）でも動くよう、document でまとめて受ける */
export function initTooltips(): void {
    const hideAll = (): void => {
        document.querySelectorAll<HTMLElement>('[data-tooltip]').forEach((el) => hideTooltip(el as TooltipHost));
    };
    document.querySelectorAll<HTMLElement>('[data-tooltip]').forEach((element) => {
        if (!element.hasAttribute('tabindex') && element.tagName !== 'BUTTON' && element.tagName !== 'A') {
            element.tabIndex = 0;
        }
    });
    const showOn = (event: Event): void => {
        const host = tooltipHost(event.target);
        if (host && !host._tooltip) showTooltip(host);
    };
    const hideOn = (event: Event): void => {
        const host = tooltipHost(event.target);
        // 子要素への移動では閉じない
        if (host && !(event instanceof MouseEvent && event.relatedTarget instanceof Node && host.contains(event.relatedTarget))) hideTooltip(host);
    };
    document.addEventListener('mouseover', showOn);
    document.addEventListener('mouseout', hideOn);
    document.addEventListener('focusin', showOn);
    document.addEventListener('focusout', hideOn);
    // スマホはホバーがなく、iOS はタップでフォーカスもしないため、タップでも開く。ほかの場所を押すと閉じる
    document.addEventListener('click', (event) => {
        const host = tooltipHost(event.target);
        hideAll();
        if (host) showTooltip(host);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') hideAll();
    });
}

/** 補足説明を「?」に変換してから、画面のツールチップ（data-tooltip）を使えるようにする */
export function initHelpTips(): void {
    convertHelpTexts();
    initTooltips();
}
