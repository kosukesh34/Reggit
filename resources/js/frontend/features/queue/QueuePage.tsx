import React, { useCallback, useEffect, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { useFrontendApp } from '../../context/FrontendAppContext';

type QueueEntry = {
    id?: number | string;
    number?: number | string;
    status?: string;
    waiting_count?: number;
};

export function QueuePage(): JSX.Element {
    const { apiClient } = useFrontendApp();
    const [entry, setEntry] = useState<QueueEntry | null>(null);
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');

    const load = useCallback(async () => {
        setStatus('loading');
        try {
            const result = await apiClient.get(AppConstants.API_ENDPOINTS.QUEUE_ME);
            const data = result.success ? result.data as QueueEntry | { entry?: QueueEntry } : null;
            if (data !== null && typeof data === 'object' && 'entry' in data) {
                setEntry(data.entry ?? null);
            } else {
                setEntry((data as QueueEntry | null) ?? null);
            }
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
        return <div className="queue-error"><p>順番待ちの読み込みに失敗しました</p></div>;
    }
    if (entry === null) {
        return (
            <div className="queue-empty">
                <p>現在順番待ちはありません</p>
                <button type="button" className="btn btn-primary" onClick={() => void load()}>更新</button>
            </div>
        );
    }

    return (
        <div className="queue-status">
            <p className="queue-status__number">整理番号 {entry.number}</p>
            <p className="queue-status__state">{entry.status || ''}</p>
            {entry.waiting_count !== undefined ? <p>待ち組数: {entry.waiting_count}</p> : null}
            <button type="button" className="btn" onClick={() => void load()}>更新</button>
        </div>
    );
}
