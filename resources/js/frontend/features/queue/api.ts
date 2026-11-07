import { DomHelper } from '../../../shared/utils/domHelper';

export { clearQueueTokenFromUrl, getQueueTokenFromUrl } from '../../../shared/utils/queueToken';

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

export function formatQueueNumber(value: number | string): string {
    return DomHelper.formatReferenceNumber(value) || String(value);
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
