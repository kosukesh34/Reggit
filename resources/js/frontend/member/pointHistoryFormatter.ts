import type { HtmlEscaper } from './memberStateTemplates';

type PointTransaction = {
    points?: unknown;
    created_at?: unknown;
    description?: unknown;
};

function normalizePoints(value: unknown): number {
    const points = Number(value ?? 0);
    return Number.isFinite(points) ? points : 0;
}

function formatTransactionDate(value: unknown): string {
    if (typeof value !== 'string' || value.trim() === '') return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export function formatPointHistoryHtml(
    transactions: unknown,
    escapeHtml: HtmlEscaper,
): string {
    if (!Array.isArray(transactions) || transactions.length === 0) {
        return '<div class="member-purchase-history-empty">ポイント履歴がありません</div>';
    }

    return transactions.map((rawTransaction) => {
        const transaction = rawTransaction && typeof rawTransaction === 'object'
            ? rawTransaction as PointTransaction
            : {};
        const points = normalizePoints(transaction.points);
        const earned = points >= 0;
        const description = typeof transaction.description === 'string'
            ? transaction.description
            : '';
        const date = formatTransactionDate(transaction.created_at);

        return `
            <div class="member-point-history-item ${earned ? 'member-point-history-earned' : 'member-point-history-used'}">
                <div class="member-point-history-row">
                    <span class="member-point-history-label">${earned ? '獲得' : '使用'}</span>
                    <span class="member-point-history-points">${points > 0 ? '+' : ''}${points.toLocaleString('ja-JP')}pt</span>
                </div>
                <div class="member-point-history-meta">
                    <span>${escapeHtml(date)}</span>
                    ${description ? `<span>${escapeHtml(description)}</span>` : ''}
                </div>
            </div>
        `;
    }).join('');
}
