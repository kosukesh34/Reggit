@php
    $reservation = $reservation ?? null;
    $isEdit = $reservation !== null;
    if ($isEdit) {
        $reservedAt = $reservation->reserved_at instanceof \Carbon\Carbon
            ? $reservation->reserved_at
            : \App\Util\DateUtil::nullableCarbon($reservation->reserved_at);
        $defaultDate = $reservedAt?->format('Y-m-d');
        $defaultTime = $reservedAt?->format('H:i');
        $defaultPeople = $reservation->number_of_people;
        $defaultCourseId = $reservation->reservation_course_id;
        $defaultNotes = $reservation->notes;
    } else {
        $defaultDate = now()->addDay()->format('Y-m-d');
        $defaultTime = '12:00';
        $defaultPeople = 2;
        $defaultCourseId = null;
        $defaultNotes = null;
    }
@endphp
<div class="reservation-form-grid">
    <div class="form-group">
        <label class="form-label" for="reserved_at_date">
            <i class="fas fa-calendar-day"></i> 予約日 <span class="form-required">*</span>
        </label>
        <input type="date" name="reserved_at_date" id="reserved_at_date" class="form-control"
               value="{{ old('reserved_at_date', $defaultDate) }}" required>
        @error('reserved_at_date')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="reserved_at_time">
            <i class="fas fa-clock"></i> 予約時間 <span class="form-required">*</span>
        </label>
        <input type="time" name="reserved_at_time" id="reserved_at_time" class="form-control"
               value="{{ old('reserved_at_time', $defaultTime) }}" required>
        @error('reserved_at_time')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="number_of_people">
            <i class="fas fa-users"></i> 人数 <span class="form-required">*</span>
        </label>
        <input type="number" name="number_of_people" id="number_of_people" class="form-control"
               value="{{ old('number_of_people', $defaultPeople) }}" min="1" max="100" required>
        @error('number_of_people')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>
    @if($courses->isNotEmpty())
    <div class="form-group"><label class="form-label" for="reservation_course_id">コース</label><select class="form-control" name="reservation_course_id" id="reservation_course_id"><option value="">指定なし</option>@foreach($courses as $course)<option value="{{ $course->id }}" {{ (string) old('reservation_course_id', $defaultCourseId) === (string) $course->id ? 'selected' : '' }}>{{ $course->name }} · {{ number_format($course->price) }}円</option>@endforeach</select></div>
    @endif
</div>

<div class="form-group">
    <label class="form-label" for="notes">
        <i class="fas fa-sticky-note"></i> 備考
    </label>
    <textarea name="notes" id="notes" class="form-control" rows="3"
              placeholder="アレルギー情報、座席の希望など">{{ old('notes', $defaultNotes) }}</textarea>
    @error('notes')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>
