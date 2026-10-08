@extends('admin.layout')

@section('title', '注文管理')
@section('page-title', '注文管理')

@section('content')
@php
    $rawOrderUserId = request()->query('user_id');
    $orderUserFilter = is_numeric($rawOrderUserId) ? ['user_id' => (int) $rawOrderUserId] : [];
@endphp
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-shopping-cart"></i> 注文管理</h1>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> ステータスフィルター</h3>
    </div>
    <div class="status-filters">
        <a href="{{ admin_route('admin.orders', $orderUserFilter + ['status' => 'all']) }}" 
           class="status-filter {{ !request('status') || request('status') === 'all' ? 'active' : '' }}">
            <i class="fas fa-check-circle"></i> 決済済み
            <span class="badge">{{ $statusCounts['all'] }}</span>
        </a>
        <a href="{{ admin_route('admin.orders', $orderUserFilter + ['status' => 'pending']) }}" 
           class="status-filter {{ request('status') === 'pending' ? 'active' : '' }}">
            <i class="fas fa-clock"></i> 未処理
            <span class="badge badge-warning">{{ $statusCounts['pending'] }}</span>
        </a>
        <a href="{{ admin_route('admin.orders', $orderUserFilter + ['status' => 'confirmed']) }}" 
           class="status-filter {{ request('status') === 'confirmed' ? 'active' : '' }}">
            <i class="fas fa-check-circle"></i> 確認済み
            <span class="badge badge-info">{{ $statusCounts['confirmed'] }}</span>
        </a>
        <a href="{{ admin_route('admin.orders', $orderUserFilter + ['status' => 'preparing']) }}" 
           class="status-filter {{ request('status') === 'preparing' ? 'active' : '' }}">
            <i class="fas fa-utensils"></i> 準備中
            <span class="badge badge-info">{{ $statusCounts['preparing'] }}</span>
        </a>
        <a href="{{ admin_route('admin.orders', $orderUserFilter + ['status' => 'ready']) }}" 
           class="status-filter {{ request('status') === 'ready' ? 'active' : '' }}">
            <i class="fas fa-check"></i> 準備完了
            <span class="badge badge-info">{{ $statusCounts['ready'] }}</span>
        </a>
        <a href="{{ admin_route('admin.orders', $orderUserFilter + ['status' => 'completed']) }}" 
           class="status-filter {{ request('status') === 'completed' ? 'active' : '' }}">
            <i class="fas fa-check-double"></i> 完了
            <span class="badge badge-success">{{ $statusCounts['completed'] }}</span>
        </a>
        <a href="{{ admin_route('admin.orders', $orderUserFilter + ['status' => 'cancelled']) }}" 
           class="status-filter {{ request('status') === 'cancelled' ? 'active' : '' }}">
            <i class="fas fa-times-circle"></i> キャンセル
            <span class="badge badge-danger">{{ $statusCounts['cancelled'] }}</span>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 注文一覧</h3>
        <div class="card-header-actions">
            <span class="card-header-note">{{ $orders->total() }}件</span>
            @if($orderUserFilter !== [])
            <a href="{{ admin_route('admin.orders', array_filter(['status' => request()->query('status')])) }}" class="btn btn-sm btn-secondary">会員の絞り込みを解除</a>
            @endif
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>注文番号</th>
                    <th>種別</th>
                    <th>ユーザー</th>
                    <th>金額</th>
                    <th>支払い方法</th>
                    <th>ステータス</th>
                    <th>日時</th>
                    <th class="table-actions-cell">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td><a href="{{ admin_route('admin.orders.detail', ['id' => $order->id]) }}">{{ $order->formattedOrderNumber() }}</a></td>
                    <td>
                        @php
                            $orderType = $order->order_type ?? '';
                            $orderTypeLabel = \App\Enums\OrderType::getLabel($orderType) ?? $orderType;
                        @endphp
                        <span class="badge badge-{{ $orderType === \App\Enums\OrderType::MOBILE ? 'info' : 'secondary' }}">
                            {{ $orderTypeLabel !== '' ? $orderTypeLabel : '—' }}
                        </span>
                    </td>
                    <td>
                        @if($order->user?->member !== null)
                            <a href="{{ admin_route('admin.members.detail', ['id' => $order->user->member->id]) }}">{{ admin_person_name($order->user?->name) }}</a>
                        @else
                            {{ admin_person_name($order->user?->name) }}
                        @endif
                    </td>
                    <td><strong>¥{{ number_format($order->total_amount) }}</strong></td>
                    <td>{{ $order->payment_brand_label }}</td>
                    <td>
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
                    </td>
                    <td>{{ $order->created_at->format('Y/m/d H:i') }}</td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            <a href="{{ admin_route('admin.orders.detail', ['id' => $order->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-eye"></i> 詳細</a>
                            @if($order->status !== 'completed' && $order->status !== 'cancelled')
                            <form action="{{ admin_route('admin.orders.complete', ['id' => $order->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('この注文を完了にしますか？')">
                                @csrf
                                <button type="submit" class="btn btn-action btn-success"><i class="fas fa-check-double"></i> 完了</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>注文がありません</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">
        {{ $orders->links('vendor.pagination.admin') }}
    </div>
</div>
@endsection
