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

        this.idCounter = 1;
        this.state = { layouts: {}, tabSettings: {}, activeTab: 'home', selectedId: null };
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
        Object.keys(this.TAB_LABELS).forEach(key => {
            this.state.tabSettings[key] = rawTabSettings[key] || { background_color: '#f5f5f5' };
            this.state.layouts[key] = [];
        });
        Object.keys(rawLayouts).forEach(tab => {
            if (!Array.isArray(rawLayouts[tab])) return;
            this.state.layouts[tab] = rawLayouts[tab].map(item => this.normalizeBlock(item));
        });
    }

    normalizeBlock(item) {
        const id = item.id || this.nextId();
        const type = item.type || 'text';
        const block = { id, type, border_radius: item.border_radius ?? 10, padding: item.padding ?? null, margin_bottom: item.margin_bottom ?? 0, text_align: item.text_align || 'left', background_color: item.background_color || '', opacity: item.opacity ?? 100, shadow: item.shadow ?? false };

        switch (type) {
            case 'hero':
                block.title = item.title || 'タイトル';
                block.subtitle = item.subtitle || item.text || '';
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
                block.image_url = item.image_url || '';
                block.alt = item.alt || '';
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
                block.title = item.title || 'タイトル';
                block.description = item.description || '';
                block.image_url = item.image_url || '';
                block.image_height = item.image_height || 140;
                break;
            case 'collection':
                block.items = item.items || [
                    { title: 'アイテム1', image_url: '', description: '' },
                    { title: 'アイテム2', image_url: '', description: '' },
                    { title: 'アイテム3', image_url: '', description: '' },
                ];
                block.item_width = item.item_width || 140;
                block.item_image_height = item.item_image_height || 100;
                block.show_description = item.show_description !== false;
                break;
            case 'slider':
                block.slides = item.slides || [
                    { image_url: '', caption: 'スライド1' },
                    { image_url: '', caption: 'スライド2' },
                    { image_url: '', caption: 'スライド3' },
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
                block.items = item.items || ['項目1', '項目2', '項目3'];
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
                block.source = item.source || 'products';
                break;
            default:
                block.text = item.text || '';
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
        this.renderPhoneNav();
        this.renderPhoneContent();
        this.renderBlockPalette();
        this.renderBlockSettings();
        this.applyScreenSettings();
    }

    renderScreenList() {
        const c = this.els.screenList; c.innerHTML = '';
        Object.keys(this.TAB_LABELS).forEach(key => {
            const active = this.state.activeTab === key;
            const count = (this.state.layouts[key] || []).length;
            const el = document.createElement('div');
            el.className = 'ld-screen-item' + (active ? ' is-active' : '');
            el.innerHTML = '<div class="ld-screen-item__icon"><i class="fas ' + (this.TAB_ICONS[key] || 'fa-circle') + '"></i></div><span class="ld-screen-item__label">' + this.esc(this.TAB_LABELS[key]) + '</span><span class="ld-screen-item__count">' + count + '</span>';
            el.addEventListener('click', () => { this.state.activeTab = key; this.state.selectedId = null; this.render(); });
            c.appendChild(el);
        });
    }

    renderPhoneNav() {
        const c = this.els.phoneNav; c.innerHTML = '';
        Object.keys(this.TAB_LABELS).forEach(key => {
            const el = document.createElement('div');
            el.className = 'ld-phone__nav-item' + (this.state.activeTab === key ? ' is-active' : '');
            el.innerHTML = '<i class="fas ' + (this.TAB_ICONS[key] || 'fa-circle') + '"></i><span>' + this.esc(this.TAB_LABELS[key]) + '</span>';
            c.appendChild(el);
        });
        this.els.phoneHeaderTitle.textContent = this.TAB_LABELS[this.state.activeTab] || '';
    }

    renderPhoneContent() {
        const c = this.els.phoneContent;
        const blocks = this.getBlocks();
        c.innerHTML = '';
        const s = this.getSettings();
        c.style.background = s.background_color || '#f5f5f5';
        c.style.backgroundImage = s.background_image ? "url('" + s.background_image + "')" : '';
        if (s.background_image) { c.style.backgroundSize = 'cover'; c.style.backgroundPosition = 'center'; }

        if (blocks.length === 0) {
            c.innerHTML = '<div class="ld-phone__empty"><i class="fas fa-hand-pointer"></i><div class="ld-phone__empty-title">ブロックを追加しましょう</div><div class="ld-phone__empty-hint">右のパネルから<strong>ドラッグ</strong>または<strong>クリック</strong>で追加</div></div>';
            return;
        }
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
        let html = '<div class="ld-block__body ld-collection" style="--item-w:' + (b.item_width || 140) + 'px">';
        items.forEach(item => {
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

    buildData(el, b) {
        const src = this.DATA_SOURCES.find(s => s.value === b.source);
        el.innerHTML += '<div class="ld-block__body"><i class="fas fa-database"></i>' + this.esc(src ? src.label : b.source) + '（自動表示）</div>';
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
        this.BLOCK_TYPES.forEach(bt => {
            const el = document.createElement('div');
            el.className = 'ld-palette-item';
            el.dataset.blockType = bt.type;
            el.title = bt.desc;
            el.innerHTML = '<div class="ld-palette-item__icon"><i class="fas ' + bt.icon + '"></i></div><span class="ld-palette-item__label">' + this.esc(bt.label) + '</span>';
            el.addEventListener('click', () => this.addBlock(bt.type));
            c.appendChild(el);
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

        this.addRange(panel, '角丸 (px)', block.border_radius, 0, 24, v => { block.border_radius = v; });
        this.addRange(panel, '余白 (px)', block.padding, 0, 32, v => { block.padding = v; });
        this.addRange(panel, '下マージン (px)', block.margin_bottom, 0, 32, v => { block.margin_bottom = v; });
        this.addRange(panel, '透明度 (%)', block.opacity, 0, 100, v => { block.opacity = v; });
        this.addColorField(panel, '背景色', block.background_color, v => { block.background_color = v; });
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
        this.addRange(p, 'アイテム幅', b.item_width, 80, 240, v => { b.item_width = v; });
        this.addRange(p, '画像の高さ', b.item_image_height, 40, 200, v => { b.item_image_height = v; });
        this.addCheckbox(p, '説明を表示', b.show_description, v => { b.show_description = v; });
        this.addArrayItems(p, 'アイテム', b.items, () => ({ title: '新規', image_url: '', description: '' }), ['title', 'image_url', 'description'], ['タイトル', '画像URL', '説明']);
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
        this.els.screenBgColor.value = s.background_color || '#f5f5f5';
        this.els.screenBgColorHex.value = s.background_color || '#f5f5f5';
        this.els.screenBgImage.value = s.background_image || '';
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
        document.addEventListener('pointerdown', this.handlePointerDown.bind(this));
        document.addEventListener('pointermove', this.handlePointerMove.bind(this));
        document.addEventListener('pointerup', this.handlePointerUp.bind(this));
        document.addEventListener('pointercancel', this.handlePointerUp.bind(this));
    }

    handlePointerDown(e) {
        const pi = e.target.closest('.ld-palette-item');
        const bh = e.target.closest('.ld-block__handle');
        if (!pi && !bh) return;
        e.preventDefault();
        this.drag.startX = e.clientX; this.drag.startY = e.clientY;
        if (pi) { this.drag.source = 'palette'; this.drag.blockType = pi.dataset.blockType; this.drag.sourceElement = pi; }
        else { const bl = bh.closest('.ld-block'); if (!bl) return; this.drag.source = 'reorder'; this.drag.blockId = bl.dataset.id; this.drag.sourceElement = bl; }
        this.drag.pendingStart = true;
    }

    handlePointerMove(e) {
        if (!this.drag.pendingStart && !this.drag.active) return;
        if (this.drag.pendingStart) {
            if (Math.abs(e.clientX - this.drag.startX) < 5 && Math.abs(e.clientY - this.drag.startY) < 5) return;
            this.drag.pendingStart = false; this.drag.active = true;
            document.body.classList.add('ld-is-dragging');
            this.createGhost(e);
            if (this.drag.source === 'reorder' && this.drag.sourceElement) this.drag.sourceElement.classList.add('ld-block--dragging');
            this.els.phoneContent.classList.add('ld-drop-zone-active');
        }
        if (!this.drag.active) return;
        if (this.drag.ghost) { this.drag.ghost.style.left = e.clientX + 'px'; this.drag.ghost.style.top = e.clientY + 'px'; }
        this.updateDropTarget(e);
        this.autoScroll(e);
    }

    handlePointerUp() {
        if (this.drag.pendingStart) { this.drag.pendingStart = false; this.resetDrag(); return; }
        if (!this.drag.active) return;
        const di = this.drag.dropIndex;
        if (this.drag.source === 'palette' && di >= 0) { this.addBlock(this.drag.blockType, di); }
        else if (this.drag.source === 'reorder' && di >= 0) {
            const blocks = this.getBlocks(); const fi = blocks.findIndex(b => b.id === this.drag.blockId);
            if (fi >= 0) { const [m] = blocks.splice(fi, 1); blocks.splice(di > fi ? di - 1 : di, 0, m); this.pushHistory(); this.renderPhoneContent(); this.renderScreenList(); }
        }
        this.resetDrag();
    }

    createGhost(e) {
        const g = document.createElement('div'); g.className = 'ld-drag-ghost';
        const bt = this.drag.source === 'palette' ? this.BLOCK_TYPES.find(b => b.type === this.drag.blockType) : (() => { const bl = this.findBlock(this.drag.blockId); return bl ? this.BLOCK_TYPES.find(b => b.type === bl.type) : null; })();
        g.innerHTML = '<i class="fas ' + (bt ? bt.icon : 'fa-cube') + '"></i> ' + this.esc(bt ? bt.label : '');
        g.style.left = e.clientX + 'px'; g.style.top = e.clientY + 'px';
        document.body.appendChild(g); this.drag.ghost = g;
        requestAnimationFrame(() => g.classList.add('ld-drag-ghost--visible'));
    }

    updateDropTarget(e) {
        this.clearDropIndicators();
        const body = this.els.phoneBody; const r = body.getBoundingClientRect();
        if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) { this.drag.dropIndex = -1; return; }
        const blocks = this.els.phoneContent.querySelectorAll('.ld-block');
        if (blocks.length === 0) { this.drag.dropIndex = 0; this.els.phoneContent.classList.add('ld-drop-zone-highlight'); return; }
        let ci = blocks.length, cd = Infinity;
        blocks.forEach((b, i) => { const br = b.getBoundingClientRect(); const d = Math.abs(e.clientY - (br.top + br.height / 2)); if (d < cd) { cd = d; ci = e.clientY < br.top + br.height / 2 ? i : i + 1; } });
        if (this.drag.source === 'reorder') { const cur = this.getBlocks().findIndex(b => b.id === this.drag.blockId); if (ci === cur || ci === cur + 1) { this.drag.dropIndex = -1; return; } }
        this.drag.dropIndex = ci;
        const ind = document.createElement('div'); ind.className = 'ld-drop-indicator';
        ind.innerHTML = '<div class="ld-drop-indicator__dot"></div><div class="ld-drop-indicator__line"></div><div class="ld-drop-indicator__dot"></div>';
        if (ci < blocks.length) blocks[ci].parentNode.insertBefore(ind, blocks[ci]); else this.els.phoneContent.appendChild(ind);
        requestAnimationFrame(() => ind.classList.add('ld-drop-indicator--visible'));
    }

    clearDropIndicators() { document.querySelectorAll('.ld-drop-indicator').forEach(e => e.remove()); this.els.phoneContent.classList.remove('ld-drop-zone-highlight'); }

    autoScroll(e) {
        const b = this.els.phoneBody; const r = b.getBoundingClientRect();
        if (this.drag.scrollTimer) { cancelAnimationFrame(this.drag.scrollTimer); this.drag.scrollTimer = null; }
        const doUp = () => { b.scrollTop -= 8; if (this.drag.active) this.drag.scrollTimer = requestAnimationFrame(doUp); };
        const doDown = () => { b.scrollTop += 8; if (this.drag.active) this.drag.scrollTimer = requestAnimationFrame(doDown); };
        if (e.clientY < r.top + 40 && e.clientY > r.top) this.drag.scrollTimer = requestAnimationFrame(doUp);
        else if (e.clientY > r.bottom - 40 && e.clientY < r.bottom) this.drag.scrollTimer = requestAnimationFrame(doDown);
    }

    resetDrag() {
        if (this.drag.ghost) this.drag.ghost.remove();
        if (this.drag.scrollTimer) cancelAnimationFrame(this.drag.scrollTimer);
        if (this.drag.sourceElement) this.drag.sourceElement.classList.remove('ld-block--dragging');
        this.clearDropIndicators();
        document.body.classList.remove('ld-is-dragging');
        this.els.phoneContent.classList.remove('ld-drop-zone-active');
        this.drag = { active: false, source: null, blockType: null, blockId: null, ghost: null, dropIndex: -1, startX: 0, startY: 0, scrollTimer: null, pendingStart: false, sourceElement: null };
    }

    /* ========== Events ========== */

    bindEvents() {
        this.els.closeSettings.addEventListener('click', () => { this.state.selectedId = null; this.renderPhoneContent(); this.renderBlockSettings(); });
        this.els.screenBgColor.addEventListener('input', () => this.onScreenChange());
        this.els.screenBgColorHex.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(this.els.screenBgColorHex.value)) { this.els.screenBgColor.value = this.els.screenBgColorHex.value; this.onScreenChange(); } });
        this.els.screenBgImage.addEventListener('input', () => this.onScreenChange());
        this.els.form.addEventListener('submit', () => { this.els.layoutsJson.value = JSON.stringify(this.state.layouts); this.els.tabSettingsJson.value = JSON.stringify(this.state.tabSettings); if (this.els.customCssInput && this.els.customCssEditor) this.els.customCssInput.value = this.els.customCssEditor.value; });
        this.els.toggleCssBtn.addEventListener('click', () => { this.els.cssSection.hidden = !this.els.cssSection.hidden; });
        this.els.undoBtn.addEventListener('click', () => this.undo());
        this.els.redoBtn.addEventListener('click', () => this.redo());
        this.els.previewBtn.addEventListener('click', () => this.openPreview());
        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'z') { e.preventDefault(); e.shiftKey ? this.redo() : this.undo(); }
            if (this.state.selectedId && (e.key === 'Delete' || e.key === 'Backspace')) { const t = e.target.tagName; if (t === 'INPUT' || t === 'TEXTAREA' || t === 'SELECT') return; e.preventDefault(); this.deleteBlock(this.state.selectedId); }
        });
        this.els.phoneContent.addEventListener('click', (e) => { if (e.target === this.els.phoneContent || e.target.closest('.ld-phone__empty')) { this.state.selectedId = null; this.renderPhoneContent(); this.renderBlockSettings(); } });
    }

    onScreenChange() {
        const s = this.getSettings(); s.background_color = this.els.screenBgColor.value; s.background_image = this.els.screenBgImage.value;
        this.els.screenBgColorHex.value = this.els.screenBgColor.value; this.state.tabSettings[this.state.activeTab] = s; this.renderPhoneContent();
    }

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
    }

    esc(str) { const d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML; }
}
