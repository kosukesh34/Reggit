@extends('admin.layout')

@section('title', 'ダッシュボード')
@section('page-title', 'ダッシュボード')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">総商品数</div>
        <div class="stat-value">{{ $stats['total_products'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">総注文数</div>
        <div class="stat-value">{{ $stats['total_orders'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">総ユーザー数</div>
        <div class="stat-value">{{ $stats['total_users'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">総会員数</div>
        <div class="stat-value">{{ $stats['total_members'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">本日の注文</div>
        <div class="stat-value">{{ $stats['today_orders'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">本日の売上</div>
        <div class="stat-value">¥{{ number_format($stats['today_revenue']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">未処理注文</div>
        <div class="stat-value">{{ $stats['pending_orders'] }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">最近の注文</h3>
        <a href="{{ route('admin.orders') }}" class="btn btn-secondary">すべて見る</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>注文番号</th>
                <th>ユーザー</th>
                <th>金額</th>
                <th>ステータス</th>
                <th>日時</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recent_orders as $order)
            <tr>
                <td><a href="{{ route('admin.orders.detail', $order->id) }}">{{ $order->order_number }}</a></td>
                <td>{{ $order->user->name ?? 'N/A' }}</td>
                <td>¥{{ number_format($order->total_amount) }}</td>
                <td>
                    <span class="badge badge-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : 'info') }}">
                        {{ $order->status }}
                    </span>
                </td>
                <td>{{ $order->created_at->format('Y/m/d H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 40px;">注文がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection


