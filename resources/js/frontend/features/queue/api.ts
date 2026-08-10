import { ApiClient } from '../../../shared/utils/apiClient';
import { AppConstants } from '../../../shared/constants';
import { DomHelper } from '../../../shared/utils/domHelper';
import { buildBuiltinSampleQueueEntry } from '../../demo/sampleData';

export type QueueStats = {
    waiting_count?: number | null;
    waitingCount?: number | null;
    estimated_wait_minutes?: number | null;
    estimatedWaitMinutes?: number | null;
    updated_at?: string | null;
    queue_entry?: QueueEntry | null;
};

export type QueueEntry = {
    id?: number | string;
    queue_number?: number | string;
    queueNumber?: number | string;
    formatted_queue_number?: string;
    formattedQueueNumber?: string;
    status?: string;
    line_linked?: boolean;
    lineLinked?: boolean;
    party_size?: number | string;
    partySize?: number | string;
    waiting_count?: number | string;
    waitingCount?: number | string;
    ahead_count?: number | string;
    aheadCount?: number | string;
    estimated_wait_minutes?: number | string;
    estimatedWaitMinutes?: number | string;
    is_sample_preview?: boolean;
};

const PENDING_QUEUE_TOKEN_KEY = 'mobile_order_pending_queue_token';

function client(): ApiClient {
    const app = window.app as { apiClient?: ApiClient } | undefined;
    return app?.apiClient ?? new ApiClient();
}

export function isQueueMembershipRequired(): boolean {
    return typeof window !== 'undefined' && window.__QUEUE_REQUIRE_MEMBERSHIP__ === true;
}

export function isCurrentUserMember(): boolean {
    const manager = (window.app as {
        memberManager?: {
            isMember?: () => boolean;
            getMemberData?: () => { is_member?: boolean } | null;
        };
    } | undefined)?.memberManager;
    if (manager === undefined) {
        return false;
    }
    if (typeof manager.isMember === 'function') {
        return manager.isMember() === true;
    }
    const data = typeof manager.getMemberData === 'function' ? manager.getMemberData() : null;
    return !!(data && data.is_member === true);
}

export async function ensureMemberContextForGate(): Promise<void> {
    const manager = (window.app as {
        memberManager?: {
            ensureIdentityReady?: (maxWaitMs?: number) => Promise<unknown>;
            resolveLineUserIdForClient?: () => Promise<unknown>;
            fetchMemberContext?: (options?: { force?: boolean }) => Promise<unknown>;
            isMember?: () => boolean;
        };
    } | undefined)?.memberManager;
    if (manager === undefined) {
        return;
    }
    if (typeof manager.ensureIdentityReady === 'function') {
        await manager.ensureIdentityReady(1500);
    } else if (typeof manager.resolveLineUserIdForClient === 'function' && !window.__LINE_USER_ID__) {
        await manager.resolveLineUserIdForClient();
    }
    if (typeof manager.isMember === 'function' && manager.isMember() === true) {
        return;
    }
    if (typeof manager.fetchMemberContext === 'function') {
        await manager.fetchMemberContext({ force: true });
    }
}

export function getSampleQueueEntry(): QueueEntry {
    const app = window.app as { getDesignerSampleQueueEntry?: () => QueueEntry } | undefined;
    if (typeof app?.getDesignerSampleQueueEntry === 'function') {
        return { ...app.getDesignerSampleQueueEntry(), is_sample_preview: true };
    }
    return { ...buildBuiltinSampleQueueEntry(), is_sample_preview: true };
}

export async function fetchQueueMe(): Promise<{ success: true; data: QueueStats } | { success: false; error: string }> {
    const result = await client().get(AppConstants.API_ENDPOINTS.QUEUE_ME);
    if (result.success !== true) {
        return { success: false, error: result.error || '順番待ちの情報を取得できませんでした。' };
    }
    return { success: true, data: (result.data || {}) as QueueStats };
}

export async function fetchQueueByToken(token: string): Promise<{ success: true; data: QueueStats } | { success: false; error: string }> {
    const endpointBase = `${AppConstants.API_ENDPOINTS.QUEUE_QR}/${encodeURIComponent(token)}`;
    const lineUserId = typeof window !== 'undefined' && window.__LINE_USER_ID__
        ? encodeURIComponent(window.__LINE_USER_ID__)
        : '';
    const endpoint = lineUserId ? `${endpointBase}?line_user_id=${lineUserId}` : endpointBase;
    const result = await client().get(endpoint);
    if (result.success !== true) {
        return { success: false, error: result.error || '順番待ちの情報を取得できませんでした。' };
    }
    return { success: true, data: result.data as QueueStats };
}

export async function createQueue(partySize: number): Promise<{ success: true; entry: QueueEntry } | { success: false; error: string }> {
    const result = await client().post(AppConstants.API_ENDPOINTS.QUEUE, { party_size: partySize });
    if (result.success !== true) {
        return { success: false, error: result.error || '受付に失敗しました。' };
    }
    const entry = (result.data as QueueStats)?.queue_entry;
    if (entry === undefined || entry === null) {
        return { success: false, error: '順番待ちの情報を取得できませんでした。' };
    }
    return { success: true, entry };
}

export async function cancelQueue(id: number | string): Promise<{ success: true } | { success: false; error: string }> {
    const result = await client().post(`${AppConstants.API_ENDPOINTS.QUEUE}/${id}/cancel`, {});
    if (result.success !== true) {
        return { success: false, error: result.error || 'キャンセルに失敗しました。' };
    }
    return { success: true };
}

export function formatQueueNumber(value: number | string): string {
    return DomHelper.formatReferenceNumber(value) || String(value);
}

export function formatUpdatedAt(value: string): string {
    try {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        return date.toLocaleString('ja-JP', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return value;
    }
}

export function getStatusInfo(status: string): { label: string; className: string } {
    const map: Record<string, { label: string; className: string }> = {
        waiting: { label: '待機中', className: 'queue-badge-waiting' },
        called: { label: '呼び出し済', className: 'queue-badge-called' },
        entered: { label: '入店済', className: 'queue-badge-entered' },
        cancelled: { label: 'キャンセル', className: 'queue-badge-cancelled' },
    };
    return map[status] || { label: status || '-', className: 'queue-badge-default' };
}

export function getQueueTokenFromUrl(): string | null {
    if (typeof window === 'undefined') {
        return null;
    }
    const params = new URLSearchParams(window.location.search || '');
    const direct = params.get('queue_token') || params.get('queue_id');
    if (direct) {
        persistQueueToken(direct);
        return direct;
    }
    try {
        return window.sessionStorage.getItem(PENDING_QUEUE_TOKEN_KEY);
    } catch {
        return null;
    }
}

export function persistQueueToken(token: string): void {
    try {
        window.sessionStorage.setItem(PENDING_QUEUE_TOKEN_KEY, token);
    } catch {
        /* ignore */
    }
}

export function clearQueueTokenFromUrl(): void {
    if (typeof window === 'undefined' || !window.history) {
        return;
    }
    const url = new URL(window.location.href);
    url.searchParams.delete('queue_token');
    url.searchParams.delete('queue_id');
    url.searchParams.delete('state');
    url.searchParams.delete('liff.state');
    url.searchParams.delete('liffRedirectUri');
    window.history.replaceState({}, '', url.toString());
    try {
        window.sessionStorage.removeItem(PENDING_QUEUE_TOKEN_KEY);
    } catch {
        /* ignore */
    }
}

export function switchToMemberTab(): void {
    if (typeof window.app?.switchTab === 'function') {
        window.app.switchTab(AppConstants.TABS.MEMBER);
    }
}
