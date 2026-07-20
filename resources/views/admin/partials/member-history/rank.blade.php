<table class="table">
    <thead>
        <tr>
            <th>達成日時</th>
            <th>変更前</th>
            <th>変更後</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $history)
        <tr>
            <td>{{ $history->achieved_at ? $history->achieved_at->format('Y/m/d H:i') : '—' }}</td>
            <td>{{ $history->from_rank_label ?? $history->from_rank ?? '—' }}</td>
            <td>{{ $history->to_rank_label ?? $history->to_rank ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="3" class="empty-state">
                <i class="fas fa-trophy"></i>
                <p>ランクアップ履歴がありません</p>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@if($items->hasPages())
<div class="member-history-pagination">{{ $items->appends($paginationQuery ?? [])->links('vendor.pagination.admin') }}</div>
@endif
