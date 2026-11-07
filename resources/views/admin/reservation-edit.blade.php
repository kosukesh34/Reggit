@extends('admin.layout')

@section('title', '予約編集')
@section('page-title', '予約編集')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.reservations') }}">予約</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.reservations.detail', ['id' => $reservation->id]) }}">{{ $reservation->formattedReservationNumber() }}</a>
    <span class="breadcrumb-sep">/</span>
    <span>編集</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-calendar-check"></i> 予約を編集</h1>
    <p class="page-header-desc">予約番号: {{ $reservation->formattedReservationNumber() }}</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-edit"></i> 予約情報</h3>
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
    <div class="card-body">
        <form action="{{ admin_route('admin.reservations.update', ['id' => $reservation->id]) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.partials._reservation_fields', ['reservation' => $reservation])
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="fas fa-save"></i> 更新する
                </button>
                <x-back-link :href="admin_route('admin.reservations.detail', ['id' => $reservation->id])" label="予約詳細へ戻る" />
            </div>
        </form>
    </div>
</div>

@if($reservation->status !== 'completed' && $reservation->status !== 'cancelled')
<div class="card card-danger-zone">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> 危険な操作</h3>
    </div>
    <div class="card-body">
        <form action="{{ admin_route('admin.reservations.destroy', ['id' => $reservation->id]) }}" method="POST"
              onsubmit="return confirm('この予約を削除してもよろしいですか？この操作は取り消せません。')">
            @csrf
            @method('DELETE')
            <p class="form-section-desc">この予約を完全に削除します。この操作は元に戻せません。</p>
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> 予約を削除
            </button>
        </form>
    </div>
</div>
@endif
@endsection
