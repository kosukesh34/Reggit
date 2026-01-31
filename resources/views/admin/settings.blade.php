@extends('admin.layout')

@section('title', '店舗設定')
@section('page-title', '店舗設定')

@section('content')
@php
    $businessHours = $settings['businessHours'] ?? ['start' => '10:00', 'end' => '22:00'];
    $timeSlots = $settings['timeSlots'] ?? [];
    $closedDays = $settings['closedDays'] ?? [];
    $closedDates = $settings['closedDates'] ?? [];
    $advanceDays = $settings['advanceDays'] ?? 30;
    $reservationCapacity = $settings['reservationCapacity'] ?? 20;
    $lineTheme = $settings['lineTheme'] ?? [];
@endphp
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-cog"></i> 店舗設定</h1>
    <p class="page-header-desc">予約とLINEアプリの見た目を管理します</p>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<form action="{{ route('admin.settings.update') }}" method="POST" id="settingsForm">
    @csrf

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-palette"></i> LINEアプリの配色</h2>
        </div>
        <div class="card-body">
            <p class="form-section-desc">お客様がLINEで開くアプリのボタンや強調色を変更できます。未設定の項目はデフォルトの色になります。</p>
            <div class="line-theme-grid">
                <div class="form-group">
                    <label class="form-label">メイン色</label>
                    <div class="color-input-row">
                        <input type="color" id="line_primary_color" class="color-picker" value="{{ $lineTheme['primary'] ?? '#000000' }}" aria-label="メイン色">
                        <input type="text" name="line_primary_color" class="form-control color-hex" value="{{ $lineTheme['primary'] ?? '#000000' }}" maxlength="7">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">メイン色（濃いめ）</label>
                    <div class="color-input-row">
                        <input type="color" id="line_primary_dark" class="color-picker" value="{{ $lineTheme['primary_dark'] ?? '#333333' }}" aria-label="メイン色（濃いめ）">
                        <input type="text" name="line_primary_dark" class="form-control color-hex" value="{{ $lineTheme['primary_dark'] ?? '#333333' }}" maxlength="7">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">成功・ポイント色</label>
                    <div class="color-input-row">
                        <input type="color" id="line_success_color" class="color-picker" value="{{ $lineTheme['success'] ?? '#000000' }}" aria-label="成功色">
                        <input type="text" name="line_success_color" class="form-control color-hex" value="{{ $lineTheme['success'] ?? '#000000' }}" maxlength="7">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">注意・キャンセル色</label>
                    <div class="color-input-row">
                        <input type="color" id="line_danger_color" class="color-picker" value="{{ $lineTheme['danger'] ?? '#dc3545' }}" aria-label="注意色">
                        <input type="text" name="line_danger_color" class="form-control color-hex" value="{{ $lineTheme['danger'] ?? '#dc3545' }}" maxlength="7">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-clock"></i> 営業時間・予約設定</h2>
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
                    @foreach($timeSlots as $index => $slot)
                    <div class="time-slot-item">
                        <input type="time" name="reservation_time_slots[]" 
                            class="form-control time-slot-input" 
                            value="{{ $slot }}" required>
                        <button type="button" class="btn btn-sm btn-danger remove-time-slot" 
                            onclick="removeTimeSlot(this)">
                            <i class="fas fa-times"></i> 削除
                        </button>
                    </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addTimeSlot()">
                    <i class="fas fa-plus"></i> 時間帯を追加
                </button>
            </div>

            <div class="form-group">
                <label class="form-label">予約枠上限（1時間帯あたり）</label>
                <input type="number" name="reservation_capacity_per_slot" class="form-control" 
                    value="{{ $reservationCapacity }}" min="1" required>
                <small class="form-text">時間帯ごとの予約上限を設定します</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">休業日（曜日）</label>
                <div class="closed-days-container">
                    @php
                        $days = ['日', '月', '火', '水', '木', '金', '土'];
                    @endphp
                    @foreach($days as $index => $day)
                    <label class="checkbox-label">
                        <input type="checkbox" name="closed_days[]" value="{{ $index }}"
                            {{ in_array($index, $closedDays) ? 'checked' : '' }}>
                        <span>{{ $day }}曜日</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">休業日（特定日）</label>
                <div id="closedDatesContainer" class="closed-dates-container">
                    @if(count($closedDates) > 0)
                        @foreach($closedDates as $date)
                        <div class="closed-date-item">
                            <input type="date" name="closed_dates[]" class="form-control closed-date-input" value="{{ $date }}">
                            <button type="button" class="btn btn-sm btn-danger remove-closed-date" onclick="removeClosedDate(this)">
                                <i class="fas fa-times"></i> 削除
                            </button>
                        </div>
                        @endforeach
                    @else
                        <div class="closed-date-item">
                            <input type="date" name="closed_dates[]" class="form-control closed-date-input">
                            <button type="button" class="btn btn-sm btn-danger remove-closed-date" onclick="removeClosedDate(this)">
                                <i class="fas fa-times"></i> 削除
                            </button>
                        </div>
                    @endif
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addClosedDate()">
                    <i class="fas fa-plus"></i> 休業日を追加
                </button>
                <small class="form-text">臨時休業や特別営業日に合わせて設定できます</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">予約可能な日数（先まで）</label>
                <input type="number" name="advance_booking_days" class="form-control" 
                    value="{{ $advanceDays }}" min="1" max="365" required>
                <small class="form-text">何日先まで予約を受け付けるか設定します</small>
            </div>
            
        </div>
    </div>

    <div class="form-actions form-actions-sticky">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> 設定を保存
        </button>
    </div>
</form>

<script>
document.querySelectorAll('.color-picker').forEach(function(picker) {
    const hexInput = picker.closest('.color-input-row').querySelector('.color-hex');
    picker.addEventListener('input', function() {
        hexInput.value = picker.value;
    });
});
document.querySelectorAll('.color-hex').forEach(function(hexInput) {
    const picker = hexInput.closest('.color-input-row').querySelector('.color-picker');
    hexInput.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(hexInput.value)) {
            picker.value = hexInput.value;
        }
    });
});

function addTimeSlot() {
    const container = document.getElementById('timeSlotsContainer');
    const newItem = document.createElement('div');
    newItem.className = 'time-slot-item';
    newItem.innerHTML = `
        <input type="time" name="reservation_time_slots[]" 
            class="form-control time-slot-input" required>
        <button type="button" class="btn btn-sm btn-danger remove-time-slot" 
            onclick="removeTimeSlot(this)">
            <i class="fas fa-times"></i> 削除
        </button>
    `;
    container.appendChild(newItem);
}

function removeTimeSlot(button) {
    const container = document.getElementById('timeSlotsContainer');
    if (container.children.length > 1) {
        button.closest('.time-slot-item').remove();
    } else {
        alert('最低1つの時間帯が必要です');
    }
}

function addClosedDate() {
    const container = document.getElementById('closedDatesContainer');
    if (!container) return;
    const newItem = document.createElement('div');
    newItem.className = 'closed-date-item';
    newItem.innerHTML = `
        <input type="date" name="closed_dates[]" class="form-control closed-date-input">
        <button type="button" class="btn btn-sm btn-danger remove-closed-date" onclick="removeClosedDate(this)">
            <i class="fas fa-times"></i> 削除
        </button>
    `;
    container.appendChild(newItem);
}

function removeClosedDate(button) {
    button.closest('.closed-date-item').remove();
}
</script>
@endsection
