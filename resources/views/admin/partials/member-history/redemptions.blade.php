<table class="table">
    <thead>
        <tr>
            <th>クーポン名</th>
            <th>利用日時</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $redemption)
        <tr>
            <td>{{ $redemption->coupon->name ?? $redemption->coupon->code ?? '—' }}</td>
            <td>{{ $redemption->used_at ? $redemption->used_at->format('Y/m/d H:i') : '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="2" class="empty-state">
                <i class="fas fa-receipt"></i>
                <p>クーポン使用履歴がありません</p>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@if($items->hasPages())
<div class="member-history-pagination">{{ $items->appends($paginationQuery ?? [])->links('vendor.pagination.admin') }}</div>
@endif
