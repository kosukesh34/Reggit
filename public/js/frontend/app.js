class MobileOrderApp {
    constructor() {
        this.productManager = new ProductManager();
        this.cartManager = new CartManager();
        this.reservationManager = new ReservationManager(this);
        this.apiClient = new ApiClient();
        this.currentTab = AppConstants.TABS.PRODUCTS;
        this.memberData = null;
        this.init();
    }

    init() {
        this.preventZoom();
        this.setupEventListeners();
        this.loadProductsWhenReady();
        this.preloadMemberData();
    }

    preloadMemberData() {
        if (typeof window === 'undefined' || !window.__LINE_USER_ID__) return;
        const self = this;
        this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME).then(function (result) {
            if (result.success && result.data && typeof result.data.is_member !== 'undefined') {
                self.memberData = result.data;
            }
        });
    }

    preventZoom() {
        document.addEventListener('gesturestart', (e) => {
            e.preventDefault();
        });
        document.addEventListener('gesturechange', (e) => {
            e.preventDefault();
        });
        document.addEventListener('gestureend', (e) => {
            e.preventDefault();
        });
        document.addEventListener('touchstart', (e) => {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, { passive: false });
        document.addEventListener('touchmove', (e) => {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, { passive: false });
        document.addEventListener('touchend', (e) => {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, { passive: false });
    }

    loadProductsWhenReady() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.loadProducts();
            });
        } else {
            this.loadProducts();
        }
    }

    setupEventListeners() {
        this.setupCartEventListeners();
        this.setupTabEventListeners();
        this.setupCategoryEventListeners();
        this.setupOrderEventListeners();
        const backdrop = document.querySelector('[data-close="profileEdit"]');
        if (backdrop) {
            backdrop.addEventListener('click', () => this.closeProfileEdit());
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
        if (closeCartBtn) {
            closeCartBtn.addEventListener('click', () => this.closeCart());
        }
        if (overlay) {
            overlay.addEventListener('click', () => this.closeCart());
        }
    }

    setupTabEventListeners() {
        DomHelper.querySelectorAll('.main-tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tab = e.currentTarget.dataset.tab;
                this.switchTab(tab);
            });
        });
    }

    setupCategoryEventListeners() {
        DomHelper.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                DomHelper.querySelectorAll('.tab-btn').forEach(b => 
                    DomHelper.removeClass(b, AppConstants.CSS_CLASSES.ACTIVE)
                );
                DomHelper.addClass(e.target, AppConstants.CSS_CLASSES.ACTIVE);
                
                const category = e.target.dataset.category;
                this.productManager.setCurrentCategory(category);
                this.renderProducts();
            });
        });
    }

    setupOrderEventListeners() {
        const orderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.ORDER_BTN);
        if (orderBtn) {
            orderBtn.addEventListener('click', () => this.placeOrder());
        }
    }

    async loadProducts() {
        const grid = DomHelper.getElementById(AppConstants.ELEMENT_IDS.PRODUCTS_GRID);
        if (grid) {
            grid.innerHTML = this.productManager.getProductSkeleton();
        }

        try {
            await this.productManager.loadProducts();
            this.renderProducts();
        } catch (error) {
            this.handleProductLoadError(error);
        }
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
        if (!grid) {
            console.error('Products grid element not found');
            return;
        }

        const products = this.productManager.getProductsByCategory(
            this.productManager.getCurrentCategory()
        );

        if (products.length === 0) {
            grid.innerHTML = '<div class="loading">商品が見つかりませんでした</div>';
            return;
        }

        grid.innerHTML = products.map(product => this.renderProductCard(product)).join('');
    }

    renderProductCard(product) {
        const cartItem = this.cartManager.getItem(product.id);
        const isInCart = cartItem !== undefined;
        const cartQuantity = isInCart ? cartItem.quantity : 0;
        const imageUrl = this.getProductImageUrl(product.image_url);
        const tooltipAttribute = isInCart ? '' : `data-tooltip="${AppConstants.MESSAGES.CART_ADD_TOOLTIP}"`;

        return `
            <div class="product-card ${isInCart ? AppConstants.CSS_CLASSES.IN_CART : ''}" 
                 data-product-id="${product.id}">
                <div class="product-image-wrapper">
                    ${isInCart ? `<div class="cart-badge"><i class="fas fa-check"></i> ${cartQuantity}</div>` : ''}
                    <img src="${imageUrl}" 
                         alt="${DomHelper.escapeHtml(product.name)}" 
                         class="product-image"
                         loading="lazy"
                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22150%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22200%22 height=%22150%22/%3E%3Ctext fill=%22%23999%22 font-family=%22sans-serif%22 font-size=%2214%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3E画像なし%3C/text%3E%3C/svg%3E'">
                </div>
                <div class="product-info">
                    <div class="product-name">${DomHelper.escapeHtml(product.name)}</div>
                    <div class="product-price">${DomHelper.formatPrice(product.price)}</div>
                    ${isInCart ? `
                        <div class="cart-quantity-info">
                            <span class="cart-quantity-badge">${cartQuantity}個追加済み</span>
                        </div>
                    ` : ''}
                    <button class="product-add-btn ${isInCart ? AppConstants.CSS_CLASSES.ADDED : ''}" 
                            onclick="app.addToCart(${product.id})"
                            ${tooltipAttribute}>
                        ${isInCart ? '<i class="fas fa-check"></i> 追加済み' : '<i class="fas fa-cart-plus"></i> カートに追加'}
                    </button>
                </div>
            </div>
        `;
    }

    getProductImageUrl(imageUrl) {
        if (!imageUrl) return '';
        if (imageUrl.startsWith('http') || imageUrl.startsWith('//')) {
            return imageUrl;
        }
        return imageUrl;
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
        if (item) {
            ToastManager.info(`${item.name}をカートから削除しました`);
        }
    }

    updateQuantity(productId, change) {
        const updated = this.cartManager.updateQuantity(productId, change);
        this.updateCartUI();
        this.renderProducts();
        return updated;
    }

    updateCartUI() {
        const count = this.cartManager.getTotalCount();
        const countElement = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_COUNT);
        if (countElement) {
            countElement.textContent = count;
        }

        const cartItems = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_ITEMS);
        const total = this.cartManager.getTotalAmount();
        const orderBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.ORDER_BTN);
        const totalPrice = DomHelper.getElementById(AppConstants.ELEMENT_IDS.TOTAL_PRICE);

        if (this.cartManager.isEmpty()) {
            if (cartItems) {
                cartItems.innerHTML = '<div class="empty-cart">カートは空です</div>';
            }
            if (orderBtn) {
                orderBtn.disabled = true;
            }
        } else {
            if (cartItems) {
                cartItems.innerHTML = this.cartManager.items.map(item => 
                    this.renderCartItem(item)
                ).join('');
            }
            if (orderBtn) {
                orderBtn.disabled = false;
            }
        }

        if (totalPrice) {
            totalPrice.textContent = DomHelper.formatPrice(total);
        }
    }

    renderCartItem(item) {
        const imageUrl = this.getProductImageUrl(item.image_url);
        return `
            <div class="cart-item">
                <img src="${imageUrl}" 
                     alt="${DomHelper.escapeHtml(item.name)}" 
                     class="cart-item-image"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2280%22 height=%2280%22%3E%3Crect fill=%22%23f0f0f0%22 width=%2280%22 height=%2280%22/%3E%3C/svg%3E'">
                <div class="cart-item-info">
                    <div class="cart-item-name">${DomHelper.escapeHtml(item.name)}</div>
                    <div class="cart-item-price">${DomHelper.formatPrice(item.price)}</div>
                    <div class="cart-item-controls">
                        <button class="quantity-btn" onclick="app.updateQuantity(${item.id}, -1)" data-tooltip="数量を減らす">
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="quantity-btn" onclick="app.updateQuantity(${item.id}, 1)" data-tooltip="数量を増やす">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="remove-btn" onclick="app.removeFromCart(${item.id})" data-tooltip="${AppConstants.MESSAGES.CART_REMOVE_BUTTON}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    openCart() {
        const sidebar = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_SIDEBAR);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);
        
        if (sidebar) {
            DomHelper.addClass(sidebar, AppConstants.CSS_CLASSES.OPEN);
        }
        if (overlay) {
            DomHelper.addClass(overlay, AppConstants.CSS_CLASSES.ACTIVE);
        }
        document.body.style.overflow = 'hidden';
    }

    closeCart() {
        const sidebar = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_SIDEBAR);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);
        
        if (sidebar) {
            DomHelper.removeClass(sidebar, AppConstants.CSS_CLASSES.OPEN);
        }
        if (overlay) {
            DomHelper.removeClass(overlay, AppConstants.CSS_CLASSES.ACTIVE);
        }
        document.body.style.overflow = '';
    }

    showCartNotification() {
        const btn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_BTN);
        if (btn) {
            btn.style.transform = 'scale(1.1)';
            setTimeout(() => {
                btn.style.transform = 'scale(1)';
            }, AppConstants.ANIMATION_DURATION.CART_NOTIFICATION);
        }
    }

    async placeOrder() {
        if (this.cartManager.isEmpty()) return;

        const total = this.cartManager.getTotalAmount();
        const paymentMethod = await this.selectPaymentMethod();
        if (!paymentMethod) return;

        const orderData = {
            items: this.cartManager.getItemsForOrder(),
            payment_method: paymentMethod,
            points_used: 0,
        };

        try {
            const result = await this.apiClient.post(
                AppConstants.API_ENDPOINTS.ORDERS,
                orderData,
            );

            if (!result.success) {
                throw new Error(result.error || 'Failed to create order');
            }

            const order = result.data.order;

            if (paymentMethod === AppConstants.PAYMENT_METHODS.STRIPE) {
                await this.processStripePayment(order.id, total);
            } else {
                this.handleOrderSuccess();
            }
        } catch (error) {
            console.error('Order error:', error);
            ToastManager.error(`${AppConstants.MESSAGES.ORDER_ERROR}: ${error.message || AppConstants.MESSAGES.ORDER_ERROR_DEFAULT}`);
        }
    }

    handleOrderSuccess() {
        ToastManager.success(AppConstants.MESSAGES.ORDER_SUCCESS);
        this.cartManager.clear();
        this.updateCartUI();
        this.renderProducts();
        this.closeCart();
    }

    async selectPaymentMethod() {
        return new Promise((resolve) => {
            const modal = this.createPaymentMethodModal(resolve);
            document.body.appendChild(modal);
        });
    }

    createPaymentMethodModal(resolve) {
        const modal = DomHelper.createElement('div', 'payment-method-modal');
        modal.innerHTML = this.getPaymentMethodModalTemplate();
        
        this.setupPaymentMethodModalEvents(modal, resolve);
        return modal;
    }

    getPaymentMethodModalTemplate() {
        const total = this.cartManager.getTotalAmount();
        return `
            <div class="payment-method-modal-content">
                <div class="payment-method-header">
                    <h2><i class="fas fa-credit-card"></i> 決済方法を選択</h2>
                    <button class="close-payment-method-btn" id="closePaymentMethodBtn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="payment-method-body">
                    <div class="order-summary">
                        <h3>注文内容</h3>
                        <div class="order-items-list">
                            ${this.cartManager.items.map(item => `
                                <div class="order-item-summary">
                                    <span class="order-item-name">${DomHelper.escapeHtml(item.name)} × ${item.quantity}</span>
                                    <span class="order-item-price">${DomHelper.formatPrice(item.price * item.quantity)}</span>
                                </div>
                            `).join('')}
                        </div>
                        <div class="order-total-summary">
                            <span class="total-label">合計金額</span>
                            <span class="total-amount">${DomHelper.formatPrice(total)}</span>
                        </div>
                    </div>
                    <div class="payment-methods">
                        <button class="payment-method-btn" data-method="${AppConstants.PAYMENT_METHODS.CASH}">
                            <i class="fas fa-money-bill-wave"></i>
                            <div class="payment-method-info">
                                <div class="payment-method-name">現金</div>
                                <div class="payment-method-desc">店頭でお支払い</div>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button class="payment-method-btn" data-method="${AppConstants.PAYMENT_METHODS.STRIPE}">
                            <i class="fas fa-credit-card"></i>
                            <div class="payment-method-info">
                                <div class="payment-method-name">クレジットカード</div>
                                <div class="payment-method-desc">Stripeで安全にお支払い</div>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    setupPaymentMethodModalEvents(modal, resolve) {
        const closeModal = () => {
            modal.remove();
            resolve(null);
        };

        modal.querySelectorAll('.payment-method-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const method = btn.dataset.method;
                modal.remove();
                resolve(method);
            });
        });

        const closeBtn = modal.querySelector('#closePaymentMethodBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    async processStripePayment(orderId, amount) {
        this.showPaymentModal(orderId, amount);
    }

    showPaymentModal(orderId, amount) {
        const modal = this.createPaymentModal(orderId, amount);
        document.body.appendChild(modal);
        this.setupPaymentModal(modal, orderId, amount);
    }

    createPaymentModal(orderId, amount) {
        const modal = DomHelper.createElement('div', 'payment-modal');
        modal.innerHTML = this.getPaymentModalTemplate(orderId, amount);
        return modal;
    }

    getPaymentModalTemplate(orderId, amount) {
        return `
            <div class="payment-modal-content">
                <div class="payment-modal-header">
                    <h2><i class="fas fa-credit-card"></i> お支払い</h2>
                    <button class="close-payment-btn" id="closePaymentModalBtn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="payment-modal-body">
                    <div class="payment-summary">
                        <div class="payment-summary-item">
                            <span>注文番号</span>
                            <span class="order-number">#${orderId}</span>
                        </div>
                        <div class="payment-summary-item">
                            <span>合計金額</span>
                            <span class="payment-amount">${DomHelper.formatPrice(amount)}</span>
                        </div>
                    </div>
                    <div class="payment-form-section">
                        <h3><i class="fas fa-lock"></i> カード情報を入力</h3>
                        <div id="payment-element" class="payment-element-container">
                            <div class="payment-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>読み込み中...</span>
                            </div>
                        </div>
                    </div>
                    <div class="payment-actions">
                        <button class="confirm-payment-btn" id="confirmPaymentBtn" disabled>
                            <i class="fas fa-lock"></i>
                            <span>${DomHelper.formatPrice(amount)} を支払う</span>
                        </button>
                        <button class="cancel-payment-btn" id="cancelPaymentBtn">
                            <i class="fas fa-times"></i>
                            キャンセル
                        </button>
                    </div>
                    <div class="payment-security-note">
                        <i class="fas fa-shield-alt"></i>
                        <span>お支払い情報は暗号化されて安全に処理されます</span>
                    </div>
                </div>
            </div>
        `;
    }

    setupPaymentModal(modal, orderId, amount) {
        const closeBtn = modal.querySelector('#closePaymentModalBtn');
        const cancelBtn = modal.querySelector('#cancelPaymentBtn');
        const confirmBtn = modal.querySelector('#confirmPaymentBtn');
        const paymentElement = modal.querySelector('#payment-element');

        const closeModal = () => modal.remove();
        
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        const stripePayment = new StripePayment();
        stripePayment.setupPaymentForm('payment-element', amount, orderId)
            .then(() => {
                const loading = paymentElement.querySelector('.payment-loading');
                if (loading) loading.remove();
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = `
                        <i class="fas fa-lock"></i>
                        <span>${DomHelper.formatPrice(amount)} を支払う</span>
                    `;
                }
            })
            .catch((error) => {
                paymentElement.innerHTML = this.getPaymentErrorTemplate(error);
            });

        if (confirmBtn) {
            confirmBtn.addEventListener('click', async () => {
                if (confirmBtn.disabled) return;

                confirmBtn.disabled = true;
                confirmBtn.innerHTML = `
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>処理中...</span>
                `;

                try {
                    const result = await stripePayment.confirmPayment(orderId);
                    if (result.success) {
                        modal.innerHTML = this.getPaymentSuccessTemplate(orderId, amount);
                    } else {
                        throw new Error('Payment was not completed');
                    }
                } catch (error) {
                    this.handlePaymentError(confirmBtn, modal, amount, error);
                }
            });
        }
    }

    getPaymentErrorTemplate(error) {
        return `
            <div class="payment-error">
                <i class="fas fa-exclamation-triangle"></i>
                <p>決済フォームの読み込みに失敗しました</p>
                <p class="error-message">${DomHelper.escapeHtml(error.message || 'エラーが発生しました')}</p>
                <button class="retry-btn" onclick="location.reload()">
                    <i class="fas fa-redo"></i> 再試行
                </button>
            </div>
        `;
    }

    getPaymentSuccessTemplate(orderId, amount) {
        return `
            <div class="payment-modal-content">
                <div class="payment-success">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2>決済が完了しました！</h2>
                    <p class="success-message">ご注文ありがとうございます</p>
                    <div class="success-details">
                        <div class="success-detail-item">
                            <span>注文番号</span>
                            <span>#${orderId}</span>
                        </div>
                        <div class="success-detail-item">
                            <span>支払い金額</span>
                            <span>${DomHelper.formatPrice(amount)}</span>
                        </div>
                    </div>
                    <button class="success-close-btn" onclick="app.handlePaymentSuccess(${orderId}, ${amount})">
                        <i class="fas fa-check"></i>
                        閉じる
                    </button>
                </div>
            </div>
        `;
    }

    handlePaymentError(confirmBtn, modal, amount, error) {
        ToastManager.error(error.message || '決済に失敗しました。もう一度お試しください。');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = `
            <i class="fas fa-lock"></i>
            <span>${DomHelper.formatPrice(amount)} を支払う</span>
        `;

        const errorMsg = DomHelper.createElement('div', 'payment-error-message');
        errorMsg.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <span>${DomHelper.escapeHtml(error.message || '決済に失敗しました。もう一度お試しください。')}</span>
        `;
        
        const actions = modal.querySelector('.payment-actions');
        if (actions) {
            actions.prepend(errorMsg);
            setTimeout(() => errorMsg.remove(), 5000);
        }
    }

    switchTab(tab) {
        this.currentTab = tab;

        DomHelper.querySelectorAll('.main-tab-btn').forEach(btn => {
            DomHelper.removeClass(btn, AppConstants.CSS_CLASSES.ACTIVE);
            if (btn.dataset.tab === tab) {
                DomHelper.addClass(btn, AppConstants.CSS_CLASSES.ACTIVE);
            }
        });

        DomHelper.querySelectorAll('.tab-content').forEach(content => {
            DomHelper.removeClass(content, AppConstants.CSS_CLASSES.ACTIVE);
        });

        if (tab === AppConstants.TABS.PRODUCTS) {
            const productsTab = DomHelper.getElementById(AppConstants.ELEMENT_IDS.PRODUCTS_TAB);
            if (productsTab) {
                DomHelper.addClass(productsTab, AppConstants.CSS_CLASSES.ACTIVE);
            }
        } else if (tab === AppConstants.TABS.MEMBER) {
            const memberTab = DomHelper.getElementById(AppConstants.ELEMENT_IDS.MEMBER_TAB);
            if (memberTab) {
                DomHelper.addClass(memberTab, AppConstants.CSS_CLASSES.ACTIVE);
            }
            if (this.memberData && this.memberData.is_member) {
                this.renderMemberCard(this.memberData);
                this.loadMemberCardInBackground();
            } else {
                this.loadMemberCard();
            }
        } else if (tab === AppConstants.TABS.RESERVATIONS) {
            const reservationsTab = DomHelper.getElementById(AppConstants.ELEMENT_IDS.RESERVATIONS_TAB);
            if (reservationsTab) {
                DomHelper.addClass(reservationsTab, AppConstants.CSS_CLASSES.ACTIVE);
            }
            this.checkMembershipAndLoadReservations();
        }
    }

    isInLineClient() {
        return typeof window !== 'undefined' && typeof liff !== 'undefined' && liff.isInClient && liff.isInClient() === true;
    }

    async waitForLineUserId(maxWaitMs) {
        const maxWait = maxWaitMs || 8000;
        const interval = 300;
        let elapsed = 0;
        while (elapsed < maxWait) {
            if (window.__LINE_USER_ID__) return true;
            await new Promise(function (r) { setTimeout(r, interval); });
            elapsed += interval;
        }
        return !!window.__LINE_USER_ID__;
    }

    loadMemberCardInBackground() {
        const self = this;
        this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME).then(function (result) {
            if (!result.success || !result.data) return;
            const data = result.data;
            if (data.is_member && self.currentTab === AppConstants.TABS.MEMBER) {
                self.memberData = data;
                self.renderMemberCard(data);
            }
        });
    }

    async loadMemberCard() {
        const container = DomHelper.getElementById(AppConstants.ELEMENT_IDS.MEMBER_SECTION);
        if (!container) return;

        if (typeof window === 'undefined' || !window.__LINE_USER_ID__) {
            container.innerHTML = this.getLineOnlyTemplate();
            this.memberData = null;
            return;
        }

        if (this.memberData && this.memberData.is_member) {
            this.renderMemberCard(this.memberData);
            this.loadMemberCardInBackground();
            return;
        }

        container.innerHTML = this.getMemberCardSkeleton();

        try {
            const result = await this.apiClient.get(
                AppConstants.API_ENDPOINTS.MEMBERS_ME,
            );

            if (!result.success) {
                throw new Error(result.error || result.data?.message || '会員情報の取得に失敗しました');
            }

            const data = result.data;
            if (!data || typeof data.is_member === 'undefined') {
                throw new Error('会員情報の形式が正しくありません');
            }

            if (!data.is_member) {
                await this.autoRegisterAndLoadMemberCard();
                return;
            }

            this.memberData = data;
            this.renderMemberCard(data);
        } catch (error) {
            console.error('Failed to load member card:', error);
            container.innerHTML = this.getMemberCardErrorTemplate(error);
        }
    }

    getLineOnlyTemplate() {
        return `
            <div class="member-card-container">
                <div class="member-card member-card--line-only">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                    </div>
                    <div class="member-card-line-only-body">
                        <p class="member-card-line-only-icon"><i class="fab fa-line"></i></p>
                        <p class="member-card-line-only-message">LINEアプリから開いてください</p>
                        <p class="member-card-line-only-sub">会員証はLINEアプリ内でのみご利用いただけます。</p>
                    </div>
                </div>
            </div>
        `;
    }

    getPermissionDeniedTemplate() {
        return `
            <div class="member-card-container">
                <div class="member-card permission-denied-notice">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                    </div>
                    <div style="text-align: center; padding: 24px 20px;">
                        <p style="margin-bottom: 12px; color: var(--text-secondary); font-size: 14px;">LINEの表示名を表示するには、アクセス許可が必要です。</p>
                        <p style="margin-bottom: 20px; color: var(--text-primary); font-size: 13px;">再読み込みすると、再度許可を求めます。</p>
                        <button type="button" class="register-member-btn" onclick="location.reload()">
                            <i class="fas fa-redo"></i> 再読み込み
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    async checkMembershipAndLoadReservations() {
        const reservationsSection = DomHelper.getElementById(AppConstants.ELEMENT_IDS.RESERVATIONS_SECTION);
        if (!reservationsSection) return;

        if (typeof window === 'undefined' || !window.__LINE_USER_ID__) {
            if (this.isInLineClient()) {
                reservationsSection.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
                var hasUserId = await this.waitForLineUserId(5000);
                if (hasUserId && window.__LINE_USER_ID__) {
                    return this.checkMembershipAndLoadReservations();
                }
            }
            reservationsSection.innerHTML = this.getLineOnlyTemplateReservations();
            return;
        }

        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME);
            if (result.success && result.data && result.data.is_member) {
                this.memberData = result.data;
                this.reservationManager.loadReservations();
            } else {
                reservationsSection.innerHTML = this.reservationManager.getMembershipRequiredTemplate();
            }
        } catch (error) {
            console.error('Failed to check membership:', error);
            reservationsSection.innerHTML = this.reservationManager.getMembershipRequiredTemplate();
        }
    }

    getLineOnlyTemplateReservations() {
        return `
            <div class="member-card-container">
                <div class="member-card member-card--line-only">
                    <div class="member-card-line-only-body">
                        <p class="member-card-line-only-icon"><i class="fab fa-line"></i></p>
                        <p class="member-card-line-only-message">LINEアプリから開いてください</p>
                        <p class="member-card-line-only-sub">予約はLINEアプリ内でのみご利用いただけます。</p>
                    </div>
                </div>
            </div>
        `;
    }

    getMemberCardErrorTemplate(error) {
        const message = error && error.message ? error.message : 'エラーが発生しました';
        const isLineHint = message.indexOf('認証') !== -1 || message.indexOf('401') !== -1;
        return `
            <div class="member-card-container">
                <div class="member-card member-card--error">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                    </div>
                    <div class="member-card-error-body">
                        <p class="member-card-error-icon"><i class="fas fa-exclamation-circle"></i></p>
                        <p class="member-card-error-message">${DomHelper.escapeHtml(message)}</p>
                        ${isLineHint ? '<p class="member-card-error-hint">LINEアプリから再度お試しください。</p>' : ''}
                        <button type="button" class="register-member-btn" onclick="window.app.loadMemberCard()">
                            <i class="fas fa-redo"></i> 再読み込み
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    renderMemberCard(data) {
        const container = DomHelper.getElementById(AppConstants.ELEMENT_IDS.MEMBER_SECTION);
        const member = data.member;
        const user = data.user || (member && member.user);
        const displayName = (user && user.name) || (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName) || '';
        const points = data.points || member.points || 0;
        const memberNumber = member.member_number || '';
        const currentRankLabel = data.current_rank_label || 'ブロンズ';
        const nextRankLabel = data.next_rank_label || null;
        const pointsToNextRank = data.points_to_next_rank != null ? data.points_to_next_rank : null;
        const pointsExpiry = data.points_expiry || null;

        const barcodeId = `barcode-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const permissionDeniedNotice = (typeof window !== 'undefined' && window.__LIFF_PERMISSION_DENIED__) ?
            '<p class="member-card-profile-notice">LINEの表示名は取得していません。<button type="button" class="link-btn" onclick="location.reload()">再読み込み</button></p>' : '';

        const currentRankKey = data.current_rank || 'bronze';
        let rankHtml = '<p class="member-card-rank"><span class="member-card-rank-badge" data-rank="' + DomHelper.escapeHtml(currentRankKey) + '">' + DomHelper.escapeHtml(currentRankLabel) + '</span></p>';
        if (nextRankLabel && pointsToNextRank !== null) {
            rankHtml += '<p class="member-card-next-rank">次のランク（' + DomHelper.escapeHtml(nextRankLabel) + '）まで <strong>' + pointsToNextRank.toLocaleString() + '</strong> pt</p>';
        }
        let expiryHtml = '';
        if (pointsExpiry && pointsExpiry.expires_at_label) {
            expiryHtml = '<p class="member-card-expiry">ポイント有効期限: ' + DomHelper.escapeHtml(pointsExpiry.expires_at_label) + (pointsExpiry.points_expiring ? ' <span class="member-card-expiry-pts">(' + pointsExpiry.points_expiring + ' pt)</span>' : '') + '</p>';
        }

        container.innerHTML = `
            <div class="member-card-container">
                <div class="member-card">
                    <div class="member-card-header">
                        <div class="member-card-header-inner">
                            <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                            ${displayName ? '<p class="member-card-display-name">' + DomHelper.escapeHtml(displayName) + '</p>' : ''}
                            ${rankHtml}
                        </div>
                        <button type="button" class="member-card-edit-btn" onclick="window.app.openProfileEdit()" aria-label="プロフィール編集"><i class="fas fa-pen"></i></button>
                    </div>
                    ${permissionDeniedNotice}
                    <div class="barcode-container barcode-container--large">
                        <svg id="${barcodeId}" class="barcode-svg"></svg>
                    </div>
                    <div class="member-card-meta">
                        ${expiryHtml}
                    </div>
                    <div class="points-display points-display--compact">
                        <div class="points-value">${points.toLocaleString()}</div>
                        <div class="points-label">ポイント</div>
                    </div>
                </div>
            </div>
        `;

        this.generateBarcode(barcodeId, memberNumber);
    }

    openProfileEdit() {
        if (!this.memberData) return;
        const user = this.memberData.user || (this.memberData.member && this.memberData.member.user);
        const member = this.memberData.member;
        const content = document.getElementById('profileEditModalContent');
        const modal = document.getElementById('profileEditModal');
        if (!content || !modal) return;
        content.innerHTML = this.getProfileEditFormHtml(user, member);
        const form = document.getElementById('profileEditForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                window.app.saveProfileEdit(e);
            });
        }
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('member-modal--open');
    }

    closeProfileEdit() {
        const modal = document.getElementById('profileEditModal');
        if (modal) {
            modal.setAttribute('aria-hidden', 'true');
            modal.classList.remove('member-modal--open');
        }
    }

    getProfileEditFormHtml(user, member) {
        const name = (user && user.name) || '';
        const phone = (user && user.phone) || '';
        const birthday = member && member.birthday ? member.birthday.split('T')[0] : '';
        const address = (member && member.address) || '';
        return `
            <div class="member-modal-inner">
                <h3 class="member-modal-title"><i class="fas fa-user-edit"></i> プロフィール編集</h3>
                <form id="profileEditForm" class="member-form">
                    <div class="member-form-group">
                        <label for="profileEditName">お名前</label>
                        <input type="text" id="profileEditName" name="name" value="${DomHelper.escapeHtml(name)}" maxlength="255">
                    </div>
                    <div class="member-form-group">
                        <label for="profileEditPhone">電話番号</label>
                        <input type="tel" id="profileEditPhone" name="phone" value="${DomHelper.escapeHtml(phone)}" maxlength="50">
                    </div>
                    <div class="member-form-group">
                        <label for="profileEditBirthday">誕生日</label>
                        <input type="date" id="profileEditBirthday" name="birthday" value="${DomHelper.escapeHtml(birthday)}">
                    </div>
                    <div class="member-form-group">
                        <label for="profileEditAddress">住所</label>
                        <input type="text" id="profileEditAddress" name="address" value="${DomHelper.escapeHtml(address)}" maxlength="500">
                    </div>
                    <div class="member-form-actions">
                        <button type="button" class="member-btn member-btn--secondary" onclick="window.app.closeProfileEdit()">キャンセル</button>
                        <button type="submit" class="member-btn member-btn--primary"><i class="fas fa-check"></i> 保存</button>
                    </div>
                </form>
            </div>
        `;
    }

    async saveProfileEdit(e) {
        if (e) e.preventDefault();
        const form = document.getElementById('profileEditForm');
        if (!form) return;
        const payload = {
            name: (form.querySelector('#profileEditName') || {}).value || '',
            phone: (form.querySelector('#profileEditPhone') || {}).value || '',
            birthday: (form.querySelector('#profileEditBirthday') || {}).value || null,
            address: (form.querySelector('#profileEditAddress') || {}).value || null,
        };
        if (!payload.birthday) payload.birthday = null;
        try {
            const result = await this.apiClient.request(AppConstants.API_ENDPOINTS.MEMBERS_ME, { method: 'PUT', body: payload });
            if (result.success) {
                this.memberData = result.data;
                this.renderMemberCard(result.data);
                this.closeProfileEdit();
                ToastManager.success('プロフィールを更新しました');
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Profile update error:', error);
            ToastManager.error('更新に失敗しました: ' + (error.message || 'エラーが発生しました'));
        }
    }

    generateBarcode(barcodeId, memberNumber) {
        if (typeof JsBarcode === 'undefined' || !memberNumber) return;

        setTimeout(() => {
            try {
                const barcodeElement = document.getElementById(barcodeId);
                if (barcodeElement) {
                    JsBarcode(`#${barcodeId}`, memberNumber, {
                        format: "CODE128",
                        width: 3,
                        height: 150,
                        displayValue: true,
                        fontSize: 18,
                        margin: 14,
                        background: "#ffffff",
                        lineColor: "#000000"
                    });
                }
            } catch (error) {
                console.error('Barcode generation error:', error);
            }
        }, AppConstants.ANIMATION_DURATION.BARCODE_DELAY);
    }

    async autoRegisterAndLoadMemberCard() {
        const payload = {};
        if (typeof window !== 'undefined' && window.__LINE_PROFILE__ && window.__LINE_PROFILE__.displayName) {
            payload.name = window.__LINE_PROFILE__.displayName;
        }
        try {
            const result = await this.apiClient.post(
                AppConstants.API_ENDPOINTS.MEMBERS_REGISTER,
                payload,
            );
            if (result.success && result.data && result.data.is_member) {
                this.memberData = result.data;
                this.renderMemberCard(result.data);
                ToastManager.success('会員証を作成しました');
            } else if (result.success) {
                await this.loadMemberCard();
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Auto register error:', error);
            const container = DomHelper.getElementById(AppConstants.ELEMENT_IDS.MEMBER_SECTION);
            if (container) {
                container.innerHTML = this.getMemberCardErrorTemplate(error);
            }
        }
    }

    getMemberCardSkeleton() {
        return `
            <div class="member-card-container">
                <div class="member-card">
                    <div class="member-card-header">
                        <div class="skeleton-line skeleton-header"></div>
                    </div>
                    <div class="barcode-container">
                        <div class="skeleton-barcode">
                            <div class="skeleton-shimmer"></div>
                        </div>
                    </div>
                    <div class="points-display">
                        <div class="skeleton-line skeleton-points-value"></div>
                        <div class="skeleton-line skeleton-points-label"></div>
                    </div>
                </div>
            </div>
        `;
    }
}

if (typeof window !== 'undefined' && !window.__LIFF_APP_BOOTSTRAP__) {
    window.__LIFF_APP_BOOTSTRAP__ = true;

    function hideLiffLoading() {
        var el = document.getElementById('liff-loading');
        if (el) el.classList.add('liff-loading--hidden');
    }

    function createApp() {
        if (window.app) return;
        window.app = new MobileOrderApp();
        hideLiffLoading();
    }

    document.addEventListener('liff-ready', function fn() {
        document.removeEventListener('liff-ready', fn);
        createApp();
    }, { once: true });

    setTimeout(function () {
        if (!window.app && typeof MobileOrderApp !== 'undefined') {
            createApp();
        }
    }, 15000);
}
