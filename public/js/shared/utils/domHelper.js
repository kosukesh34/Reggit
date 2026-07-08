class DomHelper {
    static getLiffGlobalSetting(key, fallback) {
        const settings = (typeof window !== 'undefined' && window.__LIFF_TAB_SETTINGS__ && window.__LIFF_TAB_SETTINGS__._global)
            ? window.__LIFF_TAB_SETTINGS__._global
            : {};
        const value = settings && Object.prototype.hasOwnProperty.call(settings, key) ? settings[key] : null;
        return value !== null && value !== undefined && String(value) !== '' ? String(value) : fallback;
    }

    /**
     * カスタムレイアウト内マウントを優先して要素を取得する。
     * 同一 id が複数タブに存在する場合の誤参照を防ぐ。
     */
    static getTabMount(mountClass, fallbackId, options) {
        options = options || {};
        const silent = options.silent === true;
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
        const anyMount = document.querySelector('.' + mountClass);
        if (anyMount) {
            return anyMount;
        }
        if (!silent) {
            console.error('Mount not found: .' + mountClass);
        }
        return null;
    }

    static getProductsGrid() {
        const activeTab = document.querySelector('.tab-content.active');
        if (activeTab) {
            const grid = activeTab.querySelector('.products-data-grid')
                || activeTab.querySelector('#' + AppConstants.ELEMENT_IDS.PRODUCTS_GRID);
            if (grid) {
                return grid;
            }
        }
        return document.querySelector('.products-data-grid')
            || document.getElementById(AppConstants.ELEMENT_IDS.PRODUCTS_GRID);
    }

    static getCategoryTabsContainer() {
        const activeTab = document.querySelector('.tab-content.active');
        if (activeTab) {
            const tabs = activeTab.querySelector('.products-category-tabs')
                || activeTab.querySelector('#categoryTabs')
                || activeTab.querySelector('.category-tabs');
            if (tabs) {
                return tabs;
            }
        }
        return document.querySelector('.products-category-tabs')
            || document.getElementById('categoryTabs')
            || document.querySelector('.category-tabs');
    }

    static getElementById(id) {
        const element = document.getElementById(id);
        if (!element) {
            console.error(`Element with id "${id}" not found`);
        }
        return element;
    }

    static querySelectorAll(selector) {
        return document.querySelectorAll(selector);
    }

    static createElement(tag, className = '', innerHTML = '') {
        const element = document.createElement(tag);
        if (className) {
            element.className = className;
        }
        if (innerHTML) {
            element.innerHTML = innerHTML;
        }
        return element;
    }

    static escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    static formatPrice(price) {
        const amount = Math.round(Number(price) || 0);
        return `¥${amount.toLocaleString()}`;
    }

    /** 予約番号・注文番号などを、お客様向けに数字だけの控えめな表示用に整形する。 */
    static formatReferenceNumber(raw, fallbackId) {
        if (raw != null && raw !== '') {
            const s = String(raw);
            // ORD-9025 のような末尾の短い数字コード
            const trailing = s.match(/-(\d{1,6})$/);
            if (trailing && trailing[1]) {
                return trailing[1];
            }
        }
        if (fallbackId != null && fallbackId !== '') {
            return String(fallbackId);
        }
        return '';
    }

    static addClass(element, className) {
        if (element) {
            element.classList.add(className);
        }
    }

    static removeClass(element, className) {
        if (element) {
            element.classList.remove(className);
        }
    }

    static toggleClass(element, className) {
        if (element) {
            element.classList.toggle(className);
        }
    }
}

