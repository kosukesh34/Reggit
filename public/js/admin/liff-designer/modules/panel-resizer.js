window.LiffDesignerModules = window.LiffDesignerModules || {};

/**
 * 左右パネル幅のドラッグリサイズ（localStorage 永続化）
 */
window.LiffDesignerModules.panelResizer = {
    STORAGE_KEY: 'ld-panel-widths-v1',
    DEFAULTS: { left: 248, right: 388 },
    LIMITS: {
        left: { min: 176, max: 420 },
        right: { min: 280, max: 560 },
    },

    bindPanelResizer() {
        try {
        const root = document.querySelector('.ld');
        if (!root) return;

        const saved = this._loadWidths();
        this._applyWidths(root, saved);

        const leftHandle = root.querySelector('[data-resizer="left"]');
        const rightHandle = root.querySelector('[data-resizer="right"]');
        if (leftHandle) this._bindHandle(root, leftHandle, 'left');
        if (rightHandle) this._bindHandle(root, rightHandle, 'right');

        const resetBtn = document.getElementById('resetPanelWidths');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                this._applyWidths(root, { ...this.DEFAULTS });
                this._saveWidths(this._readWidths(root));
            });
        }

        document.querySelectorAll('[data-panel-preset]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const preset = btn.dataset.panelPreset;
                const widths = this._presetWidths(preset);
                if (!widths) return;
                this._applyWidths(root, widths);
                this._saveWidths(widths);
                document.querySelectorAll('[data-panel-preset]').forEach((b) => {
                    b.classList.toggle('is-active', b === btn);
                });
            });
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth <= 980) root.classList.remove('ld-is-resizing');
        });
        } catch (err) {
            console.warn('panel resizer init failed', err);
        }
    },

    _presetWidths(preset) {
        const map = {
            compact: { left: 200, right: 300 },
            standard: { left: 248, right: 388 },
            wide: { left: 300, right: 460 },
            focus: { left: 200, right: 520 },
        };
        return map[preset] || null;
    },

    _loadWidths() {
        try {
            const raw = localStorage.getItem(this.STORAGE_KEY);
            if (!raw) return { ...this.DEFAULTS };
            const parsed = JSON.parse(raw);
            return {
                left: this._clamp(parsed.left, this.LIMITS.left),
                right: this._clamp(parsed.right, this.LIMITS.right),
            };
        } catch (e) {
            return { ...this.DEFAULTS };
        }
    },

    _saveWidths(widths) {
        try {
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(widths));
        } catch (e) { /* ignore */ }
    },

    _readWidths(root) {
        const left = parseInt(getComputedStyle(root).getPropertyValue('--ld-screens-width'), 10);
        const right = parseInt(getComputedStyle(root).getPropertyValue('--ld-sidebar-width'), 10);
        return {
            left: this._clamp(left || this.DEFAULTS.left, this.LIMITS.left),
            right: this._clamp(right || this.DEFAULTS.right, this.LIMITS.right),
        };
    },

    _applyWidths(root, widths) {
        root.style.setProperty('--ld-screens-width', widths.left + 'px');
        root.style.setProperty('--ld-sidebar-width', widths.right + 'px');
    },

    _clamp(value, limits) {
        const n = Number(value);
        if (!Number.isFinite(n)) return limits.min;
        return Math.max(limits.min, Math.min(limits.max, Math.round(n)));
    },

    _bindHandle(root, handle, side) {
        const limits = this.LIMITS[side];
        let startX = 0;
        let startWidth = 0;

        const onMove = (e) => {
            if (window.innerWidth <= 980) return;
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const delta = clientX - startX;
            const next = side === 'left'
                ? startWidth + delta
                : startWidth - delta;
            const key = side === 'left' ? '--ld-screens-width' : '--ld-sidebar-width';
            root.style.setProperty(key, this._clamp(next, limits) + 'px');
        };

        const onEnd = () => {
            root.classList.remove('ld-is-resizing');
            document.body.classList.remove('ld-body--no-select');
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onEnd);
            document.removeEventListener('touchmove', onMove);
            document.removeEventListener('touchend', onEnd);
            this._saveWidths(this._readWidths(root));
        };

        const onStart = (e) => {
            if (window.innerWidth <= 980) return;
            e.preventDefault();
            const widths = this._readWidths(root);
            startX = e.touches ? e.touches[0].clientX : e.clientX;
            startWidth = side === 'left' ? widths.left : widths.right;
            root.classList.add('ld-is-resizing');
            document.body.classList.add('ld-body--no-select');
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onEnd);
            document.addEventListener('touchmove', onMove, { passive: false });
            document.addEventListener('touchend', onEnd);
        };

        handle.addEventListener('mousedown', onStart);
        handle.addEventListener('touchstart', onStart, { passive: false });
        handle.addEventListener('dblclick', () => {
            const widths = this._readWidths(root);
            const reset = side === 'left' ? this.DEFAULTS.left : this.DEFAULTS.right;
            const key = side === 'left' ? '--ld-screens-width' : '--ld-sidebar-width';
            root.style.setProperty(key, reset + 'px');
            this._saveWidths({ ...widths, [side]: reset });
        });
    },
};
