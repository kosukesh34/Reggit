import { ApiClient } from '../../../shared/utils/apiClient';
import { AppConstants } from '../../../shared/constants';

export type OrderItem = {
    quantity?: number;
    product_name?: string | null;
    product?: { name?: string } | null;
};

export type Order = {
    id?: number | string;
    order_number?: string;
    status?: string;
    order_type?: string;
    created_at?: string;
    total_amount?: number | string;
    items?: OrderItem[];
};

export const ACTIVE_ORDER_STATUSES = ['pending', 'confirmed', 'preparing', 'ready'];

export const ACTIVE_TRACKER_STEPS = [
    { key: 'confirmed', tMark: 'T+0', label: '受付', detail: '注文を受け付けました' },
    { key: 'preparing', tMark: 'T+1', label: '準備中', detail: 'キッチンで準備しています' },
    { key: 'ready', tMark: 'T+2', label: '受取可', detail: '受け取りの準備が整いました' },
    { key: 'completed', tMark: 'T+3', label: '完了', detail: 'ミッション完了' },
] as const;

function client(): ApiClient {
    const app = window.app as { apiClient?: ApiClient } | undefined;
    return app?.apiClient ?? new ApiClient();
}

export async function fetchOrders(): Promise<Order[]> {
    const result = await client().get(AppConstants.API_ENDPOINTS.ORDERS);
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

type MemberManagerLike = {
    resolveLineUserIdForClient?: () => Promise<unknown>;
    hasAuthenticatedIdentity?: () => boolean;
};

export async function canViewOrders(): Promise<boolean> {
    const mm = (window.app as { memberManager?: MemberManagerLike } | undefined)?.memberManager;
    if (typeof window !== 'undefined' && !window.__LINE_USER_ID__ && typeof mm?.resolveLineUserIdForClient === 'function') {
        await mm.resolveLineUserIdForClient();
    }
    const hasIdentity = typeof mm?.hasAuthenticatedIdentity === 'function'
        ? mm.hasAuthenticatedIdentity()
        : !!(typeof window !== 'undefined' && window.__LINE_USER_ID__);
    const mobileApp = typeof window !== 'undefined' && window.__MOBILE_APP_MODE__ === true;
    return hasIdentity || mobileApp;
}

export function trackerIndex(status: string | undefined): number {
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
