export interface RankUpHistoryItem {
    achieved_at?: unknown;
    from_rank_label?: unknown;
    to_rank_label?: unknown;
}

const EMPTY_HISTORY_HTML = '<div class="member-rank-history-empty">まだランクアップ履歴がありません</div>';

export function formatRankUpHistoryHtml(history: unknown): string {
    if (!Array.isArray(history) || history.length === 0) {
        return EMPTY_HISTORY_HTML;
    }

    return history.map((item: RankUpHistoryItem) => {
        const date = displayValue(item?.achieved_at, '');
        const fromLabel = displayValue(item?.from_rank_label, '—');
        const toLabel = displayValue(item?.to_rank_label, '—');

        return '<div class="member-rank-history-item">'
            + `<span class="member-rank-history-from">${escapeHtml(fromLabel)}</span>`
            + ' <i class="fas fa-arrow-right member-rank-history-arrow" aria-hidden="true"></i> '
            + `<span class="member-rank-history-to">${escapeHtml(toLabel)}</span>`
            + `<span class="member-rank-history-date">${escapeHtml(date)}</span>`
            + '</div>';
    }).join('');
}

function displayValue(value: unknown, fallback: string): string {
    return value === null || value === undefined || value === '' ? fallback : String(value);
}

function escapeHtml(value: string): string {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
