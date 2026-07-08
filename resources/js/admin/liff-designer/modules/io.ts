import { ensureLiffDesignerModules } from '../ensureModules';

type LiffDesignerIoHost = {
    state: {
        activeTab: string;
        layouts: Record<string, unknown[]>;
        tabSettings: Record<string, Record<string, unknown>>;
        globalSettings: Record<string, unknown>;
        menuItems: unknown[];
        selectedId: string | null;
    };
    TAB_LABELS: Record<string, string>;
    els: {
        customCssEditor?: HTMLTextAreaElement | HTMLInputElement | null;
    };
    serializeTabSettings: () => unknown;
    normalizeBlock: (item: Record<string, unknown>) => unknown;
    normalizeMenuItems: (items: unknown[]) => unknown[];
    syncGlobalDesignControls: () => void;
    applyGlobalDesignPreview: () => void;
    pushHistory: () => void;
    render: () => void;
};

export const io = {
    exportJson(this: LiffDesignerIoHost): void {
        const payload = {
            exported_at: new Date().toISOString(),
            active_tab: this.state.activeTab,
            layouts: this.state.layouts,
            tab_settings: this.serializeTabSettings(),
            menu_items: this.state.menuItems || [],
            custom_css: this.els.customCssEditor ? this.els.customCssEditor.value : '',
        };
        const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const date = new Date().toISOString().slice(0, 10);
        a.href = url;
        a.download = `liff-designer-${date}.json`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 500);
    },

    importJson(this: LiffDesignerIoHost, file: File | null | undefined): void {
        if (!file) {
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            try {
                const data = JSON.parse(String(reader.result || '{}')) as {
                    layouts?: Record<string, unknown>;
                    tab_settings?: Record<string, unknown>;
                    menu_items?: unknown[];
                    custom_css?: string;
                };
                if (data === null || typeof data !== 'object') {
                    throw new Error('JSON形式が不正です');
                }
                if (data.layouts === undefined || typeof data.layouts !== 'object') {
                    throw new Error('layouts がありません');
                }

                const layouts: Record<string, unknown[]> = {};
                Object.keys(this.TAB_LABELS).forEach((key) => {
                    const rows = Array.isArray(data.layouts?.[key]) ? data.layouts[key] as unknown[] : [];
                    layouts[key] = rows.map((item) => this.normalizeBlock((item || {}) as Record<string, unknown>));
                });
                this.state.layouts = layouts;

                if (data.tab_settings !== undefined && typeof data.tab_settings === 'object') {
                    Object.keys(this.TAB_LABELS).forEach((key) => {
                        const src = data.tab_settings?.[key];
                        if (src !== undefined && typeof src === 'object' && src !== null) {
                            this.state.tabSettings[key] = { ...this.state.tabSettings[key], ...(src as Record<string, unknown>) };
                        }
                    });
                    const global = data.tab_settings._global;
                    if (global !== undefined && typeof global === 'object' && global !== null) {
                        this.state.globalSettings = { ...this.state.globalSettings, ...(global as Record<string, unknown>) };
                        this.syncGlobalDesignControls();
                        this.applyGlobalDesignPreview();
                    }
                }
                if (Array.isArray(data.menu_items) && data.menu_items.length > 0) {
                    this.state.menuItems = this.normalizeMenuItems(data.menu_items);
                }
                if (typeof data.custom_css === 'string' && this.els.customCssEditor) {
                    this.els.customCssEditor.value = data.custom_css;
                }

                this.state.selectedId = null;
                this.pushHistory();
                this.render();
                alert('JSONを読み込みました。必要なら保存してください。');
            } catch (err) {
                const message = err instanceof Error ? err.message : '不明なエラー';
                alert(`JSON読み込みに失敗しました: ${message}`);
            }
        };
        reader.readAsText(file);
    },

    openPreview(this: unknown): void {
        const ov = document.createElement('div');
        ov.className = 'ld-preview-overlay';
        const cl = document.createElement('button');
        cl.className = 'ld-preview-overlay__close';
        cl.innerHTML = '<i class="fas fa-times"></i>';
        cl.addEventListener('click', () => ov.remove());
        ov.appendChild(cl);
        const phone = document.querySelector('.ld-phone');
        if (phone === null) {
            return;
        }
        const ph = phone.cloneNode(true) as HTMLElement;
        ph.querySelectorAll('.ld-block__toolbar,.ld-block__handle').forEach((e) => e.remove());
        ph.querySelectorAll('.ld-block').forEach((el) => {
            const e = el as HTMLElement;
            e.classList.remove('is-selected');
            e.style.cursor = 'default';
        });
        ov.appendChild(ph);
        ov.addEventListener('click', (e) => {
            if (e.target === ov) {
                ov.remove();
            }
        });
        document.body.appendChild(ov);
    },
};

ensureLiffDesignerModules().io = io;
