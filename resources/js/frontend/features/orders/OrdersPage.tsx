import React, { useCallback, useEffect, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { useFrontendApp } from '../../context/FrontendAppContext';

type Order = {
    id?: number | string;
    status?: string;
    total?: number;
    created_at?: string;
    reference_number?: string;
};

export function OrdersPage(): JSX.Element {
    const { apiClient } = useFrontendApp();
    const [orders, setOrders] = useState<Order[]>([]);
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');

    const load = useCallback(async () => {
        setStatus('loading');
        try {
            const result = await apiClient.get(AppConstants.API_ENDPOINTS.ORDERS);
            const data = result.success ? result.data as { orders?: Order[] } | Order[] : null;
            const list = Array.isArray(data) ? data : (data?.orders ?? []);
            setOrders(list);
            setStatus('ready');
        } catch {
            setStatus('error');
        }
    }, [apiClient]);

    useEffect(() => {
        void load();
    }, [load]);

    if (status === 'loading') {
        return <div className="loading"><i className="fas fa-spinner fa-spin" /><p>読み込み中...</p></div>;
    }
    if (status === 'error') {
        return <div className="orders-error"><p>注文履歴の読み込みに失敗しました</p></div>;
    }
    if (orders.length === 0) {
        return <div className="orders-empty"><p>注文履歴はまだありません</p></div>;
    }

    return (
        <div className="orders-list">
            {orders.map((o) => (
                <div key={String(o.id)} className="order-card">
                    <div className="order-card__meta">
                        <span>{o.reference_number || o.id}</span>
                        <span>{o.status || ''}</span>
                    </div>
                    <div className="order-card__total">¥{Number(o.total ?? 0).toLocaleString()}</div>
                    {o.created_at ? <div className="order-card__date">{new Date(o.created_at).toLocaleString('ja-JP')}</div> : null}
                </div>
            ))}
        </div>
    );
}
