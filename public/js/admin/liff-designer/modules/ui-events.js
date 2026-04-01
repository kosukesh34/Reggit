window.LiffDesignerModules = window.LiffDesignerModules || {};

window.LiffDesignerModules.uiEvents = {
    bindEvents() {
        this.els.closeSettings.addEventListener('click', () => { this.state.selectedId = null; this.renderPhoneContent(); this.renderBlockSettings(); });
        this.els.screenBgColor.addEventListener('input', () => this.onScreenChange());
        this.els.screenBgColorHex.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(this.els.screenBgColorHex.value)) { this.els.screenBgColor.value = this.els.screenBgColorHex.value; this.onScreenChange(); } });
        this.els.screenBgImage.addEventListener('input', () => this.onScreenChange());
        this.els.form.addEventListener('submit', () => { this.els.layoutsJson.value = JSON.stringify(this.state.layouts); this.els.tabSettingsJson.value = JSON.stringify(this.state.tabSettings); if (this.els.customCssInput && this.els.customCssEditor) this.els.customCssInput.value = this.els.customCssEditor.value; });
        this.els.toggleCssBtn.addEventListener('click', () => { this.els.cssSection.hidden = !this.els.cssSection.hidden; });
        if (this.els.customCssEditor) this.els.customCssEditor.addEventListener('input', () => this.applyCustomCssPreview());
        this.els.undoBtn.addEventListener('click', () => this.undo());
        this.els.redoBtn.addEventListener('click', () => this.redo());
        this.els.previewBtn.addEventListener('click', () => this.openPreview());
        if (this.els.templateBtn) this.els.templateBtn.addEventListener('click', () => this.openTemplateModal());
        if (this.els.exportBtn) this.els.exportBtn.addEventListener('click', () => this.exportJson());
        if (this.els.importBtn && this.els.importJsonFile) {
            this.els.importBtn.addEventListener('click', () => this.els.importJsonFile.click());
            this.els.importJsonFile.addEventListener('change', () => {
                const file = this.els.importJsonFile.files && this.els.importJsonFile.files[0];
                if (file) this.importJson(file);
                this.els.importJsonFile.value = '';
            });
        }
        if (this.els.paletteSearch) {
            this.els.paletteSearch.addEventListener('input', () => {
                this.ui.paletteSearch = this.els.paletteSearch.value || '';
                this.renderBlockPalette();
            });
        }
        if (this.els.tabTemplateList) {
            this.els.tabTemplateList.addEventListener('click', (e) => {
                const templates = this.getTemplatesForTab(this.state.activeTab);
                const previewBtn = e.target.closest('[data-tab-template-preview-id]');
                if (previewBtn) {
                    const templateId = previewBtn.dataset.tabTemplatePreviewId || '';
                    const tpl = templates.find((item) => String(item.id || '') === templateId);
                    if (!tpl || !Array.isArray(tpl.blocks)) return;
                    this.openTabTemplatePreview(tpl);
                    return;
                }
                const applyBtn = e.target.closest('[data-tab-template-id]');
                if (!applyBtn) return;
                const templateId = applyBtn.dataset.tabTemplateId || '';
                const tpl = templates.find((item) => String(item.id || '') === templateId);
                if (!tpl || !Array.isArray(tpl.blocks)) return;
                this.applyTabTemplate(tpl);
            });
        }
        if (this.els.zoomRange) {
            this.els.zoomRange.addEventListener('input', () => {
                this.ui.zoom = parseInt(this.els.zoomRange.value || '100', 10);
                this.applyZoom();
            });
        }
        if (this.els.zoomOutBtn) {
            this.els.zoomOutBtn.addEventListener('click', () => {
                this.ui.zoom = Math.max(60, (this.ui.zoom || 100) - 5);
                this.applyZoom();
            });
        }
        if (this.els.zoomInBtn) {
            this.els.zoomInBtn.addEventListener('click', () => {
                this.ui.zoom = Math.min(120, (this.ui.zoom || 100) + 5);
                this.applyZoom();
            });
        }
        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'z') { e.preventDefault(); e.shiftKey ? this.redo() : this.undo(); }
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') { e.preventDefault(); this.els.form.requestSubmit(); }
            if (this.state.selectedId && (e.key === 'Delete' || e.key === 'Backspace')) { const t = e.target.tagName; if (t === 'INPUT' || t === 'TEXTAREA' || t === 'SELECT') return; e.preventDefault(); this.deleteBlock(this.state.selectedId); }
        });
        this.els.phoneContent.addEventListener('click', (e) => { if (e.target === this.els.phoneContent || e.target.closest('.ld-phone__empty')) { this.state.selectedId = null; this.renderPhoneContent(); this.renderBlockSettings(); } });
    },

    onScreenChange() {
        const s = this.getSettings(); s.background_color = this.els.screenBgColor.value; s.background_image = this.els.screenBgImage.value;
        this.els.screenBgColorHex.value = this.els.screenBgColor.value; this.state.tabSettings[this.state.activeTab] = s; this.renderPhoneContent();
    },

    applyZoom() {
        if (!this.els.phoneFrame) return;
        const scale = (this.ui.zoom || 100) / 100;
        this.els.phoneFrame.style.transform = 'scale(' + scale + ')';
        if (this.els.zoomValue) this.els.zoomValue.textContent = Math.round(this.ui.zoom || 100) + '%';
        if (this.els.zoomRange) this.els.zoomRange.value = String(Math.round(this.ui.zoom || 100));
    },

    applyPreviewDevice() {
        if (!this.els.phoneFrame) return;
        let device = this.ui.previewDevice || 'line';
        this.els.phoneFrame.classList.remove('is-line', 'is-ios', 'is-android');
        this.els.phoneFrame.classList.add('is-' + device);
        let header = this.els.phoneFrame.querySelector('.ld-phone__header');
        if (header) {
            if (device === 'line') {
                header.style.background = '#06C755';
            } else if (device === 'ios') {
                header.style.background = '#000';
            } else if (device === 'android') {
                header.style.background = '#1f2937';
            }
        }
    },

    applyCustomCssPreview() {
        const css = this.els.customCssEditor ? this.els.customCssEditor.value : '';
        let style = document.getElementById('ldCustomCssPreview');
        if (!style) {
            style = document.createElement('style');
            style.id = 'ldCustomCssPreview';
            document.head.appendChild(style);
        }
        style.textContent = css || '';
        if (this.els.customCssInput) this.els.customCssInput.value = css || '';
    },
};
