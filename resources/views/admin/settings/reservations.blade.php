@extends('admin.layout')
@section('title', '予約設定')
@section('page-title', '予約設定')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a><span class="breadcrumb-sep">/</span><a href="{{ admin_route('admin.reservations') }}">予約</a><span class="breadcrumb-sep">/</span><span>予約設定</span></nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-calendar-check"></i> 予約設定</h1>
</div>
@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@php
    $timeSlots = $settings['timeSlots'] ?? ['10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00'];
    $closedDays = $settings['closedDays'] ?? [];
    $closedDates = $settings['closedDates'] ?? [];
    $advanceDays = $settings['advanceDays'] ?? 30;
    $reservationCapacity = $settings['reservationCapacity'] ?? 20;
@endphp
<form action="{{ admin_route('admin.settings.reservations.update') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">予約可能な時間帯</label>
                <div id="timeSlotsContainer" class="time-slots-container">
                    @foreach($timeSlots as $slot)
                    <div class="time-slot-item"><input type="time" name="reservation_time_slots[]" class="form-control" value="{{ $slot }}"> <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.time-slot-item').remove()"><i class="fas fa-times"></i></button></div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-secondary mt-1" onclick="addSlot()"><i class="fas fa-plus"></i> 追加</button>
            </div>
            <div class="form-group">
                <label class="form-label">予約枠上限（1時間帯あたり）</label>
                <input type="number" name="reservation_capacity_per_slot" class="form-control" value="{{ $reservationCapacity }}" min="1" required>
            </div>
            <div class="form-group">
                <label class="form-label">休業日（曜日）</label>
                @php $days = ['日','月','火','水','木','金','土']; @endphp
                @foreach($days as $i => $d)
                <label class="checkbox-label"><input type="checkbox" name="closed_days[]" value="{{ $i }}" {{ in_array($i, $closedDays) ? 'checked' : '' }}> {{ $d }}曜</label>
                @endforeach
            </div>
            <div class="form-group">
                <label class="form-label">休業日（特定日）</label>
                <div id="closedDatesContainer">
                    @forelse($closedDates as $d)
                    <div class="closed-date-item mb-1"><input type="date" name="closed_dates[]" class="form-control d-inline-block w-auto" value="{{ $d }}"> <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.closed-date-item').remove()"><i class="fas fa-times"></i></button></div>
                    @empty
                    <div class="closed-date-item mb-1"><input type="date" name="closed_dates[]" class="form-control d-inline-block w-auto"> <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.closed-date-item').remove()"><i class="fas fa-times"></i></button></div>
                    @endforelse
                </div>
                <button type="button" class="btn btn-sm btn-secondary mt-1" onclick="addClosedDate()"><i class="fas fa-plus"></i> 休業日を追加</button>
            </div>
            <div class="form-group">
                <label class="form-label">予約可能な日数（何日先まで）</label>
                <input type="number" name="advance_booking_days" class="form-control" value="{{ $advanceDays }}" min="1" max="365" required>
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button> <a href="{{ admin_route('admin.reservations') }}" class="btn btn-secondary">予約一覧へ</a></div>
        </div>
    </div>
</form>
<script>
function addSlot(){ var c=document.getElementById('timeSlotsContainer'); var d=document.createElement('div'); d.className='time-slot-item'; d.innerHTML='<input type="time" name="reservation_time_slots[]" class="form-control"> <button type="button" class="btn btn-sm btn-danger" onclick="this.closest(\'.time-slot-item\').remove()"><i class="fas fa-times"></i></button>'; c.appendChild(d); }
function addClosedDate(){ var c=document.getElementById('closedDatesContainer'); var d=document.createElement('div'); d.className='closed-date-item mb-1'; d.innerHTML='<input type="date" name="closed_dates[]" class="form-control d-inline-block w-auto"> <button type="button" class="btn btn-sm btn-danger" onclick="this.closest(\'.closed-date-item\').remove()"><i class="fas fa-times"></i></button>'; c.appendChild(d); }
</script>
@endsection
