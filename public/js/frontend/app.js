class ReggitApp {
    constructor() {
        this.apiClient = new ApiClient();
        this.deviceManager = new DeviceManager(this.apiClient);
        this.productManager = new ProductManager();
        this.cartManager = new CartManager();
        this.orderManager = new OrderManager(this.apiClient);
        this.memberManager = new MemberManager(this.apiClient, this.orderManager);
        this.stampsManager = new StampsManager(this.apiClient);
        this.couponsManager = new CouponsManager(this.apiClient);
        this.galleryManager = new GalleryManager(this.apiClient);
        this.layoutManager = new LiffLayoutManager(this);
        this.queueManager = new QueueManager(this.apiClient);
        this.reservationManager = new ReservationManager(this);
        this.announcementManager = new AnnouncementManager(this.apiClient);
        this.checkoutManager = new CheckoutManager(this.apiClient, this.cartManager);
        this.orderMode = this.getOrderMode();
        this.designerPreviewSource = this.getDesignerPreviewSource();
        this.designerPreviewEmbed = this.isDesignerPreviewEmbed();
        this.designerPreviewSample = this.isDesignerPreviewSample();
        this.designerPreviewBootstrappedSource = null;

        this.currentTab = this.getInitialTab();
        this.init();
    }

    getInitialTab() {
        const allowedTabs = new Set(['home', 'products', 'orders', 'stamps', 'coupons', 'queue', 'member', 'reservations', 'gallery']);
        if (typeof window.__NATIVE_TARGET_TAB__ !== 'undefined' && window.__NATIVE_TARGET_TAB__ && allowedTabs.has(window.__NATIVE_TARGET_TAB__)) {
            return window.__NATIVE_TARGET_TAB__;
        }
        const fromUrl = this.getTabFromUrl();
        if (fromUrl) return fromUrl;
        const firstBtn = document.querySelector('.main-tab-btn.active') || document.querySelector('.main-tab-btn');
        if (firstBtn && firstBtn.dataset.tab) return firstBtn.dataset.tab;
        return AppConstants.TABS.PRODUCTS;
    }

    getTabFromUrl() {
        if (typeof window === 'undefined') return null;
        const allowedTabs = new Set(['home', 'products', 'orders', 'stamps', 'coupons', 'queue', 'member', 'reservations', 'gallery']);
        const params = new URLSearchParams(window.location.search || '');
        const tab = params.get('tab');
        if (tab && allowedTabs.has(tab)) return tab;
        const liffState = params.get('liff.state');
        if (liffState) {
            const normalized = liffState.startsWith('?') ? liffState.slice(1) : liffState;
            const stateParams = new URLSearchParams(normalized);
            const stateTab = stateParams.get('tab');
            if (stateTab && allowedTabs.has(stateTab)) return stateTab;
        }
        return null;
    }

    getOrderMode() {
        if (typeof window === 'undefined') return 'both';
        const mode = String(window.__ORDER_MODE__ || 'both');
        if (mode === 'mobile_only' || mode === 'in_store_only' || mode === 'both') return mode;
        return 'both';
    }

    getDesignerPreviewSource() {
        if (typeof window === 'undefined') return null;
        const params = new URLSearchParams(window.location.search || '');
        if (params.get('designer_live_preview') !== '1') return null;
        const source = params.get('designer_source');
        const allowed = new Set(['products', 'orders', 'stamps', 'coupons', 'queue', 'reservations', 'member', 'member_card', 'gallery', 'announcements']);
        if (!source || !allowed.has(source)) return null;
        return source;
    }

    isDesignerPreviewEmbed() {
        if (typeof window === 'undefined') return false;
        const params = new URLSearchParams(window.location.search || '');
        return params.get('designer_embed') === '1';
    }

    isDesignerPreviewSample() {
        if (typeof window === 'undefined') return false;
        const params = new URLSearchParams(window.location.search || '');
        return params.get('designer_sample') === '1';
    }

    getDesignerPreviewTab(source) {
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
        return map[source] || 'home';
    }

    shouldOpenMemberRegisterOnLoad() {
        if (typeof window === 'undefined') return false;
        const params = new URLSearchParams(window.location.search || '');
        return params.get('member_register') === '1';
    }

    get memberData() {
        return this.memberManager.getMemberData();
    }

    init() {
        const app = this;
        if (typeof window.__mobileOrderLog === 'function') {
            window.__mobileOrderLog('app.init', { currentTab: app.currentTab, ua: typeof navigator !== 'undefined' ? navigator.userAgent : '' });
        }
        (async () => {
            await app.deviceManager.restoreSessionIfNeeded();
            app._continueInit();
        })();
    }

    _continueInit() {
        this.deviceManager.init();
        this.preventZoom();
        this.setupEventListeners();
        this.setupPullToRefresh();
        // デザイナー埋め込みプレビューではカスタムレイアウトを描画せず、
        // 実画面ローダー（products/member/stamps など）だけを使って表示する
        if (!(this.designerPreviewSource && this.designerPreviewEmbed)) {
            this.layoutManager.render(this.currentTab);
        }
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('app.init render done', { tab: this.currentTab });
        if (this.designerPreviewSource) {
            this.applyDesignerPreviewMode(false);
        }
        this.loadProductsWhenReady();
        this.announcementManager.load();
        if (!(typeof window !== 'undefined' && window.__IS_LIFF__ && !window.__LINE_USER_ID__)) {
            this.memberManager.preload();
        }
        if (this.shouldOpenMemberRegisterOnLoad()) {
            this.switchTab(AppConstants.TABS.MEMBER);
            this.memberManager.openRegisterFullScreen();
        }
        this.setupNativePushDeepLink();
    }

    /** ネイティブアプリ: プッシュ通知タップ等で指定タブを開く */
    setupNativePushDeepLink() {
        if (typeof window === 'undefined' || !window.__MOBILE_APP_MODE__) return;
        const allowedTabs = new Set(['home', 'products', 'orders', 'stamps', 'coupons', 'queue', 'member', 'reservations', 'gallery']);
        window.__nativeOpenTab__ = (tabKey, options) => {
            options = options || {};
            if (!tabKey || !allowedTabs.has(tabKey)) return false;
            this.switchTab(tabKey);
            if (tabKey === AppConstants.TABS.COUPONS && this.couponsManager) {
                if (options.couponReceived) {
                    this.couponsManager.onCouponReceived(options);
                } else {
                    this.couponsManager.load();
                }
            }
            return true;
        };
        window.addEventListener('mobile-coupon-received', () => {
            if (this.couponsManager) {
                this.couponsManager.onCouponReceived({ silent: false });
            }
        });
        if (typeof window.__NATIVE_PENDING_TAB__ !== 'undefined' && window.__NATIVE_PENDING_TAB__) {
            const pending = window.__NATIVE_PENDING_TAB__;
            window.__NATIVE_PENDING_TAB__ = null;
            window.__nativeOpenTab__(pending, { couponReceived: pending === AppConstants.TABS.COUPONS });
        }
    }

    /** スワイプ（プル）で画面を更新する */
    setupPullToRefresh() {
        if (typeof window === 'undefined' || !window.__MOBILE_APP_MODE__) return;
        const app = this;
        let startY = 0;
        let pullDistance = 0;
        let refreshEl = null;
        const MIN_PULL = 120;

        const createRefreshIndicator = () => {
            if (refreshEl) return refreshEl;
            refreshEl = document.createElement('div');
            refreshEl.className = 'pull-to-refresh-indicator';
            refreshEl.setAttribute('aria-hidden', 'true');
            refreshEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>更新中...</span>';
            refreshEl.style.cssText = 'position:fixed;top:0;left:0;right:0;padding:12px;text-align:center;background:#f5f5f5;z-index:1000;transform:translateY(-100%);transition:transform 0.2s;font-size:14px;';
            document.body.appendChild(refreshEl);
            return refreshEl;
        };

        const getScrollContainer = () => document.querySelector('.tab-content.active .tab-section') || document.querySelector('.main-content');
        const scrollContainer = document.querySelector('.main-content') || document.body;
        scrollContainer.addEventListener('touchstart', (e) => {
            if (e.touches.length !== 1) return;
            const el = getScrollContainer();
            if (!el) return;
            const scrollTop = el.scrollTop || 0;
            if (scrollTop <= 2) {
                startY = e.touches[0].clientY;
                pullDistance = 0;
            }
        }, { passive: true });

        scrollContainer.addEventListener('touchmove', (e) => {
            if (e.touches.length !== 1) return;
            const el = getScrollContainer();
            if (!el || (el.scrollTop || 0) > 2) return;
            const y = e.touches[0].clientY;
            pullDistance = y - startY;
            if (pullDistance > 20) e.preventDefault();
        }, { passive: false });

        scrollContainer.addEventListener('touchend', () => {
            const el = getScrollContainer();
            if (!el || (el.scrollTop || 0) > 5) {
                pullDistance = 0;
                return;
            }
            const didPull = pullDistance >= MIN_PULL;
            pullDistance = 0;
            if (!didPull) return;
            const indicator = createRefreshIndicator();
            indicator.style.transform = 'translateY(0)';
            indicator.querySelector('i').classList.add('fa-spin');
            this.layoutManager.render(this.currentTab);
            const loader = this.getTabLoader(this.currentTab);
            if (loader) loader();
            setTimeout(() => {
                indicator.style.transform = 'translateY(-100%)';
                indicator.querySelector('i').classList.remove('fa-spin');
            }, 600);
        }, { passive: true });
    }

    /** タブごとのデータローダー（プルリフレッシュ・情報更新時の再表示で共通利用） */
    getTabLoader(tab) {
        const loaders = {
            home: () => this.announcementManager.load(),
            products: () => this.loadProducts(),
            orders: () => this.loadOrdersTab(),
            stamps: () => this.stampsManager.load(),
            coupons: () => this.couponsManager.load(),
            queue: () => this.queueManager.load(),
            member: () => this.loadMemberTab(),
            reservations: () => this.checkMembershipAndLoadReservations(),
            gallery: () => this.galleryManager.load(),
        };
        return loaders[tab] || null;
    }

    /** 情報更新時に現在表示中のタブのデータだけ再読み込みする（レイアウトは触らず表示を更新する） */
    refreshCurrentTab() {
        const loader = this.getTabLoader(this.currentTab);
        if (loader) loader();
    }

    /** 会員情報が更新されたとき（注文完了・ポイント付与など）に会員データを再取得し、全画面の表示を更新する */
    async refreshAfterMemberUpdate() {
        try {
            const payload = await this.memberManager.fetchMemberContext({ force: true });
            if (payload) {
                this.memberManager.setMemberData(payload);
                this.memberManager.render(payload);
            }
        } catch (e) {
            // 未ログインなどは無視
        }
        try {
            this.refreshCurrentTab();
        } catch (e) {
            console.warn('refreshCurrentTab error:', e);
        }
    }

    async onLiffReady() {
        // ネイティブアプリ内では注入タイミングが遅いことがあるため、ready 時にも init してデバイス登録を確実にする
        this.deviceManager.init();
        await this.memberManager.ensureIdentityReady();
        if (window.__LINE_USER_ID__ && this.apiClient) {
            this.apiClient.persistLineUserId(window.__LINE_USER_ID__);
        }
        try {
            const payload = await this.memberManager._fetchMemberContextWithIdentityRetry({ force: false });
            if (payload && payload.is_member) {
                this.memberManager.setMemberData(payload);
                if (this.currentTab === AppConstants.TABS.MEMBER) {
                    this.memberManager.render(payload);
                }
            }
        } catch (e) {
            // 未ログインなどは無視
        }
        this.memberManager.preload();
        const projectKey = (typeof window !== 'undefined' && window.__PROJECT_KEY__) ? window.__PROJECT_KEY__ : 'default';
        if (this.memberManager && typeof this.memberManager._prefetchAppleWalletOpenUrl === 'function') {
            this.memberManager._prefetchAppleWalletOpenUrl(projectKey);
        }
        const hasQueueToken = this.queueManager.getQueueTokenFromUrl() !== null;

        if (hasQueueToken) {
            await this.queueManager.linkPendingQueueOnStartup();
        }

        if (hasQueueToken) {
            this.switchTab(AppConstants.TABS.QUEUE);
            return;
        }
        if (this.currentTab === AppConstants.TABS.QUEUE) {
            this.queueManager.load();
            return;
        }
        if (this.currentTab === AppConstants.TABS.MEMBER) {
            await this.loadMemberTab();
        }
    }

    preventZoom() {
        document.addEventListener('gesturestart', (e) => e.preventDefault());
        document.addEventListener('gesturechange', (e) => e.preventDefault());
        document.addEventListener('gestureend', (e) => e.preventDefault());
        document.addEventListener(
            'touchstart',
            (e) => {
                if (e.touches.length > 1) e.preventDefault();
            },
            { passive: false }
        );
        document.addEventListener(
            'touchmove',
            (e) => {
                if (e.touches.length > 1) e.preventDefault();
            },
            { passive: false }
        );
        document.addEventListener(
            'touchend',
            (e) => {
                if (e.touches.length > 1) e.preventDefault();
            },
            { passive: false }
        );
        document.addEventListener(
            'dblclick',
            (e) => e.preventDefault(),
            { passive: false }
        );
        window.addEventListener(
            'wheel',
            (e) => {
                if (e.ctrlKey) e.preventDefault();
            },
            { passive: false }
        );
    }

    setupEventListeners() {
        this.setupCartEventListeners();
        this.setupFriendAddLink();
        this.setupTabEventListeners();
        this.setupCategoryEventListeners();
        this.setupOrderEventListeners();
        this.setupNativeAppEventListeners();
    }

    /** 友だち追加リンク: LINE内なら liff.openWindow で開き、LINE内ブラウザで友だち追加画面を表示 */
    setupFriendAddLink() {
        const url = typeof window !== 'undefined' ? (window.__LINE_FRIEND_ADD_URL__ || '') : '';
        if (!url) return;
        const el = document.getElementById('headerFriendAdd');
        if (!el) return;
        el.addEventListener('click', (e) => {
            if (typeof window !== 'undefined' && window.__IS_LIFF__ && typeof liff !== 'undefined' && typeof liff.openWindow === 'function') {
                e.preventDefault();
                liff.openWindow({ url: url, external: false });
            }
        });
    }

    /** ネイティブアプリでデバイス情報が注入されたあと、初期タブへ切り替え・会員タブのウォレット表示を更新する */
    setupNativeAppEventListeners() {
        const app = this;
        const allowedTabs = new Set(['home', 'products', 'orders', 'stamps', 'coupons', 'queue', 'member', 'reservations', 'gallery']);
        document.addEventListener('mobile-device-info-injected', () => {
            if (typeof window.__MOBILE_APP_MODE__ === 'undefined' || !window.__MOBILE_APP_MODE__) return;
            if (!window.__NATIVE_WEB_READY__) return;
            if (window.__NATIVE_DEVICE_INFO_TAB_SYNCED__) return;
            window.__NATIVE_DEVICE_INFO_TAB_SYNCED__ = true;
            const params = new URLSearchParams(window.location.search || '');
            const inMemberFlow = params.get('member_register') === '1'
                || params.get('member_screen') === 'register'
                || params.get('member_screen') === 'edit';
            const targetTab = typeof window.__NATIVE_TARGET_TAB__ !== 'undefined' ? window.__NATIVE_TARGET_TAB__ : app.currentTab;
            if (!inMemberFlow && targetTab && allowedTabs.has(targetTab) && targetTab !== app.currentTab) {
                app.switchTab(targetTab);
            }
            if (app.currentTab === AppConstants.TABS.MEMBER || targetTab === AppConstants.TABS.MEMBER) {
                app.memberManager.load();
            }
            if (app.currentTab === AppConstants.TABS.COUPONS) {
                app.couponsManager.load();
            }
        });
    }

    setupCartEventListeners() {
        const cartBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_BTN);
        const closeCartBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CLOSE_CART_BTN);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);
        if (cartBtn) {
            cartBtn.removeAttribute('data-tooltip');
            cartBtn.addEventListener('click', () => this.openCart());
        }
        if (closeCartBtn) closeCartBtn.addEventListener('click', () => this.closeCart());
        if (overlay) overlay.addEventListener('click', () => this.closeCart());
    }

    setupTabEventListeners() {
        // イベント委譲でタブボタンにバインド（注文後などでDOMが更新されても切り替え可能にする）
        const delegateRoot = document.body;
        if (!delegateRoot) return;
        delegateRoot.addEventListener('click', (e) => {
            const btn = e.target && e.target.closest && e.target.closest('.main-tab-btn');
            if (!btn || !btn.dataset.tab) return;
            e.preventDefault();
            this.switchTab(btn.dataset.tab);
        });
    }

    applyDesignerPreviewMode(fromSwitch) {
        if (!this.designerPreviewSource) return;
        const source = this.designerPreviewSource;
        const targetTab = this.getDesignerPreviewTab(source);
        const needsBootstrap = this.designerPreviewBootstrappedSource !== source;
        document.body.classList.add('designer-live-preview-mode');
        if (this.designerPreviewEmbed) {
            document.body.classList.add('designer-live-preview-embed');
        }
        const header = document.querySelector('header.header');
        const cart = document.getElementById('cartSidebar');
        const overlay = document.getElementById('overlay');
        const footer = document.querySelector('footer.main-tabs');
        if (header) header.style.display = 'none';
        if (cart) cart.style.display = 'none';
        if (overlay) overlay.style.display = 'none';
        if (footer) footer.style.display = 'none';

        // 上部のお知らせバーは announcements 以外では非表示にする
        const topAnnouncements = document.querySelector('main.main-content > #announcementsSection');
        if (topAnnouncements) {
            topAnnouncements.style.display = source === 'announcements' ? '' : 'none';
        }

        if (this.currentTab !== targetTab) {
            this.switchTab(targetTab);
            return;
        }

        // 対象タブ以外のコンテンツを非表示（描画ロジック自体は本番と同じ）
        DomHelper.querySelectorAll('.tab-content').forEach((content) => {
            const active = content.id === (targetTab + 'Tab');
            content.style.display = active ? '' : 'none';
        });

        if (this.designerPreviewEmbed && needsBootstrap) {
            this.mountDesignerPreviewSource(source);
        }

        if (!fromSwitch || needsBootstrap) {
            const loaderBySource = {
                products: () => this.loadProducts(),
                orders: () => this.loadOrdersTab(),
                stamps: () => this.stampsManager.load(),
                coupons: () => this.couponsManager.load(),
                queue: () => this.queueManager.load(),
                reservations: () => this.checkMembershipAndLoadReservations(),
                member: () => this.loadMemberTab(this.designerPreviewSample),
                member_card: () => this.loadMemberTab(this.designerPreviewSample),
                gallery: () => this.galleryManager.load(),
                announcements: () => this.announcementManager.load(),
            };
            const loader = loaderBySource[source];
            if (loader) loader();
            this.designerPreviewBootstrappedSource = source;
        }
    }

    mountDesignerPreviewSource(source) {
        const targetTab = this.getDesignerPreviewTab(source);
        const sectionIdMap = {
            home: 'homeSection',
            products: 'productsSection',
            orders: 'ordersSection',
            stamps: 'stampsSection',
            coupons: 'couponsSection',
            queue: 'queueSection',
            member: 'memberSection',
            reservations: 'reservationsSection',
            gallery: 'gallerySection',
        };
        const sectionId = sectionIdMap[targetTab];
        if (!sectionId) return;
        const section = document.getElementById(sectionId);
        if (!section) return;

        if (source === 'products') {
            section.innerHTML = `
                <div class="products-data-mount">
                    <div class="category-tabs products-category-tabs" id="categoryTabs">
                        <button class="tab-btn active" data-category="all"><i class="fas fa-th-large"></i><span>すべて</span></button>
                    </div>
                    <div class="products-section"><div class="products-grid products-data-grid" id="productsGrid"></div></div>
                </div>
            `;
            return;
        }
        if (source === 'announcements') {
            const topAnnouncements = document.getElementById('announcementsSection');
            if (topAnnouncements) topAnnouncements.innerHTML = '';
            section.innerHTML = '';
            return;
        }
        section.innerHTML = '';
    }

    setupCategoryEventListeners() {
        // カテゴリタブのイベントは動的に設定（renderCategoryTabs内で行う）
        this._attachCategoryListeners();
    }

    _attachCategoryListeners() {
        const container = DomHelper.getCategoryTabsContainer();
        if (!container) return;
        container.querySelectorAll('.tab-btn[data-category]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const tabs = DomHelper.getCategoryTabsContainer();
                if (tabs) {
                    tabs.querySelectorAll('.tab-btn[data-category]').forEach((b) =>
                        DomHelper.removeClass(b, AppConstants.CSS_CLASSES.ACTIVE)
                    );
                }
                DomHelper.addClass(e.currentTarget, AppConstants.CSS_CLASSES.ACTIVE);
                this.productManager.setCurrentCategory(e.currentTarget.dataset.category);
                this.renderProducts();
            });
        });
    }

    setupOrderEventListeners() {
        const orderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.ORDER_BTN);
        const inStoreOrderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.IN_STORE_ORDER_BTN);
        if (orderBtn) orderBtn.addEventListener('click', () => this.checkoutManager.placeMobileOrder());
        if (inStoreOrderBtn) inStoreOrderBtn.addEventListener('click', () => this.checkoutManager.placeInStoreOrder());
        this.applyOrderModeButtons();
    }

    applyOrderModeButtons() {
        const orderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.ORDER_BTN);
        const inStoreOrderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.IN_STORE_ORDER_BTN);
        const mode = this.orderMode || 'both';
        if (orderBtn) orderBtn.style.display = (mode === 'in_store_only') ? 'none' : '';
        if (inStoreOrderBtn) inStoreOrderBtn.style.display = (mode === 'mobile_only') ? 'none' : '';
    }

    loadProductsWhenReady() {
        const run = () => {
            if (this.currentTab !== AppConstants.TABS.PRODUCTS) return;
            this.loadProducts();
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    }

    async loadProducts() {
        const grid = DomHelper.getProductsGrid();
        if (!grid) {
            if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('loadProducts: productsGrid not found', { currentTab: this.currentTab });
            return;
        }
        grid.innerHTML = this.productManager.getProductSkeleton();
        try {
            await this.productManager.loadProducts();
            this.renderCategoryTabs();
            this.renderProducts();
        } catch (error) {
            this.handleProductLoadError(error);
        }
    }

    /** APIから取得したカテゴリでタブを動的に構築 */
    renderCategoryTabs() {
        const container = DomHelper.getCategoryTabsContainer();
        if (!container) return;
        const categories = this.productManager.getCategories();
        if (!categories || categories.length === 0) return;

        const current = this.productManager.getCurrentCategory();
        container.innerHTML = categories.map(cat => {
            const isActive = cat.key === current ? 'active' : '';
            return `<button class="tab-btn ${isActive}" data-category="${DomHelper.escapeHtml(cat.key)}">
                <i class="fas ${DomHelper.escapeHtml(cat.icon)}"></i>
                <span>${DomHelper.escapeHtml(cat.label)}</span>
                <span class="tab-count">${cat.count || 0}</span>
            </button>`;
        }).join('');
        this._attachCategoryListeners();
    }

    handleProductLoadError(error) {
        console.error('Failed to load products:', error);
        const grid = DomHelper.getProductsGrid();
        if (grid) {
            grid.innerHTML = this.getErrorTemplate(
                '商品の読み込みに失敗しました',
                error.message || 'エラーが発生しました'
            );
        }
    }

    getErrorTemplate(title, message) {
        return `
            <div class="loading" style="color: #e60012;">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${DomHelper.escapeHtml(title)}</p>
                <p style="font-size: 12px; margin-top: 8px;">${DomHelper.escapeHtml(message)}</p>
                <button onclick="location.reload()" style="margin-top: 12px; padding: 8px 16px; background: #e60012; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-redo"></i> 再読み込み
                </button>
            </div>
        `;
    }

    renderProducts() {
        const grid = DomHelper.getProductsGrid();
        if (!grid) return;
        const currentCat = this.productManager.getCurrentCategory();
        const products = this.productManager.getProductsByCategory(currentCat);

        if (products.length === 0) {
            grid.innerHTML = '<div class="loading"><i class="fas fa-search"></i><p>商品が見つかりませんでした</p></div>';
            return;
        }

        // 「すべて」の場合はカテゴリ別セクションで表示
        if (currentCat === 'all') {
            const grouped = this.productManager.getProductsGroupedByCategory();
            const categories = this.productManager.getCategories().filter(c => c.key !== 'all');
            let html = '';
            categories.forEach(cat => {
                const catProducts = grouped[cat.key];
                if (!catProducts || catProducts.length === 0) return;
                html += `<div class="product-category-section">
                    <div class="product-category-header">
                        <i class="fas ${DomHelper.escapeHtml(cat.icon)}"></i>
                        <h3>${DomHelper.escapeHtml(cat.label)}</h3>
                        <span class="product-category-count">${catProducts.length}品</span>
                    </div>
                    <div class="products-grid-inner">
                        ${catProducts.map(p => this.renderProductCard(p)).join('')}
                    </div>
                </div>`;
            });
            grid.innerHTML = html;
        } else {
            grid.innerHTML = `<div class="products-grid-inner">${products.map(p => this.renderProductCard(p)).join('')}</div>`;
        }
    }

    renderProductCard(product) {
        const cartQuantity = this.cartManager.getQuantityForProduct(product.id);
        const isInCart = cartQuantity > 0;
        const imageUrl = product.image_url || '';
        const hasOptions = Array.isArray(product.option_groups) && product.option_groups.length > 0;
        const btnLabel = hasOptions
            ? '<i class="fas fa-sliders-h"></i> 選んで追加'
            : (isInCart ? '<i class="fas fa-check"></i> 追加済み' : '<i class="fas fa-cart-plus"></i> カートに追加');
        const cardAction = hasOptions
            ? `app.openProductDetail(${product.id})`
            : `app.addToCart(${product.id})`;
        const btnAction = hasOptions
            ? `app.openProductDetail(${product.id})`
            : `app.addToCart(${product.id})`;
        return `
            <div class="product-card ${isInCart ? AppConstants.CSS_CLASSES.IN_CART : ''}" data-product-id="${product.id}" onclick="${cardAction}">
                <div class="product-image-wrapper">
                    ${isInCart ? `<div class="cart-badge"><i class="fas fa-check"></i> ${cartQuantity}</div>` : ''}
                    <img src="${imageUrl}" alt="${DomHelper.escapeHtml(product.name)}" class="product-image" loading="lazy"
                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22150%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22200%22 height=%22150%22/%3E%3Ctext fill=%22%23999%22 font-family=%22sans-serif%22 font-size=%2214%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3E画像なし%3C/text%3E%3C/svg%3E'">
                    ${hasOptions ? '<span class="product-option-tag"><i class="fas fa-sliders-h"></i> カスタム可</span>' : ''}
                </div>
                <div class="product-info">
                    <div class="product-name">${DomHelper.escapeHtml(product.name)}</div>
                    <div class="product-price">${DomHelper.formatPrice(product.price)}${hasOptions ? '<span class="product-price-from">〜</span>' : ''}</div>
                    <button class="product-add-btn ${isInCart ? AppConstants.CSS_CLASSES.ADDED : ''}" onclick="event.stopPropagation(); ${btnAction}">
                        ${btnLabel}
                    </button>
                </div>
            </div>
        `;
    }

    /** 商品をワンタップで追加（オプションなし商品向けのフォールバック）。 */
    addToCart(productId) {
        const product = this.productManager.getProductById(productId);
        if (!product) {
            ToastManager.error('商品が見つかりませんでした');
            return;
        }
        this.cartManager.addItem(product);
        this.updateCartUI();
        this.showCartNotification();
        this.renderProducts();
        ToastManager.success(`${product.name}をカートに追加しました`);
    }

    removeFromCart(lineKey) {
        const item = this.cartManager.getItem(lineKey);
        this.cartManager.removeItem(lineKey);
        this.updateCartUI();
        this.renderProducts();
        if (item) ToastManager.info(`${item.name}をカートから削除しました`);
    }

    updateQuantity(lineKey, change) {
        this.cartManager.updateQuantity(lineKey, change);
        this.updateCartUI();
        this.renderProducts();
    }

    updateCartUI() {
        const count = this.cartManager.getTotalCount();
        const countEl = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_COUNT);
        if (countEl) countEl.textContent = count;

        const cartItems = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_ITEMS);
        const total = this.cartManager.getTotalAmount();
        const orderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.ORDER_BTN);
        const inStoreOrderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.IN_STORE_ORDER_BTN);
        const totalPrice = DomHelper.getElementById(AppConstants.ELEMENT_IDS.TOTAL_PRICE);

        if (this.cartManager.isEmpty()) {
            if (cartItems) cartItems.innerHTML = '<div class="empty-cart">カートは空です</div>';
            if (orderBtn) orderBtn.disabled = true;
            if (inStoreOrderBtn) inStoreOrderBtn.disabled = true;
        } else {
            if (cartItems) {
                cartItems.innerHTML = this.cartManager.items
                    .map((item) => this.renderCartItem(item))
                    .join('');
            }
            if (orderBtn) orderBtn.disabled = false;
            if (inStoreOrderBtn) inStoreOrderBtn.disabled = false;
        }
        if (this.orderMode === 'mobile_only' && inStoreOrderBtn) inStoreOrderBtn.disabled = true;
        if (this.orderMode === 'in_store_only' && orderBtn) orderBtn.disabled = true;
        if (totalPrice) totalPrice.textContent = DomHelper.formatPrice(total);
    }

    renderCartItem(item) {
        const imageUrl = item.image_url || '';
        const key = item.lineKey;
        const optionsText = (item.options && item.options.length > 0)
            ? item.options.map(o => DomHelper.escapeHtml(o.name)).join('・')
            : '';
        const notesText = item.notes ? DomHelper.escapeHtml(item.notes) : '';
        return `
            <div class="cart-item">
                <img src="${imageUrl}" alt="${DomHelper.escapeHtml(item.name)}" class="cart-item-image"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2280%22 height=%2280%22%3E%3Crect fill=%22%23f0f0f0%22 width=%2280%22 height=%2280%22/%3E%3C/svg%3E'">
                <div class="cart-item-info">
                    <div class="cart-item-name">${DomHelper.escapeHtml(item.name)}</div>
                    ${optionsText ? `<div class="cart-item-options">${optionsText}</div>` : ''}
                    ${notesText ? `<div class="cart-item-note"><i class="fas fa-comment-dots"></i> ${notesText}</div>` : ''}
                    <div class="cart-item-price">${DomHelper.formatPrice(item.price)}</div>
                    <div class="cart-item-controls">
                        <button class="quantity-btn" onclick="app.updateQuantity('${key}', -1)"><i class="fas fa-minus"></i></button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="quantity-btn" onclick="app.updateQuantity('${key}', 1)"><i class="fas fa-plus"></i></button>
                        <button class="remove-btn" onclick="app.removeFromCart('${key}')"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        `;
    }

    /* ===== 商品詳細モーダル（タップで詳細・オプション選択・数量） ===== */

    openProductDetail(productId) {
        const product = this.productManager.getProductById(productId);
        if (!product) {
            ToastManager.error('商品が見つかりませんでした');
            return;
        }
        this._detailProduct = product;
        this._detailQty = 1;

        let host = document.getElementById('productDetailModal');
        if (!host) {
            host = document.createElement('div');
            host.id = 'productDetailModal';
            document.body.appendChild(host);
        }
        host.innerHTML = this._renderProductDetail(product);
        host.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        host.querySelectorAll('input[data-opt]').forEach(input => {
            input.addEventListener('change', () => this._recalcProductDetail());
        });
        this._recalcProductDetail();
    }

    closeProductDetail() {
        const host = document.getElementById('productDetailModal');
        if (host) {
            host.classList.remove('is-open');
            host.innerHTML = '';
        }
        document.body.style.overflow = '';
    }

    detailQty(change) {
        this._detailQty = Math.max(1, (this._detailQty || 1) + change);
        const el = document.getElementById('detailQtyValue');
        if (el) el.textContent = this._detailQty;
        this._recalcProductDetail();
    }

    _renderProductDetail(product) {
        const imageUrl = product.image_url || '';
        const groups = Array.isArray(product.option_groups) ? product.option_groups : [];
        const esc = (s) => DomHelper.escapeHtml(s);
        const desc = product.description ? `<p class="pd-desc">${esc(product.description)}</p>` : '';

        const groupsHtml = groups.map(group => {
            const multiple = group.selection_type === 'multiple';
            const type = multiple ? 'checkbox' : 'radio';
            const required = group.is_required;
            const max = group.max_select || 0;
            const meta = multiple
                ? (max > 0 ? `最大${max}個` : '複数選択可')
                : '1つ選択';
            const options = (group.options || []).map((opt, i) => {
                const defChecked = (!multiple && (opt.is_default || i === 0)) ? 'checked' : (multiple && opt.is_default ? 'checked' : '');
                const delta = Number(opt.price_delta) || 0;
                const deltaLabel = delta > 0 ? `+${DomHelper.formatPrice(delta)}` : (delta < 0 ? DomHelper.formatPrice(delta) : '');
                return `
                    <label class="pd-option">
                        <input type="${type}" name="grp_${group.id}" value="${opt.id}"
                               data-opt data-group="${group.id}" data-group-type="${group.selection_type}"
                               data-max="${max}" data-price="${delta}" data-name="${esc(opt.name)}" ${defChecked}>
                        <span class="pd-option-check"></span>
                        <span class="pd-option-name">${esc(opt.name)}</span>
                        ${deltaLabel ? `<span class="pd-option-price">${deltaLabel}</span>` : ''}
                    </label>`;
            }).join('');
            return `
                <div class="pd-group" data-group-wrap="${group.id}">
                    <div class="pd-group-head">
                        <span class="pd-group-name">${esc(group.name)}</span>
                        <span class="pd-group-meta">${required ? '<span class="pd-required">必須</span>' : ''}${esc(meta)}</span>
                    </div>
                    <div class="pd-group-options">${options}</div>
                </div>`;
        }).join('');

        return `
            <div class="pd-overlay" onclick="app.closeProductDetail()"></div>
            <div class="pd-sheet" role="dialog" aria-modal="true">
                <button class="pd-close" onclick="app.closeProductDetail()" aria-label="閉じる"><i class="fas fa-times"></i></button>
                <div class="pd-scroll">
                    <div class="pd-hero">
                        <img src="${imageUrl}" alt="${esc(product.name)}" class="pd-image"
                             onerror="this.style.display='none'">
                    </div>
                    <div class="pd-body">
                        <h2 class="pd-name">${esc(product.name)}</h2>
                        <div class="pd-base-price">${DomHelper.formatPrice(product.price)}</div>
                        ${desc}
                        ${groupsHtml}
                        <div class="pd-group">
                            <div class="pd-group-head"><span class="pd-group-name">ご要望（任意）</span></div>
                            <input type="text" id="pdNotes" class="pd-notes" maxlength="500" placeholder="例: ソース少なめ など">
                        </div>
                    </div>
                </div>
                <div class="pd-footer">
                    <div class="pd-qty">
                        <button class="pd-qty-btn" onclick="app.detailQty(-1)" aria-label="減らす"><i class="fas fa-minus"></i></button>
                        <span id="detailQtyValue" class="pd-qty-value">1</span>
                        <button class="pd-qty-btn" onclick="app.detailQty(1)" aria-label="増やす"><i class="fas fa-plus"></i></button>
                    </div>
                    <button class="pd-add-btn" id="pdAddBtn" onclick="app.addToCartFromDetail()">
                        <span>カートに追加</span>
                        <span class="pd-add-total" id="pdAddTotal"></span>
                    </button>
                </div>
            </div>
        `;
    }

    _collectDetailSelection() {
        const host = document.getElementById('productDetailModal');
        if (!host) return { options: [], delta: 0, valid: true };
        const product = this._detailProduct || {};
        const groups = Array.isArray(product.option_groups) ? product.option_groups : [];
        const options = [];
        let delta = 0;
        let valid = true;

        groups.forEach(group => {
            const checked = host.querySelectorAll(`input[data-group="${group.id}"]:checked`);
            if (group.is_required && checked.length === 0) valid = false;
            const max = group.max_select || 0;
            if (group.selection_type === 'multiple' && max > 0 && checked.length > max) valid = false;
            checked.forEach(input => {
                const price = Number(input.getAttribute('data-price')) || 0;
                delta += price;
                options.push({
                    group_name: group.name,
                    option_id: parseInt(input.value, 10),
                    name: input.getAttribute('data-name') || '',
                    price_delta: price,
                });
            });
        });
        return { options, delta, valid };
    }

    _recalcProductDetail() {
        const host = document.getElementById('productDetailModal');
        if (!host) return;
        const product = this._detailProduct || {};

        // 複数選択の上限を超えたら最後の選択を外す
        const groups = Array.isArray(product.option_groups) ? product.option_groups : [];
        groups.forEach(group => {
            const max = group.max_select || 0;
            if (group.selection_type === 'multiple' && max > 0) {
                const checked = host.querySelectorAll(`input[data-group="${group.id}"]:checked`);
                if (checked.length > max) {
                    checked[checked.length - 1].checked = false;
                    ToastManager.info(`${group.name}は最大${max}個までです`);
                }
            }
        });

        const sel = this._collectDetailSelection();
        const base = Number(product.price) || 0;
        const qty = this._detailQty || 1;
        const total = (base + sel.delta) * qty;
        const totalEl = document.getElementById('pdAddTotal');
        if (totalEl) totalEl.textContent = DomHelper.formatPrice(total);
        const addBtn = document.getElementById('pdAddBtn');
        if (addBtn) addBtn.classList.toggle('is-disabled', !sel.valid);
    }

    addToCartFromDetail() {
        const product = this._detailProduct;
        if (!product) return;
        const sel = this._collectDetailSelection();
        if (!sel.valid) {
            ToastManager.error('必須の選択を確認してください');
            return;
        }
        const notesEl = document.getElementById('pdNotes');
        const notes = notesEl ? notesEl.value : '';
        this.cartManager.addItem(product, { options: sel.options, quantity: this._detailQty || 1, notes });
        this.closeProductDetail();
        this.updateCartUI();
        this.showCartNotification();
        this.renderProducts();
        ToastManager.success(`${product.name}をカートに追加しました`);
    }

    openCart() {
        const sidebar = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_SIDEBAR);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);
        if (sidebar) DomHelper.addClass(sidebar, AppConstants.CSS_CLASSES.OPEN);
        if (overlay) DomHelper.addClass(overlay, AppConstants.CSS_CLASSES.ACTIVE);
        document.body.style.overflow = 'hidden';
    }

    closeCart() {
        const sidebar = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_SIDEBAR);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);
        if (sidebar) DomHelper.removeClass(sidebar, AppConstants.CSS_CLASSES.OPEN);
        if (overlay) DomHelper.removeClass(overlay, AppConstants.CSS_CLASSES.ACTIVE);
        document.body.style.overflow = '';
    }

    showCartNotification() {
        const btn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_BTN);
        if (btn) {
            btn.style.transform = 'scale(1.1)';
            setTimeout(() => (btn.style.transform = 'scale(1)'), AppConstants.ANIMATION_DURATION.CART_NOTIFICATION);
        }
    }

    handleOrderSuccess() {
        ToastManager.success(AppConstants.MESSAGES.ORDER_SUCCESS);
        this.cartManager.clear();
        this.updateCartUI();
        this.renderProducts();
        this.closeCart();
    }

    handlePaymentSuccess() {
        const paymentModal = document.querySelector('.payment-modal');
        if (paymentModal && typeof paymentModal.remove === 'function') {
            paymentModal.remove();
        }
        this.handleOrderSuccess();
    }

    _refreshActiveTabDataMounts() {
        const tabContentEl = document.querySelector('.tab-content.active');
        if (!tabContentEl) return;
        const M = AppConstants.TAB_MOUNTS;
        if (tabContentEl.querySelector('.' + M.MEMBER)) {
            const memberData = this.memberManager.getMemberData();
            if (memberData && memberData.is_member === true) {
                this.memberManager.render(memberData);
            } else {
                this.loadMemberTab();
            }
        }
        if (tabContentEl.querySelector('.' + M.PRODUCTS)) this.loadProducts();
        if (tabContentEl.querySelector('.' + M.ORDERS)) this.loadOrdersTab();
        if (tabContentEl.querySelector('.' + M.STAMPS)) this.stampsManager.load();
        if (tabContentEl.querySelector('.' + M.COUPONS)) this.couponsManager.load();
        if (tabContentEl.querySelector('.' + M.QUEUE)) this.queueManager.load();
        if (tabContentEl.querySelector('.' + M.RESERVATIONS)) this.checkMembershipAndLoadReservations();
        if (tabContentEl.querySelector('.' + M.GALLERY)) this.galleryManager.load();
        if (tabContentEl.querySelector('.' + M.ANNOUNCEMENTS)) this.announcementManager.load();
    }

    switchTab(tab) {
        if (typeof window.__mobileOrderLog === 'function') window.__mobileOrderLog('switchTab', { tab });
        this.currentTab = tab;

        DomHelper.querySelectorAll('.main-tab-btn').forEach((btn) => {
            DomHelper.removeClass(btn, AppConstants.CSS_CLASSES.ACTIVE);
            if (btn.dataset.tab === tab) DomHelper.addClass(btn, AppConstants.CSS_CLASSES.ACTIVE);
        });
        DomHelper.querySelectorAll('.tab-content').forEach((content) => {
            DomHelper.removeClass(content, AppConstants.CSS_CLASSES.ACTIVE);
        });

        const tabIdMap = {
            [AppConstants.TABS.HOME]: AppConstants.ELEMENT_IDS.HOME_TAB,
            [AppConstants.TABS.PRODUCTS]: AppConstants.ELEMENT_IDS.PRODUCTS_TAB,
            [AppConstants.TABS.ORDERS]: AppConstants.ELEMENT_IDS.ORDERS_TAB,
            [AppConstants.TABS.STAMPS]: AppConstants.ELEMENT_IDS.STAMPS_TAB,
            [AppConstants.TABS.COUPONS]: AppConstants.ELEMENT_IDS.COUPONS_TAB,
            [AppConstants.TABS.QUEUE]: AppConstants.ELEMENT_IDS.QUEUE_TAB,
            [AppConstants.TABS.MEMBER]: AppConstants.ELEMENT_IDS.MEMBER_TAB,
            [AppConstants.TABS.RESERVATIONS]: AppConstants.ELEMENT_IDS.RESERVATIONS_TAB,
            [AppConstants.TABS.GALLERY]: AppConstants.ELEMENT_IDS.GALLERY_TAB,
        };
        const tabEl = DomHelper.getElementById(tabIdMap[tab]);
        if (tabEl) DomHelper.addClass(tabEl, AppConstants.CSS_CLASSES.ACTIVE);

        const sectionContainer = document.getElementById(tab + 'Section');
        const layoutWasRendered = !!(sectionContainer && sectionContainer.dataset.layoutRendered === '1');

        if (!(this.designerPreviewSource && this.designerPreviewEmbed)) {
            this.layoutManager.render(tab);
        }
        if (layoutWasRendered) {
            this._refreshActiveTabDataMounts();
        }
        if (this.designerPreviewSource) {
            this.applyDesignerPreviewMode(true);
        }
    }

    async loadOrdersTab() {
        const section = DomHelper.getTabMount(
            AppConstants.TAB_MOUNTS.ORDERS,
            AppConstants.ELEMENT_IDS.ORDERS_SECTION,
            { silent: true }
        );
        if (!section) return;
        if (typeof window !== 'undefined' && !window.__LINE_USER_ID__ && this.memberManager) {
            await this.memberManager.resolveLineUserIdForClient();
        }
        const hasIdentity = this.memberManager && typeof this.memberManager.hasAuthenticatedIdentity === 'function'
            ? this.memberManager.hasAuthenticatedIdentity()
            : (typeof window !== 'undefined' && !!window.__LINE_USER_ID__);
        if (!hasIdentity) {
            section.innerHTML = this.memberManager.getLoginOptionalTemplate(
                '注文履歴',
                'ログインすると注文履歴をご利用いただけます。'
            );
            return;
        }
        section.innerHTML =
            '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const orders = await this.orderManager.fetchOrders();
            this.orderManager.renderOrdersSection(section, orders);
        } catch (error) {
            console.error('Failed to load orders:', error);
            section.innerHTML = this.getOrdersErrorTemplate(error);
        }
    }

    getOrdersErrorTemplate(error) {
        const message = error && error.message ? error.message : '注文履歴の読み込みに失敗しました';
        return `
            <div class="orders-error">
                <i class="fas fa-exclamation-circle"></i>
                <p>${DomHelper.escapeHtml(message)}</p>
                <button type="button" class="btn btn-secondary" onclick="app.loadOrdersTab()">
                    <i class="fas fa-redo"></i> 再読み込み
                </button>
            </div>
        `;
    }

    async loadMemberTab(useSample) {
        if (typeof window !== 'undefined' && window.__IS_LIFF__) {
            await this.memberManager.ensureIdentityReady();
        } else if (typeof window !== 'undefined' && !window.__LINE_USER_ID__) {
            await this.memberManager.resolveLineUserIdForClient();
        }
        const isDesignerPreviewMember = !!this.designerPreviewSource && (this.designerPreviewSource === 'member' || this.designerPreviewSource === 'member_card');
        if (isDesignerPreviewMember && useSample) {
            // デザイナー埋め込みプレビュー: バックエンドから渡されたランク設定ベースの実表示用ペイロードを優先（DBの会員証設定を反映）
            const payload = typeof window !== 'undefined' && window.__DESIGNER_PREVIEW_MEMBER_PAYLOAD__;
            if (payload && typeof payload === 'object' && payload.is_member) {
                this.memberManager.render(payload);
                return;
            }
            this.memberManager.render(this.getDesignerSampleMemberData());
            return;
        }
        const cached = this.memberManager.getMemberData();
        if (cached && cached.is_member) {
            this.memberManager.render(cached);
        } else {
            const container = this.memberManager.getContainer();
            if (container && (!cached || !cached.is_member)) {
                container.innerHTML = this.memberManager.getSkeletonHtml();
            }
        }

        try {
            const payload = await this.memberManager._fetchMemberContextWithIdentityRetry({
                force: !(cached && cached.is_member),
            });
            if (payload && payload.is_member) {
                this.memberManager.setMemberData(payload);
                this.memberManager.render(payload);
                this.memberManager.preload();
                return;
            }
            if (payload) {
                await this.memberManager._handleGuestContext(payload);
                return;
            }
        } catch (e) {
            // 未ログインなど
        }
        const data = this.memberManager.getMemberData();
        if (data && data.is_member) {
            this.memberManager.render(data);
            this.memberManager.preload();
        } else if (!cached || !cached.is_member) {
            this.memberManager.load();
        }
    }

    getDesignerSampleMemberData() {
        return {
            is_member: true,
            points: 1280,
            current_rank: 'gold',
            current_rank_label: 'ゴールド',
            current_rank_color: '#d97706',
            next_rank_label: 'プラチナ',
            points_to_next_rank: 720,
            days_to_next_rank: 18,
            days_required_for_next_rank: 30,
            show_points_display: true,
            show_rank_progress: true,
            member_card_design: 'default',
            member_card_display_type: 'card',
            member_card_code_type: 'barcode',
            points_expiry: {
                expires_at_label: '2026/12/31',
                points_expiring: 200,
            },
            rank_up_history: [
                { from_rank_label: 'ブロンズ', to_rank_label: 'シルバー', achieved_at: '2025/11/03' },
                { from_rank_label: 'シルバー', to_rank_label: 'ゴールド', achieved_at: '2026/01/20' },
            ],
            user: {
                name: 'サンプル会員',
            },
            member: {
                member_number: '000123456789',
                points: 1280,
            },
        };
    }

    async checkMembershipAndLoadReservations() {
        const section = DomHelper.getTabMount(
            AppConstants.TAB_MOUNTS.RESERVATIONS,
            AppConstants.ELEMENT_IDS.RESERVATIONS_SECTION,
            { silent: true }
        );
        if (!section) return;
        if (typeof window !== 'undefined' && !window.__LINE_USER_ID__ && this.memberManager) {
            await this.memberManager.resolveLineUserIdForClient();
        }
        const hasIdentity = this.memberManager && typeof this.memberManager.hasAuthenticatedIdentity === 'function'
            ? this.memberManager.hasAuthenticatedIdentity()
            : (typeof window !== 'undefined' && !!window.__LINE_USER_ID__);
        if (!hasIdentity) {
            section.innerHTML = this.memberManager.getLoginOptionalTemplate(
                '予約',
                'ログインすると予約をご利用いただけます。'
            );
            return;
        }
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME);
            if (result.success && result.data && result.data.is_member) {
                this.memberManager.setMemberData(result.data);
                this.reservationManager.loadReservations();
            } else {
                section.innerHTML = this.reservationManager.getMembershipRequiredTemplate();
            }
        } catch (error) {
            console.error('Failed to check membership:', error);
            section.innerHTML = this.reservationManager.getMembershipRequiredTemplate();
        }
    }
}

if (typeof window !== 'undefined' && !window.__LIFF_APP_BOOTSTRAP__) {
    window.__LIFF_APP_BOOTSTRAP__ = true;
    if (typeof window.__clientDebugLog === 'function') {
        window.__clientDebugLog('app_bootstrap_set', {});
    }

    function hideLiffLoading() {
        const el = document.getElementById('liff-loading');
        if (el) el.classList.add('liff-loading--hidden');
    }

    function restorePersistedLineUserIdBeforeApp() {
        // LIFF では liff-ready 後に LIFF トークンで ID を確定するため、ここでは復元しない
        if (typeof window === 'undefined' || window.__LINE_USER_ID__ || window.__IS_LIFF__) return;
        try {
            const projectKey = window.__PROJECT_KEY__ || 'default';
            const scoped = String(window.localStorage.getItem('mobile_order_line_user_id_' + projectKey) || '').trim();
            const legacy = String(window.localStorage.getItem('mobile_order_line_user_id') || '').trim();
            const cached = /^U[0-9a-f]{32}$/i.test(scoped) ? scoped : (/^U[0-9a-f]{32}$/i.test(legacy) ? legacy : '');
            if (cached) {
                window.__LINE_USER_ID__ = cached;
            }
        } catch (e) { /* ignore */ }
    }

    function createApp() {
        if (window.app) return;
        if (!window.__IS_LIFF__) {
            restorePersistedLineUserIdBeforeApp();
        }
        window.app = new ReggitApp();
        if (typeof window.__clientDebugLog === 'function') {
            window.__clientDebugLog('app_created', {});
        }
        hideLiffLoading();
    }

    document.addEventListener(
        'liff-ready',
        function fn() {
            document.removeEventListener('liff-ready', fn);
            if (!window.app) {
                createApp();
            }
            if (window.app && typeof window.app.onLiffReady === 'function') {
                if (typeof window.__clientDebugLog === 'function') {
                    window.__clientDebugLog('app_on_liff_ready_called', {});
                }
                window.app.onLiffReady();
            }
        },
        { once: true }
    );

    function canBootstrapWithoutLiffReady() {
        if (!window.__IS_LIFF__) return true;
        if (window.__LIFF_ID_MISSING__) return true;
        return false;
    }

    // フォールバック: 5秒以内にliff-readyが来なければ起動（LIFFでは LINE ID 確定後のみ members/me を叩く）
    setTimeout(() => {
        if (!window.app && typeof ReggitApp !== 'undefined' && canBootstrapWithoutLiffReady()) {
            createApp();
        }
        const loadingEl = document.getElementById('liff-loading');
        if (loadingEl && !loadingEl.classList.contains('liff-loading--hidden')) {
            loadingEl.classList.add('liff-loading--hidden');
        }
    }, 5000);

    // LIFF 初期化が極端に遅い場合の最終フォールバック
    setTimeout(() => {
        if (!window.app && typeof ReggitApp !== 'undefined') {
            createApp();
        }
    }, 15000);
}
