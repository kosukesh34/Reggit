class MobileOrderApp {
    constructor() {
        this.products = [];
        this.cart = [];
        this.currentCategory = 'all';
        this.currentTab = 'products';
        this.memberData = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        // DOMContentLoadedを待ってから商品を読み込む
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.loadProducts();
            });
        } else {
            this.loadProducts();
        }
    }

    setupEventListeners() {
        // カートボタン
        document.getElementById('cartBtn').addEventListener('click', () => {
            this.openCart();
        });

        // カートを閉じる
        document.getElementById('closeCartBtn').addEventListener('click', () => {
            this.closeCart();
        });

        // オーバーレイクリック
        document.getElementById('overlay').addEventListener('click', () => {
            this.closeCart();
        });

        // メインタブ
        document.querySelectorAll('.main-tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tab = e.currentTarget.dataset.tab;
                this.switchTab(tab);
            });
        });

        // カテゴリータブ
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.currentCategory = e.target.dataset.category;
                this.renderProducts();
            });
        });

        // 注文ボタン
        document.getElementById('orderBtn').addEventListener('click', () => {
            this.placeOrder();
        });
    }

    async loadProducts() {
        try {
            const response = await fetch('/api/products', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            // レスポンスがJSONかどうか確認
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('サーバーエラーが発生しました');
            }

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            this.products = await response.json();
            
            if (!Array.isArray(this.products)) {
                throw new Error('商品データの形式が正しくありません');
            }

            this.renderProducts();
        } catch (error) {
            console.error('商品の読み込みに失敗しました:', error);
            const grid = document.getElementById('productsGrid');
            if (grid) {
                grid.innerHTML = `
                    <div class="loading" style="color: #e60012;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>商品の読み込みに失敗しました</p>
                        <p style="font-size: 12px; margin-top: 8px;">${error.message || 'エラーが発生しました'}</p>
                        <button onclick="location.reload()" style="margin-top: 12px; padding: 8px 16px; background: #e60012; color: white; border: none; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-redo"></i> 再読み込み
                        </button>
                    </div>
                `;
            }
        }
    }

    renderProducts() {
        const grid = document.getElementById('productsGrid');
        
        if (!grid) {
            console.error('productsGrid element not found');
            return;
        }

        if (!this.products || !Array.isArray(this.products)) {
            grid.innerHTML = '<div class="loading">商品データがありません</div>';
            return;
        }

        const filtered = this.currentCategory === 'all' 
            ? this.products 
            : this.products.filter(p => p.category === this.currentCategory);

        if (filtered.length === 0) {
            grid.innerHTML = '<div class="loading">商品が見つかりませんでした</div>';
            return;
        }

        grid.innerHTML = filtered.map(product => {
            // 画像URLの処理: 相対パスの場合はそのまま使用
            let imageUrl = product.image_url || '';
            if (imageUrl && !imageUrl.startsWith('http') && !imageUrl.startsWith('//')) {
                // 相対パスの場合はそのまま使用（Laravelのasset()で処理済み）
                imageUrl = imageUrl;
            }
            
            const cartItem = this.cart.find(item => item.id === product.id);
            const isInCart = cartItem !== undefined;
            const cartQuantity = isInCart ? cartItem.quantity : 0;
            
            return `
            <div class="product-card ${isInCart ? 'in-cart' : ''}" data-product-id="${product.id}">
                <div class="product-image-wrapper">
                    ${isInCart ? `<div class="cart-badge"><i class="fas fa-check"></i> ${cartQuantity}</div>` : ''}
                    <img src="${imageUrl}" 
                         alt="${this.escapeHtml(product.name)}" 
                         class="product-image"
                         loading="lazy"
                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22150%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22200%22 height=%22150%22/%3E%3Ctext fill=%22%23999%22 font-family=%22sans-serif%22 font-size=%2214%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3E画像なし%3C/text%3E%3C/svg%3E'">
                </div>
                <div class="product-info">
                    <div class="product-name">${this.escapeHtml(product.name)}</div>
                    <div class="product-price">¥${parseInt(product.price).toLocaleString()}</div>
                    ${isInCart ? `
                        <div class="cart-quantity-info">
                            <span class="cart-quantity-badge">${cartQuantity}個追加済み</span>
                        </div>
                    ` : ''}
                    <button class="product-add-btn ${isInCart ? 'added' : ''}" onclick="app.addToCart(${product.id})">
                        ${isInCart ? '<i class="fas fa-check"></i> 追加済み' : '<i class="fas fa-cart-plus"></i> カートに追加'}
                    </button>
                </div>
            </div>
        `;
        }).join('');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    addToCart(productId) {
        const product = this.products.find(p => p.id === productId);
        if (!product) return;

        const existingItem = this.cart.find(item => item.id === productId);
        if (existingItem) {
            existingItem.quantity++;
        } else {
            this.cart.push({
                ...product,
                quantity: 1
            });
        }

        this.updateCartUI();
        this.showCartNotification();
        this.renderProducts(); // 商品一覧を再描画してチェックマークを更新
    }

    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.id !== productId);
        this.updateCartUI();
        this.renderProducts(); // 商品一覧を再描画してチェックマークを更新
    }

    updateQuantity(productId, change) {
        const item = this.cart.find(item => item.id === productId);
        if (!item) return;

        item.quantity += change;
        if (item.quantity <= 0) {
            this.removeFromCart(productId);
        } else {
            this.updateCartUI();
            this.renderProducts(); // 商品一覧を再描画して数量を更新
        }
    }

    updateCartUI() {
        const count = this.cart.reduce((sum, item) => sum + item.quantity, 0);
        document.getElementById('cartCount').textContent = count;

        const cartItems = document.getElementById('cartItems');
        const total = this.cart.reduce((sum, item) => sum + (parseInt(item.price) * item.quantity), 0);

        if (this.cart.length === 0) {
            cartItems.innerHTML = '<div class="empty-cart">カートは空です</div>';
            document.getElementById('orderBtn').disabled = true;
        } else {
            cartItems.innerHTML = this.cart.map(item => {
                const imageUrl = item.image_url 
                    ? (item.image_url.startsWith('http') ? item.image_url : item.image_url)
                    : '';
                return `
                <div class="cart-item">
                    <img src="${imageUrl}" 
                         alt="${item.name}" 
                         class="cart-item-image"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2280%22 height=%2280%22%3E%3Crect fill=%22%23f0f0f0%22 width=%2280%22 height=%2280%22/%3E%3C/svg%3E'">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">¥${parseInt(item.price).toLocaleString()}</div>
                        <div class="cart-item-controls">
                            <button class="quantity-btn" onclick="app.updateQuantity(${item.id}, -1)"><i class="fas fa-minus"></i></button>
                            <span class="quantity">${item.quantity}</span>
                            <button class="quantity-btn" onclick="app.updateQuantity(${item.id}, 1)"><i class="fas fa-plus"></i></button>
                            <button class="remove-btn" onclick="app.removeFromCart(${item.id})" title="削除"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            `;
            }).join('');
            document.getElementById('orderBtn').disabled = false;
        }

        document.getElementById('totalPrice').textContent = `¥${total.toLocaleString()}`;
    }

    openCart() {
        document.getElementById('cartSidebar').classList.add('open');
        document.getElementById('overlay').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    closeCart() {
        document.getElementById('cartSidebar').classList.remove('open');
        document.getElementById('overlay').classList.remove('active');
        document.body.style.overflow = '';
    }

    showCartNotification() {
        const btn = document.getElementById('cartBtn');
        btn.style.transform = 'scale(1.1)';
        setTimeout(() => {
            btn.style.transform = 'scale(1)';
        }, 200);
    }

    async placeOrder() {
        if (this.cart.length === 0) return;

        const total = this.cart.reduce((sum, item) => sum + (parseInt(item.price) * item.quantity), 0);
        
        // 決済方法を選択
        const paymentMethod = await this.selectPaymentMethod();
        if (!paymentMethod) return;

        const orderData = {
            items: this.cart.map(item => ({
                product_id: item.id,
                quantity: item.quantity
            })),
            payment_method: paymentMethod,
            points_used: 0
        };

        try {
            // 注文を作成
            const response = await fetch('/api/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Line-User-Id': 'test-user' // テスト用
                },
                body: JSON.stringify(orderData)
            });

            // レスポンスがJSONかどうか確認
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('サーバーエラーが発生しました。JSON形式のレスポンスが返されませんでした。');
            }

            const orderResult = await response.json();
            
            if (!response.ok) {
                throw new Error(orderResult.error || '注文の作成に失敗しました');
            }

            const order = orderResult.order;

            // Stripe決済の場合
            if (paymentMethod === 'stripe') {
                await this.processStripePayment(order.id, total);
            } else {
                alert('注文が完了しました！');
                this.cart = [];
                this.updateCartUI();
                this.renderProducts();
                this.closeCart();
            }
        } catch (error) {
            console.error('注文エラー:', error);
            alert(`注文に失敗しました: ${error.message || 'エラーが発生しました'}`);
        }
    }

    async selectPaymentMethod() {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'payment-method-modal';
            modal.innerHTML = `
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
                                ${this.cart.map(item => `
                                    <div class="order-item-summary">
                                        <span class="order-item-name">${item.name} × ${item.quantity}</span>
                                        <span class="order-item-price">¥${(parseInt(item.price) * item.quantity).toLocaleString()}</span>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="order-total-summary">
                                <span class="total-label">合計金額</span>
                                <span class="total-amount">¥${this.cart.reduce((sum, item) => sum + (parseInt(item.price) * item.quantity), 0).toLocaleString()}</span>
                            </div>
                        </div>
                        <div class="payment-methods">
                            <button class="payment-method-btn" data-method="cash">
                                <i class="fas fa-money-bill-wave"></i>
                                <div class="payment-method-info">
                                    <div class="payment-method-name">現金</div>
                                    <div class="payment-method-desc">店頭でお支払い</div>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            <button class="payment-method-btn" data-method="stripe">
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
            document.body.appendChild(modal);

            const closeModal = () => {
                modal.remove();
                resolve(null);
            };

            // 決済方法ボタンのイベント
            modal.querySelectorAll('.payment-method-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const method = btn.dataset.method;
                    modal.remove();
                    resolve(method);
                });
            });

            // 閉じるボタンのイベント
            const closeBtn = modal.querySelector('#closePaymentMethodBtn');
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }

            // オーバーレイクリックで閉じる
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
        });
    }

    async processStripePayment(orderId, amount) {
        // 決済モーダルを表示
        this.showPaymentModal(orderId, amount);
    }

    showPaymentModal(orderId, amount) {
        const modal = document.createElement('div');
        modal.className = 'payment-modal';
        modal.innerHTML = `
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
                            <span class="payment-amount">¥${amount.toLocaleString()}</span>
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
                            <span>¥${amount.toLocaleString()} を支払う</span>
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
        document.body.appendChild(modal);

        const closeBtn = modal.querySelector('#closePaymentModalBtn');
        const cancelBtn = modal.querySelector('#cancelPaymentBtn');
        const confirmBtn = modal.querySelector('#confirmPaymentBtn');
        const paymentElement = modal.querySelector('#payment-element');

        // 閉じるボタンのイベント
        const closeModal = () => {
            modal.remove();
        };
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Stripe決済フォームをセットアップ
        const stripePayment = new StripePayment();
        stripePayment.setupPaymentForm('payment-element', amount, orderId)
            .then(() => {
                // ローディングを非表示
                const loading = paymentElement.querySelector('.payment-loading');
                if (loading) {
                    loading.remove();
                }
                // 決済ボタンを有効化
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = `
                    <i class="fas fa-lock"></i>
                    <span>¥${amount.toLocaleString()} を支払う</span>
                `;
            })
            .catch((error) => {
                paymentElement.innerHTML = `
                    <div class="payment-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>決済フォームの読み込みに失敗しました</p>
                        <p class="error-message">${error.message || 'エラーが発生しました'}</p>
                        <button class="retry-btn" onclick="location.reload()">
                            <i class="fas fa-redo"></i> 再試行
                        </button>
                    </div>
                `;
            });

        // 決済確認ボタンのイベント
        confirmBtn.addEventListener('click', async () => {
            if (confirmBtn.disabled) return;

            // ローディング状態
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = `
                <i class="fas fa-spinner fa-spin"></i>
                <span>処理中...</span>
            `;

            try {
                const result = await stripePayment.confirmPayment(orderId);
                if (result.success) {
                    // 成功メッセージ
                    modal.innerHTML = `
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
                                        <span>¥${amount.toLocaleString()}</span>
                                    </div>
                                </div>
                                <button class="success-close-btn" onclick="this.closest('.payment-modal').remove(); app.cart = []; app.updateCartUI(); app.renderProducts(); app.closeCart();">
                                    <i class="fas fa-check"></i>
                                    閉じる
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    throw new Error('決済が完了しませんでした');
                }
            } catch (error) {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = `
                    <i class="fas fa-lock"></i>
                    <span>¥${amount.toLocaleString()} を支払う</span>
                `;
                
                // エラーメッセージを表示
                const errorMsg = document.createElement('div');
                errorMsg.className = 'payment-error-message';
                errorMsg.innerHTML = `
                    <i class="fas fa-exclamation-circle"></i>
                    <span>${error.message || '決済に失敗しました。もう一度お試しください。'}</span>
                `;
                modal.querySelector('.payment-actions').prepend(errorMsg);
                
                setTimeout(() => {
                    errorMsg.remove();
                }, 5000);
            }
        });
    }

    switchTab(tab) {
        this.currentTab = tab;
        
        // タブボタンの状態を更新
        document.querySelectorAll('.main-tab-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.tab === tab) {
                btn.classList.add('active');
            }
        });

        // タブコンテンツの表示を切り替え
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        if (tab === 'products') {
            document.getElementById('productsTab').classList.add('active');
        } else if (tab === 'member') {
            document.getElementById('memberTab').classList.add('active');
            this.loadMemberCard();
        }
    }

    async loadMemberCard() {
        const container = document.getElementById('memberSection');
        if (!container) return;

        container.innerHTML = '<div class="loading">読み込み中...</div>';

        try {
            // テスト用: 認証がない場合はテストユーザーを使用
            const userId = 'test-user';
            const response = await fetch(`/api/members/me`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Line-User-Id': userId,
                },
            });

            if (!response.ok) {
                throw new Error('会員情報の取得に失敗しました');
            }

            const data = await response.json();

            if (!data.is_member) {
                container.innerHTML = `
                    <div class="member-card-container">
                        <div class="member-card">
                            <div class="member-card-header">
                                <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                            </div>
                            <div style="text-align: center; padding: 40px 20px;">
                                <p style="margin-bottom: 20px; color: var(--text-primary);">会員登録がまだです</p>
                                <button class="register-member-btn" onclick="app.registerMember()">
                                    <i class="fas fa-user-plus"></i> 会員登録する
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                this.memberData = data;
                this.renderMemberCard(data);
            }
        } catch (error) {
            console.error('会員証の読み込みに失敗しました:', error);
            container.innerHTML = `
                <div class="loading" style="color: var(--text-primary);">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>会員証の読み込みに失敗しました</p>
                    <p style="font-size: 12px; margin-top: 8px;">${error.message || 'エラーが発生しました'}</p>
                    <button onclick="app.loadMemberCard()" style="margin-top: 12px; padding: 8px 16px; background: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer;">
                        <i class="fas fa-redo"></i> 再読み込み
                    </button>
                </div>
            `;
        }
    }

    renderMemberCard(data) {
        const container = document.getElementById('memberSection');
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

        // バーコードを生成
        if (typeof JsBarcode !== 'undefined' && memberNumber) {
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
                    console.error('バーコード生成エラー:', error);
                }
            }, 100);
        }
    }

    async registerMember() {
        try {
            const userId = 'test-user';
            const response = await fetch('/api/members/register', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Line-User-Id': userId,
                },
                body: JSON.stringify({}),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || '会員登録に失敗しました');
            }

            const data = await response.json();
            alert('会員登録が完了しました！');
            this.loadMemberCard();
        } catch (error) {
            console.error('会員登録エラー:', error);
            alert(`会員登録に失敗しました: ${error.message || 'エラーが発生しました'}`);
        }
    }
}


const app = new MobileOrderApp();

