import { AppConstants } from '../constants';

type GetTabMountOptions = {
    silent?: boolean;
};

export class DomHelper {
    static getLiffGlobalSetting(key: string, fallback: string): string {
        const rawSettings =
            typeof window !== 'undefined'
            && window.__LIFF_TAB_SETTINGS__
            && window.__LIFF_TAB_SETTINGS__._global
                ? window.__LIFF_TAB_SETTINGS__._global
                : {};
        const settings: Record<string, unknown> = typeof rawSettings === 'object' && rawSettings !== null
            ? rawSettings as Record<string, unknown>
            : {};
        const value =
            settings && Object.prototype.hasOwnProperty.call(settings, key) ? settings[key] : null;
        return value !== null && value !== undefined && String(value) !== '' ? String(value) : fallback;
    }

    static getTabMount(
        mountClass: string,
        fallbackId?: string | null,
        options?: GetTabMountOptions,
    ): Element | null {
        const silent = options?.silent === true;
        const activeTab = document.querySelector('.tab-content.active');
        if (activeTab) {
            const inActiveMount = activeTab.querySelector('.' + mountClass);
            if (inActiveMount) {
                return inActiveMount;
            }
            if (fallbackId) {
                const inActiveById = activeTab.querySelector('#' + fallbackId);
                if (inActiveById) {
                    return inActiveById;
                }
            }
        }
        // セクションIDより data-mount を優先（非アクティブタブの Builder 全体を潰さない）
        const anyMount = document.querySelector('.' + mountClass);
        if (anyMount) {
            return anyMount;
        }
        if (fallbackId) {
            const fallbackElement = document.getElementById(fallbackId);
            if (fallbackElement) {
                return fallbackElement;
            }
            if (!silent) {

                console.error('Mount not found: .' + mountClass + ' / #' + fallbackId);
            }
            return null;
        }
        if (!silent) {

            console.error('Mount not found: .' + mountClass);
        }
        return null;
    }

    static getProductsGrid(): Element | null {
        const activeTab = document.querySelector('.tab-content.active');
        if (activeTab) {
            const grid =
                activeTab.querySelector('.products-data-grid')
                || activeTab.querySelector('#' + AppConstants.ELEMENT_IDS.PRODUCTS_GRID);
            if (grid) {
                return grid;
            }
        }
        return (
            document.querySelector('.products-data-grid')
            || document.getElementById(AppConstants.ELEMENT_IDS.PRODUCTS_GRID)
        );
    }

    static getCategoryTabsContainer(): Element | null {
        const activeTab = document.querySelector('.tab-content.active');
        if (activeTab) {
            const tabs =
                activeTab.querySelector('.products-category-tabs')
                || activeTab.querySelector('#categoryTabs')
                || activeTab.querySelector('.category-tabs');
            if (tabs) {
                return tabs;
            }
        }
        return (
            document.querySelector('.products-category-tabs')
            || document.getElementById('categoryTabs')
            || document.querySelector('.category-tabs')
        );
    }

    static getElementById(id: string): HTMLElement | null {
        const element = document.getElementById(id);
        if (!element) {

            console.error(`Element with id "${id}" not found`);
        }
        return element;
    }

    static querySelectorAll(selector: string): NodeListOf<Element> {
        return document.querySelectorAll(selector);
    }

    static createElement(tag: string, className = '', innerHTML = ''): HTMLElement {
        const element = document.createElement(tag);
        if (className) {
            element.className = className;
        }
        if (innerHTML) {
            element.innerHTML = innerHTML;
        }
        return element;
    }

    static escapeHtml(text: string | number | null | undefined): string {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    static formatPrice(price: number | string | null | undefined): string {
        const amount = Math.round(Number(price) || 0);
        return `¥${amount.toLocaleString()}`;
    }

    static formatReferenceNumber(
        raw: string | number | null | undefined,
        fallbackId?: string | number | null,
    ): string {
        if (raw != null && raw !== '') {
            const s = String(raw);
            // DB形式 YYYYMMDD+通番 → 表示は日+通番（202604201 → 201）
            const stored = s.match(/^(\d{4})(\d{2})(\d{2})(\d+)$/);
            if (stored && stored[3] && stored[4]) {
                return String(Number(stored[3])) + stored[4];
            }
            // すでに表示形式（日+通番）または従来形式
            if (/^\d+$/.test(s)) {
                return s;
            }
            const trailing = s.match(/-(\d+)$/);
            if (trailing && trailing[1]) {
                return trailing[1];
            }
        }
        if (fallbackId != null && fallbackId !== '') {
            return String(fallbackId);
        }
        return '';
    }

    static addClass(element: Element | null, className: string): void {
        if (element) {
            element.classList.add(className);
        }
    }

    static removeClass(element: Element | null, className: string): void {
        if (element) {
            element.classList.remove(className);
        }
    }

    static toggleClass(element: Element | null, className: string): void {
        if (element) {
            element.classList.toggle(className);
        }
    }
}
