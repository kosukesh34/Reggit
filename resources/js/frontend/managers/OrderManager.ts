import type { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';

type OrderItem = {
    quantity?: number;
    product?: { name?: string } | null;
};

type Order = {
    id?: number | string;
    order_number?: string;
    status?: string;
    order_type?: string;
    created_at?: string;
    total_amount?: number | string;
    items?: OrderItem[];
};

export class OrderManager {
    apiClient: ApiClient;

    constructor(apiClient: ApiClient) {
        this.apiClient = apiClient;
    }

    async fetchOrders(): Promise<Order[]> {
        const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.ORDERS);
        if (!result.success) {
            return [];
        }
        const data = result.data as { data?: Order[] } | Order[];
        if (data && !Array.isArray(data) && data.data) {
            return data.data;
        }
        if (Array.isArray(data)) {
            return data;
        }
        return [];
    }

    buildOrderCardHtml(order: Order): string {
        const labels = AppConstants.ORDER_STATUS_LABELS || {};
        const statusLabel = labels[order.status as keyof typeof labels] || order.status;
        const typeLabels = AppConstants.ORDER_TYPE_LABELS || {};
        const typeKey = order.order_type || '';
        const typeLabel =
            typeKey && typeLabels[typeKey as keyof typeof typeLabels]
                ? typeLabels[typeKey as keyof typeof typeLabels]
                : '';
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
                    `<span class="order-item-line">${DomHelper.escapeHtml((i.product && i.product.name) || '商品')} × ${i.quantity || 0}</span>`,
            )
            .join('');
        const refNumber = DomHelper.formatReferenceNumber(order.order_number);
        const typeBadge = typeLabel
            ? `<span class="order-card-type order-card-type--${DomHelper.escapeHtml(typeKey)}">${DomHelper.escapeHtml(typeLabel)}</span>`
            : '';
        return `
            <div class="order-card">
                <div class="order-card-header">
                    <span class="order-card-date">${DomHelper.escapeHtml(dateStr)}</span>
                    ${typeBadge}
                    <span class="order-card-status order-card-status--${order.status}">${DomHelper.escapeHtml(statusLabel)}</span>
                    ${refNumber ? `<span class="ref-number" aria-label="注文番号">${DomHelper.escapeHtml(refNumber)}</span>` : ''}
                </div>
                <div class="order-card-items">${items}</div>
                <div class="order-card-total">合計: ${DomHelper.formatPrice(order.total_amount || 0)}</div>
            </div>
        `;
    }

    buildOrderListHtml(orders: Order[]): string {
        if (!orders || orders.length === 0) {
            return '';
        }
        return orders.map((order) => this.buildOrderCardHtml(order)).join('');
    }

    renderOrdersSection(container: Element | null, orders: Order[]): void {
        if (!container) {
            return;
        }
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

        const grouped: Record<string, Order[]> = {};
        orders.forEach((order) => {
            const s = order.status || 'pending';
            if (!grouped[s]) {
                grouped[s] = [];
            }
            grouped[s].push(order);
        });

        const activeStatuses = ['pending', 'confirmed', 'preparing', 'ready'];
        const activeOrders = activeStatuses.flatMap((s) => grouped[s] || []);
        const pastOrders = ['completed', 'cancelled'].flatMap((s) => grouped[s] || []);

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

    renderOrderListOnly(container: Element | null, orders: Order[]): void {
        if (!container) {
            return;
        }
        if (!orders || orders.length === 0) {
            container.innerHTML = '<div class="member-purchase-history-empty"><p>購入履歴はありません</p></div>';
            return;
        }
        const listHtml = this.buildOrderListHtml(orders);
        container.innerHTML = `<div class="member-purchase-history-list-inner">${listHtml}</div>`;
    }
}
