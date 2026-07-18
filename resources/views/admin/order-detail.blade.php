@extends('admin.layout')

@section('title', '注文詳細')
@section('page-title', '注文詳細: ' . $order->formattedOrderNumber())

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle"></i> 注文情報</h3>
    </div>
    <div class="order-detail-padding">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">注文番号</div>
                <div class="info-value">{{ $order->formattedOrderNumber() }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">ユーザー</div>
                <div class="info-value">
                    @if($order->user?->member !== null)
                        <a href="{{ admin_route('admin.members.detail', ['id' => $order->user->member->id]) }}">{{ $order->user->name ?? 'N/A' }}</a>
                    @else
                        {{ $order->user->name ?? 'N/A' }}
                    @endif
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">合計金額</div>
                <div class="info-value">¥{{ number_format($order->total_amount) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">注文種別</div>
                <div class="info-value">
                    @php
                        $orderType = $order->order_type ?? '';
                        $orderTypeLabel = \App\Enums\OrderType::getLabel($orderType) ?? $orderType;
                    @endphp
                    {{ $orderTypeLabel !== '' ? $orderTypeLabel : '—' }}
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">支払い方法</div>
                <div class="info-value">{{ $order->payment_method ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">ステータス</div>
                <div class="info-value">
                    @php
                        $statusLabels = [
                            'pending' => '未処理',
                            'confirmed' => '確認済み',
                            'preparing' => '準備中',
                            'ready' => '準備完了',
                            'completed' => '完了',
                            'cancelled' => 'キャンセル',
                        ];
                        $statusColors = [
                            'pending' => 'warning',
                            'confirmed' => 'info',
                            'preparing' => 'info',
                            'ready' => 'info',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                        ];
                    @endphp
                    <span class="badge badge-{{ $statusColors[$order->status] ?? 'info' }}">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">注文日時</div>
                <div class="info-value">{{ $order->created_at->format('Y/m/d H:i:s') }}</div>
            </div>
        </div>

        <div class="order-status-actions">
            @if($order->status !== 'completed' && $order->status !== 'cancelled')
            <form action="{{ admin_route('admin.orders.complete', ['id' => $order->id]) }}" method="POST" class="complete-form">
                @csrf
                <button type="submit" class="btn btn-success btn-large" onclick="return confirm('この注文を完了にしますか？')">
                    <i class="fas fa-check-double"></i> 完了にする
                </button>
            </form>
            @endif
            
            <div class="quick-actions">
                <h4><i class="fas fa-bolt"></i> クイックアクション</h4>
                <div class="quick-action-buttons">
                    @if($order->status === 'pending')
                    <form action="{{ admin_route('admin.orders.status', ['id' => $order->id]) }}" method="POST" class="form-inline">
                        @csrf
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-check-circle"></i> 確認済み
                        </button>
                    </form>
                    @endif
                    
                    @if($order->status === 'confirmed')
                    <form action="{{ admin_route('admin.orders.status', ['id' => $order->id]) }}" method="POST" class="form-inline">
                        @csrf
                        <input type="hidden" name="status" value="preparing">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-utensils"></i> 準備中
                        </button>
                    </form>
                    @endif
                    
                    @if($order->status === 'preparing')
                    <form action="{{ admin_route('admin.orders.status', ['id' => $order->id]) }}" method="POST" class="form-inline">
                        @csrf
                        <input type="hidden" name="status" value="ready">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-check"></i> 準備完了
                        </button>
                    </form>
                    @endif
                    
                    @if($order->status === 'ready')
                    <form action="{{ admin_route('admin.orders.complete', ['id' => $order->id]) }}" method="POST" class="form-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('この注文を完了にしますか？')">
                            <i class="fas fa-check-double"></i> 完了
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            
            <div class="status-selector">
                <h4><i class="fas fa-cog"></i> ステータス変更</h4>
                <form action="{{ admin_route('admin.orders.status', ['id' => $order->id]) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <select name="status" class="form-select">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>未処理 (pending)</option>
                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>確認済み (confirmed)</option>
                            <option value="preparing" {{ $order->status === 'preparing' ? 'selected' : '' }}>準備中 (preparing)</option>
                            <option value="ready" {{ $order->status === 'ready' ? 'selected' : '' }}>準備完了 (ready)</option>
                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>完了 (completed)</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>キャンセル (cancelled)</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-mt-10">
                            <i class="fas fa-save"></i> 更新
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 注文明細</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>商品名</th>
                <th>数量</th>
                <th>単価</th>
                <th>小計</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->name ?? 'N/A' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>¥{{ number_format($item->price) }}</td>
                <td>¥{{ number_format($item->price * $item->quantity) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="table-right-bold">合計:</td>
                <td class="table-bold">¥{{ number_format($order->total_amount) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="form-actions">
    <a href="{{ admin_route('admin.orders') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> 戻る
    </a>
</div>
@endsection
