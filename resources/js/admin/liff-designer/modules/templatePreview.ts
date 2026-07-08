import { ensureLiffDesignerModules } from '../ensureModules';

/**
 * テンプレートのライブプレビュー（実ブロック描画 + テーマ適用）
 * ページ読み込みを阻害しないよう、遅延マウントとフォールバックを標準とする。
 */
export const templatePreview = {
    _previewObserver: null,
    _previewMountQueue: [],
    _previewMountTimer: null,

    resolveTemplateSettings(tpl, tabKey) {
        try {
            const global = Object.assign({}, this.getGlobalSettings ? this.getGlobalSettings() : {});
            if (tpl && tpl.theme && typeof tpl.theme === 'object') {
                Object.assign(global, tpl.theme);
                if (tpl.theme.theme_preset) global.theme_preset = tpl.theme.theme_preset;
            } else if (typeof tpl.theme === 'string' && tpl.theme) {
                global.theme_preset = tpl.theme;
            }

            const tab = tabKey || (this.state && this.state.activeTab) || 'home';
            const tabSettings = (this.state && this.state.tabSettings) || {};
            const screen = Object.assign({}, tabSettings[tab] || {});
            if (tpl && tpl.tab_settings && typeof tpl.tab_settings === 'object' && !Array.isArray(tpl.tab_settings)) {
                if (tpl.tab_settings[tab]) {
                    Object.assign(screen, tpl.tab_settings[tab]);
                } else if (!tpl.layouts) {
                    Object.assign(screen, tpl.tab_settings);
                }
            }
            return { global, screen, tab };
        } catch (err) {
            console.warn('resolveTemplateSettings failed', err);
            return { global: {}, screen: {}, tab: tabKey || 'home' };
        }
    },

    getTemplatePreviewTabs(tpl, scope) {
        try {
            if (scope === 'super' && tpl.layouts && typeof tpl.layouts === 'object') {
                const keys = Object.keys(this.TAB_LABELS || {}).filter((key) => {
                    const rows = tpl.layouts[key];
                    return Array.isArray(rows) && rows.length > 0;
                });
                return keys.length > 0 ? keys : ['home'];
            }
            if (Array.isArray(tpl.target_tabs) && tpl.target_tabs.length > 0) {
                return tpl.target_tabs;
            }
            return [(this.state && this.state.activeTab) || 'home'];
        } catch (err) {
            return ['home'];
        }
    },

    applyTemplateThemeToPhone(frameEl, settings) {
        if (!frameEl || !settings) return;
        try {
            const g = settings.global || {};
            const s = settings.screen || {};
            const TP = window.LiffDesignerModules.themePanel || {};
            const themeVars = typeof this.resolveThemeVars === 'function' ? this.resolveThemeVars(g) : {};
            Object.keys(themeVars).forEach((k) => {
                frameEl.style.setProperty(k, themeVars[k]);
            });

            const preset = typeof this.findThemePreset === 'function' ? this.findThemePreset(g.theme_preset) : null;
            const bgStyle = g.background_style || (preset ? preset.background_style : 'solid') || 'solid';
            const tabStyle = g.tab_style || (preset ? preset.tab_style : '') || 'default';
            const widthKey = g.content_width || (preset ? preset.content_width : '') || 'comfy';
            const widths = (TP.CONTENT_WIDTHS || {}) as Record<string, string>;
            frameEl.style.maxWidth = widths[widthKey] || widths.comfy || '100%';
            const previewClasses = [
                'system-ui-refined', 'system-ui-editorial', 'system-ui-compact', 'system-ui-luxe',
                'system-card-soft', 'system-card-elevated', 'system-card-outline', 'system-card-flat',
                'system-media-rounded', 'system-media-square', 'system-media-arch',
                'system-header-minimal', 'system-header-editorial', 'system-header-hero',
                'member-screen-wallet', 'member-screen-premium', 'member-screen-minimal', 'member-screen-compact',
                'coupon-screen-ticket', 'coupon-screen-gallery', 'coupon-screen-compact', 'coupon-screen-luxury',
                'reservation-screen-cards', 'reservation-screen-timeline', 'reservation-screen-compact', 'reservation-screen-concierge',
                'activity-screen-cards', 'activity-screen-timeline', 'activity-screen-compact', 'activity-screen-dashboard',
            ];
            frameEl.classList.remove(...previewClasses);
            frameEl.classList.add('system-ui-' + (g.system_ui_style || 'refined'));
            frameEl.classList.add('system-card-' + (g.system_card_style || 'soft'));
            frameEl.classList.add('system-media-' + (g.system_media_shape || 'rounded'));
            frameEl.classList.add('system-header-' + (g.system_header_style || 'minimal'));
            frameEl.classList.add('member-screen-' + (g.member_screen_design || 'wallet'));
            frameEl.classList.add('coupon-screen-' + (g.coupon_screen_design || 'ticket'));
            frameEl.classList.add('reservation-screen-' + (g.reservation_screen_design || 'cards'));
            frameEl.classList.add('activity-screen-' + (g.activity_screen_design || 'cards'));

            const bodyEl = frameEl.querySelector('.ld-phone__body');
            const contentEl = frameEl.querySelector('.ld-phone__content');
            const navEl = frameEl.querySelector('.ld-phone__nav');

            if (contentEl) {
                const pad = s.content_padding != null ? s.content_padding : (g.content_padding || 18);
                contentEl.style.padding = pad + 'px';
                contentEl.style.gap = (g.section_gap || 16) + 'px';
                if (s.background_color) contentEl.style.background = s.background_color;
                if (s.background_image) {
                    contentEl.style.backgroundImage = "url('" + s.background_image + "')";
                    contentEl.style.backgroundSize = s.background_size || 'cover';
                    contentEl.style.backgroundPosition = s.background_position || 'center';
                }
            }

            if (bodyEl) {
                if (bgStyle === 'gradient') {
                    bodyEl.style.background = 'var(--app-bg-gradient)';
                } else if (bgStyle === 'image' && themeVars['--app-bg-image']) {
                    bodyEl.style.backgroundColor = 'var(--app-bg-color)';
                    bodyEl.style.backgroundImage = 'var(--app-bg-image)';
                    bodyEl.style.backgroundSize = 'cover';
                    bodyEl.style.backgroundPosition = 'center';
                } else {
                    bodyEl.style.background = s.background_color || themeVars['--app-bg-color'] || '#fafaf9';
                }
            }

            if (navEl) {
                navEl.classList.toggle('is-pill', tabStyle === 'pill');
                navEl.classList.toggle('is-floating', tabStyle === 'floating');
            }

            const scales = (TP.RADIUS_SCALES || {}) as Record<string, { md?: string }>;
            const scale = scales[g.radius_scale] || scales.soft;
            const cardStyle = g.card_style || (preset ? preset.card_style : '') || '';
            const buttonShape = g.button_shape || (preset ? preset.button_shape : '') || '';
            if (scale && contentEl) {
                contentEl.querySelectorAll('.ld-block').forEach((block) => {
                    block.style.borderRadius = scale.md;
                    if (cardStyle === 'flat') block.style.boxShadow = 'none';
                    if (cardStyle === 'elevated') block.style.boxShadow = '0 6px 20px rgba(15,23,42,0.1)';
                });
            }
            if (buttonShape && contentEl) {
                const btnRadius = { rounded: '10px', pill: '999px', square: '5px' }[buttonShape] || '';
                if (btnRadius) {
                    contentEl.querySelectorAll('.ld-block--button-inner, .ld-btn-group-preview__btn').forEach((el) => {
                        el.style.borderRadius = btnRadius;
                    });
                }
            }
        } catch (err) {
            console.warn('applyTemplateThemeToPhone failed', err);
        }
    },

    fillLivePreviewContent(contentEl, blocks, ctx) {
        if (!contentEl) return;
        ctx = ctx || {};
        contentEl.innerHTML = '';
        contentEl.classList.add('ld-phone__content--customer-preview');
        const normalized = [];
        (blocks || []).forEach((item) => {
            try {
                normalized.push(this.normalizeBlock(item));
            } catch (err) {
                console.warn('skip invalid preview block', err);
            }
        });
        if (normalized.length === 0) {
            contentEl.innerHTML = '<div class="ld-quickstart"><div class="ld-quickstart__title">ブロックがありません</div></div>';
            return;
        }
        normalized.forEach((block, i) => {
            try {
                const rendered = this.createBlockEl(block, i);
                rendered.querySelectorAll('.ld-block__toolbar,.ld-block__handle').forEach((node) => node.remove());
                rendered.classList.remove('is-selected');
                if (ctx.readonly !== false) rendered.classList.add('ld-block--readonly');
                contentEl.appendChild(rendered);
            } catch (err) {
                console.warn('preview block render failed', block && block.type, err);
            }
        });
    },

    renderStaticPhonePreviewHtml(tpl, scope, tabKey, options) {
        options = options || {};
        const accent = typeof this.getTemplateThemeAccent === 'function'
            ? this.getTemplateThemeAccent(tpl)
            : '#0d9488';
        const theme = (tpl && tpl.theme && typeof tpl.theme === 'object') ? tpl.theme : {};
        const preset = theme.theme_preset && typeof this.findThemePreset === 'function'
            ? this.findThemePreset(theme.theme_preset)
            : null;
        const blocks = typeof this.getTemplateBlocksForTab === 'function'
            ? this.getTemplateBlocksForTab(tpl, scope, tabKey).slice(0, options.limit || 5)
            : [];
        const tabLabel = (this.TAB_LABELS && this.TAB_LABELS[tabKey]) || tabKey || 'ホーム';
        const renderBlock = typeof this.renderTemplateRichDemoBlock === 'function'
            ? (b) => this.renderTemplateRichDemoBlock(b, accent)
            : () => '';
        const blocksHtml = blocks.length > 0
            ? blocks.map(renderBlock).join('')
            : (typeof this.renderTemplateDemoFallback === 'function'
                ? this.renderTemplateDemoFallback(accent)
                : '');
        let bodyBg = theme.bg_color || (preset ? preset.bg_color : '#fafaf9');
        const bgStyle = theme.background_style || (preset ? preset.background_style : '');
        if (bgStyle === 'gradient') {
            const gs = theme.bg_gradient_start || (preset && preset.bg_gradient_start) || '#f8fafc';
            const ge = theme.bg_gradient_end || (preset && preset.bg_gradient_end) || '#fff';
            bodyBg = 'linear-gradient(180deg,' + gs + ',' + ge + ')';
        }
        return (
            '<div class="ld-template-card__phone-preview ld-template-card__phone-preview--static" style="--demo-accent:' + accent + '">' +
                '<div class="ld-template-card__phone-preview__status"><span>9:41</span><span class="ld-template-card__phone-preview__status-dots"><span></span><span></span><span></span></span></div>' +
                '<div class="ld-template-card__phone-preview__header">' + this.esc(tabLabel) + '</div>' +
                '<div class="ld-template-card__phone-preview__body" style="background:' + bodyBg + '">' + blocksHtml + '</div>' +
            '</div>'
        );
    },

    renderStaticTemplateMiniPreview(tpl) {
        if (!Array.isArray(tpl.blocks) || tpl.blocks.length === 0) {
            return '<div class="ld-tab-template-card__preview"></div>';
        }
        const accent = typeof this.getTemplateThemeAccent === 'function'
            ? this.getTemplateThemeAccent(tpl)
            : '#0d9488';
        const blocks = tpl.blocks.slice(0, 5);
        const renderBlock = typeof this.renderTemplateRichDemoBlock === 'function'
            ? (b) => this.renderTemplateRichDemoBlock(b, accent)
            : () => '';
        const demo = blocks.map((block) => {
            try {
                return renderBlock(block);
            } catch (err) {
                console.warn('static preview block failed', err);
                return '';
            }
        }).join('');
        const chips = typeof this.renderTemplateBlockChips === 'function'
            ? this.renderTemplateBlockChips(blocks)
            : '';
        return (
            '<div class="ld-tab-template-card__preview">' +
                '<div class="ld-tab-template-card__mini-demo ld-tab-template-card__mini-demo--rich">' + demo + '</div>' +
                (chips ? '<div class="ld-tab-template-card__chips">' + chips + '</div>' : '') +
            '</div>'
        );
    },

    createLivePhonePreview(tpl, scope, tabKey, options) {
        options = options || {};
        const activeTab = tabKey || (this.state && this.state.activeTab) || 'home';
        const settings = this.resolveTemplateSettings(tpl, activeTab);
        const compact = !!options.compact;
        const mini = !!options.mini;
        const showNav = options.showNav !== false;

        const root = document.createElement('div');
        root.className = 'ld-tpl-live-phone'
            + (compact ? ' ld-tpl-live-phone--compact' : '')
            + (mini ? ' ld-tpl-live-phone--mini' : '')
            + (options.interactive ? ' ld-tpl-live-phone--interactive' : '');

        const frame = document.createElement('div');
        frame.className = 'ld-phone__frame is-line ld-phone__frame--preview';

        const status = document.createElement('div');
        status.className = 'ld-phone__status';
        status.innerHTML = '<span class="ld-phone__status-time">9:41</span><span class="ld-phone__status-icons" aria-hidden="true"><span></span><span></span><span></span></span>';

        const notch = document.createElement('div');
        notch.className = 'ld-phone__notch';

        const header = document.createElement('div');
        header.className = 'ld-phone__header';
        const titleEl = document.createElement('span');
        titleEl.className = 'ld-phone__header-title';
        titleEl.textContent = typeof this.tabLabel === 'function' ? this.tabLabel(activeTab) : activeTab;
        header.appendChild(titleEl);

        const body = document.createElement('div');
        body.className = 'ld-phone__body';
        const content = document.createElement('div');
        content.className = 'ld-phone__content';
        body.appendChild(content);

        const nav = document.createElement('div');
        nav.className = 'ld-phone__nav';

        frame.appendChild(notch);
        if (!compact && !mini) frame.appendChild(status);
        frame.appendChild(header);
        frame.appendChild(body);

        const previewTabs = this.getTemplatePreviewTabs(tpl, scope);
        const navTabs = scope === 'super' && previewTabs.length > 1
            ? previewTabs
            : (showNav ? [activeTab] : []);

        if (navTabs.length > 0 && showNav) {
            navTabs.forEach((key) => {
                const item = document.createElement(scope === 'super' && previewTabs.length > 1 ? 'button' : 'div') as HTMLButtonElement | HTMLDivElement;
                if (item.tagName === 'BUTTON') (item as HTMLButtonElement).type = 'button';
                item.className = 'ld-phone__nav-item' + (key === activeTab ? ' is-active' : '');
                item.dataset.previewTab = key;
                const icon = typeof this.tabIcon === 'function' ? this.tabIcon(key) : 'fa-circle';
                const label = typeof this.tabLabel === 'function' ? this.tabLabel(key) : key;
                item.innerHTML = '<i class="fas ' + this.esc(icon) + '"></i><span>' + this.esc(label) + '</span>';
                nav.appendChild(item);
            });
            frame.appendChild(nav);
        }

        const blocks = typeof this.getTemplateBlocksForTab === 'function'
            ? this.getTemplateBlocksForTab(tpl, scope, activeTab)
            : [];
        const displayBlocks = (compact || mini) ? blocks.slice(0, 6) : blocks;
        this.fillLivePreviewContent(content, displayBlocks, { readonly: true, settings });
        this.applyTemplateThemeToPhone(frame, settings);

        root.appendChild(frame);
        return { root, frame, content, nav, titleEl, settings, activeTab };
    },

    safeCreateLivePhonePreview(tpl, scope, tabKey, options) {
        try {
            return this.createLivePhonePreview(tpl, scope, tabKey, options);
        } catch (err) {
            console.warn('live phone preview failed, using static fallback', err);
            const root = document.createElement('div');
            root.className = 'ld-tpl-live-phone ld-tpl-live-phone--fallback';
            root.innerHTML = this.renderStaticPhonePreviewHtml(tpl, scope, tabKey, { limit: 4 });
            return { root, frame: null, content: null, nav: null, titleEl: null, settings: null, activeTab: tabKey };
        }
    },

    renderVisualPreviewMount(tpl, scope) {
        const tplId = this.esc(tpl.id || '');
        const tabKey = (this.state && this.state.activeTab) || 'home';
        const blocks = typeof this.getTemplateBlocksForTab === 'function'
            ? this.getTemplateBlocksForTab(tpl, scope, tabKey)
            : [];
        const count = blocks.length;
        const staticPreview = this.renderStaticPhonePreviewHtml(tpl, scope, tabKey, { limit: 4 });
        return (
            '<button type="button" class="ld-template-card__preview-btn" data-template-preview-trigger="' + tplId + '">' +
                '<div class="ld-template-card__phone-mount" data-tpl-mount="' + tplId + '" data-tpl-scope="' + this.esc(scope) + '" data-tpl-tab="' + this.esc(tabKey) + '" data-tpl-pending="1">' +
                    staticPreview +
                '</div>' +
                '<span class="ld-template-card__preview-hint"><i class="fas fa-search-plus"></i> タップして実機プレビュー（' + count + 'ブロック）</span>' +
            '</button>' +
            '<div class="ld-template-card__block-chips-wrap" data-tpl-chips="' + tplId + '">' +
                (typeof this.renderTemplateBlockChips === 'function'
                    ? '<div class="ld-template-card__block-chips">' + this.renderTemplateBlockChips(blocks.slice(0, 8)) + '</div>'
                    : '') +
            '</div>'
        );
    },

    _ensurePreviewObserver() {
        if (this._previewObserver) return;
        const self = this;
        this._previewObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    self._enqueuePreviewMount(entry.target);
                }
            });
        }, { root: null, rootMargin: '80px', threshold: 0.05 });
    },

    _enqueuePreviewMount(mountEl) {
        if (!mountEl || mountEl.dataset.tplPending !== '1' || mountEl.dataset.tplQueued === '1') return;
        mountEl.dataset.tplQueued = '1';
        this._previewMountQueue.push(mountEl);
        this._schedulePreviewMountFlush();
    },

    _schedulePreviewMountFlush() {
        if (this._previewMountTimer) return;
        const self = this;
        const run = () => {
            self._previewMountTimer = null;
            if (self._previewMountQueue.length === 0) return;
            const mountEl = self._previewMountQueue.shift();
            if (mountEl && mountEl.isConnected) {
                self._mountSinglePreview(mountEl);
            }
            if (self._previewMountQueue.length > 0) {
                self._previewMountTimer = window.requestAnimationFrame(run);
            }
        };
        this._previewMountTimer = window.requestAnimationFrame(run);
    },

    _mountSinglePreview(mountEl) {
        if (!mountEl || mountEl.dataset.tplPending !== '1') return;
        const id = mountEl.dataset.tplMount || '';
        const tplScope = mountEl.dataset.tplScope || 'tab';
        const tabKey = mountEl.dataset.tplTab || (this.state && this.state.activeTab) || 'home';
        const tpl = (this._previewTplMap && this._previewTplMap[id]) || null;
        mountEl.dataset.tplPending = '0';
        mountEl.dataset.tplQueued = '0';
        if (this._previewObserver) this._previewObserver.unobserve(mountEl);

        if (!tpl) return;

        try {
            const preview = this.safeCreateLivePhonePreview(tpl, tplScope, tabKey, { compact: true, showNav: false });
            mountEl.innerHTML = '';
            mountEl.appendChild(preview.root);
            mountEl.classList.add('is-live-mounted');
        } catch (err) {
            console.warn('mount single preview failed', err);
        }
    },

    scheduleTemplatePreviews(container, templates, scope) {
        if (!container || !Array.isArray(templates)) return;
        try {
            this._previewTplMap = {};
            templates.forEach((tpl, i) => { this._previewTplMap[String(tpl.id || i)] = tpl; });

            this._ensurePreviewObserver();
            container.querySelectorAll('[data-tpl-mount][data-tpl-pending="1"]').forEach((mount) => {
                this._previewObserver.observe(mount);
            });

            const first = container.querySelector('[data-tpl-mount][data-tpl-pending="1"]');
            if (first) this._enqueuePreviewMount(first);
        } catch (err) {
            console.warn('scheduleTemplatePreviews failed', err);
        }
    },

    mountTemplatePreviews(container, templates, scope) {
        this.scheduleTemplatePreviews(container, templates, scope);
    },

    teardownTemplatePreviews() {
        if (this._previewObserver) {
            this._previewObserver.disconnect();
            this._previewObserver = null;
        }
        this._previewMountQueue = [];
        if (this._previewMountTimer) {
            window.cancelAnimationFrame(this._previewMountTimer);
            this._previewMountTimer = null;
        }
        this._previewTplMap = null;
    },

    renderTemplateThemeMeta(tpl) {
        try {
            const settings = this.resolveTemplateSettings(tpl, (this.state && this.state.activeTab) || 'home');
            const g = settings.global;
            const preset = typeof this.findThemePreset === 'function' ? this.findThemePreset(g.theme_preset) : null;
            const accent = g.accent_color || (preset ? preset.accent_color : '#0d9488');
            const presetName = preset ? preset.name : (g.theme_preset === 'custom' ? 'カスタム' : (g.theme_preset || 'デフォルト'));
            const fontKey = g.font_family || (preset ? preset.font_family : '');
            const fontLabel = fontKey && this.fontStacks && this.fontStacks[fontKey]
                ? this.fontStacks[fontKey].label
                : 'システムフォント';
            const widthKey = g.content_width || (preset ? preset.content_width : '') || 'comfy';
            const widthLabels = { edge: 'フル幅', comfy: '標準', narrow: 'コンパクト' };

            return (
                '<div class="ld-template-preview-modal__theme">' +
                    '<div class="ld-template-preview-modal__sidebar-title">テーマ</div>' +
                    '<div class="ld-template-preview-modal__theme-row">' +
                        '<span class="ld-template-preview-modal__theme-swatch" style="background:' + this.esc(accent) + '"></span>' +
                        '<div><div class="ld-template-preview-modal__theme-name">' + this.esc(presetName) + '</div>' +
                        '<div class="ld-template-preview-modal__theme-sub">' + this.esc(fontLabel) + ' · ' + this.esc(widthLabels[widthKey] || widthKey) + '</div></div>' +
                    '</div>' +
                '</div>'
            );
        } catch (err) {
            return '';
        }
    },
};


ensureLiffDesignerModules().templatePreview = templatePreview;
