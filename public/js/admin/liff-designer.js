/**
 * LIFF Designer — Block-based Page Builder
 */
class LiffDesigner {
    constructor(config) {
        this.TAB_LABELS = {
            home: 'ホーム', products: '商品', orders: '注文', stamps: 'スタンプ',
            coupons: 'クーポン', queue: '順番待ち', reservations: '予約', member: '会員',
            gallery: 'ギャラリー', announcements: 'お知らせ'
        };

        this.TAB_ICONS = {
            home: 'fa-home', products: 'fa-utensils', orders: 'fa-receipt',
            stamps: 'fa-stamp', coupons: 'fa-ticket-alt', queue: 'fa-users',
            reservations: 'fa-calendar-alt', member: 'fa-user',
            gallery: 'fa-images', announcements: 'fa-bullhorn'
        };
        this.DEVICE_PROFILES = {
            line: { label: 'LINE', frameClass: 'is-line' },
            ios: { label: 'iOS', frameClass: 'is-ios' },
            android: { label: 'Android', frameClass: 'is-android' },
        };

        this.BLOCK_TYPES = [
            { type: 'hero', label: 'ヒーロー', icon: 'fa-star', desc: '大きな画像+テキスト', category: 'layout' },
            { type: 'heading', label: '見出し', icon: 'fa-heading', desc: 'セクション見出し', category: 'basic' },
            { type: 'text', label: 'テキスト', icon: 'fa-paragraph', desc: '説明文・本文', category: 'basic' },
            { type: 'image', label: '画像', icon: 'fa-image', desc: '画像を表示', category: 'basic' },
            { type: 'button', label: 'ボタン', icon: 'fa-mouse-pointer', desc: 'アクションボタン', category: 'basic' },
            { type: 'card', label: 'カード', icon: 'fa-id-card', desc: '画像+テキスト', category: 'layout' },
            { type: 'collection', label: 'コレクション', icon: 'fa-th-large', desc: '横スクロール一覧', category: 'layout' },
            { type: 'slider', label: 'スライダー', icon: 'fa-images', desc: '画像カルーセル', category: 'layout' },
            { type: 'icon_text', label: 'アイコン+文字', icon: 'fa-list-ul', desc: 'アイコン付きテキスト行', category: 'layout' },
            { type: 'list', label: 'リスト', icon: 'fa-list-ol', desc: '箇条書きリスト', category: 'layout' },
            { type: 'banner', label: 'バナー', icon: 'fa-bullhorn', desc: 'お知らせバナー', category: 'layout' },
            { type: 'spacer', label: '余白', icon: 'fa-arrows-alt-v', desc: 'スペースを追加', category: 'basic' },
            { type: 'divider', label: '区切り線', icon: 'fa-minus', desc: '区切り線を追加', category: 'basic' },
            { type: 'member_summary', label: '会員サマリー', icon: 'fa-id-badge', desc: 'ポイント・ランク表示', category: 'data' },
            { type: 'quick_actions', label: 'クイックアクション', icon: 'fa-grip', desc: 'タブへのショートカット', category: 'data' },
            { type: 'data', label: 'データ', icon: 'fa-database', desc: '動的データ表示', category: 'data' },
        ];

        this.DATA_SOURCES = [
            { value: 'products', label: '商品一覧' },
            { value: 'orders', label: '注文履歴' },
            { value: 'coupons', label: 'クーポン一覧' },
            { value: 'stamps', label: 'スタンプカード' },
            { value: 'announcements', label: 'お知らせ' },
            { value: 'queue', label: '順番待ち' },
            { value: 'reservations', label: '予約一覧' },
            { value: 'gallery', label: 'ギャラリー' },
            { value: 'member_card', label: '会員カード' },
        ];

        this.ALIGN_OPTIONS = [
            { value: 'left', label: '左' },
            { value: 'center', label: '中央' },
            { value: 'right', label: '右' },
        ];

        this.TAB_FEATURE_MAP = {
            home: 'feature_home_enabled',
            products: 'feature_orders_enabled',
            orders: 'feature_orders_enabled',
            stamps: 'feature_stamps_enabled',
            coupons: 'feature_coupons_enabled',
            queue: 'feature_queue_enabled',
            reservations: 'feature_reservations_enabled',
            member: 'feature_members_enabled',
            gallery: 'feature_gallery_enabled',
            announcements: 'feature_announcements_enabled',
        };

        this.featureFlags = config.featureFlags || {};
        this.templates = Array.isArray(config.templates) ? config.templates : [];
        this.themePresets = Array.isArray(config.themePresets) ? config.themePresets : [];
        this.fontStacks = config.fontStacks || {};
        this.surfaceTones = config.surfaceTones || {};
        this.previewNonce = Date.now();
        this.idCounter = 1;
        this.state = { layouts: {}, tabSettings: {}, globalSettings: {}, activeTab: 'home', selectedId: null };
        this.ui = {
            paletteSearch: '',
            paletteCategory: 'all',
            zoom: 100,
            previewDevice: 'line',
        };
        this.history = [];
        this.historyIndex = -1;
        this.maxHistory = 50;
        this.drag = { active: false, source: null, blockType: null, blockId: null, ghost: null, dropIndex: -1, startX: 0, startY: 0, scrollTimer: null, pendingStart: false, sourceElement: null };

        this.initState(config);
        this.cacheElements();
        this.render();
        this.bindEvents();
        this.bindDragSystem();
        this.pushHistory();
    }

    /* ========== Init ========== */

    initState(config) {
        const rawLayouts = config.layouts || {};
        const rawTabSettings = config.tabSettings || {};
        this.state.globalSettings = rawTabSettings._global || this.defaultGlobalSettings();
        Object.keys(this.TAB_LABELS).forEach(key => {
            this.state.tabSettings[key] = rawTabSettings[key] || { background_color: '#fafaf9' };
            this.state.layouts[key] = [];
        });
        Object.keys(rawLayouts).forEach(tab => {
            if (!Array.isArray(rawLayouts[tab])) return;
            this.state.layouts[tab] = rawLayouts[tab].map(item => this.normalizeBlock(item));
        });
    }

    defaultGlobalSettings() {
        return { content_padding: 18, section_gap: 16, radius_scale: 'soft', tab_style: 'default' };
    }

    getGlobalSettings() {
        return this.state.globalSettings || this.defaultGlobalSettings();
    }

    serializeTabSettings() {
        return Object.assign({}, this.state.tabSettings, { _global: this.getGlobalSettings() });
    }

    RADIUS_SCALES = {
        compact: { xs: '6px', sm: '10px', md: '12px', lg: '14px', xl: '18px' },
        default: { xs: '8px', sm: '12px', md: '16px', lg: '20px', xl: '24px' },
        soft: { xs: '10px', sm: '14px', md: '18px', lg: '22px', xl: '28px' },
        round: { xs: '12px', sm: '18px', md: '24px', lg: '28px', xl: '32px' },
    };

    findThemePreset(presetId) {
        return (this.themePresets || []).find(p => p && p.id === presetId) || null;
    }

    hexToRgbString(hex) {
        if (typeof hex !== 'string') return '0, 0, 0';
        const m = hex.replace('#', '');
        if (!/^[0-9a-fA-F]{6}$/.test(m)) return '0, 0, 0';
        return [parseInt(m.slice(0, 2), 16), parseInt(m.slice(2, 4), 16), parseInt(m.slice(4, 6), 16)].join(', ');
    }

    darkenHex(hex, amount) {
        const m = (hex || '').replace('#', '');
        if (!/^[0-9a-fA-F]{6}$/.test(m)) return hex;
        const ch = [0, 2, 4].map(i => {
            const v = Math.round(parseInt(m.slice(i, i + 2), 16) * (1 - amount));
            return Math.max(0, Math.min(255, v)).toString(16).padStart(2, '0');
        });
        return '#' + ch.join('');
    }

    /** _global からプレビューに当てるテーマ用 CSS 変数を解決 */
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
            Object.keys(tokens).forEach(k => { vars[k] = tokens[k]; });
        }
        return vars;
    }

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
        const themeDecl = Object.keys(themeVars).map(k => k + ': ' + themeVars[k] + ';').join(' ');
        style.textContent = [
            '.ld-phone__frame.is-line { ' + themeDecl + ' }',
            '.ld-phone__content { --content-padding: ' + (g.content_padding || 18) + 'px; --section-gap: ' + (g.section_gap || 16) + 'px; padding: ' + (g.content_padding || 18) + 'px !important; gap: ' + (g.section_gap || 16) + 'px !important; font-family: var(--font-base, inherit); }',
            '.ld-phone__content .ld-block { border-radius: ' + scale.md + '; }',
            '.ld-phone__nav { ' + (g.tab_style === 'floating' ? 'margin: 0 10px 10px; border-radius: 18px; border: 1px solid rgba(28,25,23,0.08);' : '') + ' }',
        ].join('\n');
        const nav = this.els.phoneNav;
        if (nav) {
            nav.classList.remove('is-pill', 'is-floating');
            if (g.tab_style === 'pill') nav.classList.add('is-pill');
            if (g.tab_style === 'floating') nav.classList.add('is-floating');
        }
    }

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
        });
        this.ui.activePreset = preset.id;
        this.syncGlobalDesignControls();
        this.applyGlobalDesignPreview();
        this.renderPhoneContent();
    }

    syncGlobalDesignControls() {
        const g = this.getGlobalSettings();
        if (this.els.globalSectionGap) {
            this.els.globalSectionGap.value = String(g.section_gap || 16);
            if (this.els.globalSectionGapVal) this.els.globalSectionGapVal.textContent = String(g.section_gap || 16);
        }
        if (this.els.globalRadiusScale) this.els.globalRadiusScale.value = g.radius_scale || 'soft';
        if (this.els.globalTabStyle) this.els.globalTabStyle.value = g.tab_style || 'default';
        if (this.els.globalFontFamily) this.els.globalFontFamily.value = g.font_family || '';
        if (this.els.globalSurfaceTone) this.els.globalSurfaceTone.value = g.surface_tone || '';
        const accent = g.accent_color || (this.findThemePreset(g.theme_preset) || {}).accent_color || '#1c1917';
        if (this.els.globalAccentColor && /^#[0-9a-fA-F]{6}$/.test(accent)) this.els.globalAccentColor.value = accent;
        if (this.els.globalAccentColorHex) this.els.globalAccentColorHex.value = accent;
        if (this.els.screenContentPadding) {
            const pad = g.content_padding || 18;
            this.els.screenContentPadding.value = String(pad);
            if (this.els.screenContentPaddingVal) this.els.screenContentPaddingVal.textContent = String(pad);
        }
        const activeId = g.theme_preset || this.ui.activePreset;
        document.querySelectorAll('.ld-theme-card').forEach(btn => {
            btn.classList.toggle('is-active', btn.dataset.preset === activeId);
        });
    }

    normalizeBlock(item) {
        const id = item.id || this.nextId();
        let type = item.type || 'text';
        if (type === 'label') type = 'heading';
        if (type === 'gallery') type = 'data';
        if (type === 'form') type = 'text';
        const block = { id, type, border_radius: item.border_radius ?? 10, padding: item.padding ?? null, margin_bottom: item.margin_bottom ?? 0, text_align: item.text_align || 'left', background_color: item.background_color || '', text_color: item.text_color || '', opacity: item.opacity ?? 100, shadow: item.shadow ?? false };

        switch (type) {
            case 'hero':
                block.title = item.title || 'ようこそ！';
                block.subtitle = item.subtitle || item.text || 'Reggitへ';
                block.image_url = item.image_url || '';
                block.gradient_start = item.gradient_start || '#667eea';
                block.gradient_end = item.gradient_end || '#764ba2';
                block.text_align = item.text_align || 'center';
                break;
            case 'heading':
                block.text = item.text || item.title || '見出し';
                block.size = item.size || 'medium';
                block.text_color = item.text_color || '#1f2937';
                break;
            case 'text':
                block.text = item.text || item.body || 'テキスト';
                block.text_color = item.text_color || '#4b5563';
                block.font_size = item.font_size || 13;
                break;
            case 'image':
                block.image_url = item.image_url || 'https://picsum.photos/seed/img1/750/400';
                block.alt = item.alt || 'サンプル画像';
                block.fit = item.fit || 'cover';
                break;
            case 'button':
                block.text = item.text || item.button_label || 'ボタン';
                block.color = item.color || '#06C755';
                block.text_color = item.text_color || '#ffffff';
                block.action = item.action || '';
                block.full_width = item.full_width !== false;
                block.text_align = 'center';
                break;
            case 'card':
                block.title = item.title || '本日のおすすめ';
                block.description = item.description || 'シェフが厳選した季節の素材を使った一品です。';
                block.image_url = item.image_url || 'https://picsum.photos/seed/card1/600/300';
                block.image_height = item.image_height || 140;
                break;
            case 'collection':
                block.items = item.items || [
                    { title: 'おすすめ商品', image_url: 'https://picsum.photos/seed/col1/300/300', description: '人気No.1' },
                    { title: '季節限定', image_url: 'https://picsum.photos/seed/col2/300/300', description: '期間限定メニュー' },
                    { title: '新メニュー', image_url: 'https://picsum.photos/seed/col3/300/300', description: 'NEW' },
                    { title: 'セット割', image_url: 'https://picsum.photos/seed/col4/300/300', description: 'お得なセット' },
                ];
                block.layout = item.layout || 'scroll';
                block.columns = item.columns ?? 2;
                block.max_rows = item.max_rows ?? 0;
                block.item_width = item.item_width || 140;
                block.item_image_height = item.item_image_height || 100;
                block.gap = item.gap ?? 8;
                block.show_description = item.show_description !== false;
                break;
            case 'slider':
                block.slides = item.slides || [
                    { image_url: 'https://picsum.photos/seed/slide1/750/400', caption: 'キャンペーン実施中' },
                    { image_url: 'https://picsum.photos/seed/slide2/750/400', caption: '新メニュー登場' },
                    { image_url: 'https://picsum.photos/seed/slide3/750/400', caption: 'ポイント2倍デー' },
                ];
                block.slide_height = item.slide_height || 160;
                block.auto_play = item.auto_play !== false;
                block.show_dots = item.show_dots !== false;
                break;
            case 'icon_text':
                block.rows = item.rows || [
                    { icon: 'fa-check-circle', text: '特徴1', color: '#06C755' },
                    { icon: 'fa-check-circle', text: '特徴2', color: '#06C755' },
                    { icon: 'fa-check-circle', text: '特徴3', color: '#06C755' },
                ];
                break;
            case 'list':
                block.items = item.items || item.list_items || ['項目1', '項目2', '項目3'];
                block.style = item.style || 'bullet';
                block.text_color = item.text_color || '#374151';
                break;
            case 'banner':
                block.text = item.text || 'お知らせテキスト';
                block.color = item.color || '#3b82f6';
                block.text_color = item.text_color || '#ffffff';
                block.icon = item.icon || 'fa-info-circle';
                block.text_align = item.text_align || 'center';
                break;
            case 'spacer':
                block.height = item.height || 16;
                break;
            case 'divider':
                block.divider_color = item.divider_color || '#e5e7eb';
                block.divider_style = item.divider_style || 'solid';
                break;
            case 'data':
                block.source = item.source === 'member' ? 'member_card' : (item.source || 'products');
                break;
            case 'member_summary':
                block.greeting = item.greeting || '';
                break;
            case 'quick_actions':
                block.actions = Array.isArray(item.actions) && item.actions.length > 0 ? item.actions : [
                    { icon: 'fa-utensils', label: '注文', action: '#products' },
                    { icon: 'fa-ticket-alt', label: 'クーポン', action: '#coupons' },
                    { icon: 'fa-stamp', label: 'スタンプ', action: '#stamps' },
                    { icon: 'fa-calendar-check', label: '予約', action: '#reservations' },
                ];
                break;
            default:
                block.text = item.text || item.body || '';
        }
        return block;
    }

    nextId() { return 'b' + (this.idCounter++); }

    cacheElements() {
        this.els = {
            form: document.getElementById('liffDesignerForm'),
            screenList: document.getElementById('screenList'),
            phoneContent: document.getElementById('phoneContent'),
            phoneHeaderTitle: document.getElementById('phoneHeaderTitle'),
            phoneNav: document.getElementById('phoneNav'),
            phoneBody: document.getElementById('phoneBody'),
            blockPalette: document.getElementById('blockPalette'),
            blockSettings: document.getElementById('blockSettings'),
            blockSettingsSection: document.getElementById('blockSettingsSection'),
            addBlockSection: document.getElementById('addBlockSection'),
            closeSettings: document.getElementById('closeSettings'),
            screenBgColor: document.getElementById('screenBgColor'),
            screenBgColorHex: document.getElementById('screenBgColorHex'),
            screenBgImage: document.getElementById('screenBgImage'),
            layoutsJson: document.getElementById('layoutsJson'),
            tabSettingsJson: document.getElementById('tabSettingsJson'),
            customCssInput: document.getElementById('customCssInput'),
            customCssEditor: document.getElementById('customCssEditor'),
            cssSection: document.getElementById('cssSection'),
            toggleCssBtn: document.getElementById('toggleCssBtn'),
            undoBtn: document.getElementById('undoBtn'),
            redoBtn: document.getElementById('redoBtn'),
            previewBtn: document.getElementById('previewBtn'),
            templateBtn: document.getElementById('templateBtn'),
            importBtn: document.getElementById('importBtn'),
            exportBtn: document.getElementById('exportBtn'),
            importJsonFile: document.getElementById('importJsonFile'),
            paletteSearch: document.getElementById('paletteSearch'),
            paletteCategories: document.getElementById('paletteCategories'),
            activeTabLabel: document.getElementById('activeTabLabel'),
            zoomOutBtn: document.getElementById('zoomOutBtn'),
            zoomInBtn: document.getElementById('zoomInBtn'),
            zoomRange: document.getElementById('zoomRange'),
            zoomValue: document.getElementById('zoomValue'),
            phoneFrame: document.querySelector('.ld-phone__frame'),
            tabTemplateList: document.getElementById('tabTemplateList'),
            screenBgSize: document.getElementById('screenBgSize'),
            screenContentPadding: document.getElementById('screenContentPadding'),
            screenContentPaddingVal: document.getElementById('screenContentPaddingVal'),
            globalSectionGap: document.getElementById('globalSectionGap'),
            globalSectionGapVal: document.getElementById('globalSectionGapVal'),
            globalRadiusScale: document.getElementById('globalRadiusScale'),
            globalTabStyle: document.getElementById('globalTabStyle'),
            globalFontFamily: document.getElementById('globalFontFamily'),
            globalSurfaceTone: document.getElementById('globalSurfaceTone'),
            globalAccentColor: document.getElementById('globalAccentColor'),
            globalAccentColorHex: document.getElementById('globalAccentColorHex'),
            designPresets: document.getElementById('designPresets'),
        };
    }

    /* ========== State ========== */

    getBlocks() { return this.state.layouts[this.state.activeTab] || []; }
    getSettings() { return this.state.tabSettings[this.state.activeTab] || {}; }
    findBlock(id) { return this.getBlocks().find(b => b.id === id) || null; }

    /* ========== History ========== */

    pushHistory() {
        const snap = JSON.stringify(this.state.layouts);
        if (this.historyIndex >= 0 && this.history[this.historyIndex] === snap) return;
        this.history = this.history.slice(0, this.historyIndex + 1);
        this.history.push(snap);
        if (this.history.length > this.maxHistory) this.history.shift();
        this.historyIndex = this.history.length - 1;
        this.updateUndoRedo();
    }

    undo() { if (this.historyIndex <= 0) return; this.historyIndex--; this.state.layouts = JSON.parse(this.history[this.historyIndex]); this.state.selectedId = null; this.render(); this.updateUndoRedo(); }
    redo() { if (this.historyIndex >= this.history.length - 1) return; this.historyIndex++; this.state.layouts = JSON.parse(this.history[this.historyIndex]); this.state.selectedId = null; this.render(); this.updateUndoRedo(); }
    updateUndoRedo() { this.els.undoBtn.disabled = this.historyIndex <= 0; this.els.redoBtn.disabled = this.historyIndex >= this.history.length - 1; }

    /* ========== Rendering ========== */

    render() {
        this.renderScreenList();
        this.renderTabTemplateList();
        this.renderPhoneNav();
        this.renderPhoneContent();
        this.renderPaletteCategories();
        this.renderBlockPalette();
        this.renderBlockSettings();
        this.applyScreenSettings();
        this.applyPreviewDevice();
        this.applyZoom();
        this.applyCustomCssPreview();
    }

    renderScreenList() {
        const c = this.els.screenList; c.innerHTML = '';
        Object.keys(this.TAB_LABELS).forEach(key => {
            const active = this.state.activeTab === key;
            const isEnabled = this.isTabEnabled(key);
            const count = (this.state.layouts[key] || []).length;
            const el = document.createElement('div');
            el.className = 'ld-screen-item' + (active ? ' is-active' : '') + (isEnabled ? '' : ' is-disabled');
            const badge = isEnabled ? '' : '<span class="ld-screen-item__badge">OFF</span>';
            el.innerHTML = '<div class="ld-screen-item__icon"><i class="fas ' + (this.TAB_ICONS[key] || 'fa-circle') + '"></i></div><span class="ld-screen-item__label">' + this.esc(this.TAB_LABELS[key]) + '</span>' + badge + '<span class="ld-screen-item__count">' + count + '</span>';
            el.addEventListener('click', () => { this.state.activeTab = key; this.state.selectedId = null; this.render(); });
            c.appendChild(el);
        });
    }

    isTabEnabled(key) {
        const flagKey = this.TAB_FEATURE_MAP[key];
        if (!flagKey) return true;
        const val = this.featureFlags[flagKey];
        return val === undefined || val === true || val === '1' || val === 1;
    }

    getEnabledTabs() {
        return Object.keys(this.TAB_LABELS).filter(key => this.isTabEnabled(key));
    }

    renderPhoneNav() {
        const c = this.els.phoneNav; c.innerHTML = '';
        const enabledTabs = this.getEnabledTabs();
        enabledTabs.forEach(key => {
            const el = document.createElement('div');
            el.className = 'ld-phone__nav-item' + (this.state.activeTab === key ? ' is-active' : '');
            el.innerHTML = '<i class="fas ' + (this.TAB_ICONS[key] || 'fa-circle') + '"></i><span>' + this.esc(this.TAB_LABELS[key]) + '</span>';
            el.addEventListener('click', () => {
                this.state.activeTab = key;
                this.state.selectedId = null;
                this.render();
            });
            c.appendChild(el);
        });
        this.els.phoneHeaderTitle.textContent = this.TAB_LABELS[this.state.activeTab] || '';
        if (this.els.activeTabLabel) {
            this.els.activeTabLabel.textContent = this.TAB_LABELS[this.state.activeTab] || '';
        }
    }

    renderPhoneContent() {
        const c = this.els.phoneContent;
        const blocks = this.getBlocks();
        c.innerHTML = '';
        const s = this.getSettings();
        c.style.background = s.background_color || '#fafaf9';
        c.style.backgroundImage = s.background_image ? "url('" + s.background_image + "')" : '';
        if (s.background_image) {
            c.style.backgroundSize = s.background_size || 'cover';
            c.style.backgroundPosition = s.background_position || 'center';
            c.style.backgroundRepeat = s.background_repeat || 'no-repeat';
        }
        const g = this.getGlobalSettings();
        const pad = s.content_padding || g.content_padding || 18;
        c.style.padding = pad + 'px';
        c.style.gap = (g.section_gap || 16) + 'px';
        this.applyGlobalDesignPreview();

        if (blocks.length === 0) {
            var tab = this.state.activeTab;
            var tabLabel = this.TAB_LABELS[tab] || tab;
            var hasTemplates = this.getTemplatesForTab && this.getTemplatesForTab(tab).length > 0;
            var wizardBtn = '<button type="button" class="ld-btn ld-btn--outline ld-btn--block" onclick="if(window.__ldDesigner)window.__ldDesigner.showOnboarding();"><i class="fas fa-wand-magic-sparkles"></i> テンプレートウィザードで作成</button>';
            var hint = hasTemplates
                ? '<div class="ld-quickstart__desc">テンプレートを選ぶだけでプロ品質のデザインが完成します。<br>AI知識は不要。誰でもかんたんに使えます。</div><div class="ld-quickstart__actions"><button type="button" class="ld-btn ld-btn--primary ld-btn--block" onclick="if(window.__ldDesigner){var t=window.__ldDesigner.getTemplatesForTab(window.__ldDesigner.state.activeTab);if(t.length)window.__ldDesigner.applyTabTemplate(t[0]);}"><i class="fas fa-magic"></i> おすすめテンプレートを適用</button>' + wizardBtn + '<button type="button" class="ld-btn ld-btn--ghost ld-btn--block" onclick="if(window.__ldDesigner)window.__ldDesigner.addBlock(\'hero\');"><i class="fas fa-plus"></i> 白紙から始める</button></div>'
                : '<div class="ld-quickstart__desc">右パネルから<strong>ブロックを追加</strong>してデザインを始めましょう。<br>ドラッグ＆ドロップにも対応しています。</div><div class="ld-quickstart__actions">' + wizardBtn + '<button type="button" class="ld-btn ld-btn--primary ld-btn--block" onclick="if(window.__ldDesigner)window.__ldDesigner.addBlock(\'hero\');"><i class="fas fa-plus"></i> ヒーローを追加</button><button type="button" class="ld-btn ld-btn--ghost ld-btn--block" onclick="if(window.__ldDesigner)window.__ldDesigner.addBlock(\'data\');"><i class="fas fa-database"></i> データブロックを追加</button></div>';
            c.innerHTML = '<div class="ld-quickstart"><i class="fas fa-wand-magic-sparkles ld-quickstart__icon"></i><div class="ld-quickstart__title">「' + this.esc(tabLabel) + '」画面をデザイン</div>' + hint + '</div>';
            window.__ldDesigner = this;
            return;
        }
        window.__ldDesigner = this;
        blocks.forEach((block, i) => c.appendChild(this.createBlockEl(block, i)));
    }

    createBlockEl(block, index) {
        const el = document.createElement('div');
        el.className = 'ld-block ld-block--' + block.type + (this.state.selectedId === block.id ? ' is-selected' : '');
        el.dataset.id = block.id;
        el.dataset.index = index;

        this.applyCommonStyles(el, block);

        const handle = document.createElement('div');
        handle.className = 'ld-block__handle';
        handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';
        el.appendChild(handle);
        el.appendChild(this.createToolbar(block, index));

        switch (block.type) {
            case 'hero': this.buildHero(el, block); break;
            case 'heading': this.buildHeading(el, block); break;
            case 'text': this.buildText(el, block); break;
            case 'image': this.buildImage(el, block); break;
            case 'button': this.buildButton(el, block); break;
            case 'card': this.buildCard(el, block); break;
            case 'collection': this.buildCollection(el, block); break;
            case 'slider': this.buildSlider(el, block); break;
            case 'icon_text': this.buildIconText(el, block); break;
            case 'list': this.buildList(el, block); break;
            case 'banner': this.buildBanner(el, block); break;
            case 'spacer': el.style.height = (block.height || 16) + 'px'; break;
            case 'divider': this.buildDivider(el, block); break;
            case 'member_summary': this.buildMemberSummary(el, block); break;
            case 'quick_actions': this.buildQuickActions(el, block); break;
            case 'data': this.buildData(el, block); break;
        }

        el.addEventListener('click', (e) => {
            if (e.target.closest('.ld-block__toolbar-btn') || e.target.closest('.ld-block__handle')) return;
            this.state.selectedId = block.id;
            this.renderPhoneContent();
            this.renderBlockSettings();
        });
        return el;
    }

    applyCommonStyles(el, block) {
        if (block.border_radius !== undefined && block.border_radius !== null) el.style.borderRadius = block.border_radius + 'px';
        if (block.padding !== undefined && block.padding !== null) el.style.padding = block.padding + 'px';
        if (block.margin_bottom) el.style.marginBottom = block.margin_bottom + 'px';
        if (block.background_color) el.style.backgroundColor = block.background_color;
        if (block.opacity !== undefined && block.opacity < 100) el.style.opacity = block.opacity / 100;
        if (block.shadow) el.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
        if (block.text_align) el.style.textAlign = block.text_align;
        if (block.text_color) el.style.color = block.text_color;
    }

    /* --- Block builders --- */

    buildHero(el, b) {
        if (b.image_url) { el.style.backgroundImage = "url('" + b.image_url + "')"; el.style.backgroundSize = 'cover'; el.style.backgroundPosition = 'center'; }
        else { el.style.background = 'linear-gradient(135deg,' + (b.gradient_start || '#667eea') + ' 0%,' + (b.gradient_end || '#764ba2') + ' 100%)'; }
        el.innerHTML += '<div class="ld-block__body"><div class="ld-block--hero-title">' + this.esc(b.title) + '</div><div class="ld-block--hero-sub">' + this.esc(b.subtitle) + '</div></div>';
    }

    buildHeading(el, b) {
        const sizes = { small: 14, medium: 16, large: 20 };
        el.style.fontSize = (sizes[b.size] || 16) + 'px';
        if (b.text_color) el.style.color = b.text_color;
        el.innerHTML += '<span class="ld-block__body">' + this.esc(b.text) + '</span>';
    }

    buildText(el, b) {
        if (b.font_size) el.style.fontSize = b.font_size + 'px';
        if (b.text_color) el.style.color = b.text_color;
        el.innerHTML += '<div class="ld-block__body">' + this.esc(b.text).replace(/\n/g, '<br>') + '</div>';
    }

    buildImage(el, b) {
        if (b.image_url) { el.innerHTML += '<div class="ld-block__body"><img src="' + this.esc(b.image_url) + '" alt="' + this.esc(b.alt) + '" style="object-fit:' + (b.fit || 'cover') + '"></div>'; }
        else { el.innerHTML += '<div class="ld-block__body ld-block--image-placeholder"><i class="fas fa-image"></i><span>画像URLを設定</span></div>'; }
    }

    buildButton(el, b) {
        const w = b.full_width ? 'width:100%;' : 'display:inline-block;';
        el.innerHTML += '<div class="ld-block__body"><div class="ld-block--button-inner" style="background:' + (b.color || '#06C755') + ';color:' + (b.text_color || '#fff') + ';' + w + '">' + this.esc(b.text) + '</div></div>';
    }

    buildCard(el, b) {
        let img = '<div class="ld-block--card-image" style="height:' + (b.image_height || 140) + 'px">';
        img += b.image_url ? '<img src="' + this.esc(b.image_url) + '" alt="">' : '<i class="fas fa-image"></i>';
        img += '</div>';
        el.innerHTML += '<div class="ld-block__body">' + img + '<div class="ld-block--card-body"><div class="ld-block--card-title">' + this.esc(b.title) + '</div><div class="ld-block--card-desc">' + this.esc(b.description) + '</div></div></div>';
    }

    buildCollection(el, b) {
        const items = b.items || [];
        const isGrid = b.layout === 'grid';
        const cols = b.columns || 2;
        const gap = b.gap ?? 8;
        const maxRows = b.max_rows || 0;
        const visibleCount = (isGrid && maxRows > 0) ? maxRows * cols : items.length;
        const visible = items.slice(0, visibleCount);
        const cls = isGrid ? 'ld-collection ld-collection--grid' : 'ld-collection';
        const style = isGrid
            ? '--col-count:' + cols + ';--col-gap:' + gap + 'px'
            : '--item-w:' + (b.item_width || 140) + 'px;--col-gap:' + gap + 'px';
        let html = '<div class="ld-block__body ' + cls + '" style="' + style + '">';
        visible.forEach(item => {
            let ih = '<div class="ld-collection__item">';
            ih += '<div class="ld-collection__img" style="height:' + (b.item_image_height || 100) + 'px">';
            ih += item.image_url ? '<img src="' + this.esc(item.image_url) + '" alt="">' : '<i class="fas fa-image"></i>';
            ih += '</div><div class="ld-collection__title">' + this.esc(item.title || '') + '</div>';
            if (b.show_description && item.description) ih += '<div class="ld-collection__desc">' + this.esc(item.description) + '</div>';
            ih += '</div>';
            html += ih;
        });
        html += '</div>';
        el.innerHTML += html;
    }

    buildSlider(el, b) {
        const slides = b.slides || [];
        let html = '<div class="ld-block__body ld-slider" style="height:' + (b.slide_height || 160) + 'px">';
        if (slides.length > 0) {
            const s = slides[0];
            if (s.image_url) { html += '<img src="' + this.esc(s.image_url) + '" alt="" class="ld-slider__img">'; }
            else { html += '<div class="ld-slider__placeholder"><i class="fas fa-images"></i></div>'; }
            if (s.caption) html += '<div class="ld-slider__caption">' + this.esc(s.caption) + '</div>';
        }
        if (b.show_dots !== false && slides.length > 1) {
            html += '<div class="ld-slider__dots">';
            slides.forEach((_, i) => { html += '<span class="ld-slider__dot' + (i === 0 ? ' is-active' : '') + '"></span>'; });
            html += '</div>';
        }
        html += '</div>';
        el.innerHTML += html;
    }

    buildIconText(el, b) {
        const rows = b.rows || [];
        let html = '<div class="ld-block__body ld-icon-text">';
        rows.forEach(r => {
            html += '<div class="ld-icon-text__row"><i class="fas ' + (r.icon || 'fa-check') + '" style="color:' + (r.color || '#06C755') + '"></i><span>' + this.esc(r.text || '') + '</span></div>';
        });
        html += '</div>';
        el.innerHTML += html;
    }

    buildList(el, b) {
        const items = b.items || [];
        const tag = b.style === 'number' ? 'ol' : 'ul';
        if (b.text_color) el.style.color = b.text_color;
        let html = '<div class="ld-block__body ld-list"><' + tag + ' class="ld-list__inner">';
        items.forEach(item => { html += '<li>' + this.esc(item) + '</li>'; });
        html += '</' + tag + '></div>';
        el.innerHTML += html;
    }

    buildBanner(el, b) {
        el.style.background = b.color || '#3b82f6';
        el.style.color = b.text_color || '#fff';
        el.innerHTML += '<div class="ld-block__body ld-banner"><i class="fas ' + (b.icon || 'fa-info-circle') + '"></i><span>' + this.esc(b.text) + '</span></div>';
    }

    buildDivider(el, b) {
        el.innerHTML += '<div class="ld-block__body"><hr style="border-top:1px ' + (b.divider_style || 'solid') + ' ' + (b.divider_color || '#e5e7eb') + ';border-bottom:none;margin:0"></div>';
    }

    buildMemberSummary(el, b) {
        const hello = b.greeting ? this.esc(b.greeting) : 'こんにちは、デモ 太郎 さん';
        el.innerHTML +=
            '<div class="ld-block__body"><div class="ld-mock-summary">' +
                '<div class="ld-mock-summary__top">' +
                    '<div>' +
                        '<div class="ld-mock-summary__hello">' + hello + '</div>' +
                        '<span class="ld-mock-summary__rank"><i class="fas fa-crown"></i> シルバー</span>' +
                    '</div>' +
                    '<span class="ld-mock-summary__qr"><i class="fas fa-qrcode"></i></span>' +
                '</div>' +
                '<div class="ld-mock-summary__points"><span class="ld-mock-summary__pt">1,280</span><span class="ld-mock-summary__unit">pt</span></div>' +
                '<div class="ld-mock-summary__track"><span style="width:26%"></span></div>' +
                '<div class="ld-mock-summary__next">次の<strong>ゴールド</strong>まであと <strong>3,720</strong> pt</div>' +
            '</div></div>';
    }

    buildQuickActions(el, b) {
        const actions = Array.isArray(b.actions) ? b.actions : [];
        let tiles = '';
        actions.forEach(a => {
            tiles += '<div class="ld-mock-qa__tile"><span class="ld-mock-qa__icon"><i class="fas ' + this.esc(a.icon || 'fa-circle') + '"></i></span><span class="ld-mock-qa__label">' + this.esc(a.label || '') + '</span></div>';
        });
        el.innerHTML += '<div class="ld-block__body"><div class="ld-mock-qa">' + tiles + '</div></div>';
    }

    buildData(el, b) {
        return window.LiffDesignerModules.dataPreview.buildData.call(this, el, b);
    }

    renderDataSourcePreview(source) {
        return window.LiffDesignerModules.dataPreview.renderDataSourcePreview.call(this, source);
    }

    createToolbar(block, index) {
        const tb = document.createElement('div'); tb.className = 'ld-block__toolbar';
        const blocks = this.getBlocks();
        if (index > 0) tb.appendChild(this.tbBtn('fa-arrow-up', '上へ', () => this.moveBlock(index, index - 1)));
        if (index < blocks.length - 1) tb.appendChild(this.tbBtn('fa-arrow-down', '下へ', () => this.moveBlock(index, index + 1)));
        tb.appendChild(this.tbBtn('fa-copy', '複製', () => this.duplicateBlock(block.id)));
        tb.appendChild(this.tbBtn('fa-trash-alt', '削除', () => this.deleteBlock(block.id), true));
        return tb;
    }

    tbBtn(icon, title, fn, danger) {
        const b = document.createElement('button'); b.type = 'button';
        b.className = 'ld-block__toolbar-btn' + (danger ? ' ld-block__toolbar-btn--danger' : '');
        b.title = title; b.innerHTML = '<i class="fas ' + icon + '"></i>';
        b.addEventListener('click', (e) => { e.stopPropagation(); fn(); });
        return b;
    }

    renderBlockPalette() {
        const c = this.els.blockPalette; c.innerHTML = '';
        const kw = (this.ui.paletteSearch || '').trim().toLowerCase();
        const list = this.BLOCK_TYPES.filter(bt => {
            const categoryMatched = this.ui.paletteCategory === 'all' || bt.category === this.ui.paletteCategory;
            if (!categoryMatched) return false;
            if (!kw) return true;
            return (bt.label + ' ' + bt.desc + ' ' + bt.type).toLowerCase().includes(kw);
        });

        if (list.length === 0) {
            c.innerHTML = '<div class="ld-palette-empty">該当するブロックがありません</div>';
            return;
        }

        list.forEach(bt => {
            const el = document.createElement('div');
            el.className = 'ld-palette-item';
            el.dataset.blockType = bt.type;
            el.title = bt.desc;
            el.innerHTML = '<div class="ld-palette-item__icon"><i class="fas ' + bt.icon + '"></i></div><span class="ld-palette-item__label">' + this.esc(bt.label) + '</span>';
            el.addEventListener('click', () => this.addBlock(bt.type));
            c.appendChild(el);
        });
    }

    renderPaletteCategories() {
        const c = this.els.paletteCategories;
        if (!c) return;
        const defs = [
            { id: 'all', label: 'すべて' },
            { id: 'basic', label: '基本' },
            { id: 'layout', label: 'レイアウト' },
            { id: 'data', label: '連携' },
        ];
        c.innerHTML = '';
        defs.forEach(def => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'ld-chip' + (this.ui.paletteCategory === def.id ? ' is-active' : '');
            b.textContent = def.label;
            b.addEventListener('click', () => {
                this.ui.paletteCategory = def.id;
                this.renderPaletteCategories();
                this.renderBlockPalette();
            });
            c.appendChild(b);
        });
    }

    /* ========== Block settings (always show palette) ========== */

    renderBlockSettings() {
        const sec = this.els.blockSettingsSection;
        const panel = this.els.blockSettings;

        if (!this.state.selectedId) { sec.hidden = true; return; }
        const block = this.findBlock(this.state.selectedId);
        if (!block) { sec.hidden = true; return; }

        sec.hidden = false;
        panel.innerHTML = '';

        const td = this.BLOCK_TYPES.find(b => b.type === block.type);
        const tag = document.createElement('div');
        tag.className = 'ld-settings__type-tag';
        tag.innerHTML = '<i class="fas ' + (td ? td.icon : 'fa-cube') + '"></i> ' + this.esc(td ? td.label : block.type) + 'ブロック';
        panel.appendChild(tag);

        switch (block.type) {
            case 'hero': this.sHero(panel, block); break;
            case 'heading': this.sHeading(panel, block); break;
            case 'text': this.sText(panel, block); break;
            case 'image': this.sImage(panel, block); break;
            case 'button': this.sButton(panel, block); break;
            case 'card': this.sCard(panel, block); break;
            case 'collection': this.sCollection(panel, block); break;
            case 'slider': this.sSlider(panel, block); break;
            case 'icon_text': this.sIconText(panel, block); break;
            case 'list': this.sList(panel, block); break;
            case 'banner': this.sBanner(panel, block); break;
            case 'spacer': this.sSpacer(panel, block); break;
            case 'divider': this.sDivider(panel, block); break;
            case 'member_summary': this.sMemberSummary(panel, block); break;
            case 'quick_actions': this.sQuickActions(panel, block); break;
            case 'data': this.sData(panel, block); break;
        }

        this.addCommonSettings(panel, block);

        const del = document.createElement('div');
        del.className = 'ld-settings__delete';
        const btn = document.createElement('button');
        btn.type = 'button'; btn.className = 'ld-btn ld-btn--danger ld-btn--block';
        btn.innerHTML = '<i class="fas fa-trash-alt"></i> 削除';
        btn.addEventListener('click', () => this.deleteBlock(block.id));
        del.appendChild(btn);
        panel.appendChild(del);
    }

    addCommonSettings(panel, block) {
        const hr = document.createElement('div');
        hr.className = 'ld-settings__divider';
        const lbl = document.createElement('div');
        lbl.className = 'ld-settings__section-label';
        lbl.textContent = 'スタイル設定';
        panel.appendChild(hr);
        panel.appendChild(lbl);

        this.addRange(panel, '角丸 (px)', block.border_radius, 0, 32, v => { block.border_radius = v; });
        this.addRange(panel, '余白 (px)', block.padding, 0, 40, v => { block.padding = v; });
        this.addRange(panel, '下マージン (px)', block.margin_bottom, 0, 40, v => { block.margin_bottom = v; });
        this.addRange(panel, '透明度 (%)', block.opacity, 0, 100, v => { block.opacity = v; });
        this.addColorField(panel, '背景色', block.background_color, v => { block.background_color = v; });
        this.addColorField(panel, '文字色', block.text_color, v => { block.text_color = v; });
        this.addCheckbox(panel, '影をつける', block.shadow, v => { block.shadow = v; });
        this.addSel(panel, 'テキスト揃え', block.text_align, this.ALIGN_OPTIONS, v => { block.text_align = v; });
    }

    /* --- Per-type settings --- */

    sHero(p, b) {
        this.addField(p, 'タイトル', b.title, v => { b.title = v; });
        this.addField(p, 'サブタイトル', b.subtitle, v => { b.subtitle = v; });
        this.addField(p, '背景画像URL', b.image_url, v => { b.image_url = v; });
        this.addColorField(p, 'グラデ開始', b.gradient_start, v => { b.gradient_start = v; });
        this.addColorField(p, 'グラデ終了', b.gradient_end, v => { b.gradient_end = v; });
    }

    sHeading(p, b) {
        this.addField(p, 'テキスト', b.text, v => { b.text = v; });
        this.addSel(p, 'サイズ', b.size, [{ value: 'small', label: '小' }, { value: 'medium', label: '中' }, { value: 'large', label: '大' }], v => { b.size = v; });
        this.addColorField(p, '文字色', b.text_color, v => { b.text_color = v; });
    }

    sText(p, b) {
        this.addTextarea(p, 'テキスト', b.text, v => { b.text = v; });
        this.addRange(p, 'フォントサイズ', b.font_size, 10, 24, v => { b.font_size = v; });
        this.addColorField(p, '文字色', b.text_color, v => { b.text_color = v; });
    }

    sImage(p, b) {
        this.addField(p, '画像URL', b.image_url, v => { b.image_url = v; });
        this.addField(p, '代替テキスト', b.alt, v => { b.alt = v; });
        this.addSel(p, 'フィット', b.fit, [{ value: 'cover', label: 'カバー' }, { value: 'contain', label: '全体表示' }, { value: 'fill', label: '引き伸ばし' }], v => { b.fit = v; });
    }

    sButton(p, b) {
        this.addField(p, 'テキスト', b.text, v => { b.text = v; });
        this.addColorField(p, 'ボタン色', b.color, v => { b.color = v; });
        this.addColorField(p, '文字色', b.text_color, v => { b.text_color = v; });
        this.addField(p, 'リンクURL', b.action, v => { b.action = v; });
        this.addCheckbox(p, '幅いっぱい', b.full_width, v => { b.full_width = v; });
    }

    sCard(p, b) {
        this.addField(p, 'タイトル', b.title, v => { b.title = v; });
        this.addField(p, '説明', b.description, v => { b.description = v; });
        this.addField(p, '画像URL', b.image_url, v => { b.image_url = v; });
        this.addRange(p, '画像の高さ', b.image_height, 60, 300, v => { b.image_height = v; });
    }

    sCollection(p, b) {
        this.addSel(p, 'レイアウト', b.layout, [
            { value: 'scroll', label: '横スクロール' },
            { value: 'grid', label: 'グリッド（折り返し）' },
        ], v => { b.layout = v; });
        if (b.layout === 'grid') {
            this.addSel(p, '列数', String(b.columns), [
                { value: '2', label: '2列' }, { value: '3', label: '3列' }, { value: '4', label: '4列' },
            ], v => { b.columns = parseInt(v, 10); });
            this.addRange(p, '表示行数 (0=全て)', b.max_rows, 0, 10, v => { b.max_rows = v; });
        } else {
            this.addRange(p, 'アイテム幅', b.item_width, 80, 240, v => { b.item_width = v; });
        }
        this.addRange(p, '画像の高さ', b.item_image_height, 40, 200, v => { b.item_image_height = v; });
        this.addRange(p, '間隔', b.gap, 0, 24, v => { b.gap = v; });
        this.addCheckbox(p, '説明を表示', b.show_description, v => { b.show_description = v; });
        this.addArrayItems(p, 'アイテム', b.items, () => ({ title: '新規', image_url: 'https://picsum.photos/seed/' + Date.now() + '/300/300', description: '' }), ['title', 'image_url', 'description'], ['タイトル', '画像URL', '説明']);
    }

    sSlider(p, b) {
        this.addRange(p, 'スライドの高さ', b.slide_height, 80, 300, v => { b.slide_height = v; });
        this.addCheckbox(p, '自動再生', b.auto_play, v => { b.auto_play = v; });
        this.addCheckbox(p, 'ドット表示', b.show_dots, v => { b.show_dots = v; });
        this.addArrayItems(p, 'スライド', b.slides, () => ({ image_url: '', caption: '' }), ['image_url', 'caption'], ['画像URL', 'キャプション']);
    }

    sIconText(p, b) {
        this.addArrayItems(p, '行', b.rows, () => ({ icon: 'fa-check-circle', text: '新規項目', color: '#06C755' }), ['icon', 'text', 'color'], ['アイコン(fa-*)', 'テキスト', '色']);
    }

    sList(p, b) {
        this.addSel(p, 'スタイル', b.style, [{ value: 'bullet', label: '● 箇条書き' }, { value: 'number', label: '1. 番号' }], v => { b.style = v; });
        this.addColorField(p, '文字色', b.text_color, v => { b.text_color = v; });
        this.addStringArray(p, '項目', b.items);
    }

    sBanner(p, b) {
        this.addField(p, 'テキスト', b.text, v => { b.text = v; });
        this.addField(p, 'アイコン (fa-*)', b.icon, v => { b.icon = v; });
        this.addColorField(p, '背景色', b.color, v => { b.color = v; });
        this.addColorField(p, '文字色', b.text_color, v => { b.text_color = v; });
    }

    sSpacer(p, b) { this.addRange(p, '高さ (px)', b.height, 4, 80, v => { b.height = v; }); }

    sDivider(p, b) {
        this.addColorField(p, '線の色', b.divider_color, v => { b.divider_color = v; });
        this.addSel(p, '線のスタイル', b.divider_style, [{ value: 'solid', label: '直線' }, { value: 'dashed', label: '破線' }, { value: 'dotted', label: '点線' }], v => { b.divider_style = v; });
    }

    sData(p, b) { this.addSel(p, 'データソース', b.source, this.DATA_SOURCES, v => { b.source = v; }); }

    sMemberSummary(p, b) {
        this.addField(p, 'あいさつ文（空欄で会員名を自動表示）', b.greeting, v => { b.greeting = v; });
        const note = document.createElement('div');
        note.className = 'ld-settings__hint';
        note.innerHTML = 'ポイント・ランク・進捗は会員データから自動取得されます。';
        p.appendChild(note);
    }

    sQuickActions(p, b) {
        if (!Array.isArray(b.actions)) b.actions = [];
        this.addArrayItems(p, 'アクション', b.actions,
            () => ({ icon: 'fa-bolt', label: '新規', action: '#products' }),
            ['icon', 'label', 'action'],
            ['アイコン(fa-*)', 'ラベル', 'リンク(#tab)']);
    }

    /* ========== Field helpers ========== */

    addField(p, label, val, fn) {
        const g = this.fg(label);
        const i = document.createElement('input'); i.type = 'text'; i.className = 'ld-form-input'; i.value = val || '';
        i.addEventListener('input', () => { fn(i.value); this.onBlockChange(); });
        g.appendChild(i); p.appendChild(g);
    }

    addTextarea(p, label, val, fn) {
        const g = this.fg(label);
        const t = document.createElement('textarea'); t.className = 'ld-form-textarea'; t.rows = 3; t.value = val || '';
        t.addEventListener('input', () => { fn(t.value); this.onBlockChange(); });
        g.appendChild(t); p.appendChild(g);
    }

    addColorField(p, label, val, fn) {
        const g = this.fg(label);
        const row = document.createElement('div'); row.className = 'ld-color-row';
        const pk = document.createElement('input'); pk.type = 'color'; pk.className = 'ld-color-picker'; pk.value = val || '#000000';
        const hx = document.createElement('input'); hx.type = 'text'; hx.className = 'ld-form-input ld-form-input--sm'; hx.value = val || ''; hx.maxLength = 7;
        pk.addEventListener('input', () => { hx.value = pk.value; fn(pk.value); this.onBlockChange(); });
        hx.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(hx.value)) { pk.value = hx.value; fn(hx.value); this.onBlockChange(); } });
        row.appendChild(pk); row.appendChild(hx); g.appendChild(row); p.appendChild(g);
    }

    addSel(p, label, val, opts, fn) {
        const g = this.fg(label);
        const s = document.createElement('select'); s.className = 'ld-form-select';
        opts.forEach(o => { const op = document.createElement('option'); op.value = o.value; op.textContent = o.label; if (o.value === val) op.selected = true; s.appendChild(op); });
        s.addEventListener('change', () => { fn(s.value); this.onBlockChange(); });
        g.appendChild(s); p.appendChild(g);
    }

    addRange(p, label, val, min, max, fn) {
        const g = this.fg(label);
        const row = document.createElement('div'); row.className = 'ld-range-row';
        const r = document.createElement('input'); r.type = 'range'; r.className = 'ld-form-range'; r.min = min; r.max = max; r.value = val ?? min;
        const v = document.createElement('span'); v.className = 'ld-range-val'; v.textContent = (val ?? min);
        r.addEventListener('input', () => { const n = parseInt(r.value, 10); v.textContent = n; fn(n); this.onBlockChange(); });
        row.appendChild(r); row.appendChild(v); g.appendChild(row); p.appendChild(g);
    }

    addCheckbox(p, label, val, fn) {
        const g = document.createElement('div'); g.className = 'ld-form-group ld-form-check';
        const lbl = document.createElement('label'); lbl.className = 'ld-check-label';
        const cb = document.createElement('input'); cb.type = 'checkbox'; cb.checked = !!val;
        cb.addEventListener('change', () => { fn(cb.checked); this.onBlockChange(); });
        lbl.appendChild(cb);
        const s = document.createElement('span'); s.textContent = ' ' + label; lbl.appendChild(s);
        g.appendChild(lbl); p.appendChild(g);
    }

    addArrayItems(p, label, arr, createDefault, fields, fieldLabels) {
        const g = this.fg(label);
        const list = document.createElement('div'); list.className = 'ld-array-list';

        const rebuild = () => {
            list.innerHTML = '';
            arr.forEach((item, i) => {
                const row = document.createElement('div'); row.className = 'ld-array-item';
                const hdr = document.createElement('div'); hdr.className = 'ld-array-item__header';
                hdr.innerHTML = '<span class="ld-array-item__num">#' + (i + 1) + '</span>';
                const del = document.createElement('button'); del.type = 'button'; del.className = 'ld-btn ld-btn--ghost ld-btn--xs';
                del.innerHTML = '<i class="fas fa-times"></i>';
                del.addEventListener('click', () => { arr.splice(i, 1); rebuild(); this.onBlockChange(); });
                hdr.appendChild(del);
                row.appendChild(hdr);

                fields.forEach((f, fi) => {
                    const fg = document.createElement('div'); fg.className = 'ld-form-group';
                    const fl = document.createElement('label'); fl.className = 'ld-form-label'; fl.textContent = fieldLabels[fi];
                    const inp = document.createElement('input'); inp.type = 'text'; inp.className = 'ld-form-input'; inp.value = item[f] || '';
                    inp.addEventListener('input', () => { item[f] = inp.value; this.onBlockChange(); });
                    fg.appendChild(fl); fg.appendChild(inp); row.appendChild(fg);
                });

                list.appendChild(row);
            });
        };
        rebuild();
        g.appendChild(list);

        const addBtn = document.createElement('button'); addBtn.type = 'button';
        addBtn.className = 'ld-btn ld-btn--outline ld-btn--block';
        addBtn.innerHTML = '<i class="fas fa-plus"></i> 追加';
        addBtn.addEventListener('click', () => { arr.push(createDefault()); rebuild(); this.onBlockChange(); });
        g.appendChild(addBtn);
        p.appendChild(g);
    }

    addStringArray(p, label, arr) {
        const g = this.fg(label);
        const list = document.createElement('div'); list.className = 'ld-array-list';

        const rebuild = () => {
            list.innerHTML = '';
            arr.forEach((item, i) => {
                const row = document.createElement('div'); row.className = 'ld-array-item ld-array-item--inline';
                const inp = document.createElement('input'); inp.type = 'text'; inp.className = 'ld-form-input'; inp.value = item;
                inp.addEventListener('input', () => { arr[i] = inp.value; this.onBlockChange(); });
                const del = document.createElement('button'); del.type = 'button'; del.className = 'ld-btn ld-btn--ghost ld-btn--xs';
                del.innerHTML = '<i class="fas fa-times"></i>';
                del.addEventListener('click', () => { arr.splice(i, 1); rebuild(); this.onBlockChange(); });
                row.appendChild(inp); row.appendChild(del); list.appendChild(row);
            });
        };
        rebuild();
        g.appendChild(list);

        const addBtn = document.createElement('button'); addBtn.type = 'button';
        addBtn.className = 'ld-btn ld-btn--outline ld-btn--block';
        addBtn.innerHTML = '<i class="fas fa-plus"></i> 追加';
        addBtn.addEventListener('click', () => { arr.push('新規項目'); rebuild(); this.onBlockChange(); });
        g.appendChild(addBtn);
        p.appendChild(g);
    }

    fg(label) {
        const g = document.createElement('div'); g.className = 'ld-form-group';
        const l = document.createElement('label'); l.className = 'ld-form-label'; l.textContent = label;
        g.appendChild(l); return g;
    }

    applyScreenSettings() {
        const s = this.getSettings();
        this.els.screenBgColor.value = s.background_color || '#fafaf9';
        this.els.screenBgColorHex.value = s.background_color || '#fafaf9';
        this.els.screenBgImage.value = s.background_image || '';
        if (this.els.screenBgSize) this.els.screenBgSize.value = s.background_size || 'cover';
        const g = this.getGlobalSettings();
        const pad = s.content_padding || g.content_padding || 18;
        if (this.els.screenContentPadding) {
            this.els.screenContentPadding.value = String(pad);
            if (this.els.screenContentPaddingVal) this.els.screenContentPaddingVal.textContent = String(pad);
        }
        this.syncGlobalDesignControls();
    }

    /* ========== Block operations ========== */

    addBlock(type, insertIndex) {
        const block = this.normalizeBlock({ type });
        block.id = this.nextId();
        const blocks = this.getBlocks();
        if (typeof insertIndex === 'number' && insertIndex >= 0) { blocks.splice(insertIndex, 0, block); }
        else { blocks.push(block); }
        this.state.selectedId = block.id;
        this.pushHistory(); this.renderPhoneContent(); this.renderBlockSettings(); this.renderScreenList();
        const el = this.els.phoneContent.querySelector('[data-id="' + block.id + '"]');
        if (el) { el.classList.add('ld-block--just-added'); el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); setTimeout(() => el.classList.remove('ld-block--just-added'), 400); }
    }

    deleteBlock(id) {
        const blocks = this.getBlocks(); const i = blocks.findIndex(b => b.id === id); if (i < 0) return;
        const el = this.els.phoneContent.querySelector('[data-id="' + id + '"]');
        const doDelete = () => { blocks.splice(i, 1); if (this.state.selectedId === id) this.state.selectedId = null; this.pushHistory(); this.renderPhoneContent(); this.renderBlockSettings(); this.renderScreenList(); };
        if (el) { el.classList.add('ld-block--removing'); setTimeout(doDelete, 200); } else { doDelete(); }
    }

    duplicateBlock(id) {
        const blocks = this.getBlocks(); const i = blocks.findIndex(b => b.id === id); if (i < 0) return;
        const clone = JSON.parse(JSON.stringify(blocks[i])); clone.id = this.nextId();
        blocks.splice(i + 1, 0, clone); this.state.selectedId = clone.id;
        this.pushHistory(); this.renderPhoneContent(); this.renderBlockSettings(); this.renderScreenList();
    }

    moveBlock(from, to) {
        const blocks = this.getBlocks(); if (from < 0 || to < 0 || from >= blocks.length || to >= blocks.length) return;
        const [m] = blocks.splice(from, 1); blocks.splice(to, 0, m);
        this.pushHistory(); this.renderPhoneContent();
    }

    onBlockChange() { this.renderPhoneContent(); this.pushHistory(); }

    /* ========== Drag & Drop ========== */

    bindDragSystem() {
        return window.LiffDesignerModules.dragDrop.bindDragSystem.call(this);
    }

    handlePointerDown(e) {
        return window.LiffDesignerModules.dragDrop.handlePointerDown.call(this, e);
    }

    handlePointerMove(e) {
        return window.LiffDesignerModules.dragDrop.handlePointerMove.call(this, e);
    }

    handlePointerUp() {
        return window.LiffDesignerModules.dragDrop.handlePointerUp.call(this);
    }

    createGhost(e) {
        return window.LiffDesignerModules.dragDrop.createGhost.call(this, e);
    }

    updateDropTarget(e) {
        return window.LiffDesignerModules.dragDrop.updateDropTarget.call(this, e);
    }

    clearDropIndicators() { return window.LiffDesignerModules.dragDrop.clearDropIndicators.call(this); }

    autoScroll(e) {
        return window.LiffDesignerModules.dragDrop.autoScroll.call(this, e);
    }

    resetDrag() {
        return window.LiffDesignerModules.dragDrop.resetDrag.call(this);
    }

    /* ========== Events ========== */

    bindEvents() {
        return window.LiffDesignerModules.uiEvents.bindEvents.call(this);
    }

    onScreenChange() {
        return window.LiffDesignerModules.uiEvents.onScreenChange.call(this);
    }

    applyZoom() {
        return window.LiffDesignerModules.uiEvents.applyZoom.call(this);
    }

    applyPreviewDevice() {
        return window.LiffDesignerModules.uiEvents.applyPreviewDevice.call(this);
    }

    applyCustomCssPreview() {
        return window.LiffDesignerModules.uiEvents.applyCustomCssPreview.call(this);
    }

    openTemplateModal() {
        return window.LiffDesignerModules.templateManager.openTemplateModal.call(this);
    }

    renderTabTemplateList() {
        return window.LiffDesignerModules.templateManager.renderTabTemplateList.call(this);
    }

    renderTabTemplateMiniPreview(tpl) {
        return window.LiffDesignerModules.templateManager.renderTabTemplateMiniPreview.call(this, tpl);
    }

    getTemplatesForTab(tabKey) {
        return window.LiffDesignerModules.templateManager.getTemplatesForTab.call(this, tabKey);
    }

    applyTabTemplate(tpl) {
        return window.LiffDesignerModules.templateManager.applyTabTemplate.call(this, tpl);
    }

    openTabTemplatePreview(tpl) {
        return window.LiffDesignerModules.templateManager.openTabTemplatePreview.call(this, tpl);
    }

    getTemplateCategoryLabel(key) {
        return window.LiffDesignerModules.templateManager.getTemplateCategoryLabel.call(this, key);
    }

    getPrimaryDeviceLabel(tpl) {
        return window.LiffDesignerModules.templateManager.getPrimaryDeviceLabel.call(this, tpl);
    }

    applySuperPattern(pattern, skipConfirm) {
        return window.LiffDesignerModules.templateManager.applySuperPattern.call(this, pattern, skipConfirm);
    }

    exportJson() {
        return window.LiffDesignerModules.io.exportJson.call(this);
    }

    importJson(file) {
        return window.LiffDesignerModules.io.importJson.call(this, file);
    }

    openPreview() {
        return window.LiffDesignerModules.io.openPreview.call(this);
    }

    /* ========== Onboarding & Help ========== */

    shouldShowOnboarding() {
        return window.LiffDesignerModules.onboarding.shouldShowOnboarding.call(this);
    }

    showOnboarding() {
        return window.LiffDesignerModules.onboarding.showOnboarding.call(this);
    }

    showHelpOverlay() {
        return window.LiffDesignerModules.onboarding.showHelpOverlay.call(this);
    }

    renderTemplateVisualPreview(tpl, scope) {
        return window.LiffDesignerModules.templateManager.renderTemplateVisualPreview.call(this, tpl, scope);
    }

    get INDUSTRY_PRESETS() {
        return window.LiffDesignerModules.onboarding.INDUSTRY_PRESETS;
    }

    getAdminProjectKey() {
        const p = (window.location && window.location.pathname) ? window.location.pathname : '';
        const m = p.match(/^\/admin\/([^/]+)\//);
        return m && m[1] ? m[1] : 'default';
    }

    getDataPreviewFrameUrl(source) {
        const map = {
            products: 'products',
            orders: 'orders',
            stamps: 'stamps',
            coupons: 'coupons',
            queue: 'queue',
            reservations: 'reservations',
            member: 'member',
            member_card: 'member',
            gallery: 'gallery',
            announcements: 'home',
        };
        const tab = map[source] || 'home';
        const key = this.getAdminProjectKey();
        const base = (window.location && window.location.origin ? window.location.origin : '') + '/liff/' + encodeURIComponent(key);
        const params = new URLSearchParams({
            tab,
            designer_live_preview: '1',
            designer_source: String(source || ''),
            designer_embed: '1',
            designer_sample: '1',
            designer_rev: String(this.previewNonce || Date.now()),
        });
        return base + '?' + params.toString();
    }

    esc(str) { const d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML; }
}
