import type { ApiClient } from '../../shared/utils/apiClient';
import type { CartManager } from './CartManager';
import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';
import { StripePayment } from '../../shared/utils/stripePayment';
import { ToastManager } from '../../shared/utils/toast';

type OrderLike = {
    id?: number | string;
    order_number?: string;
    total_amount?: number | string;
};

type AppLike = {
    updateCartUI?: () => void;
    renderProducts?: () => void;
    closeCart?: () => void;
    loadOrdersTab?: () => void;
    refreshAfterMemberUpdate?: () => Promise<void>;
};

export class CheckoutManager {
    apiClient: ApiClient;
    cartManager: CartManager;
    stripePayment: StripePayment;
    paymentModal: HTMLElement | null;
    isSubmitting: boolean;
    isPaymentFlowActive: boolean;
    pendingMobileOrderId: number | null;
    isPaymentCompleted: boolean;

    constructor(apiClient: ApiClient, cartManager: CartManager) {
        this.apiClient = apiClient;
        this.cartManager = cartManager;
        this.stripePayment = new StripePayment();
        this.paymentModal = null;
        this.isSubmitting = false;
        this.isPaymentFlowActive = false;
        this.pendingMobileOrderId = null;
        this.isPaymentCompleted = false;
    }

    async placeInStoreOrder(): Promise<void> {
        if (this.isSubmitting || this.isPaymentFlowActive) {
            return;
        }
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
                // eslint-disable-next-line no-console
                console.error(
                    'In-store order failed:',
                    result && result.success !== true ? result.error : 'unknown error',
                );
                ToastManager.error(
                    (result && result.success !== true && result.error)
                        || AppConstants.MESSAGES.ORDER_ERROR_DEFAULT,
                );
                return;
            }

            const data = result.data as { message?: string; order?: OrderLike };
            const issuedNumber = DomHelper.formatReferenceNumber(data?.order?.order_number);
            ToastManager.success(
                issuedNumber
                    ? `店内注文を受け付けました（引換番号: ${issuedNumber}）`
                    : ((data && data.message) || '店内注文を受け付けました'),
            );
            this.afterOrderCompleted(data?.order?.order_number ?? null);
        } finally {
            this.isSubmitting = false;
        }
    }

    async placeMobileOrder(): Promise<void> {
        if (this.isSubmitting || this.isPaymentFlowActive) {
            return;
        }
        if (!this.isMobileOrderEnabled()) {
            ToastManager.error('現在はモバイル注文を利用できません');
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
                payment_method: 'stripe',
                points_used: 0,
                notes: '',
                order_type: 'mobile',
            };
            const result = await this.apiClient.post(AppConstants.API_ENDPOINTS.ORDERS, payload);
            if (
                !result
                || result.success !== true
                || !result.data
                || !(result.data as { order?: OrderLike }).order
            ) {
                // eslint-disable-next-line no-console
                console.error(
                    'Mobile order failed:',
                    result && result.success !== true ? result.error : 'unknown error',
                );
                ToastManager.error(
                    (result && result.success !== true && result.error)
                        || AppConstants.MESSAGES.ORDER_ERROR_DEFAULT,
                );
                return;
            }

        const order = (result.data as { order: OrderLike }).order;
        await this.openPaymentModal(order);
        } finally {
            this.isSubmitting = false;
        }
    }

    async openPaymentModal(order: OrderLike): Promise<void> {
        this.closePaymentModal(false);
        // React CheckoutModal があるときは CustomEvent で UI を開く
        const hasReactCheckout =
            typeof document !== 'undefined'
            && (document.getElementById('app-container') !== null
                || document.getElementById('react-frontend-root') !== null);
        if (hasReactCheckout) {
            this.isPaymentFlowActive = true;
            this.isPaymentCompleted = false;
            this.pendingMobileOrderId = order && order.id ? Number(order.id) : null;
            document.dispatchEvent(new CustomEvent('liff-open-payment', { detail: { order } }));
            return;
        }

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
            if (e.target === modal) {
                this.closePaymentModal(true);
            }
        });

        const errorEl = document.getElementById('mobileOrderPaymentError');
        const confirmBtn = document.getElementById('mobileOrderConfirmBtn') as HTMLButtonElement | null;

        try {
            await this.stripePayment.setupPaymentForm(
                'mobileOrderPaymentElement',
                Number(order.total_amount || 0),
                order.id,
            );
            if (confirmBtn) {
                confirmBtn.disabled = false;
            }
        } catch (error) {
            const message =
                error instanceof Error && error.message
                    ? error.message
                    : '決済フォームの準備に失敗しました';
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
                        throw new Error(
                            (paymentResult && paymentResult.message) || '決済の確認に失敗しました',
                        );
                    }
                    this.isPaymentCompleted = true;
                    const confirmedOrder = (paymentResult as { order?: OrderLike }).order;
                    ToastManager.success('モバイル注文が完了しました');
                    document.dispatchEvent(new CustomEvent('liff-payment-completed', {
                        detail: { order: confirmedOrder ?? order },
                    }));
                    this.closePaymentModal(false);
                    this.afterOrderCompleted(confirmedOrder?.order_number ?? null);
                } catch (error) {
                    const message =
                        error instanceof Error && error.message ? error.message : '決済に失敗しました';
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

    closePaymentModal(shouldCancelOrder: boolean): void {
        const hasReactCheckout =
            typeof document !== 'undefined'
            && (document.getElementById('app-container') !== null
                || document.getElementById('react-frontend-root') !== null);

        if (hasReactCheckout) {
            // React CheckoutModal はイベントでアンマウント（DOM を直接 remove しない）
            document.dispatchEvent(new CustomEvent('liff-close-payment', {
                detail: { shouldCancelOrder: shouldCancelOrder === true },
            }));
        } else {
            if (this.paymentModal && typeof this.paymentModal.remove === 'function') {
                this.paymentModal.remove();
            }
            document.querySelectorAll('.payment-modal').forEach((el) => {
                if (el !== this.paymentModal && typeof (el as HTMLElement).remove === 'function') {
                    (el as HTMLElement).remove();
                }
            });
            this.paymentModal = null;
        }

        const orderId = this.pendingMobileOrderId;
        const shouldCancel = shouldCancelOrder === true && !this.isPaymentCompleted && !!orderId;
        this.isPaymentFlowActive = false;
        this.pendingMobileOrderId = null;
        this.isPaymentCompleted = false;
        if (shouldCancel) {
            void this.cancelPendingMobileOrder(orderId);
        }
    }

    async cancelPendingMobileOrder(orderId: number): Promise<void> {
        try {
            const endpoint =
                AppConstants.API_ENDPOINTS.ORDERS
                + '/'
                + encodeURIComponent(String(orderId))
                + '/cancel-unpaid';
            const result = await this.apiClient.post(endpoint, {});
            if (!result || result.success !== true) {
                return;
            }
            if (typeof window !== 'undefined' && window.app) {
                if (typeof window.app.loadOrdersTab === 'function') {
                    window.app.loadOrdersTab();
                }
            }
        } catch (_e) {
            // 未決済注文キャンセルはベストエフォート
        }
    }

    afterOrderCompleted(orderNumber: string | number | null = null): void {
        this.cartManager.clear();
        if (typeof window !== 'undefined' && window.app) {
            const app = window.app as AppLike & {
                openOrderFlow?: (step: 'cart' | 'confirm' | 'complete') => void;
                lastOrderCompleteMessage?: string | null;
                lastOrderReferenceNumber?: string | null;
            };
            const displayNumber = DomHelper.formatReferenceNumber(orderNumber);
            if (typeof app.lastOrderCompleteMessage !== 'undefined') {
                app.lastOrderCompleteMessage = 'ご注文ありがとうございました';
            }
            if (typeof app.lastOrderReferenceNumber !== 'undefined') {
                app.lastOrderReferenceNumber = displayNumber !== '' ? displayNumber : null;
            }
            if (typeof app.updateCartUI === 'function') {
                app.updateCartUI();
            }
            if (typeof app.renderProducts === 'function') {
                app.renderProducts();
            }
            if (typeof app.openOrderFlow === 'function') {
                app.openOrderFlow('complete');
            } else if (typeof app.closeCart === 'function') {
                app.closeCart();
            }
            if (typeof app.loadOrdersTab === 'function') {
                app.loadOrdersTab();
            }
            if (typeof app.refreshAfterMemberUpdate === 'function') {
                app.refreshAfterMemberUpdate().catch(() => {});
            }
        }
    }

    getOrderMode(): string {
        if (typeof window === 'undefined') {
            return 'both';
        }
        const mode = String(window.__ORDER_MODE__ || 'both');
        return mode === 'mobile_only' || mode === 'in_store_only' || mode === 'both' ? mode : 'both';
    }

    isMobileOrderEnabled(): boolean {
        return this.getOrderMode() !== 'in_store_only';
    }

    isInStoreOrderEnabled(): boolean {
        return this.getOrderMode() !== 'mobile_only';
    }
}
