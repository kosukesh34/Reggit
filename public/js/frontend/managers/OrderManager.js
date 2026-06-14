class OrderManager {
    constructor(apiClient) {
        this.apiClient = apiClient;
    }

    async fetchOrders() {
        const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.ORDERS);
        if (result.data && result.data.data) return result.data.data;
        if (Array.isArray(result.data)) return result.data;
        return [];
    }

    buildOrderCardHtml(order) {
        const labels = AppConstants.ORDER_STATUS_LABELS || {};
        const statusLabel = labels[order.status] || order.status;
        const dateStr = order.created_at
            ? new Date(order.created_at).toLocaleDateString('ja-JP', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            })
            : '';
        const items = (order.items || [])
            .map(
                (i) =>
                    `<span class="order-item-line">${DomHelper.escapeHtml((i.product && i.product.name) || '商品')} × ${i.quantity || 0}</span>`
            )
            .join('');
        const refNumber = DomHelper.formatReferenceNumber(order.order_number, order.id);
        return `
            <div class="order-card">
                <div class="order-card-header">
                    <span class="order-card-date">${DomHelper.escapeHtml(dateStr)}</span>
                    <span class="order-card-status order-card-status--${order.status}">${DomHelper.escapeHtml(statusLabel)}</span>
                    ${refNumber ? `<span class="ref-number" aria-label="注文番号">${DomHelper.escapeHtml(refNumber)}</span>` : ''}
                </div>
                <div class="order-card-items">${items}</div>
                <div class="order-card-total">合計: ${DomHelper.formatPrice(order.total_amount || 0)}</div>
            </div>
        `;
    }

    buildOrderListHtml(orders) {
        if (!orders || orders.length === 0) return '';
        return orders.map((order) => this.buildOrderCardHtml(order)).join('');
    }

    renderOrdersSection(container, orders) {
        if (!container) return;
        if (!orders || orders.length === 0) {
            container.innerHTML = `
                <div class="orders-empty">
                    <i class="fas fa-receipt"></i>
                    <p>注文履歴はありません</p>
                    <p class="orders-empty-sub">商品タブから注文してみましょう</p>
                </div>
            `;
            return;
        }

        // ステータス別にグループ化
        const statusOrder = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];
        const statusIcons = {
            pending: 'fa-clock', confirmed: 'fa-check', preparing: 'fa-fire',
            ready: 'fa-bell', completed: 'fa-check-double', cancelled: 'fa-times-circle'
        };
        const labels = AppConstants.ORDER_STATUS_LABELS || {};
        const grouped = {};
        orders.forEach(order => {
            const s = order.status || 'pending';
            if (!grouped[s]) grouped[s] = [];
            grouped[s].push(order);
        });

        // アクティブ注文（pending/confirmed/preparing/ready）とその他を分ける
        const activeStatuses = ['pending', 'confirmed', 'preparing', 'ready'];
        const activeOrders = activeStatuses.flatMap(s => grouped[s] || []);
        const pastOrders = ['completed', 'cancelled'].flatMap(s => grouped[s] || []);

        let html = '<div class="orders-header"><h2 class="section-title"><i class="fas fa-receipt"></i> 注文履歴</h2></div>';

        if (activeOrders.length > 0) {
            html += `<div class="order-group">
                <div class="order-group-header order-group-header--active">
                    <i class="fas fa-bolt"></i>
                    <span>進行中の注文</span>
                    <span class="order-group-count">${activeOrders.length}件</span>
                </div>
                <div class="orders-list">${this.buildOrderListHtml(activeOrders)}</div>
            </div>`;
        }

        if (pastOrders.length > 0) {
            html += `<div class="order-group">
                <div class="order-group-header">
                    <i class="fas fa-history"></i>
                    <span>過去の注文</span>
                    <span class="order-group-count">${pastOrders.length}件</span>
                </div>
                <div class="orders-list">${this.buildOrderListHtml(pastOrders)}</div>
            </div>`;
        }

        container.innerHTML = html;
    }

    renderOrderListOnly(container, orders) {
        if (!container) return;
        if (!orders || orders.length === 0) {
            container.innerHTML = '<div class="member-purchase-history-empty"><p>購入履歴はありません</p></div>';
            return;
        }
        const listHtml = this.buildOrderListHtml(orders);
        container.innerHTML = `<div class="member-purchase-history-list-inner">${listHtml}</div>`;
    }
}
