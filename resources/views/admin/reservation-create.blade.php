@extends('admin.layout')

@section('title', '予約作成')
@section('page-title', '予約作成')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.reservations') }}">予約</a>
    <span class="breadcrumb-sep">/</span>
    <span>新規作成</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-calendar-plus"></i> 予約を新規作成</h1>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-edit"></i> 予約情報</h3>
    </div>
    <div class="card-body">
        <form action="{{ admin_route('admin.reservations.store') }}" method="POST">
            @csrf
            <div class="reservation-form-grid">
                <div class="form-group">
                    <label class="form-label" for="reserved_at_date">
                        <i class="fas fa-calendar-day"></i> 予約日 <span class="form-required">*</span>
                    </label>
                    <input type="date" name="reserved_at_date" id="reserved_at_date" class="form-control"
                           value="{{ old('reserved_at_date', now()->addDay()->format('Y-m-d')) }}" required>
                    @error('reserved_at_date')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="reserved_at_time">
                        <i class="fas fa-clock"></i> 予約時間 <span class="form-required">*</span>
                    </label>
                    <input type="time" name="reserved_at_time" id="reserved_at_time" class="form-control"
                           value="{{ old('reserved_at_time', '12:00') }}" required>
                    @error('reserved_at_time')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="number_of_people">
                        <i class="fas fa-users"></i> 人数 <span class="form-required">*</span>
                    </label>
                    <input type="number" name="number_of_people" id="number_of_people" class="form-control"
                           value="{{ old('number_of_people', 2) }}" min="1" max="100" required>
                    @error('number_of_people')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">
                    <i class="fas fa-sticky-note"></i> 備考
                </label>
                <textarea name="notes" id="notes" class="form-control" rows="3"
                          placeholder="アレルギー情報、座席の希望など">{{ old('notes') }}</textarea>
                @error('notes')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="fas fa-check"></i> 予約を作成
                </button>
                <a href="{{ admin_route('admin.reservations') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> 一覧に戻る
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
