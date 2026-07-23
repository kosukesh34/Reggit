/**
 * LiffLayoutManager
 *
 * LIFF Builder のカスタムレイアウトを描画するマネージャー。
 * admin の LIFF Designer で保存したブロック配列をユーザー画面に反映する。
 */
declare const liff: any;
import { DomHelper } from '../../shared/utils/domHelper';
import { AppConstants } from '../../shared/constants';
import { isIosFixedUiDemo } from '../ios/demoMode';

export class LiffLayoutManager {
    app: any;
    tabSettings: Record<string, any>;
    _memberMeCache: Promise<any> | null;

    static readonly STATS_SAMPLE_VALUES: Record<string, string> = {
        points: '1,280',
        rank: 'ゴールド',
        points_to_next: '720',
    };

    constructor(app: any) {
        this.app = app;
        this.tabSettings = (typeof window !== 'undefined' && window.__LIFF_TAB_SETTINGS__)
            ? window.__LIFF_TAB_SETTINGS__
            : {};
        this._memberMeCache = null;
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
            this._memberMeCache = null;
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
     * タブ切替時の描画。固定UIモードでは Designer レイアウトを無視する。
     */
    render(tab: string): void {
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('LiffLayoutManager.render', { tab });
        const sectionId = tab + 'Section';
        const container = document.getElementById(sectionId) as HTMLElement | null;
        if (container === null) {
            if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('LiffLayoutManager.render: no container', { sectionId });
            return;
        }
        // iOS 固定UI: 店舗 Designer のブロック配列は使わない
        this._loadDefaultTabContent(tab);
    }

    _flagEnabled(key: string): boolean {
        if (isIosFixedUiDemo()) {
            return true;
        }
        const flags = (typeof window !== 'undefined' && window.__FEATURE_FLAGS__) ? window.__FEATURE_FLAGS__ : {};
        return flags[key] !== false;
    }

    _buildIosHomeHtml(): string {
        const title = (window.__FRONTEND_HEADER__ && typeof window.__FRONTEND_HEADER__.title === 'string'
            && window.__FRONTEND_HEADER__.title.trim() !== '')
            ? window.__FRONTEND_HEADER__.title
            : (document.title || 'Reggit');

        const actions: Array<{ tab: string; en: string; ja: string; svg: string }> = [];
        if (this._flagEnabled('feature_orders_enabled')) {
            actions.push({
                tab: 'products',
                en: 'Order',
                ja: '事前に注文する',
                svg: '<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M8 4h8v3H8V4zM5 8h14l-1.2 11.2A2 2 0 0 1 15.8 21H8.2a2 2 0 0 1-2-1.8L5 8z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 12h6M9 15h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
            });
        }
        if (this._flagEnabled('feature_members_enabled')) {
            actions.push({
                tab: 'member',
                en: 'Membership',
                ja: '会員証を表示する',
                svg: '<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="12" rx="2.5" stroke="currentColor" stroke-width="1.6"/><path d="M3 10h18M7 15h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
            });
        }
        if (this._flagEnabled('feature_queue_enabled')) {
            actions.push({
                tab: 'queue',
                en: 'Queue',
                ja: '順番待ち状況を確認',
                svg: '<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><circle cx="8" cy="9" r="2.2" stroke="currentColor" stroke-width="1.6"/><circle cx="16" cy="9" r="2.2" stroke="currentColor" stroke-width="1.6"/><path d="M4 18c.6-2.4 2.4-3.6 4-3.6s3.4 1.2 4 3.6M12 18c.6-2.4 2.4-3.6 4-3.6s3.4 1.2 4 3.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
            });
        }

        const actionsHtml = actions.slice(0, 3).map((a) => `
            <button type="button" class="lp-home-action" data-ios-nav-tab="${a.tab}">
                <span class="lp-home-action__icon" aria-hidden="true">${a.svg}</span>
                <span class="lp-home-action__en">${this._esc(a.en)}</span>
                <span class="lp-home-action__ja">${this._esc(a.ja)}</span>
                <span class="lp-home-action__chevron" aria-hidden="true">›</span>
            </button>
        `).join('');

        const day = new Date().getDate();
        const queueNo = String(day) + '2';
        const queueCardHtml = !this._flagEnabled('feature_queue_enabled')
            ? ''
            : (isIosFixedUiDemo()
                ? `
                <button type="button" class="lp-home-queue" data-ios-nav-tab="queue">
                    <span class="lp-home-queue__ticket" aria-hidden="true">${this._esc(queueNo)}</span>
                    <span class="lp-home-queue__body">
                        <span class="lp-home-queue__label">あなたの現在の番号</span>
                        <span class="lp-home-queue__num">${this._esc(queueNo)} <small>番</small></span>
                    </span>
                    <span class="lp-home-queue__meta">
                        <span class="lp-home-queue__badge">ご案内中</span>
                        <span class="lp-home-queue__wait">あと 5 組 · 目安 25 分</span>
                    </span>
                    <span class="lp-home-queue__chevron" aria-hidden="true">›</span>
                </button>
                `
                : `
                <button type="button" class="lp-home-queue" data-ios-nav-tab="queue">
                    <span class="lp-home-queue__body">
                        <span class="lp-home-queue__label">順番待ち</span>
                        <span class="lp-home-queue__num">状況を確認する</span>
                    </span>
                    <span class="lp-home-queue__chevron" aria-hidden="true">›</span>
                </button>
                `);

        return `
            <div class="home-content ios-home lp-home" data-ios-home-version="5">
                <section class="lp-home-brand">
                    <p class="lp-home-brand__name">${this._esc(title)}</p>
                    <p class="lp-home-brand__tag">スマートに、心地よく、あなたの時間を。</p>
                </section>

                ${queueCardHtml}

                ${actionsHtml !== '' ? `<div class="lp-home-actions">${actionsHtml}</div>` : ''}

                ${this._flagEnabled('feature_members_enabled') ? `
                <button type="button" class="lp-home-promo" data-ios-nav-tab="member">
                    <span class="lp-home-promo__eyebrow">Reggit Members</span>
                    <span class="lp-home-promo__title">会員登録で特典を受け取ろう</span>
                    <span class="lp-home-promo__text">登録すると、ドリンク1杯サービス！</span>
                    <span class="lp-home-promo__chevron" aria-hidden="true">›</span>
                </button>
                ` : ''}

                <div class="ios-status-card" id="iosHomeStatus" hidden>
                    <p class="ios-status-card__title" id="iosHomeStatusTitle"></p>
                    <p class="ios-status-card__text" id="iosHomeStatusText"></p>
                </div>
                <p class="ios-section-label">お知らせ</p>
                <div class="ios-group ios-announce-list announcements-data-mount" id="iosHomeAnnouncements">
                    <div class="ios-row home-loading"><div class="ios-row__body"><div class="ios-row__title">読み込み中…</div></div></div>
                </div>
            </div>
        `;
    }

    _iosMoreIcon(kind: string): string {
        const icons: Record<string, string> = {
            history: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 7v5l3 2" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="12" r="8" stroke="#fff" stroke-width="1.8"/></svg>',
            megaphone: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 10v4h3l7 4V6L7 10H4z" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/><path d="M18 9.5v5" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/></svg>',
            photos: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3.5" y="5" width="17" height="14" rx="2" stroke="#fff" stroke-width="1.8"/><circle cx="9" cy="10" r="1.6" fill="#fff"/><path d="M3.5 16l4.5-4 3.5 3 3-2.5 6 3.5" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/></svg>',
            ticket: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 8.5A2.5 2.5 0 0 0 6.5 11 2.5 2.5 0 0 0 4 13.5v2h16v-2A2.5 2.5 0 0 0 17.5 11 2.5 2.5 0 0 0 20 8.5v-2H4v2z" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/></svg>',
            stamp: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="9" r="4" stroke="#fff" stroke-width="1.8"/><path d="M6 19h12" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/></svg>',
            link: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M10 13a5 5 0 0 0 7.1.1l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/><path d="M14 11a5 5 0 0 0-7.1-.1l-2 2a5 5 0 0 0 7.1 7.1l1.1-1.1" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/></svg>',
        };
        return icons[kind] || icons.link;
    }

    _iosMoreRow(tab: string, label: string, iconKind: string): string {
        const colorClass =
            iconKind === 'history' ? 'ios-row__icon--gray'
                : (iconKind === 'megaphone' ? 'ios-row__icon--orange'
                    : (iconKind === 'photos' ? 'ios-row__icon--pink'
                        : (iconKind === 'ticket' ? 'ios-row__icon--purple'
                            : (iconKind === 'stamp' ? 'ios-row__icon--green' : 'ios-row__icon--gray'))));
        return `
            <button type="button" class="ios-row" data-ios-nav-tab="${this._esc(tab)}">
                <span class="ios-row__icon ${colorClass}" aria-hidden="true">${this._iosMoreIcon(iconKind)}</span>
                <span class="ios-row__body"><span class="ios-row__title">${this._esc(label)}</span></span>
                <span class="ios-row__chevron" aria-hidden="true">›</span>
            </button>
        `;
    }

    _buildIosWalletHtml(data: any): string {
        const isMember = !!(data && data.is_member === true);
        let title = '会員登録でポイントが貯まります';
        let hint = 'タップして開く';
        if (isMember) {
            const points = Number(data.points != null ? data.points : (data.member && data.member.points) || 0);
            const rankLabel = data.current_rank_label != null ? String(data.current_rank_label) : '';
            const pointsLabel = points.toLocaleString('ja-JP') + ' pt';
            title = rankLabel !== '' ? `${pointsLabel} · ${rankLabel}` : pointsLabel;
            hint = 'タップして会員証を開く';
        }
        return `
                <button type="button" class="ios-wallet" data-ios-nav-tab="member">
                    <span class="ios-wallet__bg" aria-hidden="true"></span>
                    <span class="ios-wallet__body">
                        <span class="ios-wallet__label">会員カード</span>
                        <span class="ios-wallet__title">${this._esc(title)}</span>
                        <span class="ios-wallet__hint">${this._esc(hint)}</span>
                    </span>
                    <span class="ios-wallet__chevron" aria-hidden="true">›</span>
                </button>
            `;
    }

    async _refreshIosWallet(container: HTMLElement): Promise<void> {
        if (!this._flagEnabled('feature_members_enabled')) {
            return;
        }
        let data = await this._fetchMemberMe();
        if ((!data || data.is_member !== true)
            && isIosFixedUiDemo()
            && this.app
            && typeof this.app.getDesignerSampleMemberData === 'function') {
            data = this.app.getDesignerSampleMemberData();
        }
        const next = this._buildIosWalletHtml(data);
        const current = container.querySelector('.ios-wallet');
        if (current !== null) {
            current.outerHTML = next.trim();
        } else if (container.querySelector('.lp-home') !== null) {
            // LP風ホームはプロモカードを使うため wallet は差し込まない
            return;
        } else {
            const hero = container.querySelector('.ios-hero');
            if (hero !== null) {
                hero.insertAdjacentHTML('afterend', next);
            }
        }
        this._bindIosHomeNav(container);
    }

    _bindIosHomeNav(container: HTMLElement): void {
        if (container.dataset.iosHomeNavBound === '1') {
            return;
        }
        container.dataset.iosHomeNavBound = '1';
        container.addEventListener('click', (event) => {
            const target = event.target as HTMLElement | null;
            const el = target !== null ? target.closest('[data-ios-nav-tab]') : null;
            if (!(el instanceof HTMLElement)) {
                return;
            }
            const tab = el.getAttribute('data-ios-nav-tab');
            if (tab !== null && tab !== '' && this.app && typeof this.app.switchTab === 'function') {
                void this.app.switchTab(tab);
            }
        });
    }

    _productsViewToggleHtml(activeView: string): string {
        const isCollection = activeView === 'collection';
        return `
            <div class="ios-large-title-row">
                <h1 class="ios-large-title">オーダー</h1>
                <div class="ios-view-toggle" role="group" aria-label="表示切替">
                    <button type="button" class="ios-view-toggle__btn${isCollection ? ' is-active' : ''}" data-products-view="collection" aria-label="コレクション表示" title="コレクション" aria-pressed="${isCollection ? 'true' : 'false'}">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="7.5" height="7.5" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5" stroke="currentColor" stroke-width="1.8"/></svg>
                    </button>
                    <button type="button" class="ios-view-toggle__btn${!isCollection ? ' is-active' : ''}" data-products-view="list" aria-label="リスト表示" title="リスト" aria-pressed="${!isCollection ? 'true' : 'false'}">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </button>
                </div>
            </div>
        `;
    }

    _readProductsViewMode(): string {
        if (this.app && typeof this.app.getProductsViewMode === 'function') {
            return this.app.getProductsViewMode();
        }
        try {
            const stored = String(window.localStorage.getItem('liff_products_view_mode') || '').trim();
            if (stored === 'collection' || stored === 'list') {
                return stored;
            }
        } catch (_e) { /* ignore */ }
        return 'list';
    }

    _loadDefaultTabContent(tab: string): void {
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('_loadDefaultTabContent', { tab });
        if (!this.app) return;
        const sectionId = tab + 'Section';
        const container = document.getElementById(sectionId);
        if (tab === 'products' && container && !container.querySelector('.products-data-grid') && !container.querySelector('#productsGrid')) {
            const viewMode = this._readProductsViewMode();
            container.innerHTML = `
                ${this._productsViewToggleHtml(viewMode)}
                <div class="products-data-mount" data-products-view="${viewMode}">
                    <div class="category-tabs products-category-tabs" id="categoryTabs">
                        <button class="tab-btn active" data-category="all"><span>すべて</span></button>
                    </div>
                    <div class="products-section"><div class="products-grid products-data-grid" id="productsGrid"></div></div>
                </div>
            `;
            if (typeof this.app.bindProductsViewToggle === 'function') {
                this.app.bindProductsViewToggle(container);
            }
        } else if (tab === 'products' && container && !container.querySelector('.ios-view-toggle')) {
            const viewMode = this._readProductsViewMode();
            const oldTitle = container.querySelector('.ios-large-title');
            if (oldTitle !== null) {
                oldTitle.outerHTML = this._productsViewToggleHtml(viewMode);
            } else {
                container.insertAdjacentHTML('afterbegin', this._productsViewToggleHtml(viewMode));
            }
            const mount = container.querySelector('.products-data-mount');
            if (mount instanceof HTMLElement) {
                mount.setAttribute('data-products-view', viewMode);
            }
            if (typeof this.app.bindProductsViewToggle === 'function') {
                this.app.bindProductsViewToggle(container);
            }
        } else if (tab === 'products' && container && !container.querySelector('.ios-large-title') && !container.querySelector('.ios-large-title-row')) {
            const viewMode = this._readProductsViewMode();
            container.insertAdjacentHTML('afterbegin', this._productsViewToggleHtml(viewMode));
            const mount = container.querySelector('.products-data-mount');
            if (mount instanceof HTMLElement) {
                mount.setAttribute('data-products-view', viewMode);
            }
            if (typeof this.app.bindProductsViewToggle === 'function') {
                this.app.bindProductsViewToggle(container);
            }
        }
        if (tab === 'home' && container && (!container.querySelector('.ios-home') || container.querySelector('.ios-home')?.getAttribute('data-ios-home-version') !== '5')) {
            container.innerHTML = this._buildIosHomeHtml();
            this._bindIosHomeNav(container);
            void this._refreshIosWallet(container);
        }
        if (tab === 'orders' && container && !container.querySelector('.orders-data-mount')) {
            container.innerHTML = `
                <h1 class="ios-large-title">注文履歴</h1>
                <div class="orders-data-mount"></div>
            `;
        }
        if (tab === 'gallery' && container && !container.querySelector('.gallery-data-mount')) {
            container.innerHTML = `
                <h1 class="ios-large-title">ギャラリー</h1>
                <div class="gallery-data-mount"></div>
            `;
        }
        if (tab === 'announcements' && container && !container.querySelector('.announcements-data-mount')) {
            container.innerHTML = `
                <h1 class="ios-large-title">お知らせ</h1>
                <div class="announcements-data-mount"></div>
            `;
        }
        if (tab === 'stamps' && container && !container.querySelector('.stamps-data-mount') && container.childElementCount === 0) {
            container.innerHTML = `<div class="stamps-data-mount"></div>`;
        }
        if (tab === 'coupons' && container && !container.querySelector('.coupons-data-mount') && container.childElementCount === 0) {
            container.innerHTML = `<div class="coupons-data-mount"></div>`;
        }
        if (tab === 'queue' && container && !container.querySelector('.queue-data-mount') && container.childElementCount === 0) {
            container.innerHTML = `<div class="queue-data-mount"></div>`;
        }
        if (tab === 'reservations' && container && !container.querySelector('.reservations-data-mount') && container.childElementCount === 0) {
            container.innerHTML = `<div class="reservations-data-mount"></div>`;
        }
        if (tab === 'member' && container && !container.querySelector('.member-card-mount') && !container.querySelector('.member-section') && container.childElementCount === 0) {
            container.innerHTML = `<div class="member-section member-card-mount"></div>`;
        }
        if (tab === 'designlab' && container !== null) {
            if (this.app.designLabManager && typeof this.app.designLabManager.mount === 'function') {
                this.app.designLabManager.mount(container);
            }
        }
        const tabLoaders: Record<string, () => void> = {
            home: () => {
                const homeSection = document.getElementById('homeSection');
                if (homeSection !== null) {
                    void this._refreshIosWallet(homeSection);
                }
                if (this.app.announcementManager && typeof this.app.announcementManager.load === 'function') {
                    void this.app.announcementManager.load();
                }
            },
            member: () => this.app.loadMemberTab(),
            products: () => this.app.loadProducts(),
            orders: () => this.app.loadOrdersTab(),
            stamps: () => this.app.stampsManager.load(),
            coupons: () => this.app.couponsManager.load(),
            queue: () => this.app.queueManager.load(),
            reservations: () => this.app.checkMembershipAndLoadReservations(),
            gallery: () => this.app.galleryManager.load(),
            announcements: () => this.app.announcementManager.load(),
            designlab: () => {
                const section = document.getElementById('designlabSection');
                if (section !== null && this.app.designLabManager) {
                    this.app.designLabManager.mount(section);
                }
            },
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
            announcements: 'announcements',
        };
    }

    static get DEFAULT_QUICK_ACTIONS(): Array<{ icon: string; label: string; action: string }> {
        return [
            { icon: 'fa-utensils', label: 'メニュー', action: '#products' },
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
        const shadow = block.shadow ? 'box-shadow:0 4px 12px rgba(0,0,0,0.1);' : '';
        const pad = block.padding !== undefined && block.padding !== null ? `padding:${block.padding}px;` : '';
        const bgColor = block.background_color ? `background-color:${block.background_color};` : '';
        const textColor = block.text_color ? `color:${block.text_color};` : '';
        const baseStyle = `border-radius:${br}px;${mb}${shadow}${pad}${bgColor}${textColor}`;

        if (type === 'data') {
            const cardDesign = block.card_design ? ` data-card-design="${esc(block.card_design)}"` : '';
            const stampDesign = block.stamp_design ? ` data-stamp-design="${esc(block.stamp_design)}"` : '';
            const stampSlotStyle = block.stamp_slot_style ? ` data-stamp-slot-style="${esc(block.stamp_slot_style)}"` : '';
            const stampFilledStyle = block.stamp_filled_style ? ` data-stamp-filled-style="${esc(block.stamp_filled_style)}"` : '';
            const stampAccent = block.stamp_accent_color ? ` data-stamp-accent-color="${esc(block.stamp_accent_color)}"` : '';
            const design = esc(block.design || 'cards');
            return `<section class="builder-block builder-data-block builder-data-block--design-${design}" style="${baseStyle}" data-source="${esc(block.source || '')}" data-design="${design}"${cardDesign}${stampDesign}${stampSlotStyle}${stampFilledStyle}${stampAccent}></section>`;
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
        if (type === 'stats_row') {
            const items = Array.isArray(block.items) ? block.items : [];
            const label = block.section_label ? `<div class="builder-section-head"><span class="builder-section-head__label">${esc(block.section_label)}</span></div>` : '';
            const cells = items.map((it: any) => {
                const field = LiffLayoutManager.resolveStatsField(it);
                return `<div class="builder-stats-row__cell" data-stats-field="${esc(field)}">
                    <span class="builder-stats-row__value">${esc(it.value || '')}</span>
                    <span class="builder-stats-row__label">${esc(it.label || '')}${it.hint ? ` <em>${esc(it.hint)}</em>` : ''}</span>
                </div>`;
            }).join('');
            return `<section class="builder-block builder-stats-row" data-source="stats_row" style="${mb}">${label}<div class="builder-stats-row__grid">${cells}</div></section>`;
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
                : `background:linear-gradient(135deg,${block.gradient_start || 'var(--primary-color)'} 0%,${block.gradient_end || 'var(--primary-dark)'} 100%);`;
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
            const full = block.full_width !== false ? ' builder-btn--full' : '';
            const action = block.action || '#';
            const isExternal = action !== '#' && (action.startsWith('http://') || action.startsWith('https://'));
            const dataExternal = isExternal ? ' data-builder-external="1"' : '';
            const design = block.design || 'cta';
            const stickyWrap = design === 'sticky' ? ' builder-button-wrap--sticky' : '';
            const stickyBtn = design === 'sticky' ? ' builder-btn--sticky' : '';
            const colorStyle = block.color
                ? ` style="--btn-bg:${esc(block.color)};${block.text_color ? `--btn-fg:${esc(block.text_color)};` : ''}"`
                : '';
            return `<section class="builder-block builder-button-wrap${stickyWrap}" style="text-align:center;padding:8px;${baseStyle}">
                <a href="${esc(action)}" class="builder-btn builder-btn--cta${stickyBtn}${full}"${dataExternal}${colorStyle}>${esc(block.text)}</a>
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
            const globalUrl = (typeof window !== 'undefined' && window.__LINE_FRIEND_ADD_URL__) ? String(window.__LINE_FRIEND_ADD_URL__) : '';
            const friendAddUrl = String(block.url || globalUrl || '');
            if (!friendAddUrl) return '';
            const text = block.text || '友だち追加';
            const colorStyle = block.color
                ? ` style="--btn-bg:${esc(block.color)};${block.text_color ? `--btn-fg:${esc(block.text_color)};` : ''}"`
                : ' style="--btn-bg:#06C755;"';
            return `<section class="builder-block builder-friend-add" style="text-align:center;padding:8px;${baseStyle}" data-friend-add-url="${esc(friendAddUrl)}">
                <a href="${esc(friendAddUrl)}" class="builder-btn builder-btn--friend-add"${colorStyle} data-builder-external="1"><i class="fab fa-line"></i> ${esc(text)}</a>
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
                + `<i class="fas ${r.icon || 'fa-check'}" style="color:${r.color || 'var(--primary-color)'};font-size:16px;flex-shrink:0;"></i>`
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
            const bindField = block.field === 'points_to_next'
                || /次のランクまで/.test(String(block.text || ''))
                ? 'points_to_next'
                : '';
            const bindAttr = bindField !== '' ? ` data-source="banner_bind" data-bind-field="${esc(bindField)}"` : '';
            const inner = `${block.icon ? `<i class="fas ${block.icon}" style="font-size:16px;flex-shrink:0;"></i>` : ''}<span class="builder-promo-banner__text">${esc(block.text)}</span>`;
            const wrapStyle = `display:flex;align-items:center;gap:8px;padding:12px 14px;font-size:13px;font-weight:600;background:${block.color || 'rgba(var(--primary-rgb), 0.1)'};color:${block.text_color || 'var(--text-primary)'};${baseStyle}`;
            if (action) {
                const isExternal = action !== '#' && (action.startsWith('http://') || action.startsWith('https://'));
                const dataExternal = isExternal ? ' data-builder-external="1"' : '';
                return `<section class="builder-block"${bindAttr} style="${wrapStyle}">
                    <a href="${esc(action)}" class="builder-nav"${dataExternal} style="display:flex;align-items:center;gap:8px;color:inherit;text-decoration:none;width:100%;">${inner}</a>
                </section>`;
            }
            return `<section class="builder-block"${bindAttr} style="${wrapStyle}">${inner}</section>`;
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
        if (block.source === 'gallery') {
            const label = block.section_label || block.title || '';
            const head = label ? `<div class="builder-section-head"><span class="builder-section-head__label">${esc(label)}</span></div>` : '';
            return `<section class="builder-block builder-carousel-wrap builder-carousel-wrap--design-${design}" style="${baseStyle}" data-source="gallery_slider" data-design="${design}" data-slide-height="${h}" data-auto-play="${block.auto_play !== false ? '1' : '0'}" data-show-dots="${block.show_dots !== false ? '1' : '0'}">
                ${head}
                <div class="builder-carousel builder-carousel--loading" style="--carousel-h:${h}px">
                    <div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>
                </div>
            </section>`;
        }
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
                ? (isOutline
                    ? `style="--btn-accent:${esc(color)};color:${esc(color)};border-color:${esc(color)};"`
                    : `style="--btn-bg:${esc(color)};"`)
                : '';
            return `<a href="${esc(action)}" class="builder-btn builder-btn-group__btn${isOutline ? ' builder-btn-group__btn--outline' : ''}"${dataExternal} ${style}>${esc(b.text)}</a>`;
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
            if (source === 'gallery_slider') { this._mountGallerySlider(el as HTMLElement); return; }
            if (source === 'announcements') { (el as HTMLElement).innerHTML += '<div class="announcements-data-mount"></div>'; this.app.announcementManager.load(); return; }
            if (source === 'announcement_detail') { return; }
            if (source === 'product_detail' || source === 'coupon_detail' || source === 'gallery_detail' || source === 'stamp_detail') { return; }
            if (source === 'order_cart' || source === 'order_confirm' || source === 'order_complete') { return; }
            if (source === 'reservation_new' || source === 'reservation_confirm' || source === 'reservation_complete') { return; }
            if (source === 'member_summary') { this._mountMemberSummary(el as HTMLElement); return; }
            if (source === 'stats_row') { this._mountStatsRow(el as HTMLElement); return; }
            if (source === 'banner_bind') { this._mountBannerBind(el as HTMLElement); return; }
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
        const data = await this._fetchMemberMe();
        fill((() => {
            const premium = this._premium();
            if (premium) return premium.memberSummaryHtml(data, greeting, (s: any) => this._esc(s), design);
            return `<div class="member-summary-card member-summary-card--guest"><p class="ms-guest-title">${this._esc(greeting || 'ようこそ')}</p></div>`;
        })());
    }

    /** ギャラリーAPIの画像をカルーセルとして描画する */
    async _mountGallerySlider(el: HTMLElement): Promise<void> {
        if (!this.app || !this.app.galleryManager || typeof this.app.galleryManager.loadCarousel !== 'function') {
            return;
        }
        await this.app.galleryManager.loadCarousel(el, {
            design: el.getAttribute('data-design') || 'hero',
            slideHeight: Number(el.getAttribute('data-slide-height') || 200),
            autoPlay: el.getAttribute('data-auto-play') !== '0',
            showDots: el.getAttribute('data-show-dots') !== '0',
            onReady: () => {
                this._initSliders(el);
                this._bindInlineHandlers(el);
            },
        });
    }

    /** stats_row のポイント・ランク等を会員APIの実値で上書きする */
    async _mountStatsRow(el: HTMLElement): Promise<void> {
        const cells = el.querySelectorAll<HTMLElement>('[data-stats-field]');
        const needsLive = Array.from(cells).some((cell) => {
            const field = cell.getAttribute('data-stats-field') || 'static';
            return field !== 'static';
        });
        if (!needsLive) return;

        const data = await this._fetchMemberMe();
        cells.forEach((cell) => {
            const field = cell.getAttribute('data-stats-field') || 'static';
            if (field === 'static') return;
            const valueEl = cell.querySelector('.builder-stats-row__value');
            if (!valueEl) return;
            valueEl.textContent = LiffLayoutManager.formatStatsValue(field, data);
        });
    }

    /** banner の「次のランクまで」文言を会員APIの実値で上書きする */
    async _mountBannerBind(el: HTMLElement): Promise<void> {
        const field = el.getAttribute('data-bind-field') || '';
        if (field !== 'points_to_next') return;
        const textEl = el.querySelector('.builder-promo-banner__text');
        if (!textEl) return;
        const data = await this._fetchMemberMe();
        textEl.textContent = LiffLayoutManager.formatBannerPointsToNext(data);
    }

    _fetchMemberMe(): Promise<any> {
        if (this._memberMeCache !== null) {
            return this._memberMeCache;
        }
        this._memberMeCache = (async () => {
            try {
                if (typeof window !== 'undefined') {
                    const params = new URLSearchParams(window.location.search || '');
                    if (params.get('designer_live_preview') === '1') {
                        const payload = window.__DESIGNER_PREVIEW_MEMBER_PAYLOAD__;
                        if (payload && typeof payload === 'object') {
                            return payload;
                        }
                        if (this.app && typeof this.app.getDesignerSampleMemberData === 'function') {
                            return this.app.getDesignerSampleMemberData();
                        }
                    }
                }
                if (this.app && this.app.apiClient && typeof AppConstants !== 'undefined') {
                    const result = await this.app.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME);
                    if (result && result.success && result.data) {
                        return result.data;
                    }
                }
            } catch (_e) {
                return null;
            }
            return null;
        })();
        return this._memberMeCache;
    }

    static resolveStatsField(item: { field?: string; label?: string } | null | undefined): string {
        if (item == null) return 'static';
        const explicit = typeof item.field === 'string' ? item.field.trim() : '';
        if (explicit === 'points' || explicit === 'rank' || explicit === 'points_to_next' || explicit === 'static') {
            return explicit;
        }
        const label = typeof item.label === 'string' ? item.label.trim() : '';
        if (label === 'ポイント' || label === '保有ポイント' || label === '所持ポイント') {
            return 'points';
        }
        if (label === 'ランク' || label === '会員ランク' || label === '現在のランク') {
            return 'rank';
        }
        if (/次回特典|次のランク|特典まで|ランクアップまで/.test(label)) {
            return 'points_to_next';
        }
        return 'static';
    }

    static formatStatsValue(field: string, data: any): string {
        const isMember = !!(data && data.is_member === true);
        if (!isMember) {
            return '—';
        }
        if (field === 'points') {
            const points = Number(data.points != null ? data.points : (data.member && data.member.points) || 0);
            return points.toLocaleString('ja-JP');
        }
        if (field === 'rank') {
            const rankLabel = data.current_rank_label != null ? String(data.current_rank_label) : '';
            return rankLabel !== '' ? rankLabel : '—';
        }
        if (field === 'points_to_next') {
            if (data.show_rank_progress === false) {
                return '—';
            }
            if (data.points_to_next_rank == null) {
                return 'MAX';
            }
            return Number(data.points_to_next_rank).toLocaleString('ja-JP');
        }
        return '—';
    }

    static formatBannerPointsToNext(data: any): string {
        const isMember = !!(data && data.is_member === true);
        if (!isMember) {
            return '会員登録でランク特典が使えます';
        }
        if (data.show_rank_progress === false) {
            return '会員ランク特典をチェック';
        }
        const nextRankLabel = data.next_rank_label != null ? String(data.next_rank_label) : '';
        if (data.points_to_next_rank == null || nextRankLabel === '') {
            return '最高ランクに到達しています';
        }
        const toNext = Number(data.points_to_next_rank).toLocaleString('ja-JP');
        return `次の${nextRankLabel}まであと ${toNext} pt`;
    }

    _buildMemberSummaryHtml(data: any, greeting: string, design: string): string {
        const premium = this._premium();
        if (premium) return premium.memberSummaryHtml(data, greeting, (s: any) => this._esc(s), design || 'rewards');
        return '';
    }

    _navigateBuilderHref(href: string, externalEl: Element | null): void {
        if (!href) return;
        if (href.startsWith('#')) {
            const route = href.replace(/^#/, '');
            if (typeof window !== 'undefined') {
                window.location.hash = '#' + route;
                return;
            }
            const tab = route;
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
