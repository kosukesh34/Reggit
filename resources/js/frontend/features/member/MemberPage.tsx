import React, { useCallback, useEffect, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { useFrontendApp } from '../../context/FrontendAppContext';

type MemberPayload = {
    name?: string;
    member_code?: string;
    points?: number;
    rank_label?: string;
    is_member?: boolean;
};

export function MemberPage(): JSX.Element {
    const { apiClient } = useFrontendApp();
    const [member, setMember] = useState<MemberPayload | null>(null);
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');

    const load = useCallback(async () => {
        setStatus('loading');
        try {
            const result = await apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_ME);
            const data = result.success ? result.data as MemberPayload : null;
            setMember(data);
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
        return <div className="member-error"><p>会員情報の読み込みに失敗しました</p></div>;
    }
    if (member === null || member.is_member === false) {
        return (
            <div className="member-guest">
                <h2>会員証</h2>
                <p>会員登録するとポイントやクーポンが使えます</p>
            </div>
        );
    }

    return (
        <div className="member-card-panel">
            <h2 className="member-card-panel__title">会員証</h2>
            <p className="member-card-panel__name">{member.name || ''}</p>
            <p className="member-card-panel__code">{member.member_code || ''}</p>
            <p className="member-card-panel__points">ポイント: {member.points ?? 0}</p>
            {member.rank_label ? <p className="member-card-panel__rank">{member.rank_label}</p> : null}
        </div>
    );
}
