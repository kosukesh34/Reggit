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
            @php
                $reservedAt = $reservation->reserved_at instanceof \Carbon\Carbon
                    ? $reservation->reserved_at
                    : \Carbon\Carbon::parse($reservation->reserved_at);
            @endphp
            <div class="reservation-form-grid">
                <div class="form-group">
                    <label class="form-label" for="reserved_at_date">
                        <i class="fas fa-calendar-day"></i> 予約日 <span class="form-required">*</span>
                    </label>
                    <input type="date" name="reserved_at_date" id="reserved_at_date" class="form-control"
                           value="{{ old('reserved_at_date', $reservedAt->format('Y-m-d')) }}" required>
                    @error('reserved_at_date')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="reserved_at_time">
                        <i class="fas fa-clock"></i> 予約時間 <span class="form-required">*</span>
                    </label>
                    <input type="time" name="reserved_at_time" id="reserved_at_time" class="form-control"
                           value="{{ old('reserved_at_time', $reservedAt->format('H:i')) }}" required>
                    @error('reserved_at_time')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="number_of_people">
                        <i class="fas fa-users"></i> 人数 <span class="form-required">*</span>
                    </label>
                    <input type="number" name="number_of_people" id="number_of_people" class="form-control"
                           value="{{ old('number_of_people', $reservation->number_of_people) }}" min="1" max="100" required>
                    @error('number_of_people')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                @if($courses->isNotEmpty())
                <div class="form-group"><label class="form-label" for="reservation_course_id">コース</label><select class="form-control" name="reservation_course_id" id="reservation_course_id"><option value="">指定なし</option>@foreach($courses as $course)<option value="{{ $course->id }}" {{ (string) old('reservation_course_id', $reservation->reservation_course_id) === (string) $course->id ? 'selected' : '' }}>{{ $course->name }} · {{ number_format($course->price) }}円</option>@endforeach</select></div>
                @endif
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">
                    <i class="fas fa-sticky-note"></i> 備考
                </label>
                <textarea name="notes" id="notes" class="form-control" rows="3"
                          placeholder="アレルギー情報、座席の希望など">{{ old('notes', $reservation->notes) }}</textarea>
                @error('notes')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

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
