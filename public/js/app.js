class MobileOrderApp {
    constructor() {
        this.productManager = new ProductManager();
        this.cartManager = new CartManager();
        this.apiClient = new ApiClient();
        this.currentTab = AppConstants.TABS.PRODUCTS;
        this.memberData = null;
        this.userId = 'test-user';
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadProductsWhenReady();
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
    }

    setupCartEventListeners() {
        const cartBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_BTN);
        const closeCartBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CLOSE_CART_BTN);
        const overlay = DomHelper.getElementById(AppConstants.ELEMENT_IDS.OVERLAY);

        if (cartBtn) {
            cartBtn.setAttribute('data-tooltip', 'カートを開く');
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
                            data-tooltip="${isInCart ? 'カートから削除するには、カートを開いてください' : 'カートに追加'}">
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
                        <button class="remove-btn" onclick="app.removeFromCart(${item.id})" data-tooltip="カートから削除">
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
                { userId: this.userId }
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
            ToastManager.error(`注文に失敗しました: ${error.message || 'エラーが発生しました'}`);
        }
    }

    handleOrderSuccess() {
        ToastManager.success('注文が完了しました！');
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
            this.loadMemberCard();
        }
    }

    async loadMemberCard() {
        const container = DomHelper.getElementById(AppConstants.ELEMENT_IDS.MEMBER_SECTION);
        if (!container) return;

        container.innerHTML = this.getMemberCardSkeleton();

        try {
            const result = await this.apiClient.get(
                AppConstants.API_ENDPOINTS.MEMBERS_ME,
                { userId: this.userId }
            );

            if (!result.success) {
                throw new Error(result.error || 'Failed to load member data');
            }

            const data = result.data;

            if (!data.is_member) {
                container.innerHTML = this.getNonMemberTemplate();
            } else {
                this.memberData = data;
                this.renderMemberCard(data);
            }
        } catch (error) {
            console.error('Failed to load member card:', error);
            container.innerHTML = this.getMemberCardErrorTemplate(error);
        }
    }

    getNonMemberTemplate() {
        return `
            <div class="member-card-container">
                <div class="member-card">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                    </div>
                    <div style="text-align: center; padding: 40px 20px;">
                        <p style="margin-bottom: 12px; color: var(--text-secondary); font-size: 14px;">会員登録は任意です</p>
                        <p style="margin-bottom: 20px; color: var(--text-primary); font-size: 13px;">会員登録をすると、ポイントが貯まります</p>
                        <button class="register-member-btn" onclick="app.registerMember()">
                            <i class="fas fa-user-plus"></i> 会員登録する
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    getMemberCardErrorTemplate(error) {
        return `
            <div class="loading" style="color: var(--text-primary);">
                <i class="fas fa-exclamation-triangle"></i>
                <p>会員証の読み込みに失敗しました</p>
                <p style="font-size: 12px; margin-top: 8px;">${DomHelper.escapeHtml(error.message || 'エラーが発生しました')}</p>
                <button onclick="app.loadMemberCard()" style="margin-top: 12px; padding: 8px 16px; background: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-redo"></i> 再読み込み
                </button>
            </div>
        `;
    }

    renderMemberCard(data) {
        const container = DomHelper.getElementById(AppConstants.ELEMENT_IDS.MEMBER_SECTION);
        const member = data.member;
        const points = data.points || member.points || 0;
        const memberNumber = member.member_number || '';

        const barcodeId = `barcode-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

        container.innerHTML = `
            <div class="member-card-container">
                <div class="member-card">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                    </div>
                    <div class="barcode-container">
                        <svg id="${barcodeId}" class="barcode-svg"></svg>
                    </div>
                    <div class="points-display">
                        <div class="points-value">${points.toLocaleString()}</div>
                        <div class="points-label">ポイント</div>
                    </div>
                </div>
            </div>
        `;

        this.generateBarcode(barcodeId, memberNumber);
    }

    generateBarcode(barcodeId, memberNumber) {
        if (typeof JsBarcode === 'undefined' || !memberNumber) return;

        setTimeout(() => {
            try {
                const barcodeElement = document.getElementById(barcodeId);
                if (barcodeElement) {
                    JsBarcode(`#${barcodeId}`, memberNumber, {
                        format: "CODE128",
                        width: 2,
                        height: 80,
                        displayValue: true,
                        fontSize: 14,
                        margin: 10,
                        background: "#ffffff",
                        lineColor: "#000000"
                    });
                }
            } catch (error) {
                console.error('Barcode generation error:', error);
            }
        }, AppConstants.ANIMATION_DURATION.BARCODE_DELAY);
    }

    async registerMember() {
        try {
            const result = await this.apiClient.post(
                AppConstants.API_ENDPOINTS.MEMBERS_REGISTER,
                {},
                { userId: this.userId }
            );

            if (!result.success) {
                throw new Error(result.error || 'Failed to register member');
            }

            ToastManager.success('会員登録が完了しました！');
            this.loadMemberCard();
        } catch (error) {
            console.error('Member registration error:', error);
            ToastManager.error(`会員登録に失敗しました: ${error.message || 'エラーが発生しました'}`);
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

const app = new MobileOrderApp();

