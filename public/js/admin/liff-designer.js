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
            { type: 'button_group', label: 'ボタン並び', icon: 'fa-ellipsis-h', desc: 'ボタンを2〜3個横並び', category: 'basic' },
            { type: 'video', label: '動画', icon: 'fa-play-circle', desc: 'YouTube動画を埋め込み', category: 'layout' },
            { type: 'hours', label: '営業時間', icon: 'fa-clock', desc: '営業時間の一覧表', category: 'store' },
            { type: 'access', label: '店舗アクセス', icon: 'fa-map-marker-alt', desc: '住所・電話・地図リンク', category: 'store' },
            { type: 'social_links', label: 'SNSリンク', icon: 'fa-share-alt', desc: 'Instagram等のリンク', category: 'store' },
            { type: 'faq', label: 'よくある質問', icon: 'fa-question-circle', desc: '開閉式のQ&A', category: 'store' },
        ];

        this.ACTION_TYPES = [
            { value: '', label: 'なし' },
            { value: 'tab', label: 'タブへ移動' },
            { value: 'url', label: '外部リンクを開く' },
            { value: 'tel', label: '電話をかける' },
            { value: 'mailto', label: 'メールを送る' },
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

        try {
            this.initState(config);
        } catch (err) {
            console.error('LIFF Designer: initState failed', err);
        }
        try {
            this.cacheElements();
        } catch (err) {
            console.warn('LIFF Designer: cacheElements failed', err);
            this.els = this.els || {};
        }
        const self = this;
        const boot = () => {
            try { self.bootDesigner(); } catch (err) {
                console.error('LIFF Designer: boot failed', err);
            }
        };
        if (typeof requestAnimationFrame === 'function') {
            requestAnimationFrame(() => setTimeout(boot, 0));
        } else {
            setTimeout(boot, 0);
        }
    }

    bootDesigner() {
        try {
            this.initThemeGallery();
        } catch (err) {
            console.warn('LIFF Designer: theme gallery init failed', err);
        }
        try {
            this.render();
        } catch (err) {
            console.error('LIFF Designer: render failed', err);
        }
        try {
            this.bindEvents();
        } catch (err) {
            console.warn('LIFF Designer: bindEvents failed', err);
        }
        try {
            this.bindDragSystem();
        } catch (err) {
            console.warn('LIFF Designer: bindDragSystem failed', err);
        }
        try {
            this.pushHistory();
            this.applyGlobalDesignPreview();
        } catch (err) {
            console.warn('LIFF Designer: post-init failed', err);
        }
        this._booted = true;
    }

    /* ========== Init ========== */

    initState(config) {
        try {
        config = config || {};
        const rawLayouts = config.layouts || {};
        const rawTabSettings = config.tabSettings || {};
        this.state.globalSettings = rawTabSettings._global || this.defaultGlobalSettings();
        Object.keys(this.TAB_LABELS).forEach(key => {
            this.state.tabSettings[key] = rawTabSettings[key] || { background_color: '#fafaf9' };
            this.state.layouts[key] = [];
        });
        Object.keys(rawLayouts).forEach(tab => {
            if (!Array.isArray(rawLayouts[tab])) return;
            this.state.layouts[tab] = rawLayouts[tab].map(item => {
                try {
                    return this.normalizeBlock(item);
                } catch (err) {
                    console.warn('LIFF Designer: skip invalid block on', tab, err);
                    return null;
                }
            }).filter(Boolean);
        });
        this.state.menuItems = this.normalizeMenuItems(config.menuItems);
        if (Array.isArray(config.templates)) this.templates = config.templates;
        } catch (err) {
            console.error('LIFF Designer: initState failed', err);
        }
    }

    /** タブメニュー（表示/非表示・並び替え・名前/アイコン）を正規化。announcements はメニュー対象外 */
    normalizeMenuItems(raw) {
        const menuTabs = Object.keys(this.TAB_LABELS).filter(k => k !== 'announcements');
        const byTab = {};
        (Array.isArray(raw) ? raw : []).forEach(item => {
            if (item && item.tab) byTab[item.tab] = item;
        });
        const items = menuTabs.map((tab, i) => {
            const saved = byTab[tab] || {};
            return {
                tab,
                label: saved.label || this.TAB_LABELS[tab],
                icon: saved.icon || this.TAB_ICONS[tab],
                order: typeof saved.order === 'number' ? saved.order : i,
                enabled: saved.enabled !== false,
            };
        });
        items.sort((a, b) => a.order - b.order);
        items.forEach((item, i) => { item.order = i; });
        return items;
    }

    getMenuItem(tab) {
        return (this.state.menuItems || []).find(m => m.tab === tab) || null;
    }

    /** タブの表示名（メニュー設定を優先） */
    tabLabel(tab) {
        const item = this.getMenuItem(tab);
        return (item && item.label) || this.TAB_LABELS[tab] || tab;
    }

    /** タブのアイコン（メニュー設定を優先） */
    tabIcon(tab) {
        const item = this.getMenuItem(tab);
        return (item && item.icon) || this.TAB_ICONS[tab] || 'fa-circle';
    }

    moveMenuItem(tab, delta) {
        const items = this.state.menuItems;
        const i = items.findIndex(m => m.tab === tab);
        const j = i + delta;
        if (i < 0 || j < 0 || j >= items.length) return;
        const [m] = items.splice(i, 1);
        items.splice(j, 0, m);
        items.forEach((item, k) => { item.order = k; });
        this.renderScreenList();
        this.renderPhoneNav();
    }

    toggleMenuItem(tab) {
        const item = this.getMenuItem(tab);
        if (!item) return;
        item.enabled = !item.enabled;
        this.renderScreenList();
        this.renderPhoneNav();
    }

    defaultGlobalSettings() {
        return {
            content_padding: 18,
            section_gap: 16,
            radius_scale: 'soft',
            tab_style: 'default',
            background_style: 'solid',
            bg_color: '#f9fafb',
            content_width: 'comfy',
            bg_overlay_opacity: 60,
            system_ui_style: 'refined',
            system_card_style: 'soft',
            system_media_shape: 'rounded',
            system_header_style: 'minimal',
            member_card_design: '',
            member_screen_design: 'wallet',
            coupon_screen_design: 'ticket',
            reservation_screen_design: 'cards',
            activity_screen_design: 'cards',
        };
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

    CONTENT_WIDTHS = {
        edge: '100%',
        comfy: 'min(100%, 440px)',
        narrow: 'min(100%, 380px)',
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
        return window.LiffDesignerModules.themePanel.resolveThemeVars.call(this, g);
    }

    applyGlobalDesignPreview() {
        return window.LiffDesignerModules.themePanel.applyGlobalDesignPreview.call(this);
    }

    applyDesignPreset(presetId) {
        return window.LiffDesignerModules.themePanel.applyDesignPreset.call(this, presetId);
    }

    syncBgStylePanels() {
        return window.LiffDesignerModules.themePanel.syncBgStylePanels.call(this);
    }

    initThemeGallery() {
        return window.LiffDesignerModules.themePanel.initThemeGallery.call(this);
    }

    renderThemeGalleryActive() {
        return window.LiffDesignerModules.themePanel.renderThemeGalleryActive.call(this);
    }

    buildThemeCardMiniPreview(preset) {
        return window.LiffDesignerModules.themePanel.buildThemeCardMiniPreview.call(this, preset);
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
        if (this.els.globalCardStyle) this.els.globalCardStyle.value = g.card_style || '';
        if (this.els.globalButtonShape) this.els.globalButtonShape.value = g.button_shape || '';
        if (this.els.globalDensity) this.els.globalDensity.value = g.density || 'default';
        if (this.els.globalSystemUiStyle) this.els.globalSystemUiStyle.value = g.system_ui_style || 'refined';
        if (this.els.globalSystemCardStyle) this.els.globalSystemCardStyle.value = g.system_card_style || 'soft';
        if (this.els.globalSystemMediaShape) this.els.globalSystemMediaShape.value = g.system_media_shape || 'rounded';
        if (this.els.globalSystemHeaderStyle) this.els.globalSystemHeaderStyle.value = g.system_header_style || 'minimal';
        if (this.els.globalMemberCardDesign) this.els.globalMemberCardDesign.value = g.member_card_design || '';
        if (this.els.globalMemberScreenDesign) this.els.globalMemberScreenDesign.value = g.member_screen_design || 'wallet';
        if (this.els.globalCouponScreenDesign) this.els.globalCouponScreenDesign.value = g.coupon_screen_design || 'ticket';
        if (this.els.globalReservationScreenDesign) this.els.globalReservationScreenDesign.value = g.reservation_screen_design || 'cards';
        if (this.els.globalActivityScreenDesign) this.els.globalActivityScreenDesign.value = g.activity_screen_design || 'cards';
        if (this.els.globalContentWidth) this.els.globalContentWidth.value = g.content_width || 'comfy';
        if (this.els.globalBgColor) this.els.globalBgColor.value = g.bg_color || '#f9fafb';
        if (this.els.globalBgColorHex) this.els.globalBgColorHex.value = g.bg_color || '#f9fafb';
        if (this.els.globalBgGradientStart) this.els.globalBgGradientStart.value = g.bg_gradient_start || '#eef2ff';
        if (this.els.globalBgGradientStartHex) this.els.globalBgGradientStartHex.value = g.bg_gradient_start || '#eef2ff';
        if (this.els.globalBgGradientEnd) this.els.globalBgGradientEnd.value = g.bg_gradient_end || '#ffffff';
        if (this.els.globalBgGradientEndHex) this.els.globalBgGradientEndHex.value = g.bg_gradient_end || '#ffffff';
        if (this.els.globalBgImageUrl) this.els.globalBgImageUrl.value = g.bg_image_url || '';
        if (this.els.globalBgOverlayOpacity) {
            this.els.globalBgOverlayOpacity.value = String(g.bg_overlay_opacity != null ? g.bg_overlay_opacity : 60);
            if (this.els.globalBgOverlayOpacityVal) this.els.globalBgOverlayOpacityVal.textContent = String(g.bg_overlay_opacity != null ? g.bg_overlay_opacity : 60);
        }
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
        this.syncBgStylePanels();
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
                block.design = this._normalizeBlockDesign('hero', item.design);
                break;
            case 'heading':
                block.text = item.text || item.title || '見出し';
                block.size = item.size || 'medium';
                block.text_color = item.text_color || '#1f2937';
                block.design = this._normalizeBlockDesign('heading', item.design);
                break;
            case 'text':
                block.text = item.text || item.body || 'テキスト';
                block.text_color = item.text_color || '#4b5563';
                block.font_size = item.font_size || 13;
                block.design = this._normalizeBlockDesign('text', item.design);
                break;
            case 'image':
                block.image_url = item.image_url || 'https://picsum.photos/seed/img1/750/400';
                block.alt = item.alt || 'サンプル画像';
                block.fit = item.fit || 'cover';
                block.design = this._normalizeBlockDesign('image', item.design);
                break;
            case 'button':
                block.text = item.text || item.button_label || 'ボタン';
                block.color = item.color || '#06C755';
                block.text_color = item.text_color || '#ffffff';
                block.action = item.action || '';
                block.full_width = item.full_width !== false;
                block.text_align = 'center';
                block.design = this._normalizeBlockDesign('button', item.design);
                break;
            case 'card':
                block.title = item.title || '本日のおすすめ';
                block.description = item.description || 'シェフが厳選した季節の素材を使った一品です。';
                block.image_url = item.image_url || 'https://picsum.photos/seed/card1/600/300';
                block.image_height = item.image_height || 140;
                block.design = this._normalizeBlockDesign('card', item.design);
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
                block.design = this._normalizeBlockDesign('collection', item.design);
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
                block.design = this._normalizeBlockDesign('slider', item.design);
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
                block.design = this._normalizeBlockDesign('banner', item.design);
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
                block.card_design = this._normalizeCardDesign(item.card_design);
                block.design = this._normalizeBlockDesign('data', item.design);
                break;
            case 'member_summary':
                block.greeting = item.greeting || '';
                block.design = this._normalizeBlockDesign('member_summary', item.design);
                break;
            case 'quick_actions':
                block.design = this._normalizeBlockDesign('quick_actions', item.design);
                block.actions = Array.isArray(item.actions) && item.actions.length > 0 ? item.actions : [
                    { icon: 'fa-utensils', label: '注文', action: '#products' },
                    { icon: 'fa-ticket-alt', label: 'クーポン', action: '#coupons' },
                    { icon: 'fa-stamp', label: 'スタンプ', action: '#stamps' },
                    { icon: 'fa-calendar-check', label: '予約', action: '#reservations' },
                ];
                break;
            case 'hours':
                block.title = item.title || '営業時間';
                block.note = item.note || '';
                block.rows = Array.isArray(item.rows) && item.rows.length > 0 ? item.rows : [
                    { day: '月〜金', time: '11:00 - 21:00' },
                    { day: '土・日・祝', time: '10:00 - 22:00' },
                    { day: '定休日', time: '水曜日' },
                ];
                block.design = this._normalizeBlockDesign('hours', item.design);
                break;
            case 'access':
                block.title = item.title || '店舗情報';
                block.address = item.address || '東京都渋谷区○○ 1-2-3';
                block.phone = item.phone || '03-1234-5678';
                block.map_url = item.map_url || '';
                block.note = item.note || '';
                block.design = this._normalizeBlockDesign('access', item.design);
                break;
            case 'social_links':
                block.links = Array.isArray(item.links) && item.links.length > 0 ? item.links : [
                    { type: 'instagram', url: 'https://instagram.com/' },
                    { type: 'x', url: 'https://x.com/' },
                    { type: 'line', url: 'https://line.me/' },
                ];
                break;
            case 'faq':
                block.title = item.title || 'よくある質問';
                block.items = Array.isArray(item.items) && item.items.length > 0 ? item.items : [
                    { q: '予約は必要ですか？', a: '当日のご来店も歓迎ですが、ご予約いただくとスムーズです。' },
                    { q: '支払い方法は？', a: '現金・クレジットカード・QR決済がご利用いただけます。' },
                ];
                block.design = this._normalizeBlockDesign('faq', item.design);
                break;
            case 'video':
                block.youtube_id = item.youtube_id || '';
                block.caption = item.caption || '';
                break;
            case 'button_group':
                block.buttons = Array.isArray(item.buttons) && item.buttons.length > 0 ? item.buttons : [
                    { text: '予約する', action: '#reservations', variant: 'solid', color: '' },
                    { text: 'メニュー', action: '#products', variant: 'outline', color: '' },
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
            menuItemsJson: document.getElementById('menuItemsJson'),
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
            globalCardStyle: document.getElementById('globalCardStyle'),
            globalButtonShape: document.getElementById('globalButtonShape'),
            globalDensity: document.getElementById('globalDensity'),
            globalSystemUiStyle: document.getElementById('globalSystemUiStyle'),
            globalSystemCardStyle: document.getElementById('globalSystemCardStyle'),
            globalSystemMediaShape: document.getElementById('globalSystemMediaShape'),
            globalSystemHeaderStyle: document.getElementById('globalSystemHeaderStyle'),
            globalMemberCardDesign: document.getElementById('globalMemberCardDesign'),
            globalMemberScreenDesign: document.getElementById('globalMemberScreenDesign'),
            globalCouponScreenDesign: document.getElementById('globalCouponScreenDesign'),
            globalReservationScreenDesign: document.getElementById('globalReservationScreenDesign'),
            globalActivityScreenDesign: document.getElementById('globalActivityScreenDesign'),
            globalContentWidth: document.getElementById('globalContentWidth'),
            globalBgColor: document.getElementById('globalBgColor'),
            globalBgColorHex: document.getElementById('globalBgColorHex'),
            globalBgGradientStart: document.getElementById('globalBgGradientStart'),
            globalBgGradientStartHex: document.getElementById('globalBgGradientStartHex'),
            globalBgGradientEnd: document.getElementById('globalBgGradientEnd'),
            globalBgGradientEndHex: document.getElementById('globalBgGradientEndHex'),
            globalBgImageUrl: document.getElementById('globalBgImageUrl'),
            globalBgOverlayOpacity: document.getElementById('globalBgOverlayOpacity'),
            globalBgOverlayOpacityVal: document.getElementById('globalBgOverlayOpacityVal'),
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
    updateUndoRedo() {
        if (this.els.undoBtn) this.els.undoBtn.disabled = this.historyIndex <= 0;
        if (this.els.redoBtn) this.els.redoBtn.disabled = this.historyIndex >= this.history.length - 1;
    }

    /* ========== Rendering ========== */

    render() {
        try { this.renderScreenList(); } catch (err) { console.warn('renderScreenList failed', err); }
        this.scheduleTabTemplateList();
        try { this.renderPhoneNav(); } catch (err) { console.warn('renderPhoneNav failed', err); }
        try { this.renderPhoneContent(); } catch (err) { console.warn('renderPhoneContent failed', err); }
        try { this.renderPaletteCategories(); } catch (err) { console.warn('renderPaletteCategories failed', err); }
        try { this.renderBlockPalette(); } catch (err) { console.warn('renderBlockPalette failed', err); }
        try { this.renderBlockSettings(); } catch (err) { console.warn('renderBlockSettings failed', err); }
        try { this.applyScreenSettings(); } catch (err) { console.warn('applyScreenSettings failed', err); }
        try { this.applyPreviewDevice(); } catch (err) { console.warn('applyPreviewDevice failed', err); }
        try { this.applyZoom(); } catch (err) { console.warn('applyZoom failed', err); }
        try { this.applyCustomCssPreview(); } catch (err) { console.warn('applyCustomCssPreview failed', err); }
    }

    scheduleTabTemplateList() {
        if (!this.els || !this.els.tabTemplateList) return;
        if (this._tabTplTimer) clearTimeout(this._tabTplTimer);
        this.els.tabTemplateList.innerHTML = '<div class="ld-tab-template-loading"><i class="fas fa-circle-notch fa-spin"></i> 読み込み中…</div>';
        const self = this;
        this._tabTplTimer = setTimeout(function() {
            try {
                self.renderTabTemplateList();
            } catch (err) {
                console.warn('renderTabTemplateList failed', err);
                if (self.els.tabTemplateList) {
                    self.els.tabTemplateList.innerHTML = '<div class="ld-tab-template-empty">テンプレート一覧の表示に失敗しました。</div>';
                }
            }
        }, 48);
    }

    renderScreenList() {
        const c = this.els.screenList;
        if (!c) return;
        c.innerHTML = '';
        const items = (this.state.menuItems || []).map(m => m.tab).concat(['announcements']);
        items.forEach((key, idx) => {
            const menuItem = this.getMenuItem(key);
            const isMenuTab = menuItem !== null;
            const active = this.state.activeTab === key;
            const featureEnabled = this.isTabEnabled(key);
            const menuEnabled = !isMenuTab || menuItem.enabled;
            const count = (this.state.layouts[key] || []).length;
            const el = document.createElement('div');
            el.className = 'ld-screen-item' + (active ? ' is-active' : '') + (featureEnabled ? '' : ' is-disabled') + (menuEnabled ? '' : ' is-hidden-tab');
            const badge = featureEnabled ? '' : '<span class="ld-screen-item__badge">OFF</span>';

            let controls = '';
            if (isMenuTab && featureEnabled) {
                controls =
                    '<span class="ld-screen-item__controls">' +
                        '<button type="button" class="ld-screen-item__ctrl" data-menu-move="-1" title="上へ"' + (idx === 0 ? ' disabled' : '') + '><i class="fas fa-chevron-up"></i></button>' +
                        '<button type="button" class="ld-screen-item__ctrl" data-menu-move="1" title="下へ"' + (idx >= this.state.menuItems.length - 1 ? ' disabled' : '') + '><i class="fas fa-chevron-down"></i></button>' +
                        '<button type="button" class="ld-screen-item__ctrl" data-menu-edit title="名前・アイコンを変更"><i class="fas fa-pen"></i></button>' +
                        '<button type="button" class="ld-screen-item__ctrl' + (menuEnabled ? '' : ' is-off') + '" data-menu-toggle title="' + (menuEnabled ? 'タブを非表示にする' : 'タブを表示する') + '"><i class="fas ' + (menuEnabled ? 'fa-eye' : 'fa-eye-slash') + '"></i></button>' +
                    '</span>';
            }

            el.innerHTML = '<div class="ld-screen-item__icon"><i class="fas ' + this.esc(this.tabIcon(key)) + '"></i></div><span class="ld-screen-item__label">' + this.esc(this.tabLabel(key)) + '</span>' + badge + controls + '<span class="ld-screen-item__count">' + count + '</span>';

            el.addEventListener('click', (e) => {
                const ctrl = e.target.closest('[data-menu-move],[data-menu-toggle],[data-menu-edit]');
                if (ctrl) {
                    e.stopPropagation();
                    if (ctrl.hasAttribute('data-menu-move')) this.moveMenuItem(key, parseInt(ctrl.dataset.menuMove, 10));
                    if (ctrl.hasAttribute('data-menu-toggle')) this.toggleMenuItem(key);
                    if (ctrl.hasAttribute('data-menu-edit')) this.openMenuItemEditor(key);
                    return;
                }
                this.state.activeTab = key; this.state.selectedId = null; this.render();
            });
            c.appendChild(el);
        });
    }

    /** タブの名前・アイコンを変更する小型モーダル */
    openMenuItemEditor(tab) {
        const item = this.getMenuItem(tab);
        if (!item) return;
        const iconChoices = [
            'fa-house', 'fa-utensils', 'fa-mug-hot', 'fa-receipt', 'fa-stamp', 'fa-ticket-alt',
            'fa-list-ol', 'fa-calendar-check', 'fa-id-card', 'fa-images', 'fa-gift', 'fa-star',
            'fa-heart', 'fa-shopping-bag', 'fa-cut', 'fa-spa', 'fa-dumbbell', 'fa-bread-slice',
        ];
        const modal = document.createElement('div');
        modal.className = 'ld-modal';
        modal.innerHTML =
            '<div class="ld-modal__dialog ld-modal__dialog--sm">' +
                '<div class="ld-modal__head">' +
                    '<h3 class="ld-modal__title"><i class="fas fa-pen"></i> タブの名前・アイコン</h3>' +
                    '<button type="button" class="ld-btn ld-btn--ghost" data-close-modal><i class="fas fa-times"></i></button>' +
                '</div>' +
                '<div class="ld-modal__body">' +
                    '<div class="ld-form-group">' +
                        '<label class="ld-form-label">タブの名前（最大6文字推奨）</label>' +
                        '<input type="text" class="ld-form-input" data-menu-label maxlength="12" value="' + this.esc(item.label) + '">' +
                    '</div>' +
                    '<div class="ld-form-group">' +
                        '<label class="ld-form-label">アイコン</label>' +
                        '<div class="ld-icon-grid" data-menu-icons>' +
                            iconChoices.map(ic => '<button type="button" class="ld-icon-grid__item' + (ic === item.icon ? ' is-active' : '') + '" data-icon="' + ic + '"><i class="fas ' + ic + '"></i></button>').join('') +
                        '</div>' +
                    '</div>' +
                    '<div class="ld-form-group" style="text-align:right;">' +
                        '<button type="button" class="ld-btn ld-btn--outline" data-menu-reset>初期値に戻す</button> ' +
                        '<button type="button" class="ld-btn ld-btn--primary" data-menu-save>反映する</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        let selectedIcon = item.icon;
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('[data-close-modal]')) { modal.remove(); return; }
            const iconBtn = e.target.closest('[data-icon]');
            if (iconBtn) {
                selectedIcon = iconBtn.dataset.icon;
                modal.querySelectorAll('[data-icon]').forEach(b => b.classList.toggle('is-active', b === iconBtn));
                return;
            }
            if (e.target.closest('[data-menu-reset]')) {
                item.label = this.TAB_LABELS[tab];
                item.icon = this.TAB_ICONS[tab];
                this.renderScreenList();
                this.renderPhoneNav();
                modal.remove();
                return;
            }
            if (e.target.closest('[data-menu-save]')) {
                const input = modal.querySelector('[data-menu-label]');
                const label = (input.value || '').trim();
                if (label) item.label = label;
                item.icon = selectedIcon;
                this.renderScreenList();
                this.renderPhoneNav();
                modal.remove();
            }
        });
        document.body.appendChild(modal);
        modal.querySelector('[data-menu-label]').focus();
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
        const c = this.els.phoneNav;
        if (!c) return;
        c.innerHTML = '';
        const navTabs = (this.state.menuItems || [])
            .filter(m => m.enabled && this.isTabEnabled(m.tab))
            .map(m => m.tab);
        navTabs.forEach(key => {
            const el = document.createElement('div');
            el.className = 'ld-phone__nav-item' + (this.state.activeTab === key ? ' is-active' : '');
            el.innerHTML = '<i class="fas ' + this.esc(this.tabIcon(key)) + '"></i><span>' + this.esc(this.tabLabel(key)) + '</span>';
            el.addEventListener('click', () => {
                this.state.activeTab = key;
                this.state.selectedId = null;
                this.render();
            });
            c.appendChild(el);
        });
        if (this.els.phoneHeaderTitle) this.els.phoneHeaderTitle.textContent = this.tabLabel(this.state.activeTab);
        if (this.els.activeTabLabel) {
            this.els.activeTabLabel.textContent = this.tabLabel(this.state.activeTab);
        }
    }

    renderPhoneContent() {
        const c = this.els.phoneContent;
        if (!c) return;
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
            case 'hours': this.buildHours(el, block); break;
            case 'access': this.buildAccess(el, block); break;
            case 'social_links': this.buildSocialLinks(el, block); break;
            case 'faq': this.buildFaq(el, block); break;
            case 'video': this.buildVideo(el, block); break;
            case 'button_group': this.buildButtonGroup(el, block); break;
        }

        el.addEventListener('click', (e) => {
            if (e.target.closest('.ld-block__toolbar-btn') || e.target.closest('.ld-block__handle')) return;
            this.state.selectedId = block.id;
            this.renderPhoneContent();
            this.renderBlockSettings();
        });
        return el;
    }

    _blockDesigns() {
        return (window.LiffDesignerModules && window.LiffDesignerModules.blockDesigns) || null;
    }

    _normalizeBlockDesign(blockType, value) {
        const bd = this._blockDesigns();
        return bd ? bd.normalize(blockType, value) : (value || '');
    }

    _normalizeCardDesign(value) {
        const bd = this._blockDesigns();
        return bd ? bd.normalizeCardDesign(value) : (value || '');
    }

    _addBlockDesignPicker(panel, block, blockType) {
        const bd = this._blockDesigns();
        if (bd) bd.addDesignPicker(this, panel, block, blockType);
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
        const design = b.design || 'editorial';
        el.classList.add('ld-block--hero-design-' + design);
        if (b.image_url) { el.style.backgroundImage = "url('" + b.image_url + "')"; el.style.backgroundSize = 'cover'; el.style.backgroundPosition = 'center'; }
        else { el.style.background = 'linear-gradient(135deg,' + (b.gradient_start || '#667eea') + ' 0%,' + (b.gradient_end || '#764ba2') + ' 100%)'; }
        el.innerHTML += '<div class="ld-block__body"><div class="ld-block--hero-title">' + this.esc(b.title) + '</div><div class="ld-block--hero-sub">' + this.esc(b.subtitle) + '</div></div>';
    }

    buildHeading(el, b) {
        el.classList.add('ld-block--heading-design-' + (b.design || 'plain'));
        const sizes = { small: 14, medium: 16, large: 20 };
        el.style.fontSize = (sizes[b.size] || 16) + 'px';
        if (b.text_color) el.style.color = b.text_color;
        el.innerHTML += '<span class="ld-block__body">' + this.esc(b.text) + '</span>';
    }

    buildText(el, b) {
        el.classList.add('ld-block--text-design-' + (b.design || 'body'));
        if (b.font_size) el.style.fontSize = b.font_size + 'px';
        if (b.text_color) el.style.color = b.text_color;
        el.innerHTML += '<div class="ld-block__body">' + this.esc(b.text).replace(/\n/g, '<br>') + '</div>';
    }

    buildImage(el, b) {
        el.classList.add('ld-block--image-design-' + (b.design || 'rounded'));
        if (b.image_url) { el.innerHTML += '<div class="ld-block__body"><img src="' + this.esc(b.image_url) + '" alt="' + this.esc(b.alt) + '" style="object-fit:' + (b.fit || 'cover') + '"></div>'; }
        else { el.innerHTML += '<div class="ld-block__body ld-block--image-placeholder"><i class="fas fa-image"></i><span>画像URLを設定</span></div>'; }
    }

    buildButton(el, b) {
        const design = b.design || 'cta';
        const w = b.full_width ? 'width:100%;' : 'display:inline-block;';
        const outline = design === 'outline' ? 'background:transparent;border:2px solid ' + (b.color || '#06C755') + ';color:' + (b.color || '#06C755') + ';' : '';
        const soft = design === 'soft' ? 'background:' + (b.color || '#06C755') + '22;color:' + (b.color || '#06C755') + ';' : '';
        const fill = design === 'cta' ? 'background:' + (b.color || '#06C755') + ';color:' + (b.text_color || '#fff') + ';' : '';
        el.innerHTML += '<div class="ld-block__body"><div class="ld-block--button-inner ld-block--button-' + design + '" style="' + (outline || soft || fill) + w + '">' + this.esc(b.text) + '</div></div>';
    }

    buildCard(el, b) {
        const design = b.design || 'editorial';
        let img = '<div class="ld-block--card-image" style="height:' + (b.image_height || 140) + 'px">';
        img += b.image_url ? '<img src="' + this.esc(b.image_url) + '" alt="">' : '<i class="fas fa-image"></i>';
        img += '</div>';
        const body = '<div class="ld-block--card-body"><div class="ld-block--card-title">' + this.esc(b.title) + '</div><div class="ld-block--card-desc">' + this.esc(b.description) + '</div></div>';
        if (design === 'horizontal') {
            el.innerHTML += '<div class="ld-block__body ld-block--card-horizontal">' + img + body + '</div>';
            return;
        }
        if (design === 'overlay') {
            el.innerHTML += '<div class="ld-block__body ld-block--card-overlay">' + img + '<div class="ld-block--card-overlay-text">' + this.esc(b.title) + '</div></div>';
            return;
        }
        el.innerHTML += '<div class="ld-block__body">' + img + body + '</div>';
    }

    buildCollection(el, b) {
        el.classList.add('ld-block--collection-design-' + (b.design || 'product_shelf'));
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
        el.classList.add('ld-block--slider-design-' + (b.design || 'hero'));
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
        const design = b.design || 'promo';
        el.classList.add('ld-block--banner-' + design);
        if (design === 'outline') {
            el.style.background = 'transparent';
            el.style.color = b.color || '#3b82f6';
            el.style.border = '2px solid ' + (b.color || '#3b82f6');
        } else if (design === 'soft') {
            el.style.background = (b.color || '#3b82f6') + '18';
            el.style.color = b.color || '#3b82f6';
        } else {
            el.style.background = b.color || '#3b82f6';
            el.style.color = b.text_color || '#fff';
        }
        const pill = design === 'pill' ? ' ld-banner--pill' : '';
        el.innerHTML += '<div class="ld-block__body ld-banner' + pill + '"><i class="fas ' + (b.icon || 'fa-info-circle') + '"></i><span>' + this.esc(b.text) + '</span></div>';
    }

    buildDivider(el, b) {
        el.innerHTML += '<div class="ld-block__body"><hr style="border-top:1px ' + (b.divider_style || 'solid') + ' ' + (b.divider_color || '#e5e7eb') + ';border-bottom:none;margin:0"></div>';
    }

    buildMemberSummary(el, b) {
        const design = b.design || 'rewards';
        const hello = b.greeting ? this.esc(b.greeting) : 'こんにちは、デモ 太郎 さん';
        const cls = 'ld-mock-summary ld-mock-summary--' + design;
        let inner = '';
        if (design === 'wallet') {
            inner = '<div class="ld-mock-summary__wallet-row">' +
                '<div><div class="ld-mock-summary__hello">' + hello + '</div><span class="ld-mock-summary__rank"><i class="fas fa-crown"></i> シルバー</span></div>' +
                '<div class="ld-mock-summary__wallet-pt"><span class="ld-mock-summary__pt">1,280</span><span class="ld-mock-summary__unit">pt</span></div>' +
                '<span class="ld-mock-summary__qr"><i class="fas fa-qrcode"></i></span></div>';
        } else if (design === 'compact') {
            inner = '<div class="ld-mock-summary__compact-row">' +
                '<span class="ld-mock-summary__rank"><i class="fas fa-crown"></i> シルバー</span>' +
                '<span class="ld-mock-summary__hello">' + hello + '</span>' +
                '<span class="ld-mock-summary__pt">1,280 pt</span></div>';
        } else if (design === 'emblem') {
            inner = '<div class="ld-mock-summary__emblem"><i class="fas fa-crown ld-mock-summary__emblem-icon"></i>' +
                '<div class="ld-mock-summary__hello">' + hello + '</div>' +
                '<span class="ld-mock-summary__rank">シルバー</span>' +
                '<div class="ld-mock-summary__pt">1,280 pt</div></div>';
        } else if (design === 'minimal') {
            inner = '<div class="ld-mock-summary__minimal"><div class="ld-mock-summary__hello">' + hello + '</div>' +
                '<div class="ld-mock-summary__minimal-pt">1,280 <span>pt</span></div></div>';
        } else if (design === 'split') {
            inner = '<div class="ld-mock-summary__split">' +
                '<div class="ld-mock-summary__split-left"><div class="ld-mock-summary__hello">' + hello + '</div><span class="ld-mock-summary__rank"><i class="fas fa-crown"></i> シルバー</span></div>' +
                '<div class="ld-mock-summary__split-right"><span class="ld-mock-summary__pt">1,280</span><span class="ld-mock-summary__unit">pt</span></div></div>';
        } else if (design === 'glass') {
            inner = '<div class="ld-mock-summary__glass">' +
                '<div class="ld-mock-summary__top"><div class="ld-mock-summary__hello">' + hello + '</div><span class="ld-mock-summary__rank"><i class="fas fa-crown"></i> シルバー</span></div>' +
                '<div class="ld-mock-summary__points"><span class="ld-mock-summary__pt">1,280</span><span class="ld-mock-summary__unit">pt</span></div></div>';
        } else {
            inner = '<div class="ld-mock-summary__top">' +
                '<div><div class="ld-mock-summary__hello">' + hello + '</div><span class="ld-mock-summary__rank"><i class="fas fa-crown"></i> シルバー</span></div>' +
                '<span class="ld-mock-summary__qr"><i class="fas fa-qrcode"></i></span></div>' +
                '<div class="ld-mock-summary__points"><span class="ld-mock-summary__pt">1,280</span><span class="ld-mock-summary__unit">pt</span></div>' +
                '<div class="ld-mock-summary__track"><span style="width:26%"></span></div>' +
                '<div class="ld-mock-summary__next">次の<strong>ゴールド</strong>まであと <strong>3,720</strong> pt</div>';
        }
        el.innerHTML += '<div class="ld-block__body"><div class="' + cls + '">' + inner + '</div></div>';
    }

    buildQuickActions(el, b) {
        const design = b.design || 'grid';
        const actions = Array.isArray(b.actions) ? b.actions : [];
        let tiles = '';
        actions.forEach(a => {
            tiles += '<div class="ld-mock-qa__tile"><span class="ld-mock-qa__icon"><i class="fas ' + this.esc(a.icon || 'fa-circle') + '"></i></span><span class="ld-mock-qa__label">' + this.esc(a.label || '') + '</span></div>';
        });
        el.innerHTML += '<div class="ld-block__body"><div class="ld-mock-qa ld-mock-qa--' + design + '">' + tiles + '</div></div>';
    }

    buildHours(el, b) {
        el.classList.add('ld-block--hours-design-' + (b.design || 'table'));
        const rows = Array.isArray(b.rows) ? b.rows : [];
        let html = '<div class="ld-block__body ld-store-card">';
        if (b.title) html += '<div class="ld-store-card__title"><i class="far fa-clock"></i> ' + this.esc(b.title) + '</div>';
        rows.forEach(r => {
            html += '<div class="ld-store-card__row"><span class="ld-store-card__key">' + this.esc(r.day || '') + '</span><span class="ld-store-card__val">' + this.esc(r.time || '') + '</span></div>';
        });
        if (b.note) html += '<div class="ld-store-card__note">' + this.esc(b.note) + '</div>';
        html += '</div>';
        el.innerHTML += html;
    }

    buildAccess(el, b) {
        el.classList.add('ld-block--access-design-' + (b.design || 'card'));
        let html = '<div class="ld-block__body ld-store-card">';
        if (b.title) html += '<div class="ld-store-card__title"><i class="fas fa-store"></i> ' + this.esc(b.title) + '</div>';
        if (b.address) html += '<div class="ld-store-card__line"><i class="fas fa-map-marker-alt"></i><span>' + this.esc(b.address) + '</span></div>';
        if (b.phone) html += '<div class="ld-store-card__line"><i class="fas fa-phone"></i><span class="ld-store-card__link">' + this.esc(b.phone) + '</span></div>';
        if (b.note) html += '<div class="ld-store-card__line"><i class="fas fa-info-circle"></i><span>' + this.esc(b.note) + '</span></div>';
        if (b.map_url) html += '<div class="ld-store-card__btn"><i class="fas fa-map"></i> 地図を開く</div>';
        html += '</div>';
        el.innerHTML += html;
    }

    buildSocialLinks(el, b) {
        const iconMap = {
            instagram: 'fab fa-instagram', x: 'fab fa-x-twitter', facebook: 'fab fa-facebook-f',
            youtube: 'fab fa-youtube', tiktok: 'fab fa-tiktok', line: 'fab fa-line', website: 'fas fa-globe',
        };
        const colorMap = { instagram: '#e1306c', facebook: '#1877f2', youtube: '#ff0000', line: '#06c755' };
        const links = Array.isArray(b.links) ? b.links : [];
        let html = '<div class="ld-block__body ld-social-preview">';
        links.forEach(l => {
            const color = colorMap[l.type] || '#374151';
            html += '<span class="ld-social-preview__icon" style="color:' + color + '"><i class="' + (iconMap[l.type] || 'fas fa-link') + '"></i></span>';
        });
        if (links.length === 0) html += '<span class="ld-store-card__note">SNSリンクを追加してください</span>';
        html += '</div>';
        el.innerHTML += html;
    }

    buildFaq(el, b) {
        el.classList.add('ld-block--faq-design-' + (b.design || 'accordion'));
        const items = Array.isArray(b.items) ? b.items : [];
        let html = '<div class="ld-block__body ld-store-card">';
        if (b.title) html += '<div class="ld-store-card__title"><i class="far fa-question-circle"></i> ' + this.esc(b.title) + '</div>';
        items.slice(0, 3).forEach(it => {
            html += '<div class="ld-store-card__row"><span class="ld-store-card__key" style="flex:1;">' + this.esc(it.q || '') + '</span><i class="fas fa-chevron-down" style="color:#9ca3af;font-size:10px;"></i></div>';
        });
        if (items.length > 3) html += '<div class="ld-store-card__note">ほか ' + (items.length - 3) + ' 件</div>';
        html += '</div>';
        el.innerHTML += html;
    }

    buildVideo(el, b) {
        const id = String(b.youtube_id || '');
        if (/^[a-zA-Z0-9_-]{11}$/.test(id)) {
            el.innerHTML += '<div class="ld-block__body ld-video-preview"><img src="https://i.ytimg.com/vi/' + id + '/hqdefault.jpg" alt=""><span class="ld-video-preview__play"><i class="fas fa-play"></i></span></div>' + (b.caption ? '<div class="ld-store-card__note" style="padding:8px 12px;">' + this.esc(b.caption) + '</div>' : '');
        } else {
            el.innerHTML += '<div class="ld-block__body ld-block--image-placeholder"><i class="fas fa-play-circle"></i><span>YouTube動画URLを設定</span></div>';
        }
    }

    buildButtonGroup(el, b) {
        const buttons = Array.isArray(b.buttons) ? b.buttons : [];
        let html = '<div class="ld-block__body ld-btn-group-preview">';
        buttons.forEach(btn => {
            const isOutline = btn.variant === 'outline';
            const color = btn.color || 'var(--primary-color, #06C755)';
            const style = isOutline
                ? 'background:transparent;border:1.5px solid ' + color + ';color:' + color + ';'
                : 'background:' + color + ';color:#fff;';
            html += '<span class="ld-btn-group-preview__btn" style="' + style + '">' + this.esc(btn.text || '') + '</span>';
        });
        html += '</div>';
        el.innerHTML += html;
    }

    buildData(el, b) {
        el.classList.add('ld-block--data-design-' + (b.design || 'cards'));
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
            { id: 'store', label: '店舗情報' },
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
            case 'hours': this.sHours(panel, block); break;
            case 'access': this.sAccess(panel, block); break;
            case 'social_links': this.sSocialLinks(panel, block); break;
            case 'faq': this.sFaq(panel, block); break;
            case 'video': this.sVideo(panel, block); break;
            case 'button_group': this.sButtonGroup(panel, block); break;
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
        this._addBlockDesignPicker(p, b, 'hero');
        this.addField(p, 'タイトル', b.title, v => { b.title = v; });
        this.addField(p, 'サブタイトル', b.subtitle, v => { b.subtitle = v; });
        this.addField(p, '背景画像URL', b.image_url, v => { b.image_url = v; });
        this.addColorField(p, 'グラデ開始', b.gradient_start, v => { b.gradient_start = v; });
        this.addColorField(p, 'グラデ終了', b.gradient_end, v => { b.gradient_end = v; });
    }

    sHeading(p, b) {
        this._addBlockDesignPicker(p, b, 'heading');
        this.addField(p, 'テキスト', b.text, v => { b.text = v; });
        this.addSel(p, 'サイズ', b.size, [{ value: 'small', label: '小' }, { value: 'medium', label: '中' }, { value: 'large', label: '大' }], v => { b.size = v; });
        this.addColorField(p, '文字色', b.text_color, v => { b.text_color = v; });
    }

    sText(p, b) {
        this._addBlockDesignPicker(p, b, 'text');
        this.addTextarea(p, 'テキスト', b.text, v => { b.text = v; });
        this.addRange(p, 'フォントサイズ', b.font_size, 10, 24, v => { b.font_size = v; });
        this.addColorField(p, '文字色', b.text_color, v => { b.text_color = v; });
    }

    sImage(p, b) {
        this._addBlockDesignPicker(p, b, 'image');
        this.addField(p, '画像URL', b.image_url, v => { b.image_url = v; });
        this.addField(p, '代替テキスト', b.alt, v => { b.alt = v; });
        this.addSel(p, 'フィット', b.fit, [{ value: 'cover', label: 'カバー' }, { value: 'contain', label: '全体表示' }, { value: 'fill', label: '引き伸ばし' }], v => { b.fit = v; });
    }

    sButton(p, b) {
        this._addBlockDesignPicker(p, b, 'button');
        this.addField(p, 'テキスト', b.text, v => { b.text = v; });
        this.addColorField(p, 'ボタン色', b.color, v => { b.color = v; });
        this.addColorField(p, '文字色', b.text_color, v => { b.text_color = v; });
        this.addActionField(p, 'タップしたときの動作', b.action, v => { b.action = v; });
        this.addCheckbox(p, '幅いっぱい', b.full_width, v => { b.full_width = v; });
    }

    sCard(p, b) {
        this._addBlockDesignPicker(p, b, 'card');
        this.addField(p, 'タイトル', b.title, v => { b.title = v; });
        this.addField(p, '説明', b.description, v => { b.description = v; });
        this.addField(p, '画像URL', b.image_url, v => { b.image_url = v; });
        this.addRange(p, '画像の高さ', b.image_height, 60, 300, v => { b.image_height = v; });
    }

    sCollection(p, b) {
        this._addBlockDesignPicker(p, b, 'collection');
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
        this._addBlockDesignPicker(p, b, 'slider');
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
        this._addBlockDesignPicker(p, b, 'banner');
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

    sData(p, b) {
        this._addBlockDesignPicker(p, b, 'data');
        this.addSel(p, 'データソース', b.source, this.DATA_SOURCES, v => {
            b.source = v;
            this.renderBlockSettings();
        });
        if (b.source === 'member_card' || b.source === 'member') {
            const bd = this._blockDesigns();
            if (bd) bd.addCardDesignPicker(this, p, b);
        }
    }

    sMemberSummary(p, b) {
        this._addBlockDesignPicker(p, b, 'member_summary');
        this.addField(p, 'あいさつ文（空欄で会員名を自動表示）', b.greeting, v => { b.greeting = v; });
        const note = document.createElement('div');
        note.className = 'ld-settings__hint';
        note.innerHTML = 'ポイント・ランク・進捗は会員データから自動取得されます。';
        p.appendChild(note);
    }

    sQuickActions(p, b) {
        this._addBlockDesignPicker(p, b, 'quick_actions');
        if (!Array.isArray(b.actions)) b.actions = [];
        this.addArrayItems(p, 'アクション', b.actions,
            () => ({ icon: 'fa-bolt', label: '新規', action: '#products' }),
            ['icon', 'label', 'action'],
            ['アイコン(fa-*)', 'ラベル', 'リンク(#tab)']);
    }

    sHours(p, b) {
        this._addBlockDesignPicker(p, b, 'hours');
        this.addField(p, 'タイトル', b.title, v => { b.title = v; });
        if (!Array.isArray(b.rows)) b.rows = [];
        this.addArrayItems(p, '営業時間の行', b.rows,
            () => ({ day: '曜日', time: '10:00 - 20:00' }),
            ['day', 'time'],
            ['曜日・区分', '時間']);
        this.addField(p, '補足（任意）', b.note, v => { b.note = v; });
    }

    sAccess(p, b) {
        this._addBlockDesignPicker(p, b, 'access');
        this.addField(p, 'タイトル', b.title, v => { b.title = v; });
        this.addField(p, '住所', b.address, v => { b.address = v; });
        this.addField(p, '電話番号', b.phone, v => { b.phone = v; });
        this.addField(p, 'GoogleマップURL', b.map_url, v => { b.map_url = v; });
        this.addField(p, '補足（任意）', b.note, v => { b.note = v; });
        const note = document.createElement('div');
        note.className = 'ld-settings__hint';
        note.textContent = '電話番号はタップで発信、地図URLはタップで地図アプリが開きます。';
        p.appendChild(note);
    }

    sSocialLinks(p, b) {
        if (!Array.isArray(b.links)) b.links = [];
        const socialTypes = [
            { value: 'instagram', label: 'Instagram' }, { value: 'x', label: 'X (Twitter)' },
            { value: 'facebook', label: 'Facebook' }, { value: 'youtube', label: 'YouTube' },
            { value: 'tiktok', label: 'TikTok' }, { value: 'line', label: 'LINE' },
            { value: 'website', label: '公式サイト' },
        ];
        const g = this.fg('SNSリンク');
        const list = document.createElement('div'); list.className = 'ld-array-list';
        const rebuild = () => {
            list.innerHTML = '';
            b.links.forEach((link, i) => {
                const row = document.createElement('div'); row.className = 'ld-array-item';
                const hdr = document.createElement('div'); hdr.className = 'ld-array-item__header';
                hdr.innerHTML = '<span class="ld-array-item__num">#' + (i + 1) + '</span>';
                const del = document.createElement('button'); del.type = 'button'; del.className = 'ld-btn ld-btn--ghost ld-btn--xs';
                del.innerHTML = '<i class="fas fa-times"></i>';
                del.addEventListener('click', () => { b.links.splice(i, 1); rebuild(); this.onBlockChange(); });
                hdr.appendChild(del);
                row.appendChild(hdr);

                const typeGroup = document.createElement('div'); typeGroup.className = 'ld-form-group';
                const typeLabel = document.createElement('label'); typeLabel.className = 'ld-form-label'; typeLabel.textContent = '種類';
                const sel = document.createElement('select'); sel.className = 'ld-form-select';
                socialTypes.forEach(o => { const op = document.createElement('option'); op.value = o.value; op.textContent = o.label; if (o.value === link.type) op.selected = true; sel.appendChild(op); });
                sel.addEventListener('change', () => { link.type = sel.value; this.onBlockChange(); });
                typeGroup.appendChild(typeLabel); typeGroup.appendChild(sel); row.appendChild(typeGroup);

                const urlGroup = document.createElement('div'); urlGroup.className = 'ld-form-group';
                const urlLabel = document.createElement('label'); urlLabel.className = 'ld-form-label'; urlLabel.textContent = 'URL';
                const inp = document.createElement('input'); inp.type = 'text'; inp.className = 'ld-form-input'; inp.value = link.url || '';
                inp.addEventListener('input', () => { link.url = inp.value; this.onBlockChange(); });
                urlGroup.appendChild(urlLabel); urlGroup.appendChild(inp); row.appendChild(urlGroup);

                list.appendChild(row);
            });
        };
        rebuild();
        g.appendChild(list);
        const addBtn = document.createElement('button'); addBtn.type = 'button';
        addBtn.className = 'ld-btn ld-btn--outline ld-btn--block';
        addBtn.innerHTML = '<i class="fas fa-plus"></i> 追加';
        addBtn.addEventListener('click', () => { b.links.push({ type: 'instagram', url: '' }); rebuild(); this.onBlockChange(); });
        g.appendChild(addBtn);
        p.appendChild(g);
    }

    sFaq(p, b) {
        this._addBlockDesignPicker(p, b, 'faq');
        this.addField(p, 'タイトル', b.title, v => { b.title = v; });
        if (!Array.isArray(b.items)) b.items = [];
        this.addArrayItems(p, '質問と回答', b.items,
            () => ({ q: '新しい質問', a: '回答を入力してください。' }),
            ['q', 'a'],
            ['質問', '回答']);
    }

    sVideo(p, b) {
        this.addField(p, 'YouTube動画のURL または ID', b.youtube_id, v => { b.youtube_id = this.extractYoutubeId(v); });
        this.addField(p, 'キャプション（任意）', b.caption, v => { b.caption = v; });
        const note = document.createElement('div');
        note.className = 'ld-settings__hint';
        note.textContent = '例: https://www.youtube.com/watch?v=XXXXXXXXXXX を貼り付けるだけでOKです。';
        p.appendChild(note);
    }

    extractYoutubeId(value) {
        const s = String(value || '').trim();
        if (/^[a-zA-Z0-9_-]{11}$/.test(s)) return s;
        const m = s.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
        return m ? m[1] : s;
    }

    sButtonGroup(p, b) {
        if (!Array.isArray(b.buttons)) b.buttons = [];
        const g = this.fg('ボタン（最大3個）');
        const list = document.createElement('div'); list.className = 'ld-array-list';
        const rebuild = () => {
            list.innerHTML = '';
            b.buttons.forEach((btn, i) => {
                const row = document.createElement('div'); row.className = 'ld-array-item';
                const hdr = document.createElement('div'); hdr.className = 'ld-array-item__header';
                hdr.innerHTML = '<span class="ld-array-item__num">#' + (i + 1) + '</span>';
                const del = document.createElement('button'); del.type = 'button'; del.className = 'ld-btn ld-btn--ghost ld-btn--xs';
                del.innerHTML = '<i class="fas fa-times"></i>';
                del.addEventListener('click', () => { b.buttons.splice(i, 1); rebuild(); this.onBlockChange(); });
                hdr.appendChild(del);
                row.appendChild(hdr);

                this.addField(row, 'テキスト', btn.text, v => { btn.text = v; });
                this.addActionField(row, '動作', btn.action, v => { btn.action = v; });
                this.addSel(row, 'スタイル', btn.variant || 'solid', [
                    { value: 'solid', label: '塗りつぶし' }, { value: 'outline', label: '枠線のみ' },
                ], v => { btn.variant = v; });

                list.appendChild(row);
            });
        };
        rebuild();
        g.appendChild(list);
        const addBtn = document.createElement('button'); addBtn.type = 'button';
        addBtn.className = 'ld-btn ld-btn--outline ld-btn--block';
        addBtn.innerHTML = '<i class="fas fa-plus"></i> 追加';
        addBtn.addEventListener('click', () => {
            if (b.buttons.length >= 3) return;
            b.buttons.push({ text: 'ボタン', action: '', variant: 'solid', color: '' });
            rebuild(); this.onBlockChange();
        });
        g.appendChild(addBtn);
        p.appendChild(g);
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

    /** アクション（'' / '#tab' / URL / tel: / mailto:）を種類選択+入力欄で編集するフィールド */
    addActionField(p, label, val, fn) {
        const g = this.fg(label);
        const parse = (action) => {
            const s = String(action || '');
            if (s === '') return { kind: '', param: '' };
            if (s.startsWith('#')) return { kind: 'tab', param: s.slice(1) };
            if (/^tel:/i.test(s)) return { kind: 'tel', param: s.replace(/^tel:/i, '') };
            if (/^mailto:/i.test(s)) return { kind: 'mailto', param: s.replace(/^mailto:/i, '') };
            return { kind: 'url', param: s };
        };
        const current = parse(val);

        const kindSel = document.createElement('select');
        kindSel.className = 'ld-form-select';
        this.ACTION_TYPES.forEach(o => {
            const op = document.createElement('option');
            op.value = o.value; op.textContent = o.label;
            if (o.value === current.kind) op.selected = true;
            kindSel.appendChild(op);
        });
        g.appendChild(kindSel);

        const paramWrap = document.createElement('div');
        paramWrap.style.marginTop = '6px';
        g.appendChild(paramWrap);

        const emit = (kind, param) => {
            if (kind === '') { fn(''); }
            else if (kind === 'tab') { fn(param ? '#' + param : ''); }
            else if (kind === 'tel') { fn(param ? 'tel:' + param : ''); }
            else if (kind === 'mailto') { fn(param ? 'mailto:' + param : ''); }
            else { fn(param || ''); }
            this.onBlockChange();
        };

        const buildParam = (kind, param) => {
            paramWrap.innerHTML = '';
            if (kind === '') return;
            if (kind === 'tab') {
                const sel = document.createElement('select');
                sel.className = 'ld-form-select';
                Object.keys(this.TAB_LABELS).filter(k => k !== 'announcements').forEach(k => {
                    const op = document.createElement('option');
                    op.value = k; op.textContent = this.TAB_LABELS[k];
                    if (k === param) op.selected = true;
                    sel.appendChild(op);
                });
                if (!param) emit('tab', sel.value);
                sel.addEventListener('change', () => emit('tab', sel.value));
                paramWrap.appendChild(sel);
                return;
            }
            const inp = document.createElement('input');
            inp.type = 'text';
            inp.className = 'ld-form-input';
            inp.value = param || '';
            inp.placeholder = kind === 'tel' ? '例: 0312345678' : (kind === 'mailto' ? '例: info@example.com' : '例: https://example.com');
            inp.addEventListener('input', () => emit(kind, inp.value.trim()));
            paramWrap.appendChild(inp);
        };

        buildParam(current.kind, current.param);
        kindSel.addEventListener('change', () => {
            buildParam(kindSel.value, '');
            if (kindSel.value === '') emit('', '');
        });

        p.appendChild(g);
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
        if (this.els.screenBgColor) this.els.screenBgColor.value = s.background_color || '#fafaf9';
        if (this.els.screenBgColorHex) this.els.screenBgColorHex.value = s.background_color || '#fafaf9';
        if (this.els.screenBgImage) this.els.screenBgImage.value = s.background_image || '';
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

    bindPanelDrawers() {
        return window.LiffDesignerModules.panelDrawers.bindPanelDrawers.call(this);
    }

    bindPanelResizer() {
        return window.LiffDesignerModules.panelResizer.bindPanelResizer.call(this);
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

    applyTabTemplate(tpl, options) {
        return window.LiffDesignerModules.templateManager.applyTabTemplate.call(this, tpl, options);
    }

    openTabTemplatePreview(tpl) {
        return window.LiffDesignerModules.templateManager.openTabTemplatePreview.call(this, tpl);
    }

    openTemplatePreview(tpl, options) {
        return window.LiffDesignerModules.templateManager.openTemplatePreview.call(this, tpl, options);
    }

    getTemplateThemeAccent(tpl) {
        return window.LiffDesignerModules.templateManager.getTemplateThemeAccent.call(this, tpl);
    }

    getTemplateBlocksForTab(tpl, scope, tabKey) {
        return window.LiffDesignerModules.templateManager.getTemplateBlocksForTab.call(this, tpl, scope, tabKey);
    }

    getBlockTypeMeta(type) {
        return window.LiffDesignerModules.templateManager.getBlockTypeMeta.call(this, type);
    }

    renderTemplateBlockChips(blocks) {
        return window.LiffDesignerModules.templateManager.renderTemplateBlockChips.call(this, blocks);
    }

    renderTemplateRichDemoBlock(block, accent) {
        return window.LiffDesignerModules.templateManager.renderTemplateRichDemoBlock.call(this, block, accent);
    }

    renderPreviewBlockListHtml(blocks) {
        return window.LiffDesignerModules.templateManager.renderPreviewBlockListHtml.call(this, blocks);
    }

    fillPreviewPhoneContent(contentEl, blocks, ctx) {
        return window.LiffDesignerModules.templateManager.fillPreviewPhoneContent.call(this, contentEl, blocks, ctx);
    }

    showApplySuccessFeedback(tpl, meta) {
        return window.LiffDesignerModules.templateManager.showApplySuccessFeedback.call(this, tpl, meta);
    }

    mountTemplatePreviews(container, templates, scope) {
        return window.LiffDesignerModules.templateManager.mountTemplatePreviews.call(this, container, templates, scope);
    }

    scheduleTemplatePreviews(container, templates, scope) {
        return window.LiffDesignerModules.templateManager.scheduleTemplatePreviews.call(this, container, templates, scope);
    }

    teardownTemplatePreviews() {
        return window.LiffDesignerModules.templateManager.teardownTemplatePreviews.call(this);
    }

    renderStaticTemplateMiniPreview(tpl) {
        return window.LiffDesignerModules.templateManager.renderStaticTemplateMiniPreview.call(this, tpl);
    }

    renderStaticPhonePreviewHtml(tpl, scope, tabKey, options) {
        return window.LiffDesignerModules.templateManager.renderStaticPhonePreviewHtml.call(this, tpl, scope, tabKey, options);
    }

    safeCreateLivePhonePreview(tpl, scope, tabKey, options) {
        return window.LiffDesignerModules.templateManager.safeCreateLivePhonePreview.call(this, tpl, scope, tabKey, options);
    }

    createLivePhonePreview(tpl, scope, tabKey, options) {
        return window.LiffDesignerModules.templateManager.createLivePhonePreview.call(this, tpl, scope, tabKey, options);
    }

    renderVisualPreviewMount(tpl, scope) {
        return window.LiffDesignerModules.templateManager.renderVisualPreviewMount.call(this, tpl, scope);
    }

    renderTemplateThemeMeta(tpl) {
        return window.LiffDesignerModules.templateManager.renderTemplateThemeMeta.call(this, tpl);
    }

    getTemplatePreviewTabs(tpl, scope) {
        return window.LiffDesignerModules.templateManager.getTemplatePreviewTabs.call(this, tpl, scope);
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

    renderTemplateDemoBlock(block, accent) {
        return window.LiffDesignerModules.templateManager.renderTemplateDemoBlock.call(this, block, accent);
    }

    renderTemplateDemoFallback(accent) {
        return window.LiffDesignerModules.templateManager.renderTemplateDemoFallback.call(this, accent);
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
