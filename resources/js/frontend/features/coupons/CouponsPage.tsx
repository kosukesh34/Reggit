import React, { useCallback, useEffect, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { useFrontendApp } from '../../context/FrontendAppContext';

type Coupon = {
    id?: number | string;
    title?: string;
    description?: string;
    status?: string;
    expires_at?: string;
};

export function CouponsPage(): JSX.Element {
    const { apiClient } = useFrontendApp();
    const [coupons, setCoupons] = useState<Coupon[]>([]);
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');

    const load = useCallback(async () => {
        setStatus('loading');
        try {
            const result = await apiClient.get(AppConstants.API_ENDPOINTS.MY_COUPONS);
            const data = result.success ? result.data as { coupons?: Coupon[] } | Coupon[] : null;
            const list = Array.isArray(data) ? data : (data?.coupons ?? []);
            setCoupons(list);
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
        return <div className="coupons-error"><p>クーポンの読み込みに失敗しました</p></div>;
    }
    if (coupons.length === 0) {
        return <div className="coupons-empty"><p>クーポンはまだありません</p></div>;
    }

    return (
        <div className="coupons-list">
            {coupons.map((c) => (
                <div key={String(c.id)} className="coupon-card">
                    <h3>{c.title || 'クーポン'}</h3>
                    {c.description ? <p>{c.description}</p> : null}
                    {c.expires_at ? <p className="coupon-card__expiry">期限: {c.expires_at}</p> : null}
                </div>
            ))}
        </div>
    );
}
