/**
 * LiffLayoutManager
 *
 * LIFF Builder のカスタムレイアウトを描画するマネージャー。
 * admin の LIFF Designer で保存したブロック配列をユーザー画面に反映する。
 */
declare const liff: any;
import { DomHelper } from '../../shared/utils/domHelper';
import { AppConstants } from '../../shared/constants';

export class LiffLayoutManager {
    app: any;
    tabSettings: Record<string, any>;

    constructor(app: any) {
        this.app = app;
        this.tabSettings = (typeof window !== 'undefined' && window.__LIFF_TAB_SETTINGS__)
            ? window.__LIFF_TAB_SETTINGS__
            : {};
    }

    /**
     * カスタムレイアウト（LIFF Builder ブロック）をコンテナに描画
     */
    renderCustomLayout(container: any, tab: string, blocks: any[]): void {
        if (!container || !Array.isArray(blocks)) return;
        this._applyTabSettings(container, tab);

        const defaultSource = LiffLayoutManager.DEFAULT_TAB_SOURCES[tab];
        if (defaultSource && !this._hasDataBlock(blocks, defaultSource)) {
            blocks = [...blocks, { type: 'data', source: defaultSource }];
        }

        try {
            container.innerHTML = this._renderBlocks(blocks);
            this._bindInlineHandlers(container);
            this._initSliders(container);
            this._mountDataSources(container);
        } catch (err) {
            // eslint-disable-next-line no-console
            console.error('LiffLayoutManager.renderCustomLayout failed:', err);
            delete container.dataset.layoutRendered;
            this._loadDefaultTabContent(tab);
        }
    }

    /**
     * タブ切替時にカスタムレイアウトを描画する。
     */
    render(tab: string): void {
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('LiffLayoutManager.render', { tab });
        const layouts = (typeof window !== 'undefined' && window.__LIFF_TAB_LAYOUTS__)
            ? window.__LIFF_TAB_LAYOUTS__
            : {};
        const blocks = layouts[tab];
        const sectionId = tab + 'Section';
        const container = document.getElementById(sectionId) as any;
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

    _loadDefaultTabContent(tab: string): void {
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('_loadDefaultTabContent', { tab });
        if (!this.app) return;
        const sectionId = tab + 'Section';
        const container = document.getElementById(sectionId);
        // デフォルトレイアウト時も products タブには productsGrid が必要（Blade では空のためここで挿入）
        if (tab === 'products' && container && !container.querySelector('.products-data-grid') && !container.querySelector('#productsGrid')) {
            container.innerHTML = `
                <div class="products-data-mount">
                    <div class="category-tabs products-category-tabs" id="categoryTabs">
                        <button class="tab-btn active" data-category="all"><i class="fas fa-th-large"></i><span>すべて</span></button>
                    </div>
                    <div class="products-section"><div class="products-grid products-data-grid" id="productsGrid"></div></div>
                </div>
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
        const tabLoaders: Record<string, () => void> = {
            home: () => {
                this.app.announcementManager.load();
                setTimeout(() => {
                    const c = document.getElementById('homeSection');
                    const loading = c && c.querySelector('.home-loading');
                    if (!loading) return;
                    const section = document.getElementById('announcementsSection');
                    if (section && section.innerHTML.trim() !== '') {
                        (loading as HTMLElement).outerHTML = '<p class="section-title" style="margin:0;padding:8px 0;"><i class="fas fa-bullhorn"></i> お知らせは上に表示されています</p>';
                    } else {
                        (loading as HTMLElement).innerHTML = '<p class="section-title" style="margin:0;"><i class="fas fa-bullhorn"></i> お知らせはありません</p>';
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

    static get DEFAULT_TAB_SOURCES(): Record<string, string> {
        return {
            home: 'announcements', products: 'products', orders: 'orders',
            stamps: 'stamps', coupons: 'coupons', queue: 'queue',
            member: 'member', reservations: 'reservations', gallery: 'gallery',
        };
    }

    static get DEFAULT_QUICK_ACTIONS(): Array<{ icon: string; label: string; action: string }> {
        return [
            { icon: 'fa-utensils', label: '注文', action: '#products' },
            { icon: 'fa-ticket-alt', label: 'クーポン', action: '#coupons' },
            { icon: 'fa-stamp', label: 'スタンプ', action: '#stamps' },
            { icon: 'fa-id-card', label: '会員証', action: '#member' },
        ];
    }

    /* ─── 内部メソッド ─── */

    _hasDataBlock(blocks: any[], source: string): boolean {
        return blocks.some((b) => {
            if (!b || b.type !== 'data') return false;
            if (b.source === source) return true;
            if (source === 'member' && b.source === 'member_card') return true;
            if (source === 'member_card' && b.source === 'member') return true;
            return false;
        });
    }

    _renderBlocks(blocks: any[]): string {
        if (!Array.isArray(blocks) || blocks.length === 0) return '';
        return `<div class="builder-container">${blocks.map((b) => this._renderBlock(b)).join('')}</div>`;
    }

    _esc(s: any): string {
        return DomHelper.escapeHtml(s || '');
    }

    _premium(): any {
        return (typeof window !== 'undefined' && window.LiffLayoutBlocks && window.LiffLayoutBlocks.premium)
            ? window.LiffLayoutBlocks.premium
            : null;
    }

    _renderBlock(block: any): string {
        const type = block.type || 'text';
        if (block.hidden) return '';
        const esc = (s: any): string => this._esc(s);
        const br = block.border_radius != null ? block.border_radius : 10;
        const mb = block.margin_bottom ? `margin-bottom:${block.margin_bottom}px;` : '';
        const opacity = (block.opacity !== undefined && block.opacity < 100) ? `opacity:${block.opacity / 100};` : '';
        const shadow = block.shadow ? 'box-shadow:0 4px 12px rgba(0,0,0,0.1);' : '';
        const pad = block.padding !== undefined && block.padding !== null ? `padding:${block.padding}px;` : '';
        const bgColor = block.background_color ? `background-color:${block.background_color};` : '';
        const textColor = block.text_color ? `color:${block.text_color};` : '';
        const baseStyle = `border-radius:${br}px;${mb}${opacity}${shadow}${pad}${bgColor}${textColor}`;

        if (type === 'data') {
            const cardDesign = block.card_design ? ` data-card-design="${esc(block.card_design)}"` : '';
            const design = esc(block.design || 'cards');
            return `<section class="builder-block builder-data-block builder-data-block--design-${design}" style="${baseStyle}" data-source="${esc(block.source || '')}" data-design="${design}"${cardDesign}></section>`;
        }
        if (type === 'member_summary') {
            const greeting = block.greeting ? esc(block.greeting) : '';
            const design = esc(block.design || 'rewards');
            return `<section class="builder-block builder-member-summary" data-source="member_summary" data-design="${design}"${greeting ? ` data-greeting="${greeting}"` : ''} style="${mb}">
                <div class="member-summary-card member-summary-card--loading member-summary-card--design-${design}">
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
            const design = esc(block.design || 'grid');
            const tiles = actions.map((a: any) => {
                const action = a.action || '#';
                const isExternal = action !== '#' && (action.startsWith('http://') || action.startsWith('https://'));
                const dataExternal = isExternal ? ' data-builder-external="1"' : '';
                return `<a href="${esc(action)}" class="quick-action-tile builder-nav"${dataExternal}>
                    <span class="qa-icon"><i class="fas ${esc(a.icon || 'fa-circle')}" aria-hidden="true"></i></span>
                    <span class="qa-label">${esc(a.label || '')}</span>
                </a>`;
            }).join('');
            return `<section class="builder-block builder-quick-actions builder-quick-actions--${design}" style="${mb}">
                <div class="builder-section-head"><span class="builder-section-head__label">ショートカット</span></div>
                <div class="quick-actions-grid quick-actions--${design}">${tiles}</div>
            </section>`;
        }
        if (type === 'hero') {
            const premium = this._premium();
            if (premium) return premium.hero(block, esc, baseStyle);
            const bg = block.image_url
                ? `background-image:url('${esc(block.image_url)}');background-size:cover;background-position:center;`
                : `background:linear-gradient(135deg,${block.gradient_start || '#4f46e5'} 0%,${block.gradient_end || '#4338ca'} 100%);`;
            return `<section class="builder-block builder-hero" style="${bg}color:#fff;padding:32px 20px;text-align:center;${baseStyle}">
                <h2 style="font-size:18px;font-weight:700;margin:0 0 4px;">${esc(block.title)}</h2>
                <p style="font-size:12px;opacity:0.85;margin:0;">${esc(block.subtitle || block.text)}</p>
            </section>`;
        }
        if (type === 'heading') {
            const premium = this._premium();
            if (premium) return premium.heading(block, esc, baseStyle);
            const sizes: Record<string, number> = { small: 14, medium: 16, large: 20 };
            const fs = sizes[block.size] || 16;
            return `<section class="builder-block" style="background:#fff;padding:12px 14px;font-size:${fs}px;font-weight:700;color:${block.text_color || '#1f2937'};text-align:${block.text_align || 'left'};${baseStyle}">${esc(block.text)}</section>`;
        }
        if (type === 'text') {
            const premium = this._premium();
            if (premium) return premium.text(block, esc, baseStyle);
            return `<section class="builder-block" style="background:#fff;padding:12px 14px;font-size:${block.font_size || 13}px;color:${block.text_color || '#4b5563'};line-height:1.6;text-align:${block.text_align || 'left'};${baseStyle}">${esc(block.text || block.body)}</section>`;
        }
        if (type === 'button') {
            const premium = this._premium();
            if (premium) return premium.button(block, esc, baseStyle);
            const w = block.full_width !== false ? 'display:block;width:100%;box-sizing:border-box;' : 'display:inline-block;';
            const action = block.action || '#';
            const isExternal = action !== '#' && (action.startsWith('http://') || action.startsWith('https://'));
            const dataExternal = isExternal ? ' data-builder-external="1"' : '';
            return `<section class="builder-block" style="text-align:center;padding:8px;${baseStyle}">
                <a href="${esc(action)}" class="builder-btn"${dataExternal} style="${w}padding:12px 24px;border-radius:8px;background:${block.color || '#4f46e5'};color:${block.text_color || '#fff'};font-size:14px;font-weight:700;text-decoration:none;text-align:center;">${esc(block.text)}</a>
            </section>`;
        }
        if (type === 'image') {
            const design = esc(block.design || 'rounded');
            const img = block.image_url
                ? `<img class="builder-image-block__img" src="${esc(block.image_url)}" alt="${esc(block.alt)}" loading="lazy">`
                : '';
            return `<section class="builder-block builder-image-block builder-image-block--design-${design}" style="${baseStyle}">${img}</section>`;
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
            const premium = this._premium();
            if (premium) return premium.card(block, esc, baseStyle);
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
            const premium = this._premium();
            if (premium) return premium.collection(block, esc, baseStyle);
            return this._renderCollection(block, baseStyle);
        }
        if (type === 'slider') {
            const premium = this._premium();
            if (premium) return premium.slider(block, esc, baseStyle);
            return this._renderSlider(block, baseStyle);
        }
        if (type === 'icon_text') {
            const rows = Array.isArray(block.rows) ? block.rows : [];
            const html = rows.map((r: any) =>
                `<div style="display:flex;align-items:center;gap:10px;font-size:13px;color:#374151;">`
                + `<i class="fas ${r.icon || 'fa-check'}" style="color:${r.color || '#4f46e5'};font-size:16px;flex-shrink:0;"></i>`
                + `<span>${esc(r.text)}</span></div>`
            ).join('');
            return `<section class="builder-block" style="background:#fff;padding:12px 14px;display:flex;flex-direction:column;gap:8px;${baseStyle}">${html}</section>`;
        }
        if (type === 'list') {
            const items = Array.isArray(block.items) ? block.items : [];
            const tag = block.style === 'number' ? 'ol' : 'ul';
            const listHtml = items.length > 0
                ? `<${tag} style="margin:0;padding-left:20px;font-size:13px;line-height:1.8;color:${block.text_color || '#374151'};">${items.map((i: any) => `<li>${esc(i)}</li>`).join('')}</${tag}>`
                : '';
            return `<section class="builder-block" style="background:#fff;padding:12px 14px;${baseStyle}">${listHtml}</section>`;
        }
        if (type === 'banner') {
            const premium = this._premium();
            if (premium) return premium.banner(block, esc, baseStyle);
            const action = block.action || block.link || '';
            const inner = `${block.icon ? `<i class="fas ${block.icon}" style="font-size:16px;flex-shrink:0;"></i>` : ''}<span>${esc(block.text)}</span>`;
            const wrapStyle = `display:flex;align-items:center;gap:8px;padding:12px 14px;font-size:13px;font-weight:600;background:${block.color || '#eef2ff'};color:${block.text_color || '#374151'};${baseStyle}`;
            if (action) {
                const isExternal = action !== '#' && (action.startsWith('http://') || action.startsWith('https://'));
                const dataExternal = isExternal ? ' data-builder-external="1"' : '';
                return `<section class="builder-block" style="${wrapStyle}">
                    <a href="${esc(action)}" class="builder-nav"${dataExternal} style="display:flex;align-items:center;gap:8px;color:inherit;text-decoration:none;width:100%;">${inner}</a>
                </section>`;
            }
            return `<section class="builder-block" style="${wrapStyle}">${inner}</section>`;
        }
        if (type === 'spacer') return `<div style="height:${block.height || 16}px;${mb}"></div>`;
        if (type === 'divider') return `<div style="padding:8px 0;${mb}"><hr style="border:none;border-top:1px ${block.divider_style || 'solid'} ${block.divider_color || '#e5e7eb'};margin:0;"></div>`;
        if (type === 'hours') return this._renderHours(block, baseStyle);
        if (type === 'access') return this._renderAccess(block, baseStyle);
        if (type === 'social_links') return this._renderSocialLinks(block, baseStyle);
        if (type === 'faq') return this._renderFaq(block, baseStyle);
        if (type === 'video') return this._renderVideo(block, baseStyle);
        if (type === 'button_group') return this._renderButtonGroup(block, baseStyle);
        return `<section class="builder-block" style="background:#fff;padding:12px 14px;${baseStyle}">${esc(block.text || block.body || '')}</section>`;
    }

    _renderCollection(block: any, baseStyle: string): string {
        const esc = (s: any): string => this._esc(s);
        const items = Array.isArray(block.items) ? block.items : [];
        const design = esc(block.design || 'product_shelf');
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
        const itemsHtml = visible.map((item: any) => {
            const img = item.image_url
                ? `<img src="${esc(item.image_url)}" alt="" style="width:100%;height:${imgH}px;object-fit:cover;display:block;">`
                : '<div style="width:100%;height:${imgH}px;background:linear-gradient(135deg,#f5f5f4,#e7e5e4);display:flex;align-items:center;justify-content:center;color:#a8a29e;"><i class="fas fa-image" style="font-size:22px;"></i></div>';
            const desc = (block.show_description && item.description)
                ? `<div style="padding:0 12px 10px;font-size:11px;line-height:1.5;color:#78716c;">${esc(item.description)}</div>` : '';
            return `<div style="${itemStyle}">${img}<div style="padding:10px 12px ${desc ? '4px' : '12px'};font-size:13px;font-weight:700;color:#1c1917;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(item.title)}</div>${desc}</div>`;
        }).join('');
        return `<section class="builder-block builder-collection-wrap builder-collection-wrap--design-${design}" style="${baseStyle}"><div class="builder-collection builder-collection--fallback builder-collection--design-${design}" style="${containerStyle}">${itemsHtml}</div></section>`;
    }

    _renderSlider(block: any, baseStyle: string): string {
        const esc = (s: any): string => this._esc(s);
        const slides = Array.isArray(block.slides) ? block.slides : [];
        const h = block.slide_height || 160;
        const design = esc(block.design || 'hero');
        if (slides.length === 0) {
            return `<section class="builder-block builder-slider-block builder-slider-block--design-${design}" style="height:${h}px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#9ca3af;${baseStyle}"><i class="fas fa-images" style="font-size:28px;"></i></section>`;
        }
        const slideHtml = slides.map((s: any, i: number) => {
            const display = i === 0 ? '' : 'display:none;';
            const img = s.image_url ? `<img src="${esc(s.image_url)}" alt="" style="width:100%;height:100%;object-fit:cover;">` : '';
            const caption = s.caption ? `<div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,0.5));color:#fff;padding:24px 12px 10px;font-size:13px;font-weight:600;">${esc(s.caption)}</div>` : '';
            return `<div class="builder-slide" data-slide-index="${i}" style="position:absolute;inset:0;${display}">${img}${caption}</div>`;
        }).join('');
        const dots = (block.show_dots !== false && slides.length > 1)
            ? `<div style="position:absolute;bottom:8px;left:50%;transform:translateX(-50%);display:flex;gap:5px;">${slides.map((_: any, i: number) => `<span class="builder-slider-dot" data-slide-index="${i}" style="width:6px;height:6px;border-radius:50%;background:${i === 0 ? '#fff' : 'rgba(255,255,255,0.4)'};cursor:pointer;transition:background 0.2s;"></span>`).join('')}</div>`
            : '';
        return `<section class="builder-block builder-slider-block builder-slider-block--design-${design}" style="position:relative;overflow:hidden;height:${h}px;${baseStyle}" data-auto-play="${block.auto_play !== false ? '1' : '0'}" data-slide-count="${slides.length}">${slideHtml}${dots}</section>`;
    }

    _renderHours(block: any, baseStyle: string): string {
        const esc = (s: any): string => this._esc(s);
        const rows = Array.isArray(block.rows) ? block.rows : [];
        const design = esc(block.design || 'table');
        const rowsHtml = rows.map((r: any) =>
            `<div class="builder-hours__row"><span class="builder-hours__day">${esc(r.day)}</span><span class="builder-hours__time">${esc(r.time)}</span></div>`
        ).join('');
        const note = block.note ? `<p class="builder-hours__note">${esc(block.note)}</p>` : '';
        return `<section class="builder-block builder-hours builder-hours--design-${design}" style="${baseStyle}">
            ${block.title ? `<div class="builder-block__title"><i class="far fa-clock" aria-hidden="true"></i> ${esc(block.title)}</div>` : ''}
            <div class="builder-hours__table">${rowsHtml}</div>
            ${note}
        </section>`;
    }

    _renderAccess(block: any, baseStyle: string): string {
        const esc = (s: any): string => this._esc(s);
        const design = esc(block.design || 'card');
        const rows: string[] = [];
        if (block.address) rows.push(`<div class="builder-access__row"><i class="fas fa-map-marker-alt" aria-hidden="true"></i><span>${esc(block.address)}</span></div>`);
        if (block.phone) rows.push(`<a class="builder-access__row builder-access__row--link" href="tel:${esc(String(block.phone).replace(/[^0-9+]/g, ''))}"><i class="fas fa-phone" aria-hidden="true"></i><span>${esc(block.phone)}</span></a>`);
        if (block.note) rows.push(`<div class="builder-access__row"><i class="fas fa-info-circle" aria-hidden="true"></i><span>${esc(block.note)}</span></div>`);
        const mapBtn = block.map_url
            ? `<a href="${esc(block.map_url)}" class="builder-btn builder-access__map-btn" data-builder-external="1"><i class="fas fa-map" aria-hidden="true"></i> 地図を開く</a>`
            : '';
        return `<section class="builder-block builder-access builder-access--design-${design}" style="${baseStyle}">
            ${block.title ? `<div class="builder-block__title"><i class="fas fa-store" aria-hidden="true"></i> ${esc(block.title)}</div>` : ''}
            <div class="builder-access__rows">${rows.join('')}</div>
            ${mapBtn}
        </section>`;
    }

    _renderSocialLinks(block: any, baseStyle: string): string {
        const esc = (s: any): string => this._esc(s);
        const iconMap: Record<string, string> = {
            instagram: 'fab fa-instagram', x: 'fab fa-x-twitter', facebook: 'fab fa-facebook-f',
            youtube: 'fab fa-youtube', tiktok: 'fab fa-tiktok', line: 'fab fa-line', website: 'fas fa-globe',
        };
        const links = (Array.isArray(block.links) ? block.links : []).filter((l: any) => l && l.url);
        if (links.length === 0) return '';
        const html = links.map((l: any) =>
            `<a href="${esc(l.url)}" class="builder-social__link builder-social__link--${esc(l.type)}" data-builder-external="1" aria-label="${esc(l.type)}"><i class="${iconMap[l.type] || 'fas fa-link'}" aria-hidden="true"></i></a>`
        ).join('');
        return `<section class="builder-block builder-social" style="${baseStyle}"><div class="builder-social__links">${html}</div></section>`;
    }

    _renderFaq(block: any, baseStyle: string): string {
        const esc = (s: any): string => this._esc(s);
        const items = Array.isArray(block.items) ? block.items : [];
        const design = esc(block.design || 'accordion');
        const html = items.map((it: any) =>
            `<details class="builder-faq__item">
                <summary class="builder-faq__q"><span>${esc(it.q)}</span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
                <div class="builder-faq__a">${esc(it.a)}</div>
            </details>`
        ).join('');
        return `<section class="builder-block builder-faq builder-faq--design-${design}" style="${baseStyle}">
            ${block.title ? `<div class="builder-block__title"><i class="far fa-question-circle" aria-hidden="true"></i> ${esc(block.title)}</div>` : ''}
            <div class="builder-faq__list">${html}</div>
        </section>`;
    }

    _renderVideo(block: any, baseStyle: string): string {
        const esc = (s: any): string => this._esc(s);
        const id = String(block.youtube_id || '');
        if (!/^[a-zA-Z0-9_-]{11}$/.test(id)) return '';
        const caption = block.caption ? `<p class="builder-video__caption">${esc(block.caption)}</p>` : '';
        return `<section class="builder-block builder-video" style="${baseStyle}">
            <div class="builder-video__frame">
                <iframe src="https://www.youtube-nocookie.com/embed/${id}" title="YouTube video" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            ${caption}
        </section>`;
    }

    _renderButtonGroup(block: any, baseStyle: string): string {
        const esc = (s: any): string => this._esc(s);
        const buttons = (Array.isArray(block.buttons) ? block.buttons : []).filter((b: any) => b && b.text);
        if (buttons.length === 0) return '';
        const html = buttons.map((b: any) => {
            const action = b.action || '#';
            const isExternal = action !== '#' && (action.startsWith('http://') || action.startsWith('https://'));
            const dataExternal = isExternal ? ' data-builder-external="1"' : '';
            const isOutline = b.variant === 'outline';
            const color = b.color || '';
            const style = color
                ? (isOutline ? `color:${color};border-color:${color};` : `background:${color};`)
                : '';
            return `<a href="${esc(action)}" class="builder-btn builder-btn-group__btn${isOutline ? ' builder-btn-group__btn--outline' : ''}"${dataExternal} style="${style}">${esc(b.text)}</a>`;
        }).join('');
        return `<section class="builder-block builder-btn-group" style="${baseStyle}"><div class="builder-btn-group__row" data-count="${buttons.length}">${html}</div></section>`;
    }

    _buildStyle(block: any): string {
        const styles: string[] = [];
        if (block.background_color) styles.push(`background:${block.background_color}`);
        if (block.text_color) styles.push(`color:${block.text_color}`);
        if (block.border_radius) styles.push(`border-radius:${Number(block.border_radius)}px`);
        return styles.join(';');
    }

    _applyTabSettings(section: HTMLElement, tab: string): void {
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
    _initSliders(container: HTMLElement): void {
        container.querySelectorAll('.builder-slider-block, .builder-carousel').forEach((sliderEl) => {
            const slider = sliderEl as any;
            const count = parseInt(slider.dataset.slideCount, 10) || 0;
            if (count <= 1) return;
            let current = 0;
            const isCarousel = slider.classList.contains('builder-carousel');
            const slideSel = isCarousel ? '.builder-carousel__slide' : '.builder-slide';
            const dotSel = isCarousel ? '.builder-carousel__dot' : '.builder-slider-dot';
            const showSlide = (idx: number): void => {
                slider.querySelectorAll(slideSel).forEach((s: any) => {
                    if (isCarousel) s.classList.toggle('is-active', parseInt(s.dataset.slideIndex, 10) === idx);
                    else s.style.display = 'none';
                });
                slider.querySelectorAll(dotSel).forEach((d: any) => {
                    if (isCarousel) d.classList.toggle('is-active', parseInt(d.dataset.slideIndex, 10) === idx);
                    else d.style.background = 'rgba(255,255,255,0.4)';
                });
                if (!isCarousel) {
                    const slide = slider.querySelector(`${slideSel}[data-slide-index="${idx}"]`) as any;
                    const dot = slider.querySelector(`${dotSel}[data-slide-index="${idx}"]`) as any;
                    if (slide) slide.style.display = '';
                    if (dot) dot.style.background = '#fff';
                }
                current = idx;
            };
            slider.querySelectorAll(dotSel).forEach((dot: any) => {
                dot.addEventListener('click', () => showSlide(parseInt(dot.dataset.slideIndex, 10)));
            });
            if (slider.dataset.autoPlay === '1') {
                if (slider._autoPlayTimer) clearInterval(slider._autoPlayTimer);
                slider._autoPlayTimer = setInterval(() => { showSlide((current + 1) % count); }, 4500);
            }
        });
    }

    _mountDataSources(container: HTMLElement): void {
        container.querySelectorAll('[data-source]').forEach((el) => {
            const source = el.getAttribute('data-source');
            if (!source) return;
            if (source === 'products') {
                (el as HTMLElement).innerHTML += `
                    <div class="products-data-mount">
                        <div class="category-tabs products-category-tabs">
                            <button class="tab-btn active" data-category="all"><i class="fas fa-th-large"></i><span>すべて</span></button>
                        </div>
                        <div class="products-section"><div class="products-grid products-data-grid"></div></div>
                    </div>
                `;
                this.app.loadProducts();
                return;
            }
            if (source === 'orders') { (el as HTMLElement).innerHTML += '<div class="orders-data-mount"></div>'; this.app.loadOrdersTab(); return; }
            if (source === 'stamps') { (el as HTMLElement).innerHTML += '<div class="stamps-data-mount"></div>'; this.app.stampsManager.load(); return; }
            if (source === 'coupons') { (el as HTMLElement).innerHTML += '<div class="coupons-data-mount"></div>'; this.app.couponsManager.load(); return; }
            if (source === 'queue') { (el as HTMLElement).innerHTML += '<div class="queue-data-mount"></div>'; this.app.queueManager.load(); return; }
            if (source === 'reservations') { (el as HTMLElement).innerHTML += '<div class="reservations-data-mount reservations-section"></div>'; this.app.checkMembershipAndLoadReservations(); return; }
            if (source === 'member' || source === 'member_card') { (el as HTMLElement).innerHTML += '<div class="member-section member-card-mount"></div>'; this.app.loadMemberTab(); return; }
            if (source === 'gallery') { (el as HTMLElement).innerHTML += '<div class="gallery-data-mount"></div>'; this.app.galleryManager.load(); return; }
            if (source === 'announcements') { (el as HTMLElement).innerHTML += '<div class="announcements-data-mount announcements-section"></div>'; this.app.announcementManager.load(); return; }
            if (source === 'member_summary') { this._mountMemberSummary(el as HTMLElement); }
        });
    }

    /** 会員サマリーカード（ホーム上部のパーソナライズ表示）を会員APIから描画 */
    async _mountMemberSummary(el: HTMLElement): Promise<void> {
        const greeting = el.getAttribute('data-greeting') || '';
        const design = el.getAttribute('data-design') || 'rewards';
        const fill = (html: string): void => {
            el.innerHTML = html;
            this._bindInlineHandlers(el);
        };
        let data: any = null;
        try {
            if (this.app && this.app.apiClient && typeof AppConstants !== 'undefined') {
                const result = await this.app.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME);
                if (result && result.success && result.data) data = result.data;
            }
        } catch (_e) {
            data = null;
        }
        fill((() => {
            const premium = this._premium();
            if (premium) return premium.memberSummaryHtml(data, greeting, (s: any) => this._esc(s), design);
            return `<div class="member-summary-card member-summary-card--guest"><p class="ms-guest-title">${this._esc(greeting || 'ようこそ')}</p></div>`;
        })());
    }

    _buildMemberSummaryHtml(data: any, greeting: string, design: string): string {
        const premium = this._premium();
        if (premium) return premium.memberSummaryHtml(data, greeting, (s: any) => this._esc(s), design || 'rewards');
        return '';
    }

    _navigateBuilderHref(href: string, externalEl: Element | null): void {
        if (!href) return;
        if (href.startsWith('#')) {
            const tab = href.replace('#', '');
            if (this.app && tab) this.app.switchTab(tab);
            return;
        }
        if (externalEl && externalEl.getAttribute('data-builder-external') === '1'
            && typeof window !== 'undefined' && window.__IS_LIFF__
            && typeof liff !== 'undefined' && typeof liff.openWindow === 'function') {
            liff.openWindow({ url: href, external: false });
            return;
        }
        if (typeof window !== 'undefined') window.location.href = href;
    }

    _bindInlineHandlers(section: any): void {
        if (!section || section.dataset.builderNavBound === '1') return;
        section.dataset.builderNavBound = '1';
        section.addEventListener('click', (e: any) => {
            const btn = e.target.closest('.builder-btn, .builder-nav, .builder-promo-banner--link, .builder-collection__item, .builder-editorial-card--link');
            if (!btn) return;
            const href = btn.getAttribute('href');
            if (!href) return;
            e.preventDefault();
            this._navigateBuilderHref(href, btn);
        });
    }
}
