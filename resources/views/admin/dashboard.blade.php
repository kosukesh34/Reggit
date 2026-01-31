@extends('admin.layout')

@section('title', 'ダッシュボード')
@section('page-title', 'ダッシュボード')

@section('content')
<div class="stats-grid">
    <div class="stat-card stat-card-primary">
        <div class="stat-icon">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">総商品数</div>
            <div class="stat-value">{{ $stats['total_products'] }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-info">
        <div class="stat-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">総注文数</div>
            <div class="stat-value">{{ $stats['total_orders'] }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-success">
        <div class="stat-icon">
            <i class="fas fa-user"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">総ユーザー数</div>
            <div class="stat-value">{{ $stats['total_users'] }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-warning">
        <div class="stat-icon">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">総会員数</div>
            <div class="stat-value">{{ $stats['total_members'] }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-danger">
        <div class="stat-icon">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">本日の注文</div>
            <div class="stat-value">{{ $stats['today_orders'] }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-success">
        <div class="stat-icon">
            <i class="fas fa-yen-sign"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">本日の売上</div>
            <div class="stat-value">¥{{ number_format($stats['today_revenue']) }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-warning">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">未処理注文</div>
            <div class="stat-value">{{ $stats['pending_orders'] }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-info">
        <div class="stat-icon">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">本日の予約</div>
            <div class="stat-value">{{ $stats['today_reservations'] }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-primary">
        <div class="stat-icon">
            <i class="fas fa-list-ol"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">順番待ち（待機中）</div>
            <div class="stat-value">{{ $stats['queue_waiting'] ?? 0 }}</div>
        </div>
    </div>
</div>

<section class="quick-menu-section" aria-label="クイックメニュー">
    <h3 class="quick-menu-title"><i class="fas fa-th-large"></i> クイックメニュー</h3>
    <p class="quick-menu-desc">各機能へ素早く移動できます</p>
    <div class="quick-menu-grid">
        <a href="{{ route('admin.orders') }}" class="quick-menu-item">
            <i class="fas fa-shopping-cart"></i>
            <span>注文（モバイル・店内）</span>
        </a>
        <a href="{{ route('admin.products') }}" class="quick-menu-item">
            <i class="fas fa-utensils"></i>
            <span>商品管理</span>
        </a>
        <a href="{{ route('admin.members') }}" class="quick-menu-item">
            <i class="fas fa-id-card"></i>
            <span>会員管理</span>
        </a>
        <a href="{{ route('admin.reservations') }}" class="quick-menu-item">
            <i class="fas fa-calendar-check"></i>
            <span>予約</span>
        </a>
        <a href="{{ route('admin.queue') }}" class="quick-menu-item">
            <i class="fas fa-list-ol"></i>
            <span>順番待ち</span>
        </a>
        <a href="{{ route('admin.stamps') }}" class="quick-menu-item">
            <i class="fas fa-stamp"></i>
            <span>スタンプ</span>
        </a>
        <a href="{{ route('admin.coupons') }}" class="quick-menu-item">
            <i class="fas fa-ticket-alt"></i>
            <span>クーポン</span>
        </a>
        <a href="{{ route('admin.announcements') }}" class="quick-menu-item">
            <i class="fas fa-bullhorn"></i>
            <span>お知らせ</span>
        </a>
        <a href="{{ route('admin.settings.basic') }}" class="quick-menu-item">
            <i class="fas fa-sliders-h"></i>
            <span>基本設定</span>
        </a>
        <a href="{{ route('admin.settings.advanced') }}" class="quick-menu-item">
            <i class="fas fa-palette"></i>
            <span>詳細設定（配色）</span>
        </a>
    </div>
</section>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 最近の注文</h3>
        <div class="card-header-actions">
            <a href="{{ route('admin.reservations', ['date' => now()->format('Y-m-d')]) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-calendar-day"></i> 本日の予約
            </a>
            <a href="{{ route('admin.orders') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> すべて見る
            </a>
        </div>
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
                <td colspan="5" class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>注文がありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection


