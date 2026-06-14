class DomHelper {
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

