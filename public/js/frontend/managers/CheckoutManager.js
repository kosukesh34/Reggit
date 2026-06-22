class CheckoutManager {
    constructor(apiClient, cartManager) {
        this.apiClient = apiClient;
        this.cartManager = cartManager;
        this.stripePayment = (typeof window !== 'undefined' && typeof window.StripePayment === 'function')
            ? new window.StripePayment()
            : null;
        this.paymentModal = null;
        this.isSubmitting = false;
        this.isPaymentFlowActive = false;
        this.pendingMobileOrderId = null;
        this.isPaymentCompleted = false;
    }

    async placeInStoreOrder() {
        if (this.isSubmitting || this.isPaymentFlowActive) return;
        if (!this.isInStoreOrderEnabled()) {
            ToastManager.error('現在は店内注文を利用できません');
            return;
        }
        if (!this.cartManager || this.cartManager.isEmpty()) {
            ToastManager.error('カートが空です');
            return;
        }
        this.isSubmitting = true;
        try {
            const payload = {
                items: this.cartManager.getItemsForOrder(),
                payment_method: 'cash',
                points_used: 0,
                notes: '',
                order_type: 'in_store',
            };
            const result = await this.apiClient.post(AppConstants.API_ENDPOINTS.ORDERS, payload);
            if (!result || result.success !== true) {
                console.error('In-store order failed:', result && result.error ? result.error : 'unknown error');
                ToastManager.error((result && result.error) || AppConstants.MESSAGES.ORDER_ERROR_DEFAULT);
                return;
            }

            ToastManager.success((result.data && result.data.message) || '店内注文を受け付けました');
            this.afterOrderCompleted();
        } finally {
            this.isSubmitting = false;
        }
    }

    async placeMobileOrder() {
        if (this.isSubmitting || this.isPaymentFlowActive) return;
        if (!this.isMobileOrderEnabled()) {
            ToastManager.error('現在はモバイル注文を利用できません');
            return;
        }
        if (!this.cartManager || this.cartManager.isEmpty()) {
            ToastManager.error('カートが空です');
            return;
        }
        if (!this.stripePayment) {
            ToastManager.error('決済機能の読み込みに失敗しました');
            return;
        }

        this.isSubmitting = true;
        try {
            const payload = {
                items: this.cartManager.getItemsForOrder(),
                payment_method: 'stripe',
                points_used: 0,
                notes: '',
                order_type: 'mobile',
            };
            const result = await this.apiClient.post(AppConstants.API_ENDPOINTS.ORDERS, payload);
            if (!result || result.success !== true || !result.data || !result.data.order) {
                console.error('Mobile order failed:', result && result.error ? result.error : 'unknown error');
                ToastManager.error((result && result.error) || AppConstants.MESSAGES.ORDER_ERROR_DEFAULT);
                return;
            }

            const order = result.data.order;
            await this.openPaymentModal(order);
        } finally {
            this.isSubmitting = false;
        }
    }

    async openPaymentModal(order) {
        this.closePaymentModal(false);
        this.isPaymentFlowActive = true;
        this.isPaymentCompleted = false;
        this.pendingMobileOrderId = order && order.id ? Number(order.id) : null;

        const modal = document.createElement('div');
        modal.className = 'payment-modal';
        modal.innerHTML = `
            <div class="payment-modal-content">
                <div class="payment-modal-header">
                    <h2><i class="fas fa-credit-card"></i> モバイル決済</h2>
                    <button type="button" class="close-payment-btn" data-close-payment><i class="fas fa-times"></i></button>
                </div>
                <div class="payment-modal-body">
                    <div class="payment-summary">
                        <div class="payment-summary-item">
                            <span>注文番号</span>
                            <span class="order-number ref-number">${DomHelper.escapeHtml(DomHelper.formatReferenceNumber(order.order_number, order.id))}</span>
                        </div>
                        <div class="payment-summary-item">
                            <span>お支払い金額</span>
                            <span class="payment-amount">${DomHelper.formatPrice(order.total_amount || 0)}</span>
                        </div>
                    </div>
                    <div class="payment-form-section">
                        <h3><i class="fas fa-lock"></i> カード情報</h3>
                        <div class="payment-element-container" id="mobileOrderPaymentElement">
                            <div class="payment-loading"><i class="fas fa-spinner fa-spin"></i><p>決済フォームを準備中...</p></div>
                        </div>
                    </div>
                    <div class="payment-error-message" id="mobileOrderPaymentError" style="display:none;"></div>
                    <div class="payment-actions">
                        <button type="button" class="confirm-payment-btn" id="mobileOrderConfirmBtn" disabled>
                            <i class="fas fa-check-circle"></i> 決済して注文確定
                        </button>
                        <button type="button" class="cancel-payment-btn" data-close-payment>
                            <i class="fas fa-times"></i> キャンセル
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        this.paymentModal = modal;

        const closeButtons = modal.querySelectorAll('[data-close-payment]');
        closeButtons.forEach((btn) => btn.addEventListener('click', () => this.closePaymentModal(true)));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) this.closePaymentModal(true);
        });

        const errorEl = document.getElementById('mobileOrderPaymentError');
        const confirmBtn = document.getElementById('mobileOrderConfirmBtn');

        try {
            await this.stripePayment.setupPaymentForm('mobileOrderPaymentElement', Number(order.total_amount || 0), order.id);
            if (confirmBtn) confirmBtn.disabled = false;
        } catch (error) {
            const message = (error && error.message) ? error.message : '決済フォームの準備に失敗しました';
            if (errorEl) {
                errorEl.innerHTML = `<i class="fas fa-exclamation-circle"></i><span>${DomHelper.escapeHtml(message)}</span>`;
                errorEl.style.display = 'flex';
            }
            ToastManager.error(message);
            this.closePaymentModal(true);
            return;
        }

        if (confirmBtn) {
            confirmBtn.addEventListener('click', async () => {
                confirmBtn.disabled = true;
                const original = confirmBtn.innerHTML;
                confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 決済中...';
                try {
                    const paymentResult = await this.stripePayment.confirmPayment(order.id);
                    if (!paymentResult || paymentResult.success !== true) {
                        throw new Error((paymentResult && paymentResult.message) || '決済の確認に失敗しました');
                    }
                    this.isPaymentCompleted = true;
                    ToastManager.success('モバイル注文が完了しました');
                    this.closePaymentModal(false);
                    this.afterOrderCompleted();
                } catch (error) {
                    const message = (error && error.message) ? error.message : '決済に失敗しました';
                    if (errorEl) {
                        errorEl.innerHTML = `<i class="fas fa-exclamation-circle"></i><span>${DomHelper.escapeHtml(message)}</span>`;
                        errorEl.style.display = 'flex';
                    }
                    ToastManager.error(message);
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = original;
                }
            });
        }
    }

    closePaymentModal(shouldCancelOrder) {
        if (this.paymentModal && typeof this.paymentModal.remove === 'function') {
            this.paymentModal.remove();
        }
        document.querySelectorAll('.payment-modal').forEach((el) => {
            if (el !== this.paymentModal && typeof el.remove === 'function') el.remove();
        });
        this.paymentModal = null;
        const orderId = this.pendingMobileOrderId;
        const shouldCancel = shouldCancelOrder === true && !this.isPaymentCompleted && !!orderId;
        this.isPaymentFlowActive = false;
        this.pendingMobileOrderId = null;
        this.isPaymentCompleted = false;
        if (shouldCancel) {
            this.cancelPendingMobileOrder(orderId);
        }
    }

    async cancelPendingMobileOrder(orderId) {
        try {
            const endpoint = AppConstants.API_ENDPOINTS.ORDERS + '/' + encodeURIComponent(String(orderId)) + '/cancel-unpaid';
            const result = await this.apiClient.post(endpoint, {});
            if (!result || result.success !== true) {
                return;
            }
            if (typeof window !== 'undefined' && window.app) {
                if (typeof window.app.loadOrdersTab === 'function') {
                    window.app.loadOrdersTab();
                }
            }
        } catch (e) {
            // 未決済注文キャンセルはベストエフォート
        }
    }

    afterOrderCompleted() {
        this.cartManager.clear();
        if (typeof window !== 'undefined' && window.app) {
            if (typeof window.app.updateCartUI === 'function') {
                window.app.updateCartUI();
            }
            if (typeof window.app.renderProducts === 'function') {
                window.app.renderProducts();
            }
            if (typeof window.app.closeCart === 'function') {
                window.app.closeCart();
            }
            if (typeof window.app.loadOrdersTab === 'function') {
                window.app.loadOrdersTab();
            }
            // 注文完了でポイント等が変わるため会員情報を再取得し、会員証・全タブの表示を更新する（非同期のため失敗してもタブ操作は維持）
            if (typeof window.app.refreshAfterMemberUpdate === 'function') {
                window.app.refreshAfterMemberUpdate().catch(function () {});
            }
        }
    }

    getOrderMode() {
        if (typeof window === 'undefined') return 'both';
        const mode = String(window.__ORDER_MODE__ || 'both');
        return (mode === 'mobile_only' || mode === 'in_store_only' || mode === 'both') ? mode : 'both';
    }

    isMobileOrderEnabled() {
        return this.getOrderMode() !== 'in_store_only';
    }

    isInStoreOrderEnabled() {
        return this.getOrderMode() !== 'mobile_only';
    }
}
