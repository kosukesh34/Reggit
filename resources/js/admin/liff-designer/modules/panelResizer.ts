type PanelSide = 'left' | 'right';
type PanelWidths = { left: number; right: number };
type PanelLimits = { min: number; max: number };

/**
 * 左右パネル幅のドラッグリサイズ（localStorage 永続化）
 */
export const panelResizer = {
    STORAGE_KEY: 'ld-panel-widths-v1',
    DEFAULTS: { left: 260, right: 388 } as PanelWidths,
    LIMITS: {
        left: { min: 240, max: 420 },
        right: { min: 280, max: 560 },
    } as Record<PanelSide, PanelLimits>,

    bindPanelResizer(this: typeof panelResizer): void {
        try {
            const root = document.querySelector('.ld') as HTMLElement | null;
            if (root === null) {
                return;
            }

            const saved = this._loadWidths();
            this._applyWidths(root, saved);

            const leftHandle = root.querySelector('[data-resizer="left"]');
            const rightHandle = root.querySelector('[data-resizer="right"]');
            if (leftHandle !== null) {
                this._bindHandle(root, leftHandle as HTMLElement, 'left');
            }
            if (rightHandle !== null) {
                this._bindHandle(root, rightHandle as HTMLElement, 'right');
            }

            const resetBtn = document.getElementById('resetPanelWidths');
            if (resetBtn !== null) {
                resetBtn.addEventListener('click', () => {
                    this._applyWidths(root, { ...this.DEFAULTS });
                    this._saveWidths(this._readWidths(root));
                });
            }

            document.querySelectorAll('[data-panel-preset]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const preset = (btn as HTMLElement).dataset.panelPreset;
                    const widths = this._presetWidths(preset);
                    if (widths === null) {
                        return;
                    }
                    this._applyWidths(root, widths);
                    this._saveWidths(widths);
                    document.querySelectorAll('[data-panel-preset]').forEach((b) => {
                        b.classList.toggle('is-active', b === btn);
                    });
                });
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth <= 980) {
                    root.classList.remove('ld-is-resizing');
                }
            });
        } catch (err) {
            // eslint-disable-next-line no-console
            console.warn('panel resizer init failed', err);
        }
    },

    _presetWidths(this: typeof panelResizer, preset: string | undefined): PanelWidths | null {
        const map: Record<string, PanelWidths> = {
            compact: { left: 240, right: 300 },
            standard: { left: 260, right: 388 },
            wide: { left: 300, right: 460 },
            focus: { left: 240, right: 520 },
        };
        if (preset === undefined) {
            return null;
        }
        return map[preset] || null;
    },

    _loadWidths(this: typeof panelResizer): PanelWidths {
        try {
            const raw = localStorage.getItem(this.STORAGE_KEY);
            if (raw === null) {
                return { ...this.DEFAULTS };
            }
            const parsed = JSON.parse(raw) as Partial<PanelWidths>;
            return {
                left: this._clamp(parsed.left, this.LIMITS.left),
                right: this._clamp(parsed.right, this.LIMITS.right),
            };
        } catch {
            return { ...this.DEFAULTS };
        }
    },

    _saveWidths(this: typeof panelResizer, widths: PanelWidths): void {
        try {
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(widths));
        } catch {
            /* ignore */
        }
    },

    _readWidths(this: typeof panelResizer, root: HTMLElement): PanelWidths {
        const left = parseInt(getComputedStyle(root).getPropertyValue('--ld-screens-width'), 10);
        const right = parseInt(getComputedStyle(root).getPropertyValue('--ld-sidebar-width'), 10);
        return {
            left: this._clamp(left || this.DEFAULTS.left, this.LIMITS.left),
            right: this._clamp(right || this.DEFAULTS.right, this.LIMITS.right),
        };
    },

    _applyWidths(this: typeof panelResizer, root: HTMLElement, widths: PanelWidths): void {
        root.style.setProperty('--ld-screens-width', `${widths.left}px`);
        root.style.setProperty('--ld-sidebar-width', `${widths.right}px`);
    },

    _clamp(this: typeof panelResizer, value: unknown, limits: PanelLimits): number {
        const n = Number(value);
        if (!Number.isFinite(n)) {
            return limits.min;
        }
        return Math.max(limits.min, Math.min(limits.max, Math.round(n)));
    },

    _bindHandle(this: typeof panelResizer, root: HTMLElement, handle: HTMLElement, side: PanelSide): void {
        const limits = this.LIMITS[side];
        let startX = 0;
        let startWidth = 0;

        const onMove = (e: MouseEvent | TouchEvent): void => {
            if (window.innerWidth <= 980) {
                return;
            }
            const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
            const delta = clientX - startX;
            const next = side === 'left'
                ? startWidth + delta
                : startWidth - delta;
            const key = side === 'left' ? '--ld-screens-width' : '--ld-sidebar-width';
            root.style.setProperty(key, `${this._clamp(next, limits)}px`);
        };

        const onEnd = (): void => {
            root.classList.remove('ld-is-resizing');
            document.body.classList.remove('ld-body--no-select');
            document.removeEventListener('mousemove', onMove as EventListener);
            document.removeEventListener('mouseup', onEnd);
            document.removeEventListener('touchmove', onMove as EventListener);
            document.removeEventListener('touchend', onEnd);
            this._saveWidths(this._readWidths(root));
        };

        const onStart = (e: MouseEvent | TouchEvent): void => {
            if (window.innerWidth <= 980) {
                return;
            }
            e.preventDefault();
            const widths = this._readWidths(root);
            startX = 'touches' in e ? e.touches[0].clientX : e.clientX;
            startWidth = side === 'left' ? widths.left : widths.right;
            root.classList.add('ld-is-resizing');
            document.body.classList.add('ld-body--no-select');
            document.addEventListener('mousemove', onMove as EventListener);
            document.addEventListener('mouseup', onEnd);
            document.addEventListener('touchmove', onMove as EventListener, { passive: false });
            document.addEventListener('touchend', onEnd);
        };

        handle.addEventListener('mousedown', onStart as EventListener);
        handle.addEventListener('touchstart', onStart as EventListener, { passive: false });
        handle.addEventListener('dblclick', () => {
            const widths = this._readWidths(root);
            const reset = side === 'left' ? this.DEFAULTS.left : this.DEFAULTS.right;
            const key = side === 'left' ? '--ld-screens-width' : '--ld-sidebar-width';
            root.style.setProperty(key, `${reset}px`);
            this._saveWidths({ ...widths, [side]: reset });
        });
    },
};

