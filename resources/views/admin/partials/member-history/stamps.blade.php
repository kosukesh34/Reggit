<table class="table">
    <thead>
        <tr>
            <th>日時</th>
            <th>スタンプカード</th>
            <th>増減</th>
            <th>付与後残高</th>
            <th>説明</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $tx)
        <tr>
            <td>{{ $tx->created_at->format('Y/m/d H:i') }}</td>
            <td>{{ $tx->stampCard->name ?? '—' }}</td>
            <td>{{ $tx->delta > 0 ? '+' : '' }}{{ $tx->delta }}</td>
            <td>{{ $tx->balance_after }}</td>
            <td>{{ $tx->description ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="empty-state">
                <i class="fas fa-stamp"></i>
                <p>スタンプ付与履歴がありません</p>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@if($items->hasPages())
<div class="member-history-pagination">{{ $items->appends($paginationQuery ?? [])->links('vendor.pagination.admin') }}</div>
@endif
