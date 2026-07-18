@extends('admin.layout')

@section('title', '予約管理')
@section('page-title', '予約管理')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>予約管理</span>
</nav>

<div class="page-header-row">
    <div class="page-header">
        <h1 class="page-header-title"><i class="fas fa-calendar-check"></i> 予約管理</h1>
        <p class="page-header-desc">予約の確認・作成・管理を行います</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ admin_route('admin.reservations.create') }}" class="btn btn-primary btn-large">
            <i class="fas fa-plus"></i> 新規予約
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-sliders-h"></i> 絞り込み</h3>
        <a href="{{ admin_route('admin.settings.reservations') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-cog"></i> 予約設定
        </a>
    </div>
    <div class="card-body">
        <form action="{{ admin_route('admin.reservations') }}" method="GET" class="reservation-filter-form">
            <div class="filter-grid">
                <div class="form-group">
                    <label class="form-label">予約日</label>
                    <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">
                </div>
                <input type="hidden" name="status" value="{{ request('status', 'all') }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> 絞り込み
                </button>
                <a href="{{ admin_route('admin.reservations') }}" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> リセット
                </a>
                <div class="quick-date-links">
                    <a href="{{ admin_route('admin.reservations', ['date' => now()->format('Y-m-d'), 'status' => request('status', 'all')]) }}" class="btn btn-sm btn-secondary">
                        今日
                    </a>
                    <a href="{{ admin_route('admin.reservations', ['date' => now()->addDay()->format('Y-m-d'), 'status' => request('status', 'all')]) }}" class="btn btn-sm btn-secondary">
                        明日
                    </a>
                    <a href="{{ admin_route('admin.reservations', ['date' => now()->addDays(7)->format('Y-m-d'), 'status' => request('status', 'all')]) }}" class="btn btn-sm btn-secondary">
                        1週間後
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> ステータスフィルター</h3>
    </div>
    <div class="status-filters">
        @php
            $filterQuery = request()->except('page', 'status');
        @endphp
        <a href="{{ admin_route('admin.reservations', array_merge($filterQuery, ['status' => 'all'])) }}" 
           class="status-filter {{ !request('status') || request('status') === 'all' ? 'active' : '' }}">
            <i class="fas fa-list"></i> すべて
            <span class="badge">{{ $statusCounts['all'] }}</span>
        </a>
        <a href="{{ admin_route('admin.reservations', array_merge($filterQuery, ['status' => 'pending'])) }}" 
           class="status-filter {{ request('status') === 'pending' ? 'active' : '' }}">
            <i class="fas fa-clock"></i> 予約待ち
            <span class="badge badge-warning">{{ $statusCounts['pending'] }}</span>
        </a>
        <a href="{{ admin_route('admin.reservations', array_merge($filterQuery, ['status' => 'confirmed'])) }}" 
           class="status-filter {{ request('status') === 'confirmed' ? 'active' : '' }}">
            <i class="fas fa-check-circle"></i> 確認済み
            <span class="badge badge-info">{{ $statusCounts['confirmed'] }}</span>
        </a>
        <a href="{{ admin_route('admin.reservations', array_merge($filterQuery, ['status' => 'completed'])) }}" 
           class="status-filter {{ request('status') === 'completed' ? 'active' : '' }}">
            <i class="fas fa-check-double"></i> 完了
            <span class="badge badge-success">{{ $statusCounts['completed'] }}</span>
        </a>
        <a href="{{ admin_route('admin.reservations', array_merge($filterQuery, ['status' => 'cancelled'])) }}" 
           class="status-filter {{ request('status') === 'cancelled' ? 'active' : '' }}">
            <i class="fas fa-times-circle"></i> キャンセル
            <span class="badge badge-danger">{{ $statusCounts['cancelled'] }}</span>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-bar"></i> 予約状況</h3>
        <span class="card-header-note">
            1枠上限: {{ $reservationCapacity }}件
        </span>
    </div>
    <div class="card-body">
        @if($selectedDate && $dateStatusCounts)
            <div class="reservation-summary">
                <div class="summary-date">
                    <i class="fas fa-calendar-day"></i> {{ $selectedDateLabel }} の予約状況
                </div>
                <div class="reservation-stats-row">
                    <div class="reservation-stat-pill reservation-stat-pill--total">
                        <span class="reservation-stat-pill-label">合計</span>
                        <span class="reservation-stat-pill-value">{{ $dateStatusCounts['all'] }}</span>
                    </div>
                    <div class="reservation-stat-pill reservation-stat-pill--pending">
                        <span class="reservation-stat-pill-label"><i class="fas fa-clock"></i> 予約待ち</span>
                        <span class="reservation-stat-pill-value">{{ $dateStatusCounts['pending'] }}</span>
                    </div>
                    <div class="reservation-stat-pill reservation-stat-pill--confirmed">
                        <span class="reservation-stat-pill-label"><i class="fas fa-check-circle"></i> 確認済み</span>
                        <span class="reservation-stat-pill-value">{{ $dateStatusCounts['confirmed'] }}</span>
                    </div>
                    <div class="reservation-stat-pill reservation-stat-pill--completed">
                        <span class="reservation-stat-pill-label"><i class="fas fa-check-double"></i> 完了</span>
                        <span class="reservation-stat-pill-value">{{ $dateStatusCounts['completed'] }}</span>
                    </div>
                    <div class="reservation-stat-pill reservation-stat-pill--cancelled">
                        <span class="reservation-stat-pill-label"><i class="fas fa-times-circle"></i> キャンセル</span>
                        <span class="reservation-stat-pill-value">{{ $dateStatusCounts['cancelled'] }}</span>
                    </div>
                </div>
            </div>
            <div class="time-slot-grid">
                @foreach($timeSlotSummary as $slot)
                    <div class="time-slot-card {{ $slot['is_full'] ? 'is-full' : 'is-open' }}">
                        <div class="time-slot-time">{{ $slot['time'] }}</div>
                        <div class="time-slot-count">{{ $slot['count'] }} / {{ $slot['capacity'] }}件</div>
                        <div class="time-slot-status">
                            <i class="fas {{ $slot['is_full'] ? 'fa-times-circle' : 'fa-check-circle' }}"></i>
                            {{ $slot['is_full'] ? '満席' : '空きあり' }}
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-calendar"></i>
                <p>予約状況を確認したい日付を選択してください</p>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 予約一覧</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>予約番号</th>
                <th>ユーザー</th>
                <th>予約日時</th>
                <th>人数</th>
                <th>ステータス</th>
                <th>備考</th>
                <th class="table-actions-cell">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservations as $reservation)
            <tr>
                <td><a href="{{ admin_route('admin.reservations.detail', ['id' => $reservation->id]) }}">{{ $reservation->reservation_number }}</a></td>
                <td>
                    @if($reservation->user?->member !== null)
                        <a href="{{ admin_route('admin.members.detail', ['id' => $reservation->user->member->id]) }}">{{ $reservation->user->name ?? 'N/A' }}</a>
                    @else
                        {{ $reservation->user->name ?? 'N/A' }}
                    @endif
                </td>
                <td>
                    @php
                        $reservedAt = $reservation->reserved_at instanceof \Carbon\Carbon 
                            ? $reservation->reserved_at 
                            : \Carbon\Carbon::parse($reservation->reserved_at);
                    @endphp
                    <div>{{ $reservedAt->format('Y年m月d日') }}</div>
                    <div class="text-muted">{{ $reservedAt->format('H:i') }}</div>
                </td>
                <td>{{ $reservation->number_of_people }}名</td>
                <td>
                    @php
                        $statusLabels = [
                            'pending' => '予約待ち',
                            'confirmed' => '確認済み',
                            'completed' => '完了',
                            'cancelled' => 'キャンセル',
                        ];
                        $statusColors = [
                            'pending' => 'warning',
                            'confirmed' => 'info',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                        ];
                    @endphp
                    <span class="badge badge-{{ $statusColors[$reservation->status] ?? 'info' }}">
                        {{ $statusLabels[$reservation->status] ?? $reservation->status }}
                    </span>
                </td>
                <td>
                    @if($reservation->notes)
                        <span class="text-muted">{{ Str::limit($reservation->notes, 30) }}</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="table-actions-cell">
                    <div class="table-actions">
                        <a href="{{ admin_route('admin.reservations.detail', ['id' => $reservation->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-eye"></i> 詳細</a>
                        @if($reservation->status !== 'completed' && $reservation->status !== 'cancelled')
                        <a href="{{ admin_route('admin.reservations.edit', ['id' => $reservation->id]) }}" class="btn btn-action btn-outline"><i class="fas fa-pen"></i> 編集</a>
                        <form action="{{ admin_route('admin.reservations.complete', ['id' => $reservation->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('この予約を完了にしますか？')">
                            @csrf
                            <button type="submit" class="btn btn-action btn-success"><i class="fas fa-check-double"></i> 完了</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>予約がありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">
        {{ $reservations->links('vendor.pagination.admin') }}
    </div>
</div>
@endsection
