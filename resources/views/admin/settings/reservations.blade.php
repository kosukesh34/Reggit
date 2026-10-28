@extends('admin.layout')
@section('title', '予約')
@section('page-title', '予約')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a><span class="breadcrumb-sep">/</span><a href="{{ admin_route('admin.reservations') }}">予約</a><span class="breadcrumb-sep">/</span><span>予約</span></nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-calendar-check"></i> 予約</h1>
</div>
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
        <div class="card-header"><h2 class="card-title">受付日時と予約枠</h2></div>
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
            @php
                $rules = $settings['rules'];
                $leadLabels = [0 => '予約時刻まで', 30 => '30分前まで', 60 => '1時間前まで', 120 => '2時間前まで', 180 => '3時間前まで', 360 => '6時間前まで', 720 => '12時間前まで', 1440 => '24時間前まで'];
                $deadlineLabels = [0 => '予約時刻まで', 1 => '1時間前まで', 3 => '3時間前まで', 6 => '6時間前まで', 12 => '12時間前まで', 24 => '前日（24時間前）まで', 48 => '2日前まで', 72 => '3日前まで'];
            @endphp
            <div class="form-group">
                <label class="form-label" for="reservation_capacity_by_people">予約枠上限の数え方</label>
                <select name="reservation_capacity_by_people" id="reservation_capacity_by_people" class="form-control">
                    <option value="0" {{ ! old('reservation_capacity_by_people', $rules->capacityByPeople) ? 'selected' : '' }}>予約の件数（1件＝1枠。サロン・個室など）</option>
                    <option value="1" {{ old('reservation_capacity_by_people', $rules->capacityByPeople) ? 'selected' : '' }}>人数の合計（4名の予約＝4席。飲食店の席数など）</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="reservation_max_party_size">1回の予約で選べる最大人数</label>
                <input type="number" name="reservation_max_party_size" id="reservation_max_party_size" class="form-control" value="{{ old('reservation_max_party_size', $rules->maxPartySize) }}" min="1" max="{{ \App\Domain\Reservation\ValueObjects\ReservationRules::MAX_PARTY_SIZE_LIMIT }}" required>
            </div>
            <div class="form-group">
                <label class="checkbox-label"><input type="hidden" name="reservation_same_day" value="0"><input type="checkbox" name="reservation_same_day" value="1" {{ old('reservation_same_day', $rules->sameDay) ? 'checked' : '' }}> 当日の予約を受け付ける</label>
                <p class="text-muted">オフのときは翌日以降の予約だけを受け付けます。</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="reservation_lead_minutes">予約の受付締め切り</label>
                <select name="reservation_lead_minutes" id="reservation_lead_minutes" class="form-control">
                    @foreach($leadLabels as $minutes => $label)
                    <option value="{{ $minutes }}" {{ (int) old('reservation_lead_minutes', $rules->leadMinutes) === $minutes ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-muted">締め切りを過ぎた時間帯は、会員の画面に表示しません。</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="reservation_change_deadline_hours">会員による変更・キャンセルの締め切り</label>
                <select name="reservation_change_deadline_hours" id="reservation_change_deadline_hours" class="form-control">
                    @foreach($deadlineLabels as $hours => $label)
                    <option value="{{ $hours }}" {{ (int) old('reservation_change_deadline_hours', $rules->changeDeadlineHours) === $hours ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-muted">締め切り後は会員の画面から変更・キャンセルできず、店舗への連絡を案内します。管理画面からはいつでも変更できます。</p>
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
            @php
                $reminder = $settings['reminder'];
                $hourLabels = [0 => '送らない', 1 => '1時間前', 3 => '3時間前', 6 => '6時間前', 24 => '前日（24時間前）', 48 => '2日前（48時間前）'];
            @endphp
            <fieldset class="form-group">
                <legend class="form-label">予約リマインド</legend>
                <div class="form-inline-flex">
                    <label for="reservation_reminder_hours">1回目</label>
                    <select name="reservation_reminder_hours" id="reservation_reminder_hours" class="form-control">
                        @foreach($hourLabels as $hours => $label)
                        <option value="{{ $hours }}" {{ (int) old('reservation_reminder_hours', $reminder->firstHours) === $hours ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label for="reservation_reminder_second_hours">2回目</label>
                    <select name="reservation_reminder_second_hours" id="reservation_reminder_second_hours" class="form-control">
                        @foreach($hourLabels as $hours => $label)
                        <option value="{{ $hours }}" {{ (int) old('reservation_reminder_second_hours', $reminder->secondHours) === $hours ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-muted">予約した会員へ、お知らせとLINEでリマインドを送ります（例: 1回目「前日」、2回目「3時間前」）。その時間より後に入った予約には、その回は送りません。</p>
                <label class="form-label" for="reservation_reminder_message">リマインドの本文</label>
                <textarea name="reservation_reminder_message" id="reservation_reminder_message" class="form-control" rows="3" maxlength="500" placeholder="{{ '{店舗}で'.\App\Domain\Reservation\Policies\ReservationReminderPolicy::DEFAULT_MESSAGE }}">{{ old('reservation_reminder_message', $reminder->message) }}</textarea>
                <p class="text-muted">空欄なら標準の本文です。差し込み：@foreach(\App\Domain\Reservation\Policies\ReservationReminderPolicy::PLACEHOLDERS as $placeholder => $label)<code>{{ $placeholder }}</code> {{ $label }}{{ $loop->last ? '' : '、' }}@endforeach</p>
            </fieldset>
            <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button> <x-back-link :href="admin_route('admin.reservations')" label="予約一覧へ戻る" /></div>
        </div>
    </div>
</form>
<div class="card" style="margin-top:24px">
    <div class="card-header"><h2 class="card-title">予約コース</h2></div>
    <div class="card-body">
        <form action="{{ admin_route('admin.settings.reservations.courses.store') }}" method="POST">
            @csrf
            <div class="settings-staff-fields">
                <div class="form-group"><label for="course-name" class="form-label">コース名</label><input id="course-name" class="form-control" name="name" maxlength="120" required></div>
                <div class="form-group"><label for="course-price" class="form-label">料金（円）</label><input id="course-price" class="form-control" type="number" name="price" min="0" value="0" required></div>
                <div class="form-group"><label for="course-description" class="form-label">説明</label><input id="course-description" class="form-control" name="description" maxlength="1000"></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">コースを追加</button></div>
        </form>
        @foreach($courses as $course)
            <details class="settings-staff-member">
                <summary><strong>{{ $course->name }}</strong><span>{{ number_format($course->price) }}円 · {{ $course->is_active ? '受付中' : '停止中' }}</span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
                <form action="{{ admin_route('admin.settings.reservations.courses.update', ['id' => $course->id]) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="settings-staff-fields">
                        <div class="form-group"><label class="form-label" for="course-name-{{ $course->id }}">コース名</label><input class="form-control" id="course-name-{{ $course->id }}" name="name" value="{{ $course->name }}" required></div>
                        <div class="form-group"><label class="form-label" for="course-price-{{ $course->id }}">料金（円）</label><input class="form-control" type="number" id="course-price-{{ $course->id }}" name="price" min="0" value="{{ $course->price }}" required></div>
                        <div class="form-group"><label class="form-label" for="course-description-{{ $course->id }}">説明</label><input class="form-control" id="course-description-{{ $course->id }}" name="description" value="{{ $course->description }}"></div>
                    </div>
                    <label class="checkbox-label"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" {{ $course->is_active ? 'checked' : '' }}> 予約を受け付ける</label>
                    <div class="form-actions"><button class="btn btn-primary" type="submit">変更を保存</button></div>
                </form>
            </details>
        @endforeach
    </div>
</div>
<script>
function addSlot(){ var c=document.getElementById('timeSlotsContainer'); var d=document.createElement('div'); d.className='time-slot-item'; d.innerHTML='<input type="time" name="reservation_time_slots[]" class="form-control"> <button type="button" class="btn btn-sm btn-danger" onclick="this.closest(\'.time-slot-item\').remove()"><i class="fas fa-times"></i></button>'; c.appendChild(d); }
function addClosedDate(){ var c=document.getElementById('closedDatesContainer'); var d=document.createElement('div'); d.className='closed-date-item mb-1'; d.innerHTML='<input type="date" name="closed_dates[]" class="form-control d-inline-block w-auto"> <button type="button" class="btn btn-sm btn-danger" onclick="this.closest(\'.closed-date-item\').remove()"><i class="fas fa-times"></i></button>'; c.appendChild(d); }
</script>
@endsection
