import { AppConstants } from '../../../shared/constants';

export function resolveStatsField(item: { field?: string; label?: string } | null | undefined): string {
    if (item == null) {
        return 'static';
    }
    const explicit = typeof item.field === 'string' ? item.field.trim() : '';
    if (explicit === 'points' || explicit === 'rank' || explicit === 'points_to_next' || explicit === 'static') {
        return explicit;
    }
    const label = typeof item.label === 'string' ? item.label.trim() : '';
    if (label === 'ポイント' || label === '保有ポイント' || label === '所持ポイント') {
        return 'points';
    }
    if (label === 'ランク' || label === '会員ランク' || label === '現在のランク') {
        return 'rank';
    }
    if (/次回特典|次のランク|特典まで|ランクアップまで/.test(label)) {
        return 'points_to_next';
    }
    return 'static';
}

export function formatStatsValue(field: string, data: Record<string, unknown> | null): string {
    const isMember = !!(data && data.is_member === true);
    if (!isMember) {
        return '—';
    }
    if (field === 'points') {
        if (data.show_points_display === false) {
            return '—';
        }
        const member = data.member as { points?: number } | undefined;
        const points = Number(data.points != null ? data.points : (member && member.points) || 0);
        return points.toLocaleString('ja-JP');
    }
    if (field === 'rank') {
        const rankLabel = data.current_rank_label != null ? String(data.current_rank_label) : '';
        return rankLabel !== '' ? rankLabel : '—';
    }
    if (field === 'points_to_next') {
        if (data.show_rank_progress === false) {
            return '—';
        }
        if (data.points_to_next_rank == null) {
            return 'MAX';
        }
        return Number(data.points_to_next_rank).toLocaleString('ja-JP');
    }
    return '—';
}

export function formatBannerPointsToNext(data: Record<string, unknown> | null): string {
    const isMember = !!(data && data.is_member === true);
    if (!isMember) {
        return '会員登録でランク特典が使えます';
    }
    if (data.show_rank_progress === false) {
        return '会員ランク特典をチェック';
    }
    const nextRankLabel = data.next_rank_label != null ? String(data.next_rank_label) : '';
    if (data.points_to_next_rank == null || nextRankLabel === '') {
        return '最高ランクに到達しています';
    }
    const toNext = Number(data.points_to_next_rank).toLocaleString('ja-JP');
    const unit = data.rank_basis === 'stamps' ? '個' : 'pt';
    return `次の${nextRankLabel}まであと ${toNext} ${unit}`;
}

let memberMeCache: Promise<Record<string, unknown> | null> | null = null;

export function resetMemberProfileCache(): void {
    memberMeCache = null;
}

export function fetchMemberProfile(): Promise<Record<string, unknown> | null> {
    if (memberMeCache !== null) {
        return memberMeCache;
    }
    memberMeCache = (async () => {
        try {
            const params = new URLSearchParams(window.location.search || '');
            if (params.get('designer_live_preview') === '1') {
                const payload = window.__DESIGNER_PREVIEW_MEMBER_PAYLOAD__;
                if (payload !== undefined && payload !== null && typeof payload === 'object') {
                    return payload as Record<string, unknown>;
                }
                const app = window.app as { getDesignerSampleMemberData?: () => Record<string, unknown> } | undefined;
                if (app !== undefined && typeof app.getDesignerSampleMemberData === 'function') {
                    return app.getDesignerSampleMemberData();
                }
            }
            const app = window.app as {
                apiClient?: { get: (url: string) => Promise<{ success?: boolean; data?: Record<string, unknown> }> };
            } | undefined;
            if (app !== undefined && app.apiClient !== undefined) {
                const result = await app.apiClient.get(AppConstants.API_ENDPOINTS.MEMBER_PROFILE);
                if (result !== undefined && result.success === true && result.data !== undefined) {
                    return result.data;
                }
            }
        } catch (_e) {
            return null;
        }
        return null;
    })();
    return memberMeCache;
}
