/**
 * LiffLayoutManager
 *
 * LIFF Builder のカスタムレイアウトを描画するマネージャー。
 * admin の LIFF Designer で保存したブロック配列をユーザー画面に反映する。
 */
class LiffLayoutManager {
    constructor(app) {
        this.app = app;
        this.tabSettings = (typeof window !== 'undefined' && window.__LIFF_TAB_SETTINGS__)
            ? window.__LIFF_TAB_SETTINGS__
            : {};
    }

    /**
     * カスタムレイアウト（LIFF Builder ブロック）をコンテナに描画
     */
    renderCustomLayout(container, tab, blocks) {
        if (!container || !Array.isArray(blocks)) return;
        this._applyTabSettings(container, tab);

        const defaultSource = LiffLayoutManager.DEFAULT_TAB_SOURCES[tab];
        if (defaultSource && !this._hasDataBlock(blocks, defaultSource)) {
            blocks = [...blocks, { type: 'data', source: defaultSource }];
        }

        container.innerHTML = this._renderBlocks(blocks);
        this._bindInlineHandlers(container);
        this._initSliders(container);
        this._mountDataSources(container);
    }

    /**
     * タブ切替時にカスタムレイアウトを描画する。
     */
    render(tab) {
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('LiffLayoutManager.render', { tab });
        const layouts = (typeof window !== 'undefined' && window.__LIFF_TAB_LAYOUTS__)
            ? window.__LIFF_TAB_LAYOUTS__
            : {};
        const blocks = layouts[tab];
        const sectionId = tab + 'Section';
        const container = document.getElementById(sectionId);
        if (!container) {
            if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('LiffLayoutManager.render: no container', { sectionId });
            return;
        }
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('LiffLayoutManager.render container found', { tab, hasBlocks: Array.isArray(blocks) && blocks.length > 0 });
        if (!Array.isArray(blocks) || blocks.length === 0) {
            this._loadDefaultTabContent(tab);
            return;
        }
        if (container.dataset.layoutRendered === '1') return;
        container.dataset.layoutRendered = '1';

        this.renderCustomLayout(container, tab, blocks);
    }

    _loadDefaultTabContent(tab) {
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('_loadDefaultTabContent', { tab });
        if (!this.app) return;
        const sectionId = tab + 'Section';
        const container = document.getElementById(sectionId);
        // デフォルトレイアウト時も products タブには productsGrid が必要（Blade では空のためここで挿入）
        if (tab === 'products' && container && !container.querySelector('#productsGrid')) {
            container.innerHTML = `
                <div class="category-tabs" id="categoryTabs">
                    <button class="tab-btn active" data-category="all"><i class="fas fa-th-large"></i><span>すべて</span></button>
                </div>
                <div class="products-section"><div class="products-grid" id="productsGrid"></div></div>
            `;
        }
        // ホームタブ: Blade の homeSection は空のため、プレースホルダを表示しつつお知らせを読み込む
        if (tab === 'home' && container && !container.querySelector('.home-content')) {
            container.innerHTML = `
                <div class="home-content">
                    <div class="loading home-loading"><i class="fas fa-spinner fa-spin"></i><p>お知らせを読み込み中...</p></div>
                </div>
            `;
        }
        const tabLoaders = {
            home: () => {
                this.app.announcementManager.load();
                setTimeout(() => {
                    const container = document.getElementById('homeSection');
                    const loading = container && container.querySelector('.home-loading');
                    if (!loading) return;
                    const section = document.getElementById('announcementsSection');
                    if (section && section.innerHTML.trim() !== '') {
                        loading.outerHTML = '<p class="section-title" style="margin:0;padding:8px 0;"><i class="fas fa-bullhorn"></i> お知らせは上に表示されています</p>';
                    } else {
                        loading.innerHTML = '<p class="section-title" style="margin:0;"><i class="fas fa-bullhorn"></i> お知らせはありません</p>';
                    }
                }, 1200);
            },
            member: () => this.app.loadMemberTab(),
            products: () => this.app.loadProducts(),
            orders: () => this.app.loadOrdersTab(),
            stamps: () => this.app.stampsManager.load(),
            coupons: () => this.app.couponsManager.load(),
            queue: () => this.app.queueManager.load(),
            reservations: () => this.app.checkMembershipAndLoadReservations(),
            gallery: () => this.app.galleryManager.load(),
        };
        const loader = tabLoaders[tab];
        if (loader) {
            if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('_loadDefaultTabContent run loader', { tab });
            loader();
        } else if (typeof window.__mobileOrderLog === 'function') {
            window.__mobileOrderLog('_loadDefaultTabContent no loader', { tab });
        }
    }

    /* ─── 定数 ─── */

    static get DEFAULT_TAB_SOURCES() {
        return {
            home: 'announcements', products: 'products', orders: 'orders',
            stamps: 'stamps', coupons: 'coupons', queue: 'queue',
            member: 'member', reservations: 'reservations', gallery: 'gallery',
        };
    }

    static get DEFAULT_QUICK_ACTIONS() {
        return [
            { icon: 'fa-utensils', label: '注文', action: '#products' },
            { icon: 'fa-ticket-alt', label: 'クーポン', action: '#coupons' },
            { icon: 'fa-stamp', label: 'スタンプ', action: '#stamps' },
            { icon: 'fa-id-card', label: '会員証', action: '#member' },
        ];
    }

    /* ─── 内部メソッド ─── */

    _hasDataBlock(blocks, source) {
        return blocks.some(b => b && b.type === 'data' && b.source === source);
    }

    _renderBlocks(blocks) {
        if (!Array.isArray(blocks) || blocks.length === 0) return '';
        return `<div class="builder-container">${blocks.map(b => this._renderBlock(b)).join('')}</div>`;
    }

    _esc(s) {
        return DomHelper.escapeHtml(s || '');
    }

    _renderBlock(block) {
        const type = block.type || 'text';
        if (block.hidden) return '';
        const esc = (s) => this._esc(s);
        const br = block.border_radius != null ? block.border_radius : 10;
        const mb = block.margin_bottom ? `margin-bottom:${block.margin_bottom}px;` : '';
        const opacity = (block.opacity !== undefined && block.opacity < 100) ? `opacity:${block.opacity / 100};` : '';
        const shadow = block.shadow ? 'box-shadow:0 4px 12px rgba(0,0,0,0.1);' : '';
        const pad = block.padding !== undefined && block.padding !== null ? `padding:${block.padding}px;` : '';
        const bgColor = block.background_color ? `background-color:${block.background_color};` : '';
        const textColor = block.text_color ? `color:${block.text_color};` : '';
        const baseStyle = `border-radius:${br}px;${mb}${opacity}${shadow}${pad}${bgColor}${textColor}`;

        if (type === 'data') {
            return `<section class="builder-block builder-data-block" style="${baseStyle}" data-source="${esc(block.source || '')}"></section>`;
        }
        if (type === 'member_summary') {
            const greeting = block.greeting ? esc(block.greeting) : '';
            return `<section class="builder-block builder-member-summary" data-source="member_summary"${greeting ? ` data-greeting="${greeting}"` : ''} style="${mb}">
                <div class="member-summary-card member-summary-card--loading">
                    <div class="ms-skeleton ms-skeleton--label"></div>
                    <div class="ms-skeleton ms-skeleton--value"></div>
                    <div class="ms-skeleton ms-skeleton--bar"></div>
                </div>
            </section>`;
        }
        if (type === 'quick_actions') {
            const actions = (Array.isArray(block.actions) && block.actions.length > 0)
                ? block.actions
                : LiffLayoutManager.DEFAULT_QUICK_ACTIONS;
            const tiles = actions.map((a) => {
                const action = a.action || '#';
                const isExternal = action !== '#' && (action.startsWith('http://') || action.startsWith('https://'));
                const dataExternal = isExternal ? ' data-builder-external="1"' : '';
                return `<a href="${esc(action)}" class="quick-action-tile builder-nav"${dataExternal}>
                    <span class="qa-icon"><i class="fas ${esc(a.icon || 'fa-circle')}" aria-hidden="true"></i></span>
                    <span class="qa-label">${esc(a.label || '')}</span>
                </a>`;
            }).join('');
            return `<section class="builder-block builder-quick-actions" style="${mb}">
                <div class="quick-actions-grid">${tiles}</div>
            </section>`;
        }
        if (type === 'hero') {
            const bg = block.image_url
                ? `background-image:url('${esc(block.image_url)}');background-size:cover;background-position:center;`
                : `background:linear-gradient(135deg,${block.gradient_start || '#4f46e5'} 0%,${block.gradient_end || '#4338ca'} 100%);`;
            return `<section class="builder-block builder-hero" style="${bg}color:#fff;padding:32px 20px;text-align:center;${baseStyle}">
                <h2 style="font-size:18px;font-weight:700;margin:0 0 4px;">${esc(block.title)}</h2>
                <p style="font-size:12px;opacity:0.85;margin:0;">${esc(block.subtitle || block.text)}</p>
            </section>`;
        }
        if (type === 'heading') {
            const sizes = { small: 14, medium: 16, large: 20 };
            const fs = sizes[block.size] || 16;
            return `<section class="builder-block" style="background:#fff;padding:12px 14px;font-size:${fs}px;font-weight:700;color:${block.text_color || '#1f2937'};text-align:${block.text_align || 'left'};${baseStyle}">${esc(block.text)}</section>`;
        }
        if (type === 'text') {
            return `<section class="builder-block" style="background:#fff;padding:12px 14px;font-size:${block.font_size || 13}px;color:${block.text_color || '#4b5563'};line-height:1.6;text-align:${block.text_align || 'left'};${baseStyle}">${esc(block.text || block.body)}</section>`;
        }
        if (type === 'image') {
            const img = block.image_url
                ? `<img src="${esc(block.image_url)}" alt="${esc(block.alt)}" style="width:100%;height:auto;display:block;object-fit:${block.fit || 'cover'};">`
                : '';
            return `<section class="builder-block" style="overflow:hidden;${baseStyle}">${img}</section>`;
        }
        if (type === 'button') {
            const w = block.full_width !== false ? 'display:block;width:100%;box-sizing:border-box;' : 'display:inline-block;';
            const action = block.action || '#';
            const isExternal = action !== '#' && (action.startsWith('http://') || action.startsWith('https://'));
            const dataExternal = isExternal ? ' data-builder-external="1"' : '';
            return `<section class="builder-block" style="text-align:center;padding:8px;${baseStyle}">
                <a href="${esc(action)}" class="builder-btn"${dataExternal} style="${w}padding:12px 24px;border-radius:8px;background:${block.color || '#4f46e5'};color:${block.text_color || '#fff'};font-size:14px;font-weight:700;text-decoration:none;text-align:center;">${esc(block.text)}</a>
            </section>`;
        }
        if (type === 'friend_add') {
            const friendAddUrl = (typeof window !== 'undefined' && window.__LINE_FRIEND_ADD_URL__) ? String(window.__LINE_FRIEND_ADD_URL__) : '';
            if (!friendAddUrl) return '';
            const text = block.text || '友だち追加';
            return `<section class="builder-block builder-friend-add" style="text-align:center;padding:8px;${baseStyle}" data-friend-add-url="${esc(friendAddUrl)}">
                <a href="${esc(friendAddUrl)}" class="builder-btn builder-btn--friend-add" data-builder-external="1" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 24px;border-radius:8px;background:${block.color || '#06C755'};color:${block.text_color || '#fff'};font-size:14px;font-weight:700;text-decoration:none;"><i class="fab fa-line"></i> ${esc(text)}</a>
            </section>`;
        }
        if (type === 'card') {
            const img = block.image_url
                ? `<img src="${esc(block.image_url)}" alt="" style="width:100%;height:${block.image_height || 140}px;object-fit:cover;display:block;">`
                : `<div style="width:100%;height:${block.image_height || 140}px;background:#f3f4f6;"></div>`;
            return `<section class="builder-block" style="background:#fff;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);${baseStyle}">
                ${img}
                <div style="padding:12px;">
                    <div style="font-size:15px;font-weight:700;color:#1f2937;margin-bottom:4px;">${esc(block.title)}</div>
                    ${block.description ? `<div style="font-size:12px;color:#6b7280;">${esc(block.description)}</div>` : ''}
                </div>
            </section>`;
        }
        if (type === 'collection') {
            return this._renderCollection(block, baseStyle);
        }
        if (type === 'slider') {
            return this._renderSlider(block, baseStyle);
        }
        if (type === 'icon_text') {
            const rows = Array.isArray(block.rows) ? block.rows : [];
            const html = rows.map(r =>
                `<div style="display:flex;align-items:center;gap:10px;font-size:13px;color:#374151;">` +
                `<i class="fas ${r.icon || 'fa-check'}" style="color:${r.color || '#4f46e5'};font-size:16px;flex-shrink:0;"></i>` +
                `<span>${esc(r.text)}</span></div>`
            ).join('');
            return `<section class="builder-block" style="background:#fff;padding:12px 14px;display:flex;flex-direction:column;gap:8px;${baseStyle}">${html}</section>`;
        }
        if (type === 'list') {
            const items = Array.isArray(block.items) ? block.items : [];
            const tag = block.style === 'number' ? 'ol' : 'ul';
            const listHtml = items.length > 0
                ? `<${tag} style="margin:0;padding-left:20px;font-size:13px;line-height:1.8;color:${block.text_color || '#374151'};">${items.map(i => `<li>${esc(i)}</li>`).join('')}</${tag}>`
                : '';
            return `<section class="builder-block" style="background:#fff;padding:12px 14px;${baseStyle}">${listHtml}</section>`;
        }
        if (type === 'banner') {
            return `<section class="builder-block" style="display:flex;align-items:center;gap:8px;padding:12px 14px;font-size:13px;font-weight:600;background:${block.color || '#eef2ff'};color:${block.text_color || '#374151'};${baseStyle}">
                ${block.icon ? `<i class="fas ${block.icon}" style="font-size:16px;flex-shrink:0;"></i>` : ''}
                <span>${esc(block.text)}</span>
            </section>`;
        }
        if (type === 'spacer') {
            return `<div style="height:${block.height || 16}px;${mb}"></div>`;
        }
        if (type === 'divider') {
            return `<div style="padding:8px 0;${mb}"><hr style="border:none;border-top:1px ${block.divider_style || 'solid'} ${block.divider_color || '#e5e7eb'};margin:0;"></div>`;
        }
        return `<section class="builder-block" style="background:#fff;padding:12px 14px;${baseStyle}">${esc(block.text || block.body || '')}</section>`;
    }

    _renderCollection(block, baseStyle) {
        const esc = (s) => this._esc(s);
        const items = Array.isArray(block.items) ? block.items : [];
        const isGrid = block.layout === 'grid';
        const cols = block.columns || 2;
        const gap = block.gap != null ? block.gap : 12;
        const maxRows = block.max_rows || 0;
        const visibleCount = (isGrid && maxRows > 0) ? maxRows * cols : items.length;
        const visible = items.slice(0, visibleCount);
        const containerStyle = isGrid
            ? `display:grid;grid-template-columns:repeat(${cols},1fr);gap:${gap}px;`
            : `display:flex;gap:${gap}px;overflow-x:auto;padding:2px 2px 10px;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;`;
        const itemStyle = isGrid
            ? 'background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 14px rgba(12,10,9,0.08);'
            : `flex-shrink:0;width:${block.item_width || 200}px;scroll-snap-align:start;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 6px 18px rgba(12,10,9,0.1);`;
        const imgH = block.item_image_height || (isGrid ? 120 : 130);
        const itemsHtml = visible.map(item => {
            const img = item.image_url
                ? `<img src="${esc(item.image_url)}" alt="" style="width:100%;height:${imgH}px;object-fit:cover;display:block;">`
                : `<div style="width:100%;height:${imgH}px;background:linear-gradient(135deg,#f5f5f4,#e7e5e4);display:flex;align-items:center;justify-content:center;color:#a8a29e;"><i class="fas fa-image" style="font-size:22px;"></i></div>`;
            const desc = (block.show_description && item.description)
                ? `<div style="padding:0 12px 10px;font-size:11px;line-height:1.5;color:#78716c;">${esc(item.description)}</div>` : '';
            return `<div style="${itemStyle}">${img}<div style="padding:10px 12px ${desc ? '4px' : '12px'};font-size:13px;font-weight:700;color:#1c1917;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(item.title)}</div>${desc}</div>`;
        }).join('');
        return `<section class="builder-block" style="${baseStyle}"><div style="${containerStyle}">${itemsHtml}</div></section>`;
    }

    _renderSlider(block, baseStyle) {
        const esc = (s) => this._esc(s);
        const slides = Array.isArray(block.slides) ? block.slides : [];
        const h = block.slide_height || 160;
        if (slides.length === 0) {
            return `<section class="builder-block" style="height:${h}px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#9ca3af;${baseStyle}"><i class="fas fa-images" style="font-size:28px;"></i></section>`;
        }
        const slideHtml = slides.map((s, i) => {
            const display = i === 0 ? '' : 'display:none;';
            const img = s.image_url ? `<img src="${esc(s.image_url)}" alt="" style="width:100%;height:100%;object-fit:cover;">` : '';
            const caption = s.caption ? `<div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,0.5));color:#fff;padding:24px 12px 10px;font-size:13px;font-weight:600;">${esc(s.caption)}</div>` : '';
            return `<div class="builder-slide" data-slide-index="${i}" style="position:absolute;inset:0;${display}">${img}${caption}</div>`;
        }).join('');
        const dots = (block.show_dots !== false && slides.length > 1)
            ? `<div style="position:absolute;bottom:8px;left:50%;transform:translateX(-50%);display:flex;gap:5px;">${slides.map((_, i) => `<span class="builder-slider-dot" data-slide-index="${i}" style="width:6px;height:6px;border-radius:50%;background:${i === 0 ? '#fff' : 'rgba(255,255,255,0.4)'};cursor:pointer;transition:background 0.2s;"></span>`).join('')}</div>`
            : '';
        return `<section class="builder-block builder-slider-block" style="position:relative;overflow:hidden;height:${h}px;${baseStyle}" data-auto-play="${block.auto_play !== false ? '1' : '0'}" data-slide-count="${slides.length}">${slideHtml}${dots}</section>`;
    }

    _buildStyle(block) {
        const styles = [];
        if (block.background_color) styles.push(`background:${block.background_color}`);
        if (block.text_color) styles.push(`color:${block.text_color}`);
        if (block.border_radius) styles.push(`border-radius:${Number(block.border_radius)}px`);
        return styles.join(';');
    }

    _applyTabSettings(section, tab) {
        const settings = (this.tabSettings && this.tabSettings[tab]) ? this.tabSettings[tab] : {};
        const global = (this.tabSettings && this.tabSettings._global) ? this.tabSettings._global : {};
        if (settings.background_color) section.style.background = settings.background_color;
        if (settings.background_image) {
            section.style.backgroundImage = `url('${settings.background_image}')`;
            section.style.backgroundSize = settings.background_size || 'cover';
            section.style.backgroundPosition = settings.background_position || 'center';
            section.style.backgroundRepeat = settings.background_repeat || 'no-repeat';
        }
        const contentPadding = settings.content_padding || global.content_padding;
        const sectionGap = global.section_gap;
        if (contentPadding) section.style.setProperty('--content-padding', contentPadding + 'px');
        if (sectionGap) section.style.setProperty('--section-gap', sectionGap + 'px');
    }

    /** スライダーの自動再生とドットナビゲーションを初期化 */
    _initSliders(container) {
        container.querySelectorAll('.builder-slider-block').forEach(slider => {
            const count = parseInt(slider.dataset.slideCount, 10) || 0;
            if (count <= 1) return;
            let current = 0;
            const showSlide = (idx) => {
                slider.querySelectorAll('.builder-slide').forEach(s => { s.style.display = 'none'; });
                slider.querySelectorAll('.builder-slider-dot').forEach(d => { d.style.background = 'rgba(255,255,255,0.4)'; });
                const slide = slider.querySelector(`.builder-slide[data-slide-index="${idx}"]`);
                const dot = slider.querySelector(`.builder-slider-dot[data-slide-index="${idx}"]`);
                if (slide) slide.style.display = '';
                if (dot) dot.style.background = '#fff';
                current = idx;
            };
            slider.querySelectorAll('.builder-slider-dot').forEach(dot => {
                dot.addEventListener('click', () => showSlide(parseInt(dot.dataset.slideIndex, 10)));
            });
            if (slider.dataset.autoPlay === '1') {
                setInterval(() => { showSlide((current + 1) % count); }, 4000);
            }
        });
    }

    _mountDataSources(container) {
        container.querySelectorAll('[data-source]').forEach(el => {
            const source = el.getAttribute('data-source');
            if (!source) return;
            if (source === 'products') {
                el.innerHTML += `
                    <div class="category-tabs" id="categoryTabs">
                        <button class="tab-btn active" data-category="all"><i class="fas fa-th-large"></i><span>すべて</span></button>
                    </div>
                    <div class="products-section"><div class="products-grid" id="productsGrid"></div></div>
                `;
                this.app.loadProducts();
                return;
            }
            if (source === 'orders') {
                el.innerHTML += '<div id="ordersSection"></div>';
                this.app.loadOrdersTab();
                return;
            }
            if (source === 'stamps') {
                el.innerHTML += '<div id="stampsSection"></div>';
                this.app.stampsManager.load();
                return;
            }
            if (source === 'coupons') {
                el.innerHTML += '<div id="couponsSection"></div>';
                this.app.couponsManager.load();
                return;
            }
            if (source === 'queue') {
                el.innerHTML += '<div id="queueSection"></div>';
                this.app.queueManager.load();
                return;
            }
            if (source === 'reservations') {
                el.innerHTML += '<div class="reservations-section" id="reservationsSection"></div>';
                this.app.checkMembershipAndLoadReservations();
                return;
            }
            if (source === 'member' || source === 'member_card') {
                el.innerHTML += '<div class="member-section" id="memberSection"></div>';
                this.app.loadMemberTab();
                return;
            }
            if (source === 'gallery') {
                el.innerHTML += '<div id="gallerySection"></div>';
                this.app.galleryManager.load();
                return;
            }
            if (source === 'announcements') {
                el.innerHTML += '<div class="announcements-section" id="announcementsSection"></div>';
                this.app.announcementManager.load();
                return;
            }
            if (source === 'member_summary') {
                this._mountMemberSummary(el);
                return;
            }
        });
    }

    /** 会員サマリーカード（ホーム上部のパーソナライズ表示）を会員APIから描画 */
    async _mountMemberSummary(el) {
        const greeting = el.getAttribute('data-greeting') || '';
        const fill = (html) => {
            el.innerHTML = html;
            this._bindInlineHandlers(el);
        };
        let data = null;
        try {
            if (this.app && this.app.apiClient && typeof AppConstants !== 'undefined') {
                const result = await this.app.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME);
                if (result && result.success && result.data) {
                    data = result.data;
                }
            }
        } catch (e) {
            data = null;
        }
        fill(this._buildMemberSummaryHtml(data, greeting));
    }

    _buildMemberSummaryHtml(data, greeting) {
        const esc = (s) => this._esc(s);
        const isMember = !!(data && data.is_member === true);

        if (!isMember) {
            return `<div class="member-summary-card member-summary-card--guest">
                <div class="ms-guest-copy">
                    <p class="ms-guest-title">${esc(greeting || 'ようこそ')}</p>
                    <p class="ms-guest-sub">会員登録でポイント・クーポンをご利用いただけます</p>
                </div>
                <a href="#member" class="builder-nav ms-guest-cta"><i class="fas fa-user-plus" aria-hidden="true"></i> 会員登録する</a>
            </div>`;
        }

        const name = (data.user && data.user.name) ? String(data.user.name) : '';
        const points = Number(data.points != null ? data.points : (data.member && data.member.points) || 0);
        const pointsLabel = points.toLocaleString('ja-JP');
        const rankLabel = data.current_rank_label || '';
        const showPoints = data.show_points_display !== false;
        const showRank = data.show_rank_progress !== false;
        const nextRankLabel = data.next_rank_label || '';
        const toNext = Number(data.points_to_next_rank != null ? data.points_to_next_rank : 0);

        const hello = greeting
            ? esc(greeting)
            : (name ? `こんにちは、${esc(name)} さん` : 'おかえりなさい');

        let progressHtml = '';
        if (showRank && nextRankLabel && toNext > 0) {
            const span = points + toNext;
            const pct = span > 0 ? Math.max(4, Math.min(100, Math.round((points / span) * 100))) : 0;
            progressHtml = `<div class="ms-progress">
                <div class="ms-progress-track"><span class="ms-progress-fill" style="width:${pct}%;"></span></div>
                <p class="ms-progress-text">次の<strong>${esc(nextRankLabel)}</strong>まであと <strong>${toNext.toLocaleString('ja-JP')}</strong> pt</p>
            </div>`;
        }

        return `<div class="member-summary-card">
            <div class="ms-top">
                <div class="ms-greeting">
                    <p class="ms-hello">${hello}</p>
                    ${rankLabel ? `<span class="ms-rank"><i class="fas fa-crown" aria-hidden="true"></i> ${esc(rankLabel)}</span>` : ''}
                </div>
                <a href="#member" class="builder-nav ms-card-link" aria-label="会員証を表示"><i class="fas fa-qrcode" aria-hidden="true"></i></a>
            </div>
            ${showPoints ? `<div class="ms-points">
                <span class="ms-points-value">${pointsLabel}</span>
                <span class="ms-points-unit">pt</span>
            </div>` : ''}
            ${progressHtml}
        </div>`;
    }

    _bindInlineHandlers(section) {
        section.querySelectorAll('.builder-btn, .builder-nav').forEach(btn => {
            btn.addEventListener('click', e => {
                const href = btn.getAttribute('href');
                if (!href) return;
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const tab = href.replace('#', '');
                    if (this.app && tab) this.app.switchTab(tab);
                    return;
                }
                // 外部URL（友だち追加など）: LINE内なら liff.openWindow でLINE内ブラウザに開く
                if (btn.getAttribute('data-builder-external') === '1' && typeof window !== 'undefined' && window.__IS_LIFF__ && typeof liff !== 'undefined' && typeof liff.openWindow === 'function') {
                    e.preventDefault();
                    liff.openWindow({ url: href, external: false });
                }
            });
        });
    }
}
