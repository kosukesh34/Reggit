<table class="table">
    <thead>
        <tr>
            <th>来店日時</th>
            <th>経路</th>
            <th>購入額</th>
            <th>購入商品</th>
            <th>注文番号</th>
            <th>メモ</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $visit)
        @php
            $visitDetailUrl = admin_route('admin.members.visits.detail', ['id' => $member->id, 'visitId' => $visit->id]);
            $orderDetailUrl = $visit->order !== null
                ? admin_route('admin.orders.detail', ['id' => $visit->order->id])
                : null;
            $rowHref = $orderDetailUrl ?? $visitDetailUrl;
            $productSummary = $visit->items->take(3)->pluck('product_name')->filter()->implode('、');
            if ($visit->items->count() > 3) {
                $productSummary .= ' 他'.($visit->items->count() - 3).'点';
            }
        @endphp
        <tr class="clickable-row" onclick="window.location='{{ $rowHref }}'" style="cursor:pointer;">
            <td>{{ $visit->visited_at->format('Y/m/d H:i') }}</td>
            <td>{{ \App\Enums\MemberVisitSource::getLabel($visit->source) }}</td>
            <td>
                @if($visit->amount !== null)
                    ¥{{ number_format($visit->amount) }}
                @else
                    —
                @endif
            </td>
            <td>{{ $productSummary !== '' ? $productSummary : '—' }}</td>
            <td>
                @if($visit->order !== null)
                    {{ $visit->order->formattedOrderNumber() }}
                @elseif($visit->smaregi_transaction_head_id !== null)
                    #{{ $visit->smaregi_transaction_head_id }}
                @else
                    —
                @endif
            </td>
            <td>{{ $visit->note ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="empty-state">
                <i class="fas fa-store"></i>
                <p>来店履歴がありません</p>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@if($items->hasPages())
<div class="member-history-pagination">{{ $items->appends($paginationQuery ?? [])->links('vendor.pagination.admin') }}</div>
@endif
