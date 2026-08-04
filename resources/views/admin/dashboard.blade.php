@extends('admin.layout')

@section('title', 'ダッシュボード')
@section('page-title', 'ダッシュボード')

@section('content')

<div class="dashboard-section-label">今日やること</div>
<div class="stats-grid stats-grid--actions">
    <a href="{{ admin_route('admin.orders', ['status' => 'pending']) }}" class="stat-card stat-card--link {{ $stats['pending_orders'] > 0 ? 'stat-card-warning' : 'stat-card-secondary' }}{{ $stats['pending_orders'] > 0 ? ' stat-card--alert' : '' }}">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-content">
            <div class="stat-label">未処理注文</div>
            <div class="stat-value {{ $stats['pending_orders'] > 0 ? 'stat-value--alert' : '' }}">{{ $stats['pending_orders'] }}</div>
            <div class="stat-link">注文を確認 <i class="fas fa-chevron-right"></i></div>
        </div>
    </a>
    <a href="{{ admin_route('admin.reservations', ['date' => now()->format('Y-m-d')]) }}" class="stat-card stat-card--link stat-card-info">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-content">
            <div class="stat-label">本日の予約</div>
            <div class="stat-value">{{ $stats['today_reservations'] }}</div>
            <div class="stat-link">予約を確認 <i class="fas fa-chevron-right"></i></div>
        </div>
    </a>
    <a href="{{ admin_route('admin.queue') }}" class="stat-card stat-card--link stat-card-primary">
        <div class="stat-icon"><i class="fas fa-list-ol"></i></div>
        <div class="stat-content">
            <div class="stat-label">順番待ち（待機中）</div>
            <div class="stat-value">{{ $stats['queue_waiting'] ?? 0 }}</div>
            <div class="stat-link">順番待ちを確認 <i class="fas fa-chevron-right"></i></div>
        </div>
    </a>
</div>

<div class="dashboard-section-label">今日・今月の数字</div>
<div class="stats-grid stats-grid--metrics">
    <div class="stat-card stat-card-success">
        <div class="stat-icon"><i class="fas fa-yen-sign"></i></div>
        <div class="stat-content">
            <div class="stat-label">本日の売上</div>
            <div class="stat-value">¥{{ number_format($stats['today_revenue']) }}</div>
            @php $t = $stats['trend_today_revenue'] @endphp
            @if($t['percent'] !== null)
            <div class="stat-trend stat-trend--{{ $t['direction'] }}">
                <i class="fas fa-arrow-{{ $t['direction'] === 'up' ? 'up' : 'down' }}"></i>
                {{ $t['percent'] }}% <span class="stat-trend-label">前日比</span>
            </div>
            @endif
        </div>
    </div>
    <div class="stat-card stat-card-danger">
        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-content">
            <div class="stat-label">本日の注文</div>
            <div class="stat-value">{{ $stats['today_orders'] }}</div>
            @php $t = $stats['trend_today_orders'] @endphp
            @if($t['percent'] !== null)
            <div class="stat-trend stat-trend--{{ $t['direction'] }}">
                <i class="fas fa-arrow-{{ $t['direction'] === 'up' ? 'up' : 'down' }}"></i>
                {{ $t['percent'] }}% <span class="stat-trend-label">前日比</span>
            </div>
            @endif
        </div>
    </div>
    <div class="stat-card stat-card-success">
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        <div class="stat-content">
            <div class="stat-label">今月の売上</div>
            <div class="stat-value">¥{{ number_format($stats['month_revenue']) }}</div>
            @php $t = $stats['trend_month_revenue'] @endphp
            @if($t['percent'] !== null)
            <div class="stat-trend stat-trend--{{ $t['direction'] }}">
                <i class="fas fa-arrow-{{ $t['direction'] === 'up' ? 'up' : 'down' }}"></i>
                {{ $t['percent'] }}% <span class="stat-trend-label">前月比</span>
            </div>
            @endif
        </div>
    </div>
    <div class="stat-card stat-card-primary">
        <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
        <div class="stat-content">
            <div class="stat-label">今月の新規会員</div>
            <div class="stat-value">{{ $stats['month_new_members'] }}</div>
            @php $t = $stats['trend_month_members'] @endphp
            @if($t['percent'] !== null)
            <div class="stat-trend stat-trend--{{ $t['direction'] }}">
                <i class="fas fa-arrow-{{ $t['direction'] === 'up' ? 'up' : 'down' }}"></i>
                {{ $t['percent'] }}% <span class="stat-trend-label">前月比</span>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 最近の注文</h3>
        <div class="card-header-actions">
            <a href="{{ admin_route('admin.reservations', ['date' => now()->format('Y-m-d')]) }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-calendar-day"></i> 本日の予約
            </a>
            <a href="{{ admin_route('admin.orders') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-arrow-right"></i> すべて見る
            </a>
        </div>
    </div>
    <div class="table-responsive">
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
                    <td><a href="{{ admin_route('admin.orders.detail', ['id' => $order->id]) }}">{{ $order->formattedOrderNumber() }}</a></td>
                    <td>
                        @if($order->user?->member !== null)
                            <a href="{{ admin_route('admin.members.detail', ['id' => $order->user->member->id]) }}">{{ $order->user->name ?? 'N/A' }}</a>
                        @else
                            {{ $order->user->name ?? 'N/A' }}
                        @endif
                    </td>
                    <td><strong>¥{{ number_format($order->total_amount) }}</strong></td>
                    <td>
                        <span class="badge badge-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : ($order->status === 'cancelled' ? 'danger' : 'info')) }}">
                            {{ \App\Enums\OrderStatus::getLabel($order->status) }}
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
</div>
@endsection
