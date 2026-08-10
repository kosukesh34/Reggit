<table class="table">
    <thead>
        <tr>
            <th>日時</th>
            <th>種類</th>
            <th>ポイント</th>
            <th>説明</th>
            <th>注文番号</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $transaction)
        <tr>
            <td>{{ $transaction->created_at->format('Y/m/d H:i') }}</td>
            <td>
                @php
                    $typeLabel = \App\Enums\PointTransactionType::getLabel($transaction->type);
                    $typeBadge = $transaction->type === \App\Enums\PointTransactionType::EARNED || $transaction->type === \App\Enums\PointTransactionType::REFUNDED
                        ? 'success'
                        : 'danger';
                @endphp
                <span class="badge badge-{{ $typeBadge }}">{{ $typeLabel }}</span>
            </td>
            <td>{{ $transaction->points > 0 ? '+' : '' }}{{ number_format($transaction->points) }}pt</td>
            <td>{{ $transaction->description ?? '—' }}</td>
            <td>
                @if($transaction->order !== null)
                    @php
                        $orderLabel = $transaction->order->formattedOrderNumber();
                        if ($orderLabel === '') {
                            $orderLabel = '#'.$transaction->order->id;
                        }
                    @endphp
                    <a href="{{ admin_route('admin.orders.detail', ['id' => $transaction->order->id]) }}">{{ $orderLabel }}</a>
                @else
                    —
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="empty-state">
                <i class="fas fa-coins"></i>
                <p>ポイント履歴がありません</p>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@if($items->hasPages())
<div class="member-history-pagination">{{ $items->appends($paginationQuery ?? [])->links('vendor.pagination.admin') }}</div>
@endif
