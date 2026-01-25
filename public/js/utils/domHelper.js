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
        return `¥${parseInt(price).toLocaleString()}`;
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

