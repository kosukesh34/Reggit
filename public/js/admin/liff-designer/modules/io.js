window.LiffDesignerModules = window.LiffDesignerModules || {};

window.LiffDesignerModules.io = {
    exportJson() {
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
        a.download = 'liff-designer-' + date + '.json';
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 500);
    },

    importJson(file) {
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
            try {
                const data = JSON.parse(String(reader.result || '{}'));
                if (!data || typeof data !== 'object') throw new Error('JSON形式が不正です');
                if (!data.layouts || typeof data.layouts !== 'object') throw new Error('layouts がありません');

                const layouts = {};
                Object.keys(this.TAB_LABELS).forEach(key => {
                    const rows = Array.isArray(data.layouts[key]) ? data.layouts[key] : [];
                    layouts[key] = rows.map(item => this.normalizeBlock(item || {}));
                });
                this.state.layouts = layouts;

                if (data.tab_settings && typeof data.tab_settings === 'object') {
                    Object.keys(this.TAB_LABELS).forEach(key => {
                        const src = data.tab_settings[key];
                        if (src && typeof src === 'object') this.state.tabSettings[key] = { ...this.state.tabSettings[key], ...src };
                    });
                    if (data.tab_settings._global && typeof data.tab_settings._global === 'object') {
                        this.state.globalSettings = { ...this.state.globalSettings, ...data.tab_settings._global };
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
                alert('JSON読み込みに失敗しました: ' + (err && err.message ? err.message : '不明なエラー'));
            }
        };
        reader.readAsText(file);
    },

    openPreview() {
        const ov = document.createElement('div'); ov.className = 'ld-preview-overlay';
        const cl = document.createElement('button'); cl.className = 'ld-preview-overlay__close'; cl.innerHTML = '<i class="fas fa-times"></i>';
        cl.addEventListener('click', () => ov.remove()); ov.appendChild(cl);
        const ph = document.querySelector('.ld-phone').cloneNode(true);
        ph.querySelectorAll('.ld-block__toolbar,.ld-block__handle').forEach(e => e.remove());
        ph.querySelectorAll('.ld-block').forEach(e => { e.classList.remove('is-selected'); e.style.cursor = 'default'; });
        ov.appendChild(ph);
        ov.addEventListener('click', (e) => { if (e.target === ov) ov.remove(); });
        document.body.appendChild(ov);
    },
};
