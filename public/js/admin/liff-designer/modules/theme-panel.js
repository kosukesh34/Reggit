window.LiffDesignerModules = window.LiffDesignerModules || {};

/**
 * テーマパネル: プリセット適用・背景・幅・プレビュー反映
 */
window.LiffDesignerModules.themePanel = {
    RADIUS_SCALES: {
        compact: { xs: '6px', sm: '10px', md: '12px', lg: '14px', xl: '18px' },
        default: { xs: '8px', sm: '12px', md: '16px', lg: '20px', xl: '24px' },
        soft: { xs: '10px', sm: '14px', md: '18px', lg: '22px', xl: '28px' },
        round: { xs: '12px', sm: '18px', md: '24px', lg: '28px', xl: '32px' },
    },

    CONTENT_WIDTHS: {
        edge: '100%',
        comfy: 'min(100%, 440px)',
        narrow: 'min(100%, 380px)',
    },

    resolveThemeVars(g) {
        const vars = {};
        const preset = this.findThemePreset(g.theme_preset);
        const accent = (g.accent_color && /^#[0-9a-fA-F]{6}$/.test(g.accent_color))
            ? g.accent_color
            : (preset ? preset.accent_color : null);
        if (accent) {
            vars['--primary-color'] = accent;
            vars['--primary-rgb'] = this.hexToRgbString(accent);
            vars['--primary-dark'] = this.darkenHex(accent, 0.16);
        }
        const fontKey = g.font_family || (preset ? preset.font_family : '');
        if (fontKey && this.fontStacks[fontKey]) {
            vars['--font-base'] = this.fontStacks[fontKey].base;
            vars['--font-display'] = this.fontStacks[fontKey].display;
        }
        const toneKey = g.surface_tone || (preset ? preset.surface_tone : '');
        if (toneKey && this.surfaceTones[toneKey]) {
            const tokens = this.surfaceTones[toneKey].tokens || {};
            Object.keys(tokens).forEach((k) => { vars[k] = tokens[k]; });
        }
        const widthKey = g.content_width || (preset ? preset.content_width : '') || 'comfy';
        vars['--content-max-width'] = this.CONTENT_WIDTHS[widthKey] || this.CONTENT_WIDTHS.comfy;

        const bgStyle = g.background_style || (preset ? preset.background_style : 'solid') || 'solid';
        const bgColor = (g.bg_color && /^#[0-9a-fA-F]{6}$/.test(g.bg_color))
            ? g.bg_color
            : ((preset && preset.bg_color) || '#f9fafb');
        vars['--app-bg-color'] = bgColor;
        const overlay = g.bg_overlay_opacity != null ? g.bg_overlay_opacity : (preset ? preset.bg_overlay_opacity : 60);
        vars['--app-bg-overlay'] = String(Math.max(0, Math.min(80, Number(overlay) || 60)) / 100);

        if (bgStyle === 'gradient') {
            const start = (g.bg_gradient_start && /^#[0-9a-fA-F]{6}$/.test(g.bg_gradient_start))
                ? g.bg_gradient_start
                : ((preset && preset.bg_gradient_start) || bgColor);
            const end = (g.bg_gradient_end && /^#[0-9a-fA-F]{6}$/.test(g.bg_gradient_end))
                ? g.bg_gradient_end
                : ((preset && preset.bg_gradient_end) || '#ffffff');
            vars['--app-bg-gradient'] = 'linear-gradient(165deg, ' + start + ' 0%, ' + end + ' 55%, ' + bgColor + ' 100%)';
        }
        if (bgStyle === 'image') {
            const url = g.bg_image_url || (preset ? preset.bg_image_url : '');
            if (url && /^https:\/\//.test(url)) {
                vars['--app-bg-image'] = "url('" + url + "')";
            }
        }
        return vars;
    },

    applyGlobalDesignPreview() {
        const g = this.getGlobalSettings();
        const scale = this.RADIUS_SCALES[g.radius_scale] || this.RADIUS_SCALES.soft;
        let style = document.getElementById('ldGlobalDesignPreview');
        if (!style) {
            style = document.createElement('style');
            style.id = 'ldGlobalDesignPreview';
            document.head.appendChild(style);
        }
        const themeVars = this.resolveThemeVars(g);
        const themeDecl = Object.keys(themeVars).map((k) => k + ': ' + themeVars[k] + ';').join(' ');
        const preset = this.findThemePreset(g.theme_preset);
        const cardStyle = g.card_style || (preset ? preset.card_style : '') || '';
        const buttonShape = g.button_shape || (preset ? preset.button_shape : '') || '';
        const density = g.density || (preset ? preset.density : '') || 'default';
        const bgStyle = g.background_style || (preset ? preset.background_style : 'solid') || 'solid';
        const widthKey = g.content_width || (preset ? preset.content_width : '') || 'comfy';
        const systemClasses = [
            'system-ui-' + (g.system_ui_style || (preset ? preset.system_ui_style : '') || 'refined'),
            'system-card-' + (g.system_card_style || (preset ? preset.system_card_style : '') || 'soft'),
            'system-media-' + (g.system_media_shape || (preset ? preset.system_media_shape : '') || 'rounded'),
            'system-header-' + (g.system_header_style || (preset ? preset.system_header_style : '') || 'minimal'),
            'member-screen-' + (g.member_screen_design || (preset ? preset.member_screen_design : '') || 'wallet'),
            'coupon-screen-' + (g.coupon_screen_design || (preset ? preset.coupon_screen_design : '') || 'ticket'),
            'reservation-screen-' + (g.reservation_screen_design || (preset ? preset.reservation_screen_design : '') || 'cards'),
            'activity-screen-' + (g.activity_screen_design || (preset ? preset.activity_screen_design : '') || 'cards'),
        ];

        const cardDecl = {
            flat: 'box-shadow: none;',
            elevated: 'box-shadow: 0 6px 20px rgba(15,23,42,0.1); border-color: transparent;',
            outline: 'box-shadow: none; border: 1px solid rgba(15,23,42,0.14);',
        }[cardStyle] || '';
        const btnRadius = { rounded: '10px', pill: '999px', square: '5px' }[buttonShape] || '';
        const blockPad = { compact: '12px', relaxed: '20px' }[density] || '';

        let phoneBg = '';
        if (bgStyle === 'gradient') {
            phoneBg = 'background: var(--app-bg-gradient);';
        } else if (bgStyle === 'image' && themeVars['--app-bg-image']) {
            phoneBg = 'background-color: var(--app-bg-color); background-image: var(--app-bg-image); background-size: cover; background-position: center;';
        } else {
            phoneBg = 'background-color: var(--app-bg-color, var(--bg-canvas));';
        }

        style.textContent = [
            '.ld-phone__frame.is-line { ' + themeDecl + ' max-width: ' + (this.CONTENT_WIDTHS[widthKey] || '100%') + '; margin: 0 auto; }',
            '.ld-phone__body { ' + phoneBg + ' }',
            '.ld-phone__content { --content-padding: ' + (g.content_padding || 18) + 'px; --section-gap: ' + (g.section_gap || 16) + 'px; padding: ' + (g.content_padding || 18) + 'px !important; gap: ' + (g.section_gap || 16) + 'px !important; font-family: var(--font-base, inherit); }',
            '.ld-phone__content .ld-block { border-radius: ' + scale.md + '; ' + cardDecl + ' }',
            btnRadius ? '.ld-phone__content .ld-block--button-inner, .ld-phone__content .ld-btn-group-preview__btn, .ld-phone__content .ld-store-card__btn { border-radius: ' + btnRadius + ' !important; }' : '',
            blockPad ? '.ld-phone__content .ld-block { padding: ' + blockPad + '; }' : '',
            '.ld-phone__nav { ' + (g.tab_style === 'floating' ? 'margin: 0 10px 10px; border-radius: 18px; border: 1px solid rgba(15,23,42,0.08);' : '') + ' }',
        ].filter(Boolean).join('\n');

        const nav = this.els.phoneNav;
        if (nav) {
            nav.classList.remove('is-pill', 'is-floating');
            if (g.tab_style === 'pill') nav.classList.add('is-pill');
            if (g.tab_style === 'floating') nav.classList.add('is-floating');
        }
        if (this.els.phoneFrame) {
            Array.from(this.els.phoneFrame.classList).forEach((cls) => {
                if (/^(system-(ui|card|media|header)|member-screen|coupon-screen|reservation-screen|activity-screen)-/.test(cls)) this.els.phoneFrame.classList.remove(cls);
            });
            this.els.phoneFrame.classList.add(...systemClasses);
        }
    },

    applyDesignPreset(presetId) {
        const preset = this.findThemePreset(presetId);
        if (!preset) return;
        this.state.globalSettings = Object.assign({}, this.getGlobalSettings(), {
            theme_preset: preset.id,
            accent_color: preset.accent_color,
            font_family: preset.font_family || '',
            surface_tone: preset.surface_tone || '',
            radius_scale: preset.radius_scale || 'soft',
            tab_style: preset.tab_style || 'default',
            section_gap: preset.section_gap || 16,
            card_style: preset.card_style || '',
            button_shape: preset.button_shape || '',
            density: preset.density || 'default',
            background_style: preset.background_style || 'solid',
            bg_color: preset.bg_color || '',
            bg_gradient_start: preset.bg_gradient_start || '',
            bg_gradient_end: preset.bg_gradient_end || '',
            bg_image_url: preset.bg_image_url || '',
            bg_overlay_opacity: preset.bg_overlay_opacity != null ? preset.bg_overlay_opacity : 60,
            content_width: preset.content_width || 'comfy',
            system_ui_style: preset.system_ui_style || 'refined',
            system_card_style: preset.system_card_style || 'soft',
            system_media_shape: preset.system_media_shape || 'rounded',
            system_header_style: preset.system_header_style || 'minimal',
            member_card_design: preset.member_card_design || '',
            member_screen_design: preset.member_screen_design || 'wallet',
            coupon_screen_design: preset.coupon_screen_design || 'ticket',
            reservation_screen_design: preset.reservation_screen_design || 'cards',
            activity_screen_design: preset.activity_screen_design || 'cards',
        });
        this.ui.activePreset = preset.id;
        this.syncGlobalDesignControls();
        this.applyGlobalDesignPreview();
        this.renderPhoneContent();
        this.renderThemeGalleryActive();
    },

    syncBgStylePanels() {
        const g = this.getGlobalSettings();
        const style = g.background_style || 'solid';
        document.querySelectorAll('.ld-bg-style-tab').forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.bgStyle === style);
        });
        const solid = document.getElementById('bgPanelSolid');
        const gradient = document.getElementById('bgPanelGradient');
        const image = document.getElementById('bgPanelImage');
        if (solid) solid.hidden = style !== 'solid';
        if (gradient) gradient.hidden = style !== 'gradient';
        if (image) image.hidden = style !== 'image';
    },

    renderThemeGalleryActive() {
        const g = this.getGlobalSettings();
        const active = g.theme_preset || '';
        document.querySelectorAll('.ld-theme-card[data-preset]').forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.preset === active);
        });
    },

    buildThemeCardMiniPreview(preset) {
        const accent = preset.accent_color || '#4f46e5';
        const screens = ['ホーム', '商品', '会員', 'クーポン'];
        const bg = preset.surface_tone === 'charcoal' ? '#1f1d1b' : '#f8fafc';
        return (
            '<div class="ld-theme-card__mini" style="--mini-accent:' + accent + '">' +
            screens.map((label) => (
                '<div class="ld-theme-card__mini-screen" data-label="' + label + '" style="--mini-bg:' + bg + '">' +
                    '<span class="ld-theme-card__mini-line ld-theme-card__mini-line--accent ld-theme-card__mini-line--wide"></span>' +
                    '<span class="ld-theme-card__mini-line ld-theme-card__mini-line--mid"></span>' +
                    '<span class="ld-theme-card__mini-line"></span>' +
                '</div>'
            )).join('') +
            '</div>'
        );
    },

    initThemeGallery() {
        const gallery = document.getElementById('designPresets');
        if (!gallery) return;
        const presets = this.themePresets || [];
        gallery.innerHTML = presets.map((preset) => {
            try {
                return (
                    '<button type="button" class="ld-theme-card" data-preset="' + this.esc(preset.id) + '" title="' + this.esc(preset.description || '') + '">' +
                        '<div class="ld-theme-card__head">' +
                            '<span class="ld-theme-card__swatch" style="background:' + this.esc(preset.accent_color || '#4f46e5') + '"></span>' +
                            '<span class="ld-theme-card__name">' + this.esc(preset.name || preset.id) + '</span>' +
                        '</div>' +
                        '<span class="ld-theme-card__desc">' + this.esc(preset.description || '') + '</span>' +
                        this.buildThemeCardMiniPreview(preset) +
                    '</button>'
                );
            } catch (err) {
                console.warn('theme card build failed', preset && preset.id, err);
                return '';
            }
        }).filter(Boolean).join('');
        this.renderThemeGalleryActive();
    },
};
