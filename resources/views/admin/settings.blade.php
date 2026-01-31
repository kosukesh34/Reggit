@extends('admin.layout')

@section('title', '店舗設定')

@section('content')
<div class="page-header">
    <h1><i class="fas fa-cog"></i> 店舗設定</h1>
    <p class="page-description">予約に関する設定を管理します</p>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-clock"></i> 営業時間設定</h2>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.settings.update') }}" method="POST" id="settingsForm">
            @csrf
            
            <div class="form-group">
                <label class="form-label">開始時間</label>
                <input type="time" name="business_hours_start" class="form-control" 
                    value="{{ $businessHours['start'] }}" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">終了時間</label>
                <input type="time" name="business_hours_end" class="form-control" 
                    value="{{ $businessHours['end'] }}" required>
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
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 設定を保存
                </button>
            </div>
        </form>
    </div>
</div>

<script>
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
