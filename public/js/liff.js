
class LiffApp {
    constructor() {
        this.liffId = null;
        this.lineUserId = null;
        this.products = [];
        this.cart = [];
        this.currentCategory = 'all';
        this.currentTab = 'menu';
        this.init();
    }

    async init() {
        try {
            // LIFF初期化
            await liff.init({ liffId: '2008962886-K2CRmPwV' });
            
            if (!liff.isLoggedIn()) {
                liff.login();
                return;
            }

            const profile = await liff.getProfile();
            this.lineUserId = profile.userId;

            // ユーザー情報をURLに追加
            if (!window.location.search.includes('userId')) {
                const url = new URL(window.location);
                url.searchParams.set('userId', this.lineUserId);
                window.history.replaceState({}, '', url);
            }

            this.setupEventListeners();
            this.loadProducts();
            this.loadMemberCard();
            this.loadOrders();
        } catch (error) {
            console.error('LIFF initialization error:', error);
            // LIFFが利用できない場合のフォールバック
            this.lineUserId = new URLSearchParams(window.location.search).get('userId');
            this.setupEventListeners();
            this.loadProducts();
        }
    }

    setupEventListeners() {
        // タブ切り替え
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tab = e.currentTarget.dataset.tab;
                this.switchTab(tab);
            });
        });

        // カテゴリーボタン
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.currentCategory = e.target.dataset.category;
                this.renderProducts();
            });
        });

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

        // 注文ボタン
        document.getElementById('orderBtn').addEventListener('click', () => {
            this.placeOrder();
        });
    }

    switchTab(tab) {
        this.currentTab = tab;
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
        document.getElementById(`${tab}Tab`).classList.add('active');

        if (tab === 'member') {
            this.loadMemberCard();
        } else if (tab === 'orders') {
            this.loadOrders();
        }
    }

    async loadProducts() {
        try {
            const url = `/liff/products${this.currentCategory !== 'all' ? '?category=' + this.currentCategory : ''}`;
            const response = await fetch(url);
            this.products = await response.json();
            this.renderProducts();
        } catch (error) {
            console.error('商品の読み込みに失敗しました:', error);
            document.getElementById('productsGrid').innerHTML = 
                '<div class="loading">商品の読み込みに失敗しました</div>';
        }
    }

    renderProducts() {
        const grid = document.getElementById('productsGrid');
        const filtered = this.currentCategory === 'all' 
            ? this.products 
            : this.products.filter(p => p.category === this.currentCategory);

        if (filtered.length === 0) {
            grid.innerHTML = '<div class="loading">商品が見つかりませんでした</div>';
            return;
        }

        grid.innerHTML = filtered.map(product => {
            const imageUrl = product.image_url 
                ? (product.image_url.startsWith('http') ? product.image_url : product.image_url)
                : '';
            const cartItem = this.cart.find(item => item.id === product.id);
            const isInCart = cartItem !== undefined;
            const cartQuantity = isInCart ? cartItem.quantity : 0;
            
            return `
            <div class="product-card ${isInCart ? 'in-cart' : ''}" data-product-id="${product.id}">
                <div class="product-image-wrapper">
                    ${isInCart ? '<div class="cart-badge"><i class="fas fa-check-circle"></i></div>' : ''}
                    <img src="${imageUrl}" 
                         alt="${product.name}" 
                         class="product-image"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22150%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22200%22 height=%22150%22/%3E%3C/svg%3E'">
                </div>
                <div class="product-info">
                    <div class="product-name">${product.name}</div>
                    <div class="product-price">¥${parseInt(product.price).toLocaleString()}</div>
                    ${isInCart ? `
                        <div class="cart-quantity-badge">${cartQuantity}個追加済み</div>
                    ` : ''}
                    <button class="product-add-btn ${isInCart ? 'added' : ''}" onclick="liffApp.addToCart(${product.id})">
                        ${isInCart ? '<i class="fas fa-check"></i> 追加済み' : '<i class="fas fa-cart-plus"></i> カートに追加'}
                    </button>
                </div>
            </div>
        `;
        }).join('');
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
        this.renderProducts();
    }

    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.id !== productId);
        this.updateCartUI();
        this.renderProducts();
    }

    updateQuantity(productId, change) {
        const item = this.cart.find(item => item.id === productId);
        if (!item) return;

        item.quantity += change;
        if (item.quantity <= 0) {
            this.removeFromCart(productId);
        } else {
            this.updateCartUI();
            this.renderProducts();
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
                            <button class="quantity-btn" onclick="liffApp.updateQuantity(${item.id}, -1)"><i class="fas fa-minus"></i></button>
                            <span class="quantity">${item.quantity}</span>
                            <button class="quantity-btn" onclick="liffApp.updateQuantity(${item.id}, 1)"><i class="fas fa-plus"></i></button>
                            <button class="remove-btn" onclick="liffApp.removeFromCart(${item.id})"><i class="fas fa-trash"></i></button>
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

    async loadMemberCard() {
        const container = document.getElementById('memberCardContainer');
        if (!this.lineUserId) {
            container.innerHTML = '<div class="loading">ユーザー情報を取得できませんでした</div>';
            return;
        }

        try {
            const response = await fetch(`/liff/member?userId=${this.lineUserId}`);
            const data = await response.json();

            if (!data.is_member) {
                container.innerHTML = `
                    <div class="member-card">
                        <div class="member-card-header">
                            <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                        </div>
                        <div style="text-align: center; padding: 40px 20px;">
                            <p style="margin-bottom: 20px;">会員登録がまだです</p>
                            <button class="register-member-btn" onclick="liffApp.registerMember()">
                                <i class="fas fa-user-plus"></i> 会員登録する
                            </button>
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="member-card">
                        <div class="member-card-header">
                            <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                        </div>
                        <div class="member-info">
                            <div class="member-info-item">
                                <span class="member-info-label">会員番号</span>
                                <span class="member-info-value">${data.member.member_number}</span>
                            </div>
                            <div class="member-info-item">
                                <span class="member-info-label">ステータス</span>
                                <span class="member-info-value">${data.member.status === 'active' ? '有効' : '無効'}</span>
                            </div>
                        </div>
                        <div class="points-display">
                            <div class="points-value">${data.member.points.toLocaleString()}</div>
                            <div class="points-label">ポイント</div>
                        </div>
                    </div>
                    ${data.recent_transactions && data.recent_transactions.length > 0 ? `
                    <div class="points-history">
                        <h3 class="points-history-title">最近のポイント履歴</h3>
                        ${data.recent_transactions.map(tx => `
                            <div class="point-transaction">
                                <div>
                                    <div class="transaction-type ${tx.type}">
                                        <i class="fas fa-${tx.type === 'earned' ? 'plus-circle' : 'minus-circle'}"></i>
                                        <span>${tx.type === 'earned' ? '獲得' : '使用'}</span>
                                    </div>
                                    <div class="transaction-date">${new Date(tx.created_at).toLocaleString('ja-JP')}</div>
                                </div>
                                <div class="transaction-points ${tx.type === 'earned' ? 'earned' : 'used'}">
                                    ${tx.points > 0 ? '+' : ''}${tx.points.toLocaleString()}pt
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    ` : ''}
                `;
            }
        } catch (error) {
            console.error('会員情報の読み込みに失敗しました:', error);
            container.innerHTML = '<div class="loading">会員情報の読み込みに失敗しました</div>';
        }
    }

    async registerMember() {
        if (!this.lineUserId) {
            alert('ユーザー情報を取得できませんでした');
            return;
        }

        try {
            const response = await fetch('/api/members/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Line-User-Id': this.lineUserId,
                },
            });

            const data = await response.json();
            if (response.ok) {
                alert('会員登録が完了しました！');
                this.loadMemberCard();
            } else {
                alert(`会員登録に失敗しました: ${data.error || 'エラーが発生しました'}`);
            }
        } catch (error) {
            console.error('会員登録エラー:', error);
            alert('会員登録に失敗しました');
        }
    }

    async loadOrders() {
        const container = document.getElementById('ordersContainer');
        if (!this.lineUserId) {
            container.innerHTML = '<div class="loading">ユーザー情報を取得できませんでした</div>';
            return;
        }

        try {
            const response = await fetch(`/liff/orders?userId=${this.lineUserId}`);
            const orders = await response.json();

            if (orders.length === 0) {
                container.innerHTML = '<div class="loading">注文履歴がありません</div>';
                return;
            }

            container.innerHTML = orders.map(order => {
                const statusClass = order.status === 'completed' ? 'completed' : 
                                  (order.status === 'pending' ? 'pending' : 'confirmed');
                return `
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-number">${order.order_number}</div>
                            <div class="order-status ${statusClass}">${order.status}</div>
                        </div>
                        <div class="order-details">
                            <div class="order-total">¥${parseInt(order.total_amount).toLocaleString()}</div>
                            <div class="order-date">${new Date(order.created_at).toLocaleString('ja-JP')}</div>
                        </div>
                    </div>
                `;
            }).join('');
        } catch (error) {
            console.error('注文履歴の読み込みに失敗しました:', error);
            container.innerHTML = '<div class="loading">注文履歴の読み込みに失敗しました</div>';
        }
    }

    async placeOrder() {
        if (this.cart.length === 0) return;

        if (!this.lineUserId) {
            alert('ユーザー情報を取得できませんでした');
            return;
        }

        const total = this.cart.reduce((sum, item) => sum + (parseInt(item.price) * item.quantity), 0);
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
            const response = await fetch('/api/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Line-User-Id': this.lineUserId,
                },
                body: JSON.stringify(orderData)
            });

            const orderResult = await response.json();
            
            if (!response.ok) {
                throw new Error(orderResult.error || '注文の作成に失敗しました');
            }

            const order = orderResult.order;

            if (paymentMethod === 'stripe') {
                await this.processStripePayment(order.id, total);
            } else {
                liff.sendMessages([{
                    type: 'text',
                    text: `注文が完了しました！\n注文番号: ${order.order_number}\n合計: ¥${total.toLocaleString()}`
                }]);
                alert('注文が完了しました！');
                this.cart = [];
                this.updateCartUI();
                this.closeCart();
                this.renderProducts();
                this.loadOrders();
            }
        } catch (error) {
            console.error('注文エラー:', error);
            alert(`注文に失敗しました: ${error.message || 'エラーが発生しました'}`);
        }
    }

    async selectPaymentMethod() {
        return new Promise((resolve) => {
            const method = prompt('決済方法を選択してください:\n1. 現金\n2. カード（Stripe）\n\n番号を入力:', '1');
            if (method === '1') {
                resolve('cash');
            } else if (method === '2') {
                resolve('stripe');
            } else {
                resolve(null);
            }
        });
    }

    async processStripePayment(orderId, amount) {
        this.showPaymentModal(orderId, amount);
    }

    showPaymentModal(orderId, amount) {
        const modal = document.createElement('div');
        modal.className = 'payment-modal';
        modal.innerHTML = `
            <div class="payment-modal-content">
                <div class="payment-modal-header">
                    <h2>お支払い</h2>
                    <button class="close-payment-btn" onclick="this.closest('.payment-modal').remove()"><i class="fas fa-times"></i></button>
                </div>
                <div class="payment-modal-body">
                    <p>合計金額: ¥${amount.toLocaleString()}</p>
                    <div id="payment-element"></div>
                    <button class="confirm-payment-btn" id="confirmPaymentBtn">決済を完了</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        const stripePayment = new StripePayment();
        stripePayment.setupPaymentForm('payment-element', amount, orderId).then(() => {
            document.getElementById('confirmPaymentBtn').addEventListener('click', async () => {
                try {
                    const result = await stripePayment.confirmPayment(orderId);
                    if (result.success) {
                        liff.sendMessages([{
                            type: 'text',
                            text: `決済が完了しました！\n注文番号: ${orderId}\n合計: ¥${amount.toLocaleString()}`
                        }]);
                        alert('決済が完了しました！');
                        modal.remove();
                        this.cart = [];
                        this.updateCartUI();
                        this.closeCart();
                        this.renderProducts();
                        this.loadOrders();
                    }
                } catch (error) {
                    alert(`決済に失敗しました: ${error.message}`);
                }
            });
        });
    }
}


const liffApp = new LiffApp();

