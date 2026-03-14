@extends('admin.layout')

@section('title', '予約詳細')
@section('page-title', '予約詳細')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.reservations') }}">予約管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>{{ $reservation->reservation_number }}</span>
</nav>

<div class="page-header-row">
    <div class="page-header">
        <h1 class="page-header-title"><i class="fas fa-calendar-check"></i> 予約詳細</h1>
        <p class="page-header-desc">予約番号: {{ $reservation->reservation_number }}</p>
    </div>
    <div class="page-header-actions">
        @if($reservation->canEdit())
        <a href="{{ admin_route('admin.reservations.edit', ['id' => $reservation->id]) }}" class="btn btn-primary">
            <i class="fas fa-pen"></i> 編集
        </a>
        @endif
        <a href="{{ admin_route('admin.reservations') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> 一覧に戻る
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle"></i> 予約情報</h3>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">予約番号</div>
                <div class="info-value">{{ $reservation->reservation_number }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">ユーザー</div>
                <div class="info-value">{{ $reservation->user->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">予約日時</div>
                <div class="info-value">
                    @php
                        $reservedAt = $reservation->reserved_at instanceof \Carbon\Carbon 
                            ? $reservation->reserved_at 
                            : \Carbon\Carbon::parse($reservation->reserved_at);
                    @endphp
                    <div>{{ $reservedAt->format('Y年m月d日') }}</div>
                    <div class="text-muted">{{ $reservedAt->format('H:i') }}</div>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">人数</div>
                <div class="info-value">{{ $reservation->number_of_people }}名</div>
            </div>
            <div class="info-item">
                <div class="info-label">ステータス</div>
                <div class="info-value">
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
                </div>
            </div>
            @if($reservation->notes)
            <div class="info-item info-item-full">
                <div class="info-label">備考</div>
                <div class="info-value">{{ $reservation->notes }}</div>
            </div>
            @endif
            <div class="info-item">
                <div class="info-label">作成日時</div>
                <div class="info-value">{{ $reservation->created_at->format('Y/m/d H:i') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">更新日時</div>
                <div class="info-value">{{ $reservation->updated_at->format('Y/m/d H:i') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cog"></i> ステータス変更</h3>
    </div>
    <div class="card-body">
        <form action="{{ admin_route('admin.reservations.status', ['id' => $reservation->id]) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="status">ステータス</label>
                <select name="status" id="status" class="form-control" required>
                    <option value="pending" {{ $reservation->status === 'pending' ? 'selected' : '' }}>予約待ち</option>
                    <option value="confirmed" {{ $reservation->status === 'confirmed' ? 'selected' : '' }}>確認済み</option>
                    <option value="completed" {{ $reservation->status === 'completed' ? 'selected' : '' }}>完了</option>
                    <option value="cancelled" {{ $reservation->status === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 更新
                </button>
            </div>
        </form>
    </div>
</div>

@if($reservation->status !== 'completed' && $reservation->status !== 'cancelled')
<div class="card">
    <div class="card-body">
        <div class="reservation-quick-actions">
            <form action="{{ admin_route('admin.reservations.complete', ['id' => $reservation->id]) }}" method="POST" onsubmit="return confirm('この予約を完了にしますか？')">
                @csrf
                <button type="submit" class="btn btn-success btn-large">
                    <i class="fas fa-check-double"></i> 予約を完了にする
                </button>
            </form>
            <form action="{{ admin_route('admin.reservations.destroy', ['id' => $reservation->id]) }}" method="POST" onsubmit="return confirm('この予約を削除してもよろしいですか？')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> 削除
                </button>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
