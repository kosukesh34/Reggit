import React, { useCallback, useEffect, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { useFrontendApp } from '../../context/FrontendAppContext';

type StampCard = {
    id?: number | string;
    name?: string;
    current_stamps?: number;
    required_stamps?: number;
    image_url?: string;
};

export function StampsPage(): JSX.Element {
    const { apiClient } = useFrontendApp();
    const [cards, setCards] = useState<StampCard[]>([]);
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');

    const load = useCallback(async () => {
        setStatus('loading');
        try {
            const result = await apiClient.get(AppConstants.API_ENDPOINTS.STAMPS);
            const data = result.success ? result.data as { stamp_cards?: StampCard[] } | StampCard[] : null;
            const list = Array.isArray(data) ? data : (data?.stamp_cards ?? []);
            setCards(list);
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
        return <div className="stamps-error"><p>スタンプの読み込みに失敗しました</p></div>;
    }
    if (cards.length === 0) {
        return <div className="stamps-empty"><p>スタンプカードはまだありません</p></div>;
    }

    return (
        <div className="stamps-list">
            {cards.map((c) => (
                <div key={String(c.id)} className="stamp-card">
                    <h3>{c.name || 'スタンプカード'}</h3>
                    <p>{c.current_stamps ?? 0} / {c.required_stamps ?? 0}</p>
                </div>
            ))}
        </div>
    );
}
