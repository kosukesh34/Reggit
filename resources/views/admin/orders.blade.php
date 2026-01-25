@extends('admin.layout')

@section('title', '注文管理')
@section('page-title', '注文管理')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> ステータスフィルター</h3>
    </div>
    <div class="status-filters">
        <a href="{{ route('admin.orders', ['status' => 'all']) }}" 
           class="status-filter {{ !request('status') || request('status') === 'all' ? 'active' : '' }}">
            <i class="fas fa-list"></i> すべて
            <span class="badge">{{ $statusCounts['all'] }}</span>
        </a>
        <a href="{{ route('admin.orders', ['status' => 'pending']) }}" 
           class="status-filter {{ request('status') === 'pending' ? 'active' : '' }}">
            <i class="fas fa-clock"></i> 未処理
            <span class="badge badge-warning">{{ $statusCounts['pending'] }}</span>
        </a>
        <a href="{{ route('admin.orders', ['status' => 'confirmed']) }}" 
           class="status-filter {{ request('status') === 'confirmed' ? 'active' : '' }}">
            <i class="fas fa-check-circle"></i> 確認済み
            <span class="badge badge-info">{{ $statusCounts['confirmed'] }}</span>
        </a>
        <a href="{{ route('admin.orders', ['status' => 'preparing']) }}" 
           class="status-filter {{ request('status') === 'preparing' ? 'active' : '' }}">
            <i class="fas fa-utensils"></i> 準備中
            <span class="badge badge-info">{{ $statusCounts['preparing'] }}</span>
        </a>
        <a href="{{ route('admin.orders', ['status' => 'ready']) }}" 
           class="status-filter {{ request('status') === 'ready' ? 'active' : '' }}">
            <i class="fas fa-check"></i> 準備完了
            <span class="badge badge-info">{{ $statusCounts['ready'] }}</span>
        </a>
        <a href="{{ route('admin.orders', ['status' => 'completed']) }}" 
           class="status-filter {{ request('status') === 'completed' ? 'active' : '' }}">
            <i class="fas fa-check-double"></i> 完了
            <span class="badge badge-success">{{ $statusCounts['completed'] }}</span>
        </a>
        <a href="{{ route('admin.orders', ['status' => 'cancelled']) }}" 
           class="status-filter {{ request('status') === 'cancelled' ? 'active' : '' }}">
            <i class="fas fa-times-circle"></i> キャンセル
            <span class="badge badge-danger">{{ $statusCounts['cancelled'] }}</span>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 注文一覧</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>注文番号</th>
                <th>ユーザー</th>
                <th>金額</th>
                <th>支払い方法</th>
                <th>ステータス</th>
                <th>日時</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td><a href="{{ route('admin.orders.detail', $order->id) }}">{{ $order->order_number }}</a></td>
                <td>{{ $order->user->name ?? 'N/A' }}</td>
                <td>¥{{ number_format($order->total_amount) }}</td>
                <td>{{ $order->payment_method ?? 'N/A' }}</td>
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
                <td>
                    <div class="action-buttons">
                    <a href="{{ route('admin.orders.detail', $order->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> 詳細
                    </a>
                        @if($order->status !== 'completed' && $order->status !== 'cancelled')
                        <form action="{{ route('admin.orders.complete', $order->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('この注文を完了にしますか？')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-check-double"></i> 完了
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>注文がありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">
        {{ $orders->links() }}
    </div>
</div>
@endsection


