import type { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';
import { ToastManager } from '../../shared/utils/toast';

type OrderItem = {
    quantity?: number;
    product_name?: string | null;
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

type TrackerStep = {
    key: string;
    tMark: string;
    label: string;
    detail: string;
};

const ACTIVE_TRACKER_STEPS: TrackerStep[] = [
    { key: 'confirmed', tMark: 'T+0', label: '受付', detail: '注文を受け付けました' },
    { key: 'preparing', tMark: 'T+1', label: '準備中', detail: 'キッチンで準備しています' },
    { key: 'ready', tMark: 'T+2', label: '受取可', detail: '受け取りの準備が整いました' },
    { key: 'completed', tMark: 'T+3', label: '完了', detail: 'ミッション完了' },
];

const ACTIVE_ORDER_STATUSES = ['pending', 'confirmed', 'preparing', 'ready'];

export class OrderManager {
    apiClient: ApiClient;
    private pollTimer: ReturnType<typeof setInterval> | null = null;
    private lastFingerprint = '';
    private knownStatuses: Record<string, string> = {};
    private visibilityBound = false;
    private visibilityHandler: (() => void) | null = null;

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

    hasActiveOrders(orders: Order[]): boolean {
        return (orders || []).some((order) => ACTIVE_ORDER_STATUSES.includes(String(order.status || '')));
    }

    pickLiveOrder(orders: Order[], preferredRef?: string | null): Order | null {
        const active = (orders || []).filter((order) =>
            ACTIVE_ORDER_STATUSES.includes(String(order.status || ''))
        );
        if (active.length === 0) {
            return null;
        }
        const preferred = typeof preferredRef === 'string' ? preferredRef.trim() : '';
        if (preferred !== '') {
            const matched = active.find(
                (order) => DomHelper.formatReferenceNumber(order.order_number) === preferred
            );
            if (matched) {
                return matched;
            }
        }
        return active[0] ?? null;
    }

    renderOrderCompleteLiveStatus(
        container: Element | null,
        order: Order | null,
    ): void {
        if (!container) {
            return;
        }
        if (!order) {
            container.innerHTML = '';
            return;
        }
        const labels = AppConstants.ORDER_STATUS_LABELS || {};
        const statusLabel = labels[order.status as keyof typeof labels] || order.status || '';
        const refNumber = DomHelper.formatReferenceNumber(order.order_number);
        const refHtml = refNumber !== ''
            ? `<p class="order-flow-complete__live-ref">引換番号 ${DomHelper.escapeHtml(refNumber)}</p>`
            : '';
        container.innerHTML = `
            <div class="order-flow-complete__live-card" data-order-id="${DomHelper.escapeHtml(String(order.id ?? ''))}" data-order-status="${DomHelper.escapeHtml(String(order.status ?? ''))}">
                ${refHtml}
                <p class="order-flow-complete__live-status">
                    <span class="order-card-status order-card-status--${DomHelper.escapeHtml(String(order.status || ''))}">${DomHelper.escapeHtml(String(statusLabel))}</span>
                </p>
                ${this.buildStatusTrackerHtml(order.status, true)}
            </div>
        `;
    }

    startOrdersPolling(refresh: () => void | Promise<void>, intervalMs = 2500): void {
        this.stopOrdersPolling();
        this.pollTimer = setInterval(() => {
            void refresh();
        }, intervalMs);
        this.bindVisibilityRefresh(refresh);
    }

    stopOrdersPolling(): void {
        if (this.pollTimer !== null) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    }

    private bindVisibilityRefresh(refresh: () => void | Promise<void>): void {
        if (typeof document === 'undefined' || this.visibilityBound) {
            return;
        }
        this.visibilityBound = true;
        this.visibilityHandler = () => {
            if (document.visibilityState !== 'visible') {
                return;
            }
            if (this.pollTimer === null) {
                return;
            }
            void refresh();
        };
        document.addEventListener('visibilitychange', this.visibilityHandler);
    }

    ordersFingerprint(orders: Order[]): string {
        return orders
            .map((o) => `${String(o.id ?? '')}:${String(o.status ?? '')}:${DomHelper.formatReferenceNumber(o.order_number)}`)
            .join('|');
    }

    shouldSkipRender(orders: Order[]): boolean {
        const next = this.ordersFingerprint(orders);
        if (next === this.lastFingerprint && next !== '') {
            return true;
        }
        this.lastFingerprint = next;
        return false;
    }

    resetFingerprint(): void {
        this.lastFingerprint = '';
        this.knownStatuses = {};
    }

    notifyIfBecameReady(orders: Order[]): void {
        const nextKnown: Record<string, string> = {};
        orders.forEach((order) => {
            const id = String(order.id ?? '');
            if (id === '') {
                return;
            }
            const status = String(order.status ?? '');
            nextKnown[id] = status;
            const prev = this.knownStatuses[id];
            if (prev !== undefined && prev !== 'ready' && status === 'ready') {
                const ref = DomHelper.formatReferenceNumber(order.order_number);
                ToastManager.success(
                    ref !== '' ? `引換番号 ${ref} が受け取り可能になりました` : '注文が受け取り可能になりました',
                    5000
                );
            }
        });
        this.knownStatuses = nextKnown;
    }

    private trackerIndex(status: string | undefined): number {
        const s = status || 'pending';
        if (s === 'cancelled') {
            return -1;
        }
        if (s === 'pending' || s === 'confirmed') {
            return 0;
        }
        if (s === 'preparing') {
            return 1;
        }
        if (s === 'ready') {
            return 2;
        }
        if (s === 'completed') {
            return 3;
        }
        return 0;
    }

    buildStatusTrackerHtml(status: string | undefined, isActive: boolean): string {
        if (status === 'cancelled') {
            return `
                <div class="order-launch-timeline order-launch-timeline--cancelled" role="status">
                    <span class="order-launch-timeline__cancelled">キャンセル済み</span>
                </div>
            `;
        }
        const current = this.trackerIndex(status);
        const steps = ACTIVE_TRACKER_STEPS.map((step, index) => {
            const isDone = index < current;
            const isCurrent = index === current;
            const stateClass = isDone
                ? 'is-done'
                : isCurrent
                    ? 'is-current'
                    : 'is-todo';
            const nodeInner = isCurrent
                ? '<i class="fas fa-rocket order-launch-timeline__rocket" aria-hidden="true"></i>'
                : '';
            return `
                <li class="order-launch-timeline__item ${stateClass}" role="listitem">
                    <span class="order-launch-timeline__t">${DomHelper.escapeHtml(step.tMark)}</span>
                    <span class="order-launch-timeline__rail" aria-hidden="true">
                        <span class="order-launch-timeline__node">${nodeInner}</span>
                    </span>
                    <div class="order-launch-timeline__body">
                        <span class="order-launch-timeline__title">${DomHelper.escapeHtml(step.label)}</span>
                        <span class="order-launch-timeline__detail">${DomHelper.escapeHtml(step.detail)}</span>
                    </div>
                </li>
            `;
        }).join('');

        return `
            <div class="order-launch-timeline ${isActive ? 'order-launch-timeline--live' : ''}" role="list" aria-label="注文状況タイムライン">
                <div class="order-launch-timeline__header">
                    <i class="fas fa-satellite" aria-hidden="true"></i>
                    <span>MISSION TIMELINE</span>
                </div>
                <ol class="order-launch-timeline__list">
                    ${steps}
                </ol>
            </div>
        `;
    }

    buildOrderCardHtml(order: Order, options: { large?: boolean } = {}): string {
        const large = options.large === true;
        const labels = AppConstants.ORDER_STATUS_LABELS || {};
        const statusLabel = labels[order.status as keyof typeof labels] || order.status || '';
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
            .map((i) => {
                const name = (i.product && i.product.name) || i.product_name || '商品';
                return `<span class="order-item-line">${DomHelper.escapeHtml(name)} × ${i.quantity || 0}</span>`;
            })
            .join('');
        const refNumber = DomHelper.formatReferenceNumber(order.order_number);
        const typeBadge = typeLabel
            ? `<span class="order-card-type order-card-type--${DomHelper.escapeHtml(typeKey)}">${DomHelper.escapeHtml(typeLabel)}</span>`
            : '';
        const isActive = ACTIVE_ORDER_STATUSES.includes(String(order.status || ''));
        const tracker = this.buildStatusTrackerHtml(order.status, isActive);

        return `
            <article class="order-card ${large ? 'order-card--hero' : ''}" data-order-id="${DomHelper.escapeHtml(String(order.id ?? ''))}" data-order-status="${DomHelper.escapeHtml(String(order.status ?? ''))}">
                <div class="order-card-header">
                    ${refNumber ? `<p class="order-card-ref" aria-label="引換番号">${DomHelper.escapeHtml(refNumber)}</p>` : ''}
                    <div class="order-card-meta">
                        <span class="order-card-date">${DomHelper.escapeHtml(dateStr)}</span>
                        ${typeBadge}
                        <span class="order-card-status order-card-status--${DomHelper.escapeHtml(String(order.status || ''))}">${DomHelper.escapeHtml(String(statusLabel))}</span>
                    </div>
                </div>
                ${tracker}
                <div class="order-card-items">${items}</div>
                <div class="order-card-total">合計: ${DomHelper.formatPrice(order.total_amount || 0)}</div>
            </article>
        `;
    }

    buildOrderListHtml(orders: Order[], options: { large?: boolean } = {}): string {
        if (!orders || orders.length === 0) {
            return '';
        }
        return orders.map((order) => this.buildOrderCardHtml(order, options)).join('');
    }

    renderOrdersSection(container: Element | null, orders: Order[]): void {
        if (!container) {
            return;
        }
        if (!orders || orders.length === 0) {
            this.lastFingerprint = '';
            this.knownStatuses = {};
            container.innerHTML = `
                <div class="orders-empty">
                    <i class="fas fa-receipt"></i>
                    <p>注文履歴はありません</p>
                    <p class="orders-empty-sub">商品タブから注文してみましょう</p>
                </div>
            `;
            return;
        }

        this.notifyIfBecameReady(orders);

        const grouped: Record<string, Order[]> = {};
        orders.forEach((order) => {
            const s = order.status || 'pending';
            if (!grouped[s]) {
                grouped[s] = [];
            }
            grouped[s].push(order);
        });

        const activeOrders = ACTIVE_ORDER_STATUSES.flatMap((s) => grouped[s] || []);
        const pastOrders = ['completed', 'cancelled'].flatMap((s) => grouped[s] || []);

        let html = '<div class="orders-header"><h2 class="section-title"><i class="fas fa-receipt"></i> 注文履歴</h2></div>';

        if (activeOrders.length > 0) {
            html += `<div class="order-group">
                <div class="order-group-header order-group-header--active">
                    <i class="fas fa-bolt"></i>
                    <span>進行中の注文</span>
                    <span class="order-group-count">${activeOrders.length}件</span>
                </div>
                <div class="orders-list orders-list--active">${this.buildOrderListHtml(activeOrders, { large: true })}</div>
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

        this.lastFingerprint = this.ordersFingerprint(orders);
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
