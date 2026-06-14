window.LiffDesignerModules = window.LiffDesignerModules || {};

window.LiffDesignerModules.uiEvents = {
    bindEvents() {
        this.els.closeSettings.addEventListener('click', () => { this.state.selectedId = null; this.renderPhoneContent(); this.renderBlockSettings(); });
        this.els.screenBgColor.addEventListener('input', () => this.onScreenChange());
        this.els.screenBgColorHex.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(this.els.screenBgColorHex.value)) { this.els.screenBgColor.value = this.els.screenBgColorHex.value; this.onScreenChange(); } });
        this.els.screenBgImage.addEventListener('input', () => this.onScreenChange());
        this.els.form.addEventListener('submit', () => {
            this.els.layoutsJson.value = JSON.stringify(this.state.layouts);
            this.els.tabSettingsJson.value = JSON.stringify(this.serializeTabSettings());
            if (this.els.customCssInput && this.els.customCssEditor) this.els.customCssInput.value = this.els.customCssEditor.value;
        });
        this.els.toggleCssBtn.addEventListener('click', () => { this.els.cssSection.hidden = !this.els.cssSection.hidden; });
        if (this.els.customCssEditor) this.els.customCssEditor.addEventListener('input', () => this.applyCustomCssPreview());
        if (this.els.screenBgSize) this.els.screenBgSize.addEventListener('change', () => this.onScreenChange());
        if (this.els.screenContentPadding) {
            this.els.screenContentPadding.addEventListener('input', () => {
                const val = parseInt(this.els.screenContentPadding.value, 10);
                if (this.els.screenContentPaddingVal) this.els.screenContentPaddingVal.textContent = String(val);
                this.state.globalSettings.content_padding = val;
                this.onScreenChange();
            });
        }
        if (this.els.globalSectionGap) {
            this.els.globalSectionGap.addEventListener('input', () => {
                const val = parseInt(this.els.globalSectionGap.value, 10);
                this.state.globalSettings.section_gap = val;
                if (this.els.globalSectionGapVal) this.els.globalSectionGapVal.textContent = String(val);
                this.applyGlobalDesignPreview();
                this.renderPhoneContent();
            });
        }
        if (this.els.globalRadiusScale) {
            this.els.globalRadiusScale.addEventListener('change', () => {
                this.state.globalSettings.radius_scale = this.els.globalRadiusScale.value;
                this.applyGlobalDesignPreview();
                this.renderPhoneContent();
            });
        }
        if (this.els.globalTabStyle) {
            this.els.globalTabStyle.addEventListener('change', () => {
                this.state.globalSettings.tab_style = this.els.globalTabStyle.value;
                this.applyGlobalDesignPreview();
            });
        }
        if (this.els.designPresets) {
            this.els.designPresets.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-preset]');
                if (!btn) return;
                this.ui.activePreset = btn.dataset.preset;
                this.applyDesignPreset(btn.dataset.preset);
            });
        }
        if (this.els.globalFontFamily) {
            this.els.globalFontFamily.addEventListener('change', () => {
                this.state.globalSettings.font_family = this.els.globalFontFamily.value;
                this.state.globalSettings.theme_preset = 'custom';
                this.syncGlobalDesignControls();
                this.applyGlobalDesignPreview();
            });
        }
        if (this.els.globalSurfaceTone) {
            this.els.globalSurfaceTone.addEventListener('change', () => {
                this.state.globalSettings.surface_tone = this.els.globalSurfaceTone.value;
                this.state.globalSettings.theme_preset = 'custom';
                this.syncGlobalDesignControls();
                this.applyGlobalDesignPreview();
            });
        }
        const onAccentChange = (val) => {
            if (!/^#[0-9a-fA-F]{6}$/.test(val)) return;
            this.state.globalSettings.accent_color = val.toLowerCase();
            this.state.globalSettings.theme_preset = 'custom';
            this.syncGlobalDesignControls();
            this.applyGlobalDesignPreview();
        };
        if (this.els.globalAccentColor) {
            this.els.globalAccentColor.addEventListener('input', () => onAccentChange(this.els.globalAccentColor.value));
        }
        if (this.els.globalAccentColorHex) {
            this.els.globalAccentColorHex.addEventListener('input', () => onAccentChange(this.els.globalAccentColorHex.value));
        }
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
        const s = this.getSettings();
        s.background_color = this.els.screenBgColor.value;
        s.background_image = this.els.screenBgImage.value;
        if (this.els.screenBgSize) s.background_size = this.els.screenBgSize.value;
        if (this.els.screenContentPadding) {
            const pad = parseInt(this.els.screenContentPadding.value, 10);
            s.content_padding = pad;
            this.state.globalSettings.content_padding = pad;
        }
        this.els.screenBgColorHex.value = this.els.screenBgColor.value;
        this.state.tabSettings[this.state.activeTab] = s;
        this.renderPhoneContent();
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
        this.els.phoneFrame.classList.remove('is-ios', 'is-android');
        this.els.phoneFrame.classList.add('is-line');
        var header = this.els.phoneFrame.querySelector('.ld-phone__header');
        if (header) {
            header.style.background = 'linear-gradient(135deg, #06C755, #04a847)';
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
