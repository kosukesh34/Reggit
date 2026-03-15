window.LiffDesignerModules = window.LiffDesignerModules || {};

window.LiffDesignerModules.templateManager = {
    openTemplateModal() {
        const templates = this.templates || [];
        const tabTemplates = templates.filter(tpl => Array.isArray(tpl && tpl.blocks));
        const superPatterns = templates.filter(tpl => tpl && tpl.scope === 'all_tabs' && tpl.layouts && typeof tpl.layouts === 'object');

        if (tabTemplates.length === 0 && superPatterns.length === 0) {
            alert('利用可能なテンプレートがありません。');
            return;
        }
        const modal = document.createElement('div');
        modal.className = 'ld-modal';
        modal.innerHTML =
            '<div class="ld-modal__dialog">' +
                '<div class="ld-modal__head">' +
                    '<h3 class="ld-modal__title"><i class="fas fa-layer-group"></i> テンプレートを適用</h3>' +
                    '<button type="button" class="ld-btn ld-btn--ghost" data-close-modal><i class="fas fa-times"></i></button>' +
                '</div>' +
                '<div class="ld-modal__body">' +
                    '<div class="ld-template-toolbar">' +
                        '<div class="ld-template-toolbar__scope">' +
                            '<button type="button" class="ld-chip is-active" data-template-scope="tab">タブテンプレート</button>' +
                            '<button type="button" class="ld-chip" data-template-scope="super">全画面パターン</button>' +
                        '</div>' +
                        '<input type="text" class="ld-form-input ld-template-toolbar__search" placeholder="テンプレ検索 (例: カフェ / 予約 / 会員)" data-template-search>' +
                    '</div>' +
                    '<div class="ld-template-categories" data-template-categories></div>' +
                    '<div data-template-results></div>' +
                '</div>' +
            '</div>';
        const resultsEl = modal.querySelector('[data-template-results]');
        const categoryEl = modal.querySelector('[data-template-categories]');
        const searchEl = modal.querySelector('[data-template-search]');
        const state = { scope: 'tab', category: 'all', keyword: '' };

        const getSource = () => state.scope === 'super' ? superPatterns : tabTemplates;
        const getFilteredList = () => {
            const kw = (state.keyword || '').trim().toLowerCase();
            return getSource().filter((tpl) => {
                const category = (tpl.category || 'other');
                if (state.category !== 'all' && category !== state.category) return false;
                if (!kw) return true;
                const targets = Array.isArray(tpl.target_tabs) ? tpl.target_tabs : [];
                const text = [tpl.name, tpl.description, category, targets.join(' ')].join(' ').toLowerCase();
                return text.includes(kw);
            });
        };
        const renderCategories = () => {
            const source = getSource();
            const categoryMap = {};
            source.forEach((tpl) => {
                const key = tpl.category || 'other';
                categoryMap[key] = this.getTemplateCategoryLabel(key);
            });
            const keys = Object.keys(categoryMap);
            if (keys.length === 0) {
                categoryEl.innerHTML = '';
                return;
            }
            const chips = ['<button type="button" class="ld-chip' + (state.category === 'all' ? ' is-active' : '') + '" data-template-category="all">すべて</button>'];
            keys.sort().forEach((key) => {
                chips.push('<button type="button" class="ld-chip' + (state.category === key ? ' is-active' : '') + '" data-template-category="' + this.esc(key) + '">' + this.esc(categoryMap[key]) + '</button>');
            });
            categoryEl.innerHTML = chips.join('');
        };
        const renderResults = () => {
            const list = getFilteredList();
            if (list.length === 0) {
                resultsEl.innerHTML = '<div class="ld-template-empty">条件に一致するテンプレートがありません。</div>';
                return;
            }

            const cards = list.map((tpl, i) => {
                const badge = '<span class="ld-template-card__support">' + this.esc(this.getPrimaryDeviceLabel(tpl)) + '</span>';
                const count = state.scope === 'super'
                    ? Object.values(tpl.layouts || {}).reduce((carry, rows) => carry + (Array.isArray(rows) ? rows.length : 0), 0)
                    : (Array.isArray(tpl.blocks) ? tpl.blocks.length : 0);
                const targetTabs = Array.isArray(tpl.target_tabs) && tpl.target_tabs.length > 0
                    ? tpl.target_tabs.map((tab) => this.TAB_LABELS[tab] || tab).join(' / ')
                    : '全般';
                const titlePrefix = state.scope === 'super' ? '<span class="ld-template-card__badge">SUPER</span> ' : '';
                const applyBtnClass = state.scope === 'super' ? 'ld-btn--outline' : 'ld-btn--primary';
                const actionAttr = state.scope === 'super'
                    ? 'data-super-template-id="' + this.esc(tpl.id || String(i)) + '"'
                    : 'data-template-id="' + this.esc(tpl.id || String(i)) + '"';
                const actionLabel = state.scope === 'super' ? '全タブに適用' : 'このタブに適用';
                const desc = tpl.description || (state.scope === 'super' ? '全タブに統一デザインを適用します。' : 'テンプレ中心でノーコード編集できます。');
                const visualPreview = this.renderTemplateVisualPreview(tpl, state.scope);
                return (
                    '<div class="ld-template-card">' +
                        '<div>' +
                            '<div class="ld-template-card__name">' + titlePrefix + this.esc(tpl.name || ('テンプレート' + (i + 1))) + '</div>' +
                            '<div class="ld-template-card__meta">' + this.esc(desc) + '</div>' +
                        '</div>' +
                        '<div class="ld-template-card__tags">' +
                            '<span class="ld-template-card__tag">' + this.esc(this.getTemplateCategoryLabel(tpl.category || 'other')) + '</span>' +
                            '<span class="ld-template-card__tag">対象: ' + this.esc(targetTabs) + '</span>' +
                            '<span class="ld-template-card__tag">要素数: ' + count + '</span>' +
                        '</div>' +
                        visualPreview +
                        '<div class="ld-template-card__supports">' + badge + '</div>' +
                        '<button type="button" class="ld-btn ' + applyBtnClass + ' ld-btn--block" ' + actionAttr + '>' + actionLabel + '</button>' +
                    '</div>'
                );
            }).join('');

            const title = state.scope === 'super' ? 'スーパターン（全タブ一括）' : 'タブ用テンプレート';
            resultsEl.innerHTML = '<div class="ld-template-section"><div class="ld-template-section__title">' + title + '</div><div class="ld-template-grid">' + cards + '</div></div>';
        };
        const renderAll = () => {
            renderCategories();
            renderResults();
            modal.querySelectorAll('[data-template-scope]').forEach((btn) => {
                btn.classList.toggle('is-active', btn.dataset.templateScope === state.scope);
            });
        };

        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('[data-close-modal]')) {
                modal.remove();
                return;
            }
            const scopeBtn = e.target.closest('[data-template-scope]');
            if (scopeBtn) {
                state.scope = scopeBtn.dataset.templateScope === 'super' ? 'super' : 'tab';
                state.category = 'all';
                renderAll();
                return;
            }
            const categoryBtn = e.target.closest('[data-template-category]');
            if (categoryBtn) {
                state.category = categoryBtn.dataset.templateCategory || 'all';
                renderAll();
                return;
            }
            const applyBtn = e.target.closest('[data-template-id]');
            if (applyBtn) {
                const templateId = applyBtn.dataset.templateId || '';
                const tpl = tabTemplates.find((item) => String(item.id || '') === templateId);
                if (!tpl || !Array.isArray(tpl.blocks)) return;
                this.applyTabTemplate(tpl);
                modal.remove();
                return;
            }
            const superBtn = e.target.closest('[data-super-template-id]');
            if (!superBtn) return;
            const templateId = superBtn.dataset.superTemplateId || '';
            const pattern = superPatterns.find((item) => String(item.id || '') === templateId);
            if (!pattern || !pattern.layouts || typeof pattern.layouts !== 'object') return;
            this.applySuperPattern(pattern);
            modal.remove();
        });
        if (searchEl) {
            searchEl.addEventListener('input', () => {
                state.keyword = searchEl.value || '';
                renderResults();
            });
        }

        renderAll();
        document.body.appendChild(modal);
    },

    renderTabTemplateList() {
        if (!this.els.tabTemplateList) return;
        const templates = this.getTemplatesForTab(this.state.activeTab);
        if (templates.length === 0) {
            this.els.tabTemplateList.innerHTML = '<div class="ld-tab-template-empty">このタブ用テンプレートがありません。</div>';
            return;
        }
        const grouped = {};
        templates.forEach((tpl) => {
            const key = tpl.category || 'other';
            if (!grouped[key]) grouped[key] = [];
            grouped[key].push(tpl);
        });
        const html = Object.keys(grouped).sort().map((key) => {
            const cards = grouped[key].map((tpl) => {
                const count = Array.isArray(tpl.blocks) ? tpl.blocks.length : 0;
                const preview = this.renderTabTemplateMiniPreview(tpl);
                const support = '<span class="ld-tab-template-card__device">' + this.esc(this.getPrimaryDeviceLabel(tpl)) + '</span>';
                return (
                    '<div class="ld-tab-template-card">' +
                        '<div class="ld-tab-template-card__name">' + this.esc(tpl.name || 'テンプレート') + '</div>' +
                        '<div class="ld-tab-template-card__meta">' + this.esc(tpl.description || 'すぐ使えるテンプレート') + '</div>' +
                        preview +
                        '<div class="ld-tab-template-card__footer">' +
                            '<span class="ld-tab-template-card__count">要素数: ' + count + '</span>' +
                            '<div class="ld-tab-template-card__devices">' + support + '</div>' +
                        '</div>' +
                        '<div class="ld-tab-template-card__actions">' +
                            '<button type="button" class="ld-btn ld-btn--outline ld-btn--xs ld-btn--block" data-tab-template-preview-id="' + this.esc(tpl.id || '') + '">プレビュー</button>' +
                            '<button type="button" class="ld-btn ld-btn--primary ld-btn--xs ld-btn--block" data-tab-template-id="' + this.esc(tpl.id || '') + '">適用</button>' +
                        '</div>' +
                    '</div>'
                );
            }).join('');
            return '<div class="ld-tab-template-group"><div class="ld-tab-template-group__title">' + this.esc(this.getTemplateCategoryLabel(key)) + '</div>' + cards + '</div>';
        }).join('');
        this.els.tabTemplateList.innerHTML = html;
    },

    renderTabTemplateMiniPreview(tpl) {
        const blocks = Array.isArray(tpl.blocks) ? tpl.blocks.slice(0, 3) : [];
        if (blocks.length === 0) return '<div class="ld-tab-template-card__preview"></div>';
        const chips = blocks.map((item) => {
            const def = this.BLOCK_TYPES.find((bt) => bt.type === (item.type || 'text'));
            const label = def ? def.label : (item.type || 'text');
            return '<span class="ld-tab-template-card__chip">' + this.esc(label) + '</span>';
        }).join('');
        return '<div class="ld-tab-template-card__preview"><div class="ld-tab-template-card__chips">' + chips + '</div></div>';
    },

    getTemplatesForTab(tabKey) {
        return (this.templates || []).filter((tpl) => {
            if (!tpl || !Array.isArray(tpl.blocks)) return false;
            if (!Array.isArray(tpl.target_tabs) || tpl.target_tabs.length === 0) return true;
            return tpl.target_tabs.includes(tabKey);
        });
    },

    applyTabTemplate(tpl) {
        this.state.layouts[this.state.activeTab] = tpl.blocks.map(item => this.normalizeBlock(item));
        if (tpl.tab_settings && typeof tpl.tab_settings === 'object') {
            this.state.tabSettings[this.state.activeTab] = { ...this.state.tabSettings[this.state.activeTab], ...tpl.tab_settings };
        }
        if (typeof tpl.custom_css === 'string' && this.els.customCssEditor) {
            this.els.customCssEditor.value = tpl.custom_css;
            this.applyCustomCssPreview();
        }
        this.state.selectedId = null;
        this.pushHistory();
        this.render();
    },

    openTabTemplatePreview(tpl) {
        const ov = document.createElement('div');
        ov.className = 'ld-preview-overlay';
        ov.innerHTML =
            '<div class="ld-template-preview-modal">' +
                '<div class="ld-template-preview-modal__head">' +
                    '<div>' +
                        '<div class="ld-template-preview-modal__title">' + this.esc(tpl.name || 'テンプレート') + '</div>' +
                        '<div class="ld-template-preview-modal__meta">' + this.esc(tpl.description || '適用前プレビュー') + '</div>' +
                    '</div>' +
                    '<button type="button" class="ld-btn ld-btn--ghost" data-close-template-preview><i class="fas fa-times"></i></button>' +
                '</div>' +
                '<div class="ld-template-preview-modal__body">' +
                    '<div class="ld-template-preview-phone"><div class="ld-phone__frame is-line"><div class="ld-phone__notch"></div><div class="ld-phone__header"><span class="ld-phone__header-title">' + this.esc(this.TAB_LABELS[this.state.activeTab] || '') + '</span></div><div class="ld-phone__body"><div class="ld-phone__content" data-template-preview-content></div></div></div></div>' +
                    '<div class="ld-template-preview-modal__actions">' +
                        '<button type="button" class="ld-btn ld-btn--outline" data-close-template-preview>閉じる</button>' +
                        '<button type="button" class="ld-btn ld-btn--primary" data-apply-template-preview="' + this.esc(tpl.id || '') + '">このテンプレートを適用</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        const content = ov.querySelector('[data-template-preview-content]');
        const blocks = Array.isArray(tpl.blocks) ? tpl.blocks.map((item) => this.normalizeBlock(item)) : [];
        blocks.forEach((block, i) => {
            const rendered = this.createBlockEl(block, i);
            const el = rendered.cloneNode(true);
            el.querySelectorAll('.ld-block__toolbar,.ld-block__handle').forEach((node) => node.remove());
            el.classList.remove('is-selected');
            content.appendChild(el);
        });

        ov.addEventListener('click', (e) => {
            if (e.target === ov || e.target.closest('[data-close-template-preview]')) {
                ov.remove();
                return;
            }
            const applyBtn = e.target.closest('[data-apply-template-preview]');
            if (!applyBtn) return;
            this.applyTabTemplate(tpl);
            ov.remove();
        });

        document.body.appendChild(ov);
    },

    renderTemplateVisualPreview(tpl, scope) {
        var blocks = [];
        if (scope === 'super' && tpl.layouts && typeof tpl.layouts === 'object') {
            blocks = Array.isArray(tpl.layouts.home) ? tpl.layouts.home : [];
        } else if (Array.isArray(tpl.blocks)) {
            blocks = tpl.blocks;
        }
        var previewBlocks = blocks.slice(0, 4);
        if (previewBlocks.length === 0) {
            return '<div class="ld-template-card__preview"><span class="ld-template-card__line wide"></span><span class="ld-template-card__line mid"></span></div>';
        }
        var blockColorMap = {
            hero: { bg: 'linear-gradient(135deg, #667eea, #764ba2)', color: '#fff', cls: '--hero' },
            button: { bg: '#06C755', color: '#fff', cls: '--button' },
            banner: { bg: '#3b82f6', color: '#fff', cls: '--banner' },
            data: { bg: '#fff', color: '#6b7280', cls: '--data' },
        };
        var html = '<div class="ld-template-card__visual">';
        html += '<div class="ld-template-card__visual-header" style="background:linear-gradient(135deg,#667eea,#764ba2)">ホーム</div>';
        html += '<div class="ld-template-card__visual-blocks">';
        for (var j = 0; j < previewBlocks.length; j++) {
            var b = previewBlocks[j];
            var bt = this.BLOCK_TYPES.find(function(x) { return x.type === (b.type || 'text'); });
            var label = bt ? bt.label : (b.type || 'text');
            var icon = bt ? bt.icon : 'fa-cube';
            var cm = blockColorMap[b.type || ''];
            var cls = cm ? ' ld-template-card__visual-block' + cm.cls : '';
            html += '<div class="ld-template-card__visual-block' + cls + '"><i class="fas ' + icon + '"></i> ' + this.esc(label) + '</div>';
        }
        if (blocks.length > 4) {
            html += '<div class="ld-template-card__visual-block" style="justify-content:center;color:#9ca3af;font-style:italic">+' + (blocks.length - 4) + ' ブロック</div>';
        }
        html += '</div></div>';
        return html;
    },

    getTemplateCategoryLabel(key) {
        const labels = {
            all: 'すべて',
            home: 'ホーム',
            order: '注文導線',
            member: '会員',
            reservation: '予約',
            campaign: '販促',
            story: 'ブランド',
            support: 'サポート',
            industry: '業種別',
            super: '全画面',
            other: 'その他',
        };
        return labels[key] || key || 'その他';
    },

    getPrimaryDeviceLabel(tpl) {
        const supports = Array.isArray(tpl && tpl.device_support) && tpl.device_support.length > 0
            ? tpl.device_support
            : ['line'];
        const first = supports[0] || 'line';
        return (this.DEVICE_PROFILES[first] || { label: first }).label;
    },

    applySuperPattern(pattern, skipConfirm) {
        if (!skipConfirm && !confirm('現在の全タブレイアウトを上書きします。適用しますか？')) {
            return;
        }

        const nextLayouts = {};
        Object.keys(this.TAB_LABELS).forEach(key => {
            const rows = Array.isArray(pattern.layouts[key]) ? pattern.layouts[key] : [];
            nextLayouts[key] = rows.map(item => this.normalizeBlock(item || {}));
        });
        this.state.layouts = nextLayouts;

        if (pattern.tab_settings && typeof pattern.tab_settings === 'object') {
            Object.keys(this.TAB_LABELS).forEach(key => {
                const src = pattern.tab_settings[key];
                if (src && typeof src === 'object') {
                    this.state.tabSettings[key] = { ...this.state.tabSettings[key], ...src };
                }
            });
        }
        if (typeof pattern.custom_css === 'string' && this.els.customCssEditor) {
            this.els.customCssEditor.value = pattern.custom_css;
            this.applyCustomCssPreview();
        }

        this.state.activeTab = 'home';
        this.state.selectedId = null;
        this.pushHistory();
        this.render();
    },
};
