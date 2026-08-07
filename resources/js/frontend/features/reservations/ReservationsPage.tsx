import React, { useCallback, useEffect, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { useFrontendApp } from '../../context/FrontendAppContext';

type Reservation = {
    id?: number | string;
    reservation_date?: string;
    reservation_time?: string;
    status?: string;
    party_size?: number;
};

export function ReservationsPage(): JSX.Element {
    const { apiClient } = useFrontendApp();
    const [items, setItems] = useState<Reservation[]>([]);
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');

    const load = useCallback(async () => {
        setStatus('loading');
        try {
            const result = await apiClient.get(AppConstants.API_ENDPOINTS.RESERVATIONS);
            const data = result.success ? result.data as { reservations?: Reservation[] } | Reservation[] : null;
            const list = Array.isArray(data) ? data : (data?.reservations ?? []);
            setItems(list);
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
        return <div className="reservations-error"><p>予約の読み込みに失敗しました</p></div>;
    }
    if (items.length === 0) {
        return <div className="reservations-empty"><p>予約はまだありません</p></div>;
    }

    return (
        <div className="reservations-list">
            {items.map((r) => (
                <div key={String(r.id)} className="reservation-card">
                    <p>{r.reservation_date} {r.reservation_time}</p>
                    <p>{r.status || ''} / {r.party_size ?? '-'}名</p>
                </div>
            ))}
        </div>
    );
}
