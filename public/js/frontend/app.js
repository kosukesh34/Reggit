class MobileOrderApp {
    constructor() {
        this.apiClient = new ApiClient();
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

        this.currentTab = this.getInitialTab();
        this.init();
    }

    getInitialTab() {
        const firstBtn = document.querySelector('.main-tab-btn.active') || document.querySelector('.main-tab-btn');
        if (firstBtn && firstBtn.dataset.tab) return firstBtn.dataset.tab;
        return AppConstants.TABS.PRODUCTS;
    }

    get memberData() {
        return this.memberManager.getMemberData();
    }

    init() {
        this.preventZoom();
        this.setupEventListeners();
        this.layoutManager.render(this.currentTab);
        this.loadProductsWhenReady();
        this.announcementManager.load();
        this.memberManager.preload();
    }

    onLiffReady() {
        this.memberManager.preload();
        const hasQueueToken = this.queueManager.getQueueTokenFromUrl() !== null;
        if (hasQueueToken) {
            this.switchTab(AppConstants.TABS.QUEUE);
            return;
        }
        if (this.currentTab === AppConstants.TABS.QUEUE) {
            this.queueManager.load();
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
    }

    setupEventListeners() {
        this.setupCartEventListeners();
        this.setupTabEventListeners();
        this.setupCategoryEventListeners();
        this.setupOrderEventListeners();
        const backdrop = document.querySelector('[data-close="profileEdit"]');
        if (backdrop) {
            backdrop.addEventListener('click', () => this.memberManager.closeProfileEdit());
        }
    }

    setupCartEventListeners() {
        const cartBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_BTN);
        const closeCartBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CLOSE_CART_BTN);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);
        if (cartBtn) {
            cartBtn.setAttribute('data-tooltip', AppConstants.MESSAGES.CART_OPEN_TOOLTIP);
            cartBtn.addEventListener('click', () => this.openCart());
        }
        if (closeCartBtn) closeCartBtn.addEventListener('click', () => this.closeCart());
        if (overlay) overlay.addEventListener('click', () => this.closeCart());
    }

    setupTabEventListeners() {
        document.querySelectorAll('.main-tab-btn, .banner-menu-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const tab = e.currentTarget.dataset.tab;
                if (tab) this.switchTab(tab);
            });
        });
    }

    setupCategoryEventListeners() {
        // カテゴリタブのイベントは動的に設定（renderCategoryTabs内で行う）
        this._attachCategoryListeners();
    }

    _attachCategoryListeners() {
        document.querySelectorAll('.tab-btn[data-category]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.tab-btn[data-category]').forEach((b) =>
                    DomHelper.removeClass(b, AppConstants.CSS_CLASSES.ACTIVE)
                );
                DomHelper.addClass(e.currentTarget, AppConstants.CSS_CLASSES.ACTIVE);
                this.productManager.setCurrentCategory(e.currentTarget.dataset.category);
                this.renderProducts();
            });
        });
    }

    setupOrderEventListeners() {
        const orderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.ORDER_BTN);
        if (orderBtn) {
            orderBtn.addEventListener('click', () => this.checkoutManager.placeOrder());
        }
    }

    loadProductsWhenReady() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.loadProducts());
        } else {
            this.loadProducts();
        }
    }

    async loadProducts() {
        const grid = DomHelper.getElementById(AppConstants.ELEMENT_IDS.PRODUCTS_GRID);
        if (grid) grid.innerHTML = this.productManager.getProductSkeleton();
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
        const container = document.querySelector('.category-tabs');
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
        const grid = DomHelper.getElementById(AppConstants.ELEMENT_IDS.PRODUCTS_GRID);
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
        const grid = DomHelper.getElementById(AppConstants.ELEMENT_IDS.PRODUCTS_GRID);
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
        const cartItem = this.cartManager.getItem(product.id);
        const isInCart = cartItem !== undefined;
        const cartQuantity = isInCart ? cartItem.quantity : 0;
        const imageUrl = product.image_url || '';
        const tooltip = isInCart ? '' : `data-tooltip="${AppConstants.MESSAGES.CART_ADD_TOOLTIP}"`;
        return `
            <div class="product-card ${isInCart ? AppConstants.CSS_CLASSES.IN_CART : ''}" data-product-id="${product.id}">
                <div class="product-image-wrapper">
                    ${isInCart ? `<div class="cart-badge"><i class="fas fa-check"></i> ${cartQuantity}</div>` : ''}
                    <img src="${imageUrl}" alt="${DomHelper.escapeHtml(product.name)}" class="product-image" loading="lazy"
                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22150%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22200%22 height=%22150%22/%3E%3Ctext fill=%22%23999%22 font-family=%22sans-serif%22 font-size=%2214%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3E画像なし%3C/text%3E%3C/svg%3E'">
                </div>
                <div class="product-info">
                    <div class="product-name">${DomHelper.escapeHtml(product.name)}</div>
                    <div class="product-price">${DomHelper.formatPrice(product.price)}</div>
                    ${isInCart ? `<div class="cart-quantity-info"><span class="cart-quantity-badge">${cartQuantity}個追加済み</span></div>` : ''}
                    <button class="product-add-btn ${isInCart ? AppConstants.CSS_CLASSES.ADDED : ''}" onclick="app.addToCart(${product.id})" ${tooltip}>
                        ${isInCart ? '<i class="fas fa-check"></i> 追加済み' : '<i class="fas fa-cart-plus"></i> カートに追加'}
                    </button>
                </div>
            </div>
        `;
    }

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

    removeFromCart(productId) {
        const item = this.cartManager.getItem(productId);
        this.cartManager.removeItem(productId);
        this.updateCartUI();
        this.renderProducts();
        if (item) ToastManager.info(`${item.name}をカートから削除しました`);
    }

    updateQuantity(productId, change) {
        this.cartManager.updateQuantity(productId, change);
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
        const totalPrice = DomHelper.getElementById(AppConstants.ELEMENT_IDS.TOTAL_PRICE);

        if (this.cartManager.isEmpty()) {
            if (cartItems) cartItems.innerHTML = '<div class="empty-cart">カートは空です</div>';
            if (orderBtn) orderBtn.disabled = true;
        } else {
            if (cartItems) {
                cartItems.innerHTML = this.cartManager.items
                    .map((item) => this.renderCartItem(item))
                    .join('');
            }
            if (orderBtn) orderBtn.disabled = false;
        }
        if (totalPrice) totalPrice.textContent = DomHelper.formatPrice(total);
    }

    renderCartItem(item) {
        const imageUrl = item.image_url || '';
        return `
            <div class="cart-item">
                <img src="${imageUrl}" alt="${DomHelper.escapeHtml(item.name)}" class="cart-item-image"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2280%22 height=%2280%22%3E%3Crect fill=%22%23f0f0f0%22 width=%2280%22 height=%2280%22/%3E%3C/svg%3E'">
                <div class="cart-item-info">
                    <div class="cart-item-name">${DomHelper.escapeHtml(item.name)}</div>
                    <div class="cart-item-price">${DomHelper.formatPrice(item.price)}</div>
                    <div class="cart-item-controls">
                        <button class="quantity-btn" onclick="app.updateQuantity(${item.id}, -1)" data-tooltip="数量を減らす"><i class="fas fa-minus"></i></button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="quantity-btn" onclick="app.updateQuantity(${item.id}, 1)" data-tooltip="数量を増やす"><i class="fas fa-plus"></i></button>
                        <button class="remove-btn" onclick="app.removeFromCart(${item.id})" data-tooltip="${AppConstants.MESSAGES.CART_REMOVE_TOOLTIP}"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        `;
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
        document.querySelector('.payment-modal')?.remove();
        this.handleOrderSuccess();
    }

    switchTab(tab) {
        this.currentTab = tab;

        DomHelper.querySelectorAll('.main-tab-btn, .banner-menu-btn').forEach((btn) => {
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

        this.layoutManager.render(tab);
    }

    async loadOrdersTab() {
        const section = DomHelper.getElementById(AppConstants.ELEMENT_IDS.ORDERS_SECTION);
        if (!section) return;
        if (typeof window === 'undefined' || !window.__LINE_USER_ID__) {
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

    loadMemberTab() {
        const data = this.memberManager.getMemberData();
        if (data && data.is_member) {
            this.memberManager.render(data);
            this.memberManager.preload();
        } else {
            this.memberManager.load();
        }
    }

    async checkMembershipAndLoadReservations() {
        const section = DomHelper.getElementById(AppConstants.ELEMENT_IDS.RESERVATIONS_SECTION);
        if (!section) return;
        if (typeof window === 'undefined' || !window.__LINE_USER_ID__) {
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

    function hideLiffLoading() {
        const el = document.getElementById('liff-loading');
        if (el) el.classList.add('liff-loading--hidden');
    }

    function createApp() {
        if (window.app) return;
        window.app = new MobileOrderApp();
        hideLiffLoading();
    }

    document.addEventListener(
        'liff-ready',
        function fn() {
            document.removeEventListener('liff-ready', fn);
            if (!window.app) {
                createApp();
            } else if (typeof window.app.onLiffReady === 'function') {
                window.app.onLiffReady();
            }
        },
        { once: true }
    );

    // フォールバック: 5秒以内にliff-readyが来なければ強制起動
    setTimeout(() => {
        if (!window.app && typeof MobileOrderApp !== 'undefined') createApp();
    }, 5000);
}
