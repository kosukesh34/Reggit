export interface WalletSummary {
    points: number;
    stamps_total: number;
    pending_lottery_count: number;
}

export function normalizeMemberPayload(resultData: unknown): Record<string, unknown> | null {
    if (resultData === null || typeof resultData !== 'object') return null;

    const result = resultData as Record<string, unknown>;
    const candidate = result.data !== null && typeof result.data === 'object'
        ? result.data as Record<string, unknown>
        : result;
    const member = candidate.member;

    if (member !== null && typeof member === 'object' && 'is_member' in member) {
        return member as Record<string, unknown>;
    }
    if ('is_member' in candidate) {
        return candidate;
    }
    return null;
}

export function normalizeWalletSummary(source: Record<string, unknown>): WalletSummary {
    return {
        points: toFiniteNumber(source.points),
        stamps_total: toFiniteNumber(source.stamps_total),
        pending_lottery_count: toFiniteNumber(source.pending_lottery_count),
    };
}

export function walletSummariesEqual(left: WalletSummary | null, right: WalletSummary): boolean {
    return left !== null
        && left.points === right.points
        && left.stamps_total === right.stamps_total
        && left.pending_lottery_count === right.pending_lottery_count;
}

function toFiniteNumber(value: unknown): number {
    const numeric = Number(value ?? 0);
    return Number.isFinite(numeric) ? numeric : 0;
}
