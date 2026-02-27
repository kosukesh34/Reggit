@extends('admin.layout')

@section('title', '基本設定')
@section('page-title', '基本設定')

@section('content')
@php
    $businessHours = $settings['businessHours'] ?? ['start' => '10:00', 'end' => '22:00'];
    $timeSlots = $settings['timeSlots'] ?? [];
    $closedDays = $settings['closedDays'] ?? [];
    $closedDates = $settings['closedDates'] ?? [];
    $advanceDays = $settings['advanceDays'] ?? 30;
    $reservationCapacity = $settings['reservationCapacity'] ?? 20;
@endphp

<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>基本設定</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-sliders-h"></i> 基本設定</h1>
    <p class="page-header-desc">営業時間と利用機能を設定します。予約の時間帯・休業日・枠数は「予約」メニュー内の「予約設定」から変更できます。</p>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif

<form action="{{ admin_route('admin.settings.basic.update') }}" method="POST" id="settingsBasicForm">
    @csrf
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-clock"></i> 営業時間・予約</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">開始時間</label>
                <input type="time" name="business_hours_start" class="form-control" value="{{ $businessHours['start'] }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">終了時間</label>
                <input type="time" name="business_hours_end" class="form-control" value="{{ $businessHours['end'] }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">予約可能な時間帯</label>
                <div id="timeSlotsContainer" class="time-slots-container">
                    @foreach($timeSlots as $slot)
                    <div class="time-slot-item">
                        <input type="time" name="reservation_time_slots[]" class="form-control time-slot-input" value="{{ $slot }}" required>
                        <button type="button" class="btn btn-sm btn-danger remove-time-slot" onclick="removeTimeSlot(this)"><i class="fas fa-times"></i> 削除</button>
                    </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addTimeSlot()"><i class="fas fa-plus"></i> 時間帯を追加</button>
            </div>
            <div class="form-group">
                <label class="form-label">予約枠上限（1時間帯あたり）</label>
                <input type="number" name="reservation_capacity_per_slot" class="form-control" value="{{ $reservationCapacity }}" min="1" required>
                <small class="form-text">時間帯ごとの予約上限です</small>
            </div>
            <div class="form-group">
                <label class="form-label">休業日（曜日）</label>
                <div class="closed-days-container">
                    @php $days = ['日', '月', '火', '水', '木', '金', '土']; @endphp
                    @foreach($days as $index => $day)
                    <label class="checkbox-label">
                        <input type="checkbox" name="closed_days[]" value="{{ $index }}" {{ in_array($index, $closedDays) ? 'checked' : '' }}>
                        <span>{{ $day }}曜日</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">休業日（特定日）</label>
                <div id="closedDatesContainer" class="closed-dates-container">
                    @forelse($closedDates as $date)
                    <div class="closed-date-item">
                        <input type="date" name="closed_dates[]" class="form-control closed-date-input" value="{{ $date }}">
                        <button type="button" class="btn btn-sm btn-danger remove-closed-date" onclick="removeClosedDate(this)"><i class="fas fa-times"></i> 削除</button>
                    </div>
                    @empty
                    <div class="closed-date-item">
                        <input type="date" name="closed_dates[]" class="form-control closed-date-input">
                        <button type="button" class="btn btn-sm btn-danger remove-closed-date" onclick="removeClosedDate(this)"><i class="fas fa-times"></i> 削除</button>
                    </div>
                    @endforelse
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addClosedDate()"><i class="fas fa-plus"></i> 休業日を追加</button>
            </div>
            <div class="form-group">
                <label class="form-label">予約可能な日数（先まで）</label>
                <input type="number" name="advance_booking_days" class="form-control" value="{{ $advanceDays }}" min="1" max="365" required>
                <small class="form-text">何日先まで予約を受け付けるか</small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
                <a href="{{ admin_route('admin.settings.advanced') }}" class="btn btn-secondary">詳細設定（配色）へ</a>
            </div>
        </div>
    </div>
</form>

<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-toggle-on"></i> 利用機能</h2>
    </div>
    <div class="card-body">
        <p class="form-text" style="margin-bottom:12px;">LINE アプリで利用できる機能の ON / OFF を切り替えます。</p>
        <form action="{{ admin_route('admin.settings.feature-flags.update') }}" method="POST">
            @csrf
            <div class="feature-flags-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px 16px;">
                @foreach(\App\Models\ShopSetting::FEATURE_FLAG_LABELS as $key => $label)
                <label class="checkbox-label">
                    <input type="checkbox" name="feature_flags[{{ $key }}]" value="1" {{ ($featureFlags[$key] ?? false) ? 'checked' : '' }}>
                    <span>{{ $label }}</span>
                </label>
                @endforeach
            </div>
            <div class="form-actions" style="margin-top:16px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 利用機能を保存</button>
            </div>
        </form>
    </div>
</div>

<script>
function addTimeSlot() {
    const c = document.getElementById('timeSlotsContainer');
    const d = document.createElement('div');
    d.className = 'time-slot-item';
    d.innerHTML = '<input type="time" name="reservation_time_slots[]" class="form-control time-slot-input" required> <button type="button" class="btn btn-sm btn-danger remove-time-slot" onclick="removeTimeSlot(this)"><i class="fas fa-times"></i> 削除</button>';
    c.appendChild(d);
}
function removeTimeSlot(btn) {
    const c = document.getElementById('timeSlotsContainer');
    if (c.children.length > 1) btn.closest('.time-slot-item').remove();
    else alert('最低1つの時間帯が必要です');
}
function addClosedDate() {
    const c = document.getElementById('closedDatesContainer');
    const d = document.createElement('div');
    d.className = 'closed-date-item';
    d.innerHTML = '<input type="date" name="closed_dates[]" class="form-control closed-date-input"> <button type="button" class="btn btn-sm btn-danger remove-closed-date" onclick="removeClosedDate(this)"><i class="fas fa-times"></i> 削除</button>';
    c.appendChild(d);
}
function removeClosedDate(btn) { btn.closest('.closed-date-item').remove(); }
</script>
@endsection
