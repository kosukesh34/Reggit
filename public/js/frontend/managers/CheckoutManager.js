class CheckoutManager {
    constructor(apiClient, cartManager) {
        this.apiClient = apiClient;
        this.cartManager = cartManager;
    }

    async placeOrder() {
        if (!this.cartManager || this.cartManager.isEmpty()) {
            ToastManager.error('カートが空です');
            return;
        }
        const payload = {
            items: this.cartManager.getItemsForOrder(),
            payment_method: 'cash',
            points_used: 0,
            notes: '',
        };
        try {
            const result = await this.apiClient.post(AppConstants.API_ENDPOINTS.ORDERS, payload);
            if (result && result.message) {
                ToastManager.success(result.message);
            } else {
                ToastManager.success(AppConstants.MESSAGES.ORDER_SUCCESS);
            }
            this.cartManager.clear();
            const cartBtn = DomHelper.getElementById(AppConstants.ELEMENT_IDS.CART_BTN);
            if (cartBtn) cartBtn.click();
        } catch (error) {
            console.error('Order failed:', error);
            ToastManager.error(AppConstants.MESSAGES.ORDER_ERROR_DEFAULT);
        }
    }
}
