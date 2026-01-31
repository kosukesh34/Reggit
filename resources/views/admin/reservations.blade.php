@extends('admin.layout')

@section('title', '予約管理')
@section('page-title', '予約管理')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-sliders-h"></i> 絞り込み</h3>
        <a href="{{ route('admin.settings') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-cog"></i> 予約枠設定へ
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.reservations') }}" method="GET" class="reservation-filter-form">
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
                <a href="{{ route('admin.reservations') }}" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> リセット
                </a>
                <div class="quick-date-links">
                    <a href="{{ route('admin.reservations', ['date' => now()->format('Y-m-d'), 'status' => request('status', 'all')]) }}" class="btn btn-sm btn-secondary">
                        今日
                    </a>
                    <a href="{{ route('admin.reservations', ['date' => now()->addDay()->format('Y-m-d'), 'status' => request('status', 'all')]) }}" class="btn btn-sm btn-secondary">
                        明日
                    </a>
                    <a href="{{ route('admin.reservations', ['date' => now()->addDays(7)->format('Y-m-d'), 'status' => request('status', 'all')]) }}" class="btn btn-sm btn-secondary">
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
        <a href="{{ route('admin.reservations', array_merge($filterQuery, ['status' => 'all'])) }}" 
           class="status-filter {{ !request('status') || request('status') === 'all' ? 'active' : '' }}">
            <i class="fas fa-list"></i> すべて
            <span class="badge">{{ $statusCounts['all'] }}</span>
        </a>
        <a href="{{ route('admin.reservations', array_merge($filterQuery, ['status' => 'pending'])) }}" 
           class="status-filter {{ request('status') === 'pending' ? 'active' : '' }}">
            <i class="fas fa-clock"></i> 予約待ち
            <span class="badge badge-warning">{{ $statusCounts['pending'] }}</span>
        </a>
        <a href="{{ route('admin.reservations', array_merge($filterQuery, ['status' => 'confirmed'])) }}" 
           class="status-filter {{ request('status') === 'confirmed' ? 'active' : '' }}">
            <i class="fas fa-check-circle"></i> 確認済み
            <span class="badge badge-info">{{ $statusCounts['confirmed'] }}</span>
        </a>
        <a href="{{ route('admin.reservations', array_merge($filterQuery, ['status' => 'completed'])) }}" 
           class="status-filter {{ request('status') === 'completed' ? 'active' : '' }}">
            <i class="fas fa-check-double"></i> 完了
            <span class="badge badge-success">{{ $statusCounts['completed'] }}</span>
        </a>
        <a href="{{ route('admin.reservations', array_merge($filterQuery, ['status' => 'cancelled'])) }}" 
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
                    <i class="fas fa-calendar-day"></i> {{ $selectedDateLabel }} の予約
                </div>
                <div class="stats-grid">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-icon"><i class="fas fa-list"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">合計</div>
                            <div class="stat-value">{{ $dateStatusCounts['all'] }}</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-warning">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">予約待ち</div>
                            <div class="stat-value">{{ $dateStatusCounts['pending'] }}</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-info">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">確認済み</div>
                            <div class="stat-value">{{ $dateStatusCounts['confirmed'] }}</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-success">
                        <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">完了</div>
                            <div class="stat-value">{{ $dateStatusCounts['completed'] }}</div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-danger">
                        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">キャンセル</div>
                            <div class="stat-value">{{ $dateStatusCounts['cancelled'] }}</div>
                        </div>
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
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservations as $reservation)
            <tr>
                <td><a href="{{ route('admin.reservations.detail', $reservation->id) }}">{{ $reservation->reservation_number }}</a></td>
                <td>{{ $reservation->user->name ?? 'N/A' }}</td>
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
                <td>
                    <div class="action-buttons">
                        <a href="{{ route('admin.reservations.detail', $reservation->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> 詳細
                        </a>
                        @if($reservation->status !== 'completed' && $reservation->status !== 'cancelled')
                        <form action="{{ route('admin.reservations.complete', $reservation->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('この予約を完了にしますか？')">
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
                    <i class="fas fa-calendar-times"></i>
                    <p>予約がありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">
        {{ $reservations->links() }}
    </div>
</div>
@endsection
