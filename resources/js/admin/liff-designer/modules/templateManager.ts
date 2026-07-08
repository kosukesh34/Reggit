import { ensureLiffDesignerModules } from '../ensureModules';

export const templateManager = {
    openTemplateModal() {
        try {
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
            '<div class="ld-modal__dialog ld-modal__dialog--templates">' +
                '<div class="ld-modal__head">' +
                    '<div class="ld-modal__head-main">' +
                        '<h3 class="ld-modal__title"><i class="fas fa-layer-group"></i> テンプレートギャラリー</h3>' +
                        '<p class="ld-modal__subtitle">現在のタブ: <strong>' + this.esc(this.TAB_LABELS[this.state.activeTab] || this.state.activeTab) + '</strong> — プロ品質デザインを選んで即適用</p>' +
                    '</div>' +
                    '<button type="button" class="ld-btn ld-btn--ghost" data-close-modal><i class="fas fa-times"></i></button>' +
                '</div>' +
                '<div class="ld-modal__body">' +
                    '<div class="ld-template-toolbar">' +
                        '<div class="ld-template-toolbar__row">' +
                            '<div class="ld-template-toolbar__scope">' +
                                '<button type="button" class="ld-chip is-active" data-template-scope="tab">タブテンプレート</button>' +
                                '<button type="button" class="ld-chip" data-template-scope="super">全画面パターン</button>' +
                            '</div>' +
                            '<div class="ld-template-toolbar__search-wrap">' +
                                '<input type="text" class="ld-form-input ld-template-toolbar__search" placeholder="業種・用途で検索 (例: カフェ / 予約 / 会員 / デリバリー)" data-template-search>' +
                                '<button type="button" class="ld-template-search-clear" data-template-search-clear hidden aria-label="検索をクリア"><i class="fas fa-times"></i></button>' +
                            '</div>' +
                        '</div>' +
                        '<div class="ld-template-toolbar__sort">' +
                            '<label for="ldTemplateSort">並び替え</label>' +
                            '<select id="ldTemplateSort" data-template-sort>' +
                                '<option value="recommended">おすすめ順</option>' +
                                '<option value="name">名前順</option>' +
                                '<option value="blocks">ブロック数（多い順）</option>' +
                            '</select>' +
                        '</div>' +
                    '</div>' +
                    '<div data-template-featured></div>' +
                    '<div class="ld-template-categories" data-template-categories></div>' +
                    '<div data-template-results></div>' +
                '</div>' +
            '</div>';
        const resultsEl = modal.querySelector('[data-template-results]') as any;
        const featuredEl = modal.querySelector('[data-template-featured]') as any;
        const categoryEl = modal.querySelector('[data-template-categories]');
        const searchEl = modal.querySelector('[data-template-search]') as any;
        const searchClearEl = modal.querySelector('[data-template-search-clear]') as any;
        const sortEl = modal.querySelector('[data-template-sort]') as any;
        const state = { scope: 'tab', category: 'all', keyword: '', sort: 'recommended' };

        const getSource = () => state.scope === 'super' ? superPatterns : tabTemplates;
        const sortTemplates = (list) => {
            const rows = list.slice();
            if (state.sort === 'name') {
                rows.sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'ja'));
                return rows;
            }
            if (state.sort === 'blocks') {
                rows.sort((a, b) => {
                    const ac = state.scope === 'super'
                        ? Object.values(a.layouts || {}).reduce((n: number, r: unknown) => n + (Array.isArray(r) ? r.length : 0), 0)
                        : (Array.isArray(a.blocks) ? a.blocks.length : 0);
                    const bc = state.scope === 'super'
                        ? Object.values(b.layouts || {}).reduce((n: number, r: unknown) => n + (Array.isArray(r) ? r.length : 0), 0)
                        : (Array.isArray(b.blocks) ? b.blocks.length : 0);
                    return bc - ac;
                });
                return rows;
            }
            const score = (tpl) => {
                let s = 0;
                const cat = tpl.category || '';
                if (cat === 'native' || cat === 'luxury') s += 30;
                if (cat === 'industry' || cat === 'super') s += 20;
                const targets = Array.isArray(tpl.target_tabs) ? tpl.target_tabs : [];
                if (targets.length === 0 || targets.includes(this.state.activeTab)) s += 15;
                if (String(this.state.lastAppliedTemplateId || '') === String(tpl.id || '')) s += 10;
                return s;
            };
            rows.sort((a, b) => score(b) - score(a) || String(a.name || '').localeCompare(String(b.name || ''), 'ja'));
            return rows;
        };
        const getFilteredList = () => {
            const kw = (state.keyword || '').trim().toLowerCase();
            const filtered = getSource().filter((tpl) => {
                const category = (tpl.category || 'other');
                if (state.category !== 'all' && category !== state.category) return false;
                if (!kw) return true;
                const targets = Array.isArray(tpl.target_tabs) ? tpl.target_tabs : [];
                const text = [tpl.name, tpl.description, category, targets.join(' ')].join(' ').toLowerCase();
                return text.includes(kw);
            });
            return sortTemplates(filtered);
        };
        const getFeaturedList = () => {
            const active = this.state.activeTab || 'home';
            const source = getSource();
            const picks = source.filter((tpl) => {
                const targets = Array.isArray(tpl.target_tabs) ? tpl.target_tabs : [];
                const okTab = targets.length === 0 || targets.includes(active);
                const cat = tpl.category || '';
                return okTab && (cat === 'native' || cat === 'luxury' || cat === 'super' || cat === 'industry');
            });
            return sortTemplates(picks).slice(0, 3);
        };
        const renderFeatured = () => {
            if (!featuredEl) return;
            if (state.keyword || state.category !== 'all') {
                featuredEl.innerHTML = '';
                return;
            }
            const picks = getFeaturedList();
            if (picks.length === 0) {
                featuredEl.innerHTML = '';
                return;
            }
            const cards = picks.map((tpl) => {
                const isSuper = state.scope === 'super';
                const previewAttr = isSuper
                    ? 'data-super-template-preview-id="' + this.esc(tpl.id || '') + '"'
                    : 'data-template-preview-id="' + this.esc(tpl.id || '') + '"';
                const applyAttr = isSuper
                    ? 'data-super-template-id="' + this.esc(tpl.id || '') + '"'
                    : 'data-template-id="' + this.esc(tpl.id || '') + '"';
                return (
                    '<div class="ld-template-featured-card">' +
                        '<div class="ld-template-featured-card__name">' + this.esc(tpl.name || 'テンプレート') + '</div>' +
                        '<div class="ld-template-featured-card__meta">' + this.esc(tpl.description || '') + '</div>' +
                        '<div class="ld-template-featured-card__actions">' +
                            '<button type="button" class="ld-btn ld-btn--outline ld-btn--xs ld-btn--block" ' + previewAttr + '><i class="fas fa-mobile-alt"></i> 実画面</button>' +
                            '<button type="button" class="ld-btn ld-btn--primary ld-btn--xs ld-btn--block" ' + applyAttr + '><i class="fas fa-check"></i> このデザインを適用</button>' +
                        '</div>' +
                    '</div>'
                );
            }).join('');
            featuredEl.innerHTML =
                '<div class="ld-template-featured">' +
                    '<div class="ld-template-featured__title"><i class="fas fa-star"></i> このタブにおすすめ</div>' +
                    '<div class="ld-template-featured__grid">' + cards + '</div>' +
                '</div>';
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
                    ? Object.values(tpl.layouts || {}).reduce((carry: number, rows: unknown) => carry + (Array.isArray(rows) ? rows.length : 0), 0)
                    : (Array.isArray(tpl.blocks) ? tpl.blocks.length : 0);
                const targetTabs = Array.isArray(tpl.target_tabs) && tpl.target_tabs.length > 0
                    ? tpl.target_tabs.map((tab) => this.TAB_LABELS[tab] || tab).join(' / ')
                    : '全般';
                const titlePrefix = state.scope === 'super' ? '<span class="ld-template-card__badge">SUPER</span> ' : '';
                const applyBtnClass = state.scope === 'super' ? 'ld-btn--outline' : 'ld-btn--primary';
                const actionAttr = state.scope === 'super'
                    ? 'data-super-template-id="' + this.esc(tpl.id || String(i)) + '"'
                    : 'data-template-id="' + this.esc(tpl.id || String(i)) + '"';
                const actionLabel = state.scope === 'super' ? '全画面をこのデザインにする' : 'このデザインを適用';
                const previewAttr = state.scope === 'super'
                    ? 'data-super-template-preview-id="' + this.esc(tpl.id || String(i)) + '"'
                    : 'data-template-preview-id="' + this.esc(tpl.id || String(i)) + '"';
                const desc = tpl.description || (state.scope === 'super' ? '全タブに統一デザインを適用します。' : 'テンプレ中心でノーコード編集できます。');
                const visualPreview = this.renderTemplateVisualPreview(tpl, state.scope);
                const isApplied = String(this.state.lastAppliedTemplateId || '') === String(tpl.id || '');
                const appliedBadge = isApplied
                    ? '<div class="ld-template-card__applied"><i class="fas fa-check-circle"></i> 適用済み</div>'
                    : '';
                return (
                    '<div class="ld-template-card' + (isApplied ? ' is-applied' : '') + '">' +
                        appliedBadge +
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
                        '<div class="ld-template-card__actions">' +
                            '<button type="button" class="ld-btn ld-btn--outline ld-btn--block" ' + previewAttr + '><i class="fas fa-mobile-alt"></i> ユーザー画面で確認</button>' +
                            '<button type="button" class="ld-btn ' + applyBtnClass + ' ld-btn--block" ' + actionAttr + '><i class="fas fa-check"></i> ' + actionLabel + '</button>' +
                        '</div>' +
                    '</div>'
                );
            }).join('');

            const title = state.scope === 'super' ? 'スーパターン（全タブ一括）' : 'タブ用テンプレート';
            resultsEl.innerHTML = '<div class="ld-template-section"><div class="ld-template-section__title"><span>' + title + '</span><span class="ld-template-section__count">' + list.length + '件</span></div><div class="ld-template-grid">' + cards + '</div></div>';
            try {
                this.teardownTemplatePreviews();
                this.scheduleTemplatePreviews(resultsEl, list, state.scope);
            } catch (previewErr) {
                console.warn('Template card previews skipped', previewErr);
            }
        };
        const renderAll = () => {
            renderFeatured();
            renderCategories();
            renderResults();
            modal.querySelectorAll('[data-template-scope]').forEach((btn: any) => {
                btn.classList.toggle('is-active', btn.dataset.templateScope === state.scope);
            });
            if (searchClearEl) searchClearEl.hidden = !(state.keyword || '').trim();
        };

        modal.addEventListener('click', (e) => {
            if (e.target === modal || (e.target as HTMLElement).closest('[data-close-modal]')) {
                this.teardownTemplatePreviews();
                modal.remove();
                return;
            }
            const scopeBtn = (e.target as HTMLElement).closest('[data-template-scope]') as HTMLElement | null;
            if (scopeBtn) {
                state.scope = scopeBtn.dataset.templateScope === 'super' ? 'super' : 'tab';
                state.category = 'all';
                renderAll();
                return;
            }
            const categoryBtn = (e.target as HTMLElement).closest('[data-template-category]') as HTMLElement | null;
            if (categoryBtn) {
                state.category = categoryBtn.dataset.templateCategory || 'all';
                renderAll();
                return;
            }
            const applyBtn = (e.target as HTMLElement).closest('[data-template-id]') as HTMLElement | null;
            if (applyBtn) {
                const templateId = applyBtn.dataset.templateId || '';
                const tpl = tabTemplates.find((item) => String(item.id || '') === templateId);
                if (!tpl || !Array.isArray(tpl.blocks)) return;
                this.applyTabTemplate(tpl, { fromModal: true });
                this.teardownTemplatePreviews();
                modal.remove();
                return;
            }
            const previewTabBtn = (e.target as HTMLElement).closest('[data-template-preview-id]') as HTMLElement | null;
            if (previewTabBtn) {
                const templateId = previewTabBtn.dataset.templatePreviewId || '';
                const tpl = tabTemplates.find((item) => String(item.id || '') === templateId);
                if (!tpl) return;
                this.openTemplatePreview(tpl, { scope: 'tab', parentModal: modal });
                return;
            }
            const previewSuperBtn = (e.target as HTMLElement).closest('[data-super-template-preview-id]') as HTMLElement | null;
            if (previewSuperBtn) {
                const templateId = previewSuperBtn.dataset.superTemplatePreviewId || '';
                const pattern = superPatterns.find((item) => String(item.id || '') === templateId);
                if (!pattern) return;
                this.openTemplatePreview(pattern, { scope: 'super', parentModal: modal });
                return;
            }
            const previewCardBtn = (e.target as HTMLElement).closest('.ld-template-card__preview-btn') as HTMLElement | null;
            if (previewCardBtn) {
                const templateId = previewCardBtn.dataset.templatePreviewTrigger || '';
                const tpl = tabTemplates.find((item) => String(item.id || '') === templateId);
                const pattern = superPatterns.find((item) => String(item.id || '') === templateId);
                const target = tpl || pattern;
                if (!target) return;
                this.openTemplatePreview(target, { scope: pattern ? 'super' : 'tab', parentModal: modal });
                return;
            }
            const superBtn = (e.target as HTMLElement).closest('[data-super-template-id]') as HTMLElement | null;
            if (!superBtn) return;
            const templateId = superBtn.dataset.superTemplateId || '';
            const pattern = superPatterns.find((item) => String(item.id || '') === templateId);
            if (!pattern || !pattern.layouts || typeof pattern.layouts !== 'object') return;
            this.applySuperPattern(pattern);
            this.teardownTemplatePreviews();
            modal.remove();
        });
        if (searchEl) {
            searchEl.addEventListener('input', () => {
                state.keyword = searchEl.value || '';
                renderResults();
                renderFeatured();
                if (searchClearEl) searchClearEl.hidden = !(state.keyword || '').trim();
            });
        }
        if (searchClearEl) {
            searchClearEl.addEventListener('click', () => {
                state.keyword = '';
                if (searchEl) searchEl.value = '';
                searchClearEl.hidden = true;
                renderAll();
            });
        }
        if (sortEl) {
            sortEl.addEventListener('change', () => {
                state.sort = sortEl.value || 'recommended';
                renderAll();
            });
        }

        renderAll();
        document.body.appendChild(modal);
        } catch (err) {
            console.error('Template modal failed:', err);
            if (typeof window.__ldShowToast === 'function') {
                window.__ldShowToast('テンプレート一覧の表示に失敗しました。ページを再読み込みしてください。', true);
            } else {
                alert('テンプレート一覧の表示に失敗しました。');
            }
        }
    },

    renderTabTemplateList() {
        if (!this.els.tabTemplateList) return;
        try {
        const templates = this.getTemplatesForTab(this.state.activeTab);
        const MAX_SIDEBAR_TEMPLATES = 10;
        const visibleTemplates = templates.slice(0, MAX_SIDEBAR_TEMPLATES);
        const hiddenCount = Math.max(0, templates.length - visibleTemplates.length);
        const superCount = (this.templates || []).filter((t) => t && t.scope === 'all_tabs').length;
        const superBtn = superCount > 0
            ? '<button type="button" class="ld-tab-template-super-btn" data-open-all-templates><i class="fas fa-layer-group"></i> 全画面パターン (' + superCount + ')</button>'
            : '';

        if (templates.length === 0) {
            this.els.tabTemplateList.innerHTML =
                (superBtn ? '<div class="ld-tab-templates__head">' + superBtn + '</div>' : '') +
                '<div class="ld-tab-template-empty">このタブ用テンプレートがありません。<br>上部の「テンプレート」から全画面パターンを選べます。</div>';
            return;
        }
        const grouped = {};
        visibleTemplates.forEach((tpl) => {
            const key = tpl.category || 'other';
            if (!grouped[key]) grouped[key] = [];
            grouped[key].push(tpl);
        });
        const html = Object.keys(grouped).sort().map((key) => {
            const cards = grouped[key].map((tpl) => {
                const count = Array.isArray(tpl.blocks) ? tpl.blocks.length : 0;
                const preview = this.renderTabTemplateMiniPreview(tpl);
                const support = '<span class="ld-tab-template-card__device">' + this.esc(this.getPrimaryDeviceLabel(tpl)) + '</span>';
                const isApplied = String(this.state.lastAppliedTemplateId || '') === String(tpl.id || '');
                const appliedBadge = isApplied
                    ? '<div class="ld-tab-template-card__applied-badge"><i class="fas fa-check-circle"></i> 適用済み</div>'
                    : '';
                return (
                    '<div class="ld-tab-template-card' + (isApplied ? ' is-applied' : '') + '">' +
                        appliedBadge +
                        '<div class="ld-tab-template-card__name">' + this.esc(tpl.name || 'テンプレート') + '</div>' +
                        '<div class="ld-tab-template-card__meta">' + this.esc(tpl.description || 'すぐ使えるテンプレート') + '</div>' +
                        preview +
                        '<div class="ld-tab-template-card__footer">' +
                            '<span class="ld-tab-template-card__count">要素 ' + count + '</span>' +
                            '<div class="ld-tab-template-card__devices">' + support + '</div>' +
                        '</div>' +
                        '<div class="ld-tab-template-card__actions">' +
                            '<button type="button" class="ld-btn ld-btn--outline ld-btn--xs ld-btn--block" data-tab-template-preview-id="' + this.esc(tpl.id || '') + '"><i class="fas fa-mobile-alt"></i> 実画面</button>' +
                            '<button type="button" class="ld-btn ld-btn--primary ld-btn--xs ld-btn--block" data-tab-template-id="' + this.esc(tpl.id || '') + '"><i class="fas fa-check"></i> 適用</button>' +
                        '</div>' +
                    '</div>'
                );
            }).join('');
            return '<div class="ld-tab-template-group"><div class="ld-tab-template-group__title">' + this.esc(this.getTemplateCategoryLabel(key)) + '</div>' + cards + '</div>';
        }).join('');
        const moreBtn = hiddenCount > 0
            ? '<div class="ld-tab-template-more"><button type="button" class="ld-btn ld-btn--ghost ld-btn--block" data-open-all-templates><i class="fas fa-th-large"></i> ほか ' + hiddenCount + ' 件のテンプレートを見る</button></div>'
            : '';
        this.els.tabTemplateList.innerHTML =
            (superBtn ? '<div class="ld-tab-templates__head">' + superBtn + '</div>' : '') + html + moreBtn;
        } catch (err) {
            console.warn('renderTabTemplateList failed', err);
            this.els.tabTemplateList.innerHTML = '<div class="ld-tab-template-empty">テンプレート一覧の表示に失敗しました。<br>上部の「テンプレート」ボタンから選べます。</div>';
        }
    },

    renderTabTemplateMiniPreview(tpl) {
        try {
            return this.renderStaticTemplateMiniPreview(tpl);
        } catch (err) {
            console.warn('Template mini preview failed', err);
            return '<div class="ld-tab-template-card__preview"><div class="ld-tab-template-card__preview-fallback">プレビュー</div></div>';
        }
    },

    getTemplatesForTab(tabKey) {
        return (this.templates || []).filter((tpl) => {
            if (!tpl || !Array.isArray(tpl.blocks)) return false;
            if (!Array.isArray(tpl.target_tabs) || tpl.target_tabs.length === 0) return true;
            return tpl.target_tabs.includes(tabKey);
        });
    },

    applyTabTemplate(tpl, options) {
        options = options || {};
        const blockCount = Array.isArray(tpl.blocks) ? tpl.blocks.length : 0;
        this.state.layouts[this.state.activeTab] = tpl.blocks.map(item => this.normalizeBlock(item));
        if (tpl.tab_settings && typeof tpl.tab_settings === 'object') {
            this.state.tabSettings[this.state.activeTab] = { ...this.state.tabSettings[this.state.activeTab], ...tpl.tab_settings };
        }
        if (tpl.theme && typeof tpl.theme === 'object') {
            this.state.globalSettings = Object.assign({}, this.getGlobalSettings(), tpl.theme, { theme_preset: tpl.theme.theme_preset || 'custom' });
            this.syncGlobalDesignControls();
            this.applyGlobalDesignPreview();
        } else if (typeof tpl.theme === 'string' && tpl.theme !== '') {
            this.applyDesignPreset(tpl.theme);
        }
        if (typeof tpl.custom_css === 'string' && this.els.customCssEditor) {
            this.els.customCssEditor.value = tpl.custom_css;
            this.applyCustomCssPreview();
        }
        this.state.selectedId = null;
        this.state.lastAppliedTemplateId = tpl.id || '';
        this.state.lastAppliedTemplateName = tpl.name || 'テンプレート';
        this.state.lastAppliedScope = 'tab';
        this.pushHistory();
        this.render();
        this.showApplySuccessFeedback(tpl, { scope: 'tab', blockCount: blockCount, fromModal: !!options.fromModal });
    },

    showApplySuccessFeedback(tpl, meta) {
        meta = meta || {};
        const tabLabel = this.TAB_LABELS[this.state.activeTab] || this.state.activeTab;
        const name = tpl.name || 'テンプレート';
        const blockCount = meta.blockCount != null ? meta.blockCount : (this.getBlocks() || []).length;
        const scope = meta.scope || 'tab';

        if (typeof window.__ldShowToast === 'function') {
            const msg = scope === 'super'
                ? '✓ 全画面パターン「' + name + '」を適用しました（全タブのレイアウトを更新）'
                : '✓ 「' + name + '」を「' + tabLabel + '」に適用しました（' + blockCount + 'ブロック）';
            window.__ldShowToast(msg, false, 5200);
        }

        const root = document.querySelector('.ld');
        if (root) {
            root.classList.remove('ld-screens-open', 'ld-sidebar-open');
            const bd = root.querySelector('.ld-drawer-backdrop') as HTMLElement | null;
            if (bd) bd.hidden = true;
        }

        const frame = this.els.phoneFrame || document.querySelector('.ld-phone__frame');
        if (frame) {
            frame.classList.remove('ld-phone--just-applied');
            void frame.offsetWidth;
            frame.classList.add('ld-phone--just-applied');
            setTimeout(() => frame.classList.remove('ld-phone--just-applied'), 2200);
        }

        const content = this.els.phoneContent;
        if (content) {
            const existing = content.querySelector('.ld-apply-banner');
            if (existing) existing.remove();
            const banner = document.createElement('div');
            banner.className = 'ld-apply-banner';
            banner.innerHTML = '<i class="fas fa-check-circle" aria-hidden="true"></i><span>テンプレート適用済み: <strong>' + this.esc(name) + '</strong>（' + this.esc(tabLabel) + '）</span>';
            content.insertBefore(banner, content.firstChild);
            requestAnimationFrame(() => banner.classList.add('is-visible'));
            setTimeout(() => {
                banner.classList.add('is-hiding');
                setTimeout(() => banner.remove(), 420);
            }, 4500);
        }

        if (this.els.tabTemplateList) {
            this.renderTabTemplateList();
        }
    },

    notifyTemplateApplied(name) {
        // 後方互換: applyTabTemplate / applySuperPattern から showApplySuccessFeedback を使用
        if (typeof window.__ldShowToast === 'function') {
            window.__ldShowToast('テンプレート「' + name + '」を適用しました', false, 4000);
        }
    },

    openTabTemplatePreview(tpl) {
        this.openTemplatePreview(tpl, { scope: 'tab' });
    },

    getTemplateThemeAccent(tpl) {
        const theme = (tpl && tpl.theme && typeof tpl.theme === 'object') ? tpl.theme : {};
        const preset = theme.theme_preset ? this.findThemePreset(theme.theme_preset) : null;
        return theme.accent_color || (preset ? preset.accent_color : '#0d9488');
    },

    getTemplateBlocksForTab(tpl, scope, tabKey) {
        if (scope === 'super' && tpl.layouts && typeof tpl.layouts === 'object') {
            return Array.isArray(tpl.layouts[tabKey]) ? tpl.layouts[tabKey] : [];
        }
        return Array.isArray(tpl.blocks) ? tpl.blocks : [];
    },

    getBlockTypeMeta(type) {
        const def = (this.BLOCK_TYPES || []).find((bt) => bt.type === type);
        return def || { type: type || 'text', label: type || 'ブロック', icon: 'fa-cube' };
    },

    renderTemplateBlockChips(blocks) {
        if (!Array.isArray(blocks) || blocks.length === 0) return '';
        return blocks.slice(0, 8).map((block) => {
            const meta = this.getBlockTypeMeta(block.type);
            return '<span class="ld-template-card__block-chip"><i class="fas ' + this.esc(meta.icon) + '"></i>' + this.esc(meta.label) + '</span>';
        }).join('');
    },

    renderPreviewBlockListHtml(blocks) {
        if (!Array.isArray(blocks) || blocks.length === 0) {
            return '<div class="ld-template-preview-modal__block-item"><i class="fas fa-info-circle"></i><span>ブロックなし</span></div>';
        }
        return blocks.map((block, i) => {
            const meta = this.getBlockTypeMeta(block.type);
            let detail = '';
            if (block.title) detail = block.title;
            else if (block.text) detail = String(block.text).slice(0, 24);
            else if (block.greeting) detail = block.greeting;
            const suffix = detail ? ' — ' + this.esc(detail) : '';
            return '<div class="ld-template-preview-modal__block-item"><i class="fas ' + this.esc(meta.icon) + '"></i><span>' + (i + 1) + '. ' + this.esc(meta.label) + suffix + '</span></div>';
        }).join('');
    },

    fillPreviewPhoneContent(contentEl, blocks, ctx) {
        return window.LiffDesignerModules.templatePreview.fillLivePreviewContent.call(this, contentEl, blocks, ctx);
    },

    openTemplatePreview(tpl, options) {
        try {
        options = options || {};
        const scope = options.scope || (tpl.scope === 'all_tabs' ? 'super' : 'tab');
        const previewTabs = this.getTemplatePreviewTabs(tpl, scope);
        const previewState = { tab: options.previewTab || this.state.activeTab };
        if (previewTabs.indexOf(previewState.tab) < 0) previewState.tab = previewTabs[0];

        const ov = document.createElement('div');
        ov.className = 'ld-preview-overlay';
        const tabBarHtml = scope === 'super' && previewTabs.length > 1
            ? '<div class="ld-template-preview-modal__tab-bar" data-preview-tab-bar></div>'
            : '';

        ov.innerHTML =
            '<div class="ld-template-preview-modal">' +
                '<div class="ld-template-preview-modal__head">' +
                    '<div>' +
                        '<div class="ld-template-preview-modal__title">' + this.esc(tpl.name || 'テンプレート') + '</div>' +
                        '<div class="ld-template-preview-modal__meta">' + this.esc(tpl.description || '適用前の画面イメージ — 実際のブロックで表示しています') + '</div>' +
                    '</div>' +
                    '<button type="button" class="ld-btn ld-btn--ghost" data-close-template-preview aria-label="閉じる"><i class="fas fa-times"></i></button>' +
                '</div>' +
                '<div class="ld-template-preview-modal__body">' +
                    '<div class="ld-template-preview-phone">' +
                        '<div data-template-preview-phone></div>' +
                    '</div>' +
                    '<div class="ld-template-preview-modal__sidebar">' +
                        '<div data-preview-theme-meta></div>' +
                        tabBarHtml +
                        '<div class="ld-template-preview-modal__sidebar-title">含まれるブロック</div>' +
                        '<div class="ld-template-preview-modal__block-list" data-preview-block-list></div>' +
                        '<div class="ld-template-preview-modal__note"><i class="fas fa-hand-pointer"></i> 下部タブで画面を切り替え、ブロック一覧をタップすると該当箇所へスクロールします。</div>' +
                    '</div>' +
                    '<div class="ld-template-preview-modal__actions">' +
                        '<button type="button" class="ld-btn ld-btn--outline" data-close-template-preview>閉じる</button>' +
                        '<div><button type="button" class="ld-btn ld-btn--primary ld-btn--block" data-apply-template-preview><i class="fas fa-check"></i> このテンプレートを適用する</button>' +
                        '<div class="ld-template-preview-modal__apply-hint">適用すると中央のプレビュー画面に反映されます</div></div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        const phoneHost = ov.querySelector('[data-template-preview-phone]');
        const blockList = ov.querySelector('[data-preview-block-list]');
        const themeMeta = ov.querySelector('[data-preview-theme-meta]');
        const tabBar = ov.querySelector('[data-preview-tab-bar]');

        const renderPreview = () => {
            try {
            const tabKey = previewState.tab;
            const blocks = this.getTemplateBlocksForTab(tpl, scope, tabKey);

            phoneHost.innerHTML = '';
            const live = typeof this.safeCreateLivePhonePreview === 'function'
                ? this.safeCreateLivePhonePreview(tpl, scope, tabKey, { interactive: true, showNav: true })
                : this.createLivePhonePreview(tpl, scope, tabKey, { interactive: true, showNav: true });
            phoneHost.appendChild(live.root);

            if (themeMeta) themeMeta.innerHTML = this.renderTemplateThemeMeta(tpl);

            if (blockList && live.content) {
                blockList.innerHTML = this.renderPreviewBlockListHtml(blocks);
                blockList.querySelectorAll('.ld-template-preview-modal__block-item').forEach((item, i) => {
                    item.addEventListener('click', () => {
                        const target = live.content.querySelectorAll('.ld-block')[i];
                        if (!target) return;
                        blockList.querySelectorAll('.ld-template-preview-modal__block-item').forEach((el) => el.classList.remove('is-highlight'));
                        item.classList.add('is-highlight');
                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        target.classList.add('ld-block--preview-flash');
                        setTimeout(() => target.classList.remove('ld-block--preview-flash'), 900);
                    });
                });
            }

            if (live.nav) {
                live.nav.querySelectorAll('[data-preview-tab]').forEach((btn: any) => {
                    btn.addEventListener('click', () => {
                        previewState.tab = btn.dataset.previewTab || previewState.tab;
                        if (tabBar) {
                            tabBar.querySelectorAll('[data-preview-tab]').forEach((chip: any) => {
                                chip.classList.toggle('is-active', chip.dataset.previewTab === previewState.tab);
                            });
                        }
                        renderPreview();
                    });
                });
            }
            } catch (previewErr) {
                console.warn('template full preview render failed', previewErr);
                if (phoneHost) {
                    phoneHost.innerHTML = '<div class="ld-template-preview-fallback">' + this.renderStaticPhonePreviewHtml(tpl, scope, previewState.tab, { limit: 8 }) + '</div>';
                }
            }
        };

        if (tabBar) {
            tabBar.innerHTML = previewTabs.map((key) => {
                const active = key === previewState.tab ? ' is-active' : '';
                const count = scope === 'super'
                    ? ((tpl.layouts && tpl.layouts[key]) || []).length
                    : (Array.isArray(tpl.blocks) ? tpl.blocks.length : 0);
                return '<button type="button" class="ld-template-preview-modal__tab-btn' + active + '" data-preview-tab="' + this.esc(key) + '">' + this.esc(this.TAB_LABELS[key] || key) + ' (' + count + ')</button>';
            }).join('');
        }

        const closePreview = () => {
            ov.remove();
            document.removeEventListener('keydown', onKey);
        };
        const onKey = (e) => {
            if (e.key === 'Escape') closePreview();
        };
        document.addEventListener('keydown', onKey);

        ov.addEventListener('click', (e) => {
            if (e.target === ov || (e.target as HTMLElement).closest('[data-close-template-preview]')) {
                closePreview();
                return;
            }
            const tabBtn = (e.target as HTMLElement).closest('[data-preview-tab]') as HTMLElement | null;
            if (tabBtn && tabBar && tabBar.contains(tabBtn)) {
                previewState.tab = tabBtn.dataset.previewTab || previewState.tab;
                tabBar.querySelectorAll('[data-preview-tab]').forEach((btn: any) => {
                    btn.classList.toggle('is-active', btn.dataset.previewTab === previewState.tab);
                });
                renderPreview();
                return;
            }
            const applyBtn = (e.target as HTMLElement).closest('[data-apply-template-preview]') as HTMLElement | null;
            if (!applyBtn) return;
            if (scope === 'super') {
                this.applySuperPattern(tpl, true);
            } else {
                this.applyTabTemplate(tpl);
            }
            if (options.parentModal) options.parentModal.remove();
            closePreview();
        });

        renderPreview();
        document.body.appendChild(ov);
        requestAnimationFrame(() => {
            const firstFocus = ov.querySelector('[data-close-template-preview]') as HTMLElement | null;
            if (firstFocus) firstFocus.focus();
        });
        } catch (err) {
            console.error('openTemplatePreview failed', err);
            if (typeof window.__ldShowToast === 'function') {
                window.__ldShowToast('プレビューの表示に失敗しました。テンプレートは「適用」から選べます。', true);
            }
        }
    },

    renderTemplateVisualPreview(tpl, scope) {
        return this.renderVisualPreviewMount(tpl, scope);
    },

    renderTemplateRichDemoBlock(block, accent) {
        try {
        const t = (block && block.type) ? block.type : 'text';
        const d = (block && block.design) ? block.design : '';
        const a = accent || '#4f46e5';
        const meta = this.getBlockTypeMeta(t);
        const style = ' style="--demo-accent:' + a + '"';

        if (t === 'member_summary') {
            const hello = block.greeting ? this.esc(block.greeting) : 'こんにちは、太郎 さん';
            return '<div class="ld-rich-demo ld-rich-demo--member' + (d ? ' ld-demo-block--ms-' + d : '') + '"' + style + '>' +
                '<div class="ld-rich-demo--member__left"><div class="ld-rich-demo--member__hello">' + hello + '</div></div>' +
                '<div class="ld-rich-demo--member__pt">1,280<small>pt</small></div></div>';
        }
        if (t === 'hero') {
            const title = this.esc(block.title || 'Welcome');
            const sub = this.esc(block.subtitle || block.text || '');
            return '<div class="ld-rich-demo ld-rich-demo--hero' + (d ? ' ld-demo-block--hero-' + d : '') + '"' + style + '>' +
                '<div class="ld-rich-demo--hero__title">' + title + '</div>' +
                (sub ? '<div class="ld-rich-demo--hero__sub">' + sub + '</div>' : '') +
                '</div>';
        }
        if (t === 'quick_actions') {
            const actions = Array.isArray(block.actions) ? block.actions.slice(0, 4) : [];
            const items = actions.map((act) => {
                return '<div class="ld-rich-demo--qa__item"><i class="fas ' + this.esc(act.icon || 'fa-circle') + '"></i><span>' + this.esc(act.label || '') + '</span></div>';
            }).join('');
            return '<div class="ld-rich-demo ld-rich-demo--qa' + (d ? ' ld-demo-block--qa-' + d : '') + '"' + style + '>' + items + '</div>';
        }
        if (t === 'collection') {
            const items = Array.isArray(block.items) ? block.items.slice(0, 2) : [{ title: 'Item' }, { title: 'Item' }];
            const cards = items.map((item) => {
                return '<div class="ld-rich-demo--collection__item"><div class="ld-rich-demo--collection__img"></div><div class="ld-rich-demo--collection__title">' + this.esc(item.title || '') + '</div></div>';
            }).join('');
            return '<div class="ld-rich-demo ld-rich-demo--collection"' + style + '>' + cards + '</div>';
        }
        if (t === 'banner') {
            return '<div class="ld-rich-demo ld-rich-demo--banner' + (d ? ' ld-demo-block--banner-' + d : '') + '"' + style + '>' + this.esc(block.text || 'お知らせ') + '</div>';
        }
        if (t === 'button') {
            return '<div class="ld-rich-demo ld-rich-demo--btn' + (d && d !== 'cta' ? ' ld-demo-block--btn-' + d : '') + '"' + style + '>' + this.esc(block.text || 'ボタン') + '</div>';
        }
        if (t === 'card') {
            return '<div class="ld-rich-demo ld-rich-demo--card' + (d && d !== 'editorial' ? ' ld-demo-block--card-' + d : '') + '">' +
                '<div class="ld-rich-demo--card__img"></div><div class="ld-rich-demo--card__body">' + this.esc(block.title || 'カード') + '</div></div>';
        }
        if (t === 'slider') {
            return '<div class="ld-demo-block ld-demo-block--slider"' + style + '><span class="ld-demo-block__slider-cap"></span></div>';
        }
        const label = block.title || block.text || meta.label;
        return '<div class="ld-rich-demo ld-rich-demo--generic"' + style + '><i class="fas ' + this.esc(meta.icon) + '"></i><span>' + this.esc(String(label).slice(0, 28)) + '</span></div>';
        } catch (err) {
            console.warn('renderTemplateRichDemoBlock failed', block && block.type, err);
            return '<div class="ld-rich-demo ld-rich-demo--generic"><span>ブロック</span></div>';
        }
    },

    renderTemplateDemoFallback(accent) {
        return (
            '<div class="ld-demo-block ld-demo-block--member"></div>' +
            '<div class="ld-demo-block ld-demo-block--hero" style="--demo-accent:' + accent + '"></div>' +
            '<div class="ld-demo-block ld-demo-block--row"></div>' +
            '<div class="ld-demo-block ld-demo-block--row ld-demo-block--short"></div>'
        );
    },

    renderTemplateDemoBlock(block, accent) {
        const t = (block && block.type) ? block.type : '';
        const d = (block && block.design) ? block.design : '';
        const a = accent || '#4f46e5';
        if (t === 'member_summary') {
            const cls = d ? ' ld-demo-block--ms-' + d : '';
            return '<div class="ld-demo-block ld-demo-block--member' + cls + '" style="--demo-accent:' + a + '"><span class="ld-demo-block__chip"></span><span class="ld-demo-block__line ld-demo-block__line--wide"></span></div>';
        }
        if (t === 'hero') {
            const cls = d ? ' ld-demo-block--hero-' + d : '';
            return '<div class="ld-demo-block ld-demo-block--hero' + cls + '" style="--demo-accent:' + a + '"><span class="ld-demo-block__hero-title"></span><span class="ld-demo-block__hero-sub"></span></div>';
        }
        if (t === 'quick_actions') {
            const cls = d ? ' ld-demo-block--qa-' + d : '';
            return '<div class="ld-demo-block ld-demo-block--actions' + cls + '"><span></span><span></span><span></span><span></span></div>';
        }
        if (t === 'slider') {
            return '<div class="ld-demo-block ld-demo-block--slider" style="--demo-accent:' + a + '"><span class="ld-demo-block__slider-cap"></span></div>';
        }
        if (t === 'collection') {
            return '<div class="ld-demo-block ld-demo-block--collection"><span></span><span></span><span></span></div>';
        }
        if (t === 'banner') {
            const cls = d ? ' ld-demo-block--banner-' + d : '';
            return '<div class="ld-demo-block ld-demo-block--banner' + cls + '" style="--demo-accent:' + a + '"></div>';
        }
        if (t === 'button') {
            const cls = d && d !== 'cta' ? ' ld-demo-block--btn-' + d : '';
            return '<div class="ld-demo-block ld-demo-block--cta' + cls + '" style="--demo-accent:' + a + '"></div>';
        }
        if (t === 'card') {
            const cls = d && d !== 'editorial' ? ' ld-demo-block--card-' + d : '';
            return '<div class="ld-demo-block ld-demo-block--card' + cls + '"><span class="ld-demo-block__card-img"></span><span class="ld-demo-block__line"></span></div>';
        }
        if (t === 'heading') {
            return '<div class="ld-demo-block ld-demo-block--heading"></div>';
        }
        if (t === 'data') {
            return '<div class="ld-demo-block ld-demo-block--data"><span></span><span></span></div>';
        }
        return '<div class="ld-demo-block ld-demo-block--row"></div>';
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
            native: 'ネイティブアプリ風',
            luxury: 'ラグジュアリー',
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

        // テーマ一括適用（プリセットID または 個別テーマ設定）
        if (typeof pattern.theme === 'string' && pattern.theme !== '') {
            this.applyDesignPreset(pattern.theme);
        } else if (pattern.theme && typeof pattern.theme === 'object') {
            this.state.globalSettings = Object.assign({}, this.getGlobalSettings(), pattern.theme, { theme_preset: pattern.theme.theme_preset || 'custom' });
            this.syncGlobalDesignControls();
            this.applyGlobalDesignPreview();
        }

        // タブ構成（表示タブ・並び順・名前）一括適用
        if (Array.isArray(pattern.menu) && pattern.menu.length > 0) {
            const byTab = {};
            pattern.menu.forEach((m) => { if (m && m.tab) byTab[m.tab] = m; });
            const ordered = [];
            pattern.menu.forEach((m) => {
                const item = this.getMenuItem(m && m.tab);
                if (!item) return;
                item.enabled = m.enabled !== false;
                if (m.label) item.label = m.label;
                if (m.icon) item.icon = m.icon;
                ordered.push(item);
            });
            // テンプレに含まれないタブは末尾で非表示に
            this.state.menuItems.forEach((item: any) => {
                if (!byTab[item.tab]) {
                    item.enabled = false;
                    ordered.push(item);
                }
            });
            ordered.forEach((item, i) => { item.order = i; });
            this.state.menuItems = ordered;
        }

        this.state.activeTab = 'home';
        this.state.selectedId = null;
        this.state.lastAppliedTemplateId = pattern.id || '';
        this.state.lastAppliedTemplateName = pattern.name || '全画面パターン';
        this.state.lastAppliedScope = 'super';
        this.pushHistory();
        this.render();
        const totalBlocks = Object.values(nextLayouts).reduce((n: number, rows: unknown) => n + (Array.isArray(rows) ? rows.length : 0), 0);
        this.showApplySuccessFeedback(pattern, { scope: 'super', blockCount: totalBlocks });
    },

    mountTemplatePreviews(container, templates, scope) {
        return window.LiffDesignerModules.templatePreview.mountTemplatePreviews.call(this, container, templates, scope);
    },

    scheduleTemplatePreviews(container, templates, scope) {
        return window.LiffDesignerModules.templatePreview.scheduleTemplatePreviews.call(this, container, templates, scope);
    },

    teardownTemplatePreviews() {
        return window.LiffDesignerModules.templatePreview.teardownTemplatePreviews.call(this);
    },

    renderStaticTemplateMiniPreview(tpl) {
        return window.LiffDesignerModules.templatePreview.renderStaticTemplateMiniPreview.call(this, tpl);
    },

    renderStaticPhonePreviewHtml(tpl, scope, tabKey, options) {
        return window.LiffDesignerModules.templatePreview.renderStaticPhonePreviewHtml.call(this, tpl, scope, tabKey, options);
    },

    safeCreateLivePhonePreview(tpl, scope, tabKey, options) {
        return window.LiffDesignerModules.templatePreview.safeCreateLivePhonePreview.call(this, tpl, scope, tabKey, options);
    },

    createLivePhonePreview(tpl, scope, tabKey, options) {
        return window.LiffDesignerModules.templatePreview.createLivePhonePreview.call(this, tpl, scope, tabKey, options);
    },

    renderVisualPreviewMount(tpl, scope) {
        return window.LiffDesignerModules.templatePreview.renderVisualPreviewMount.call(this, tpl, scope);
    },

    renderTemplateThemeMeta(tpl) {
        return window.LiffDesignerModules.templatePreview.renderTemplateThemeMeta.call(this, tpl);
    },

    getTemplatePreviewTabs(tpl, scope) {
        return window.LiffDesignerModules.templatePreview.getTemplatePreviewTabs.call(this, tpl, scope);
    },
};


ensureLiffDesignerModules().templateManager = templateManager;
