<table class="table">
    <thead>
        <tr>
            <th>クーポン名</th>
            <th>状態</th>
            <th>発行日</th>
            <th>有効期限</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $mc)
        <tr>
            <td>{{ $mc->coupon_name_snapshot ?? $mc->coupon->name ?? '—' }}</td>
            <td>
                <span class="badge badge-{{ $mc->status === 'unused' ? 'success' : 'secondary' }}">
                    {{ $mc->status === 'unused' ? '未使用' : '使用済み' }}
                </span>
            </td>
            <td>{{ $mc->issued_at ? $mc->issued_at->format('Y/m/d H:i') : '—' }}</td>
            <td>{{ $mc->valid_until ? $mc->valid_until->format('Y/m/d H:i') : '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="empty-state">
                <i class="fas fa-ticket-alt"></i>
                <p>クーポン発行履歴がありません</p>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@if($items->hasPages())
<div class="member-history-pagination">{{ $items->appends($paginationQuery ?? [])->links('vendor.pagination.admin') }}</div>
@endif
