@php
    use App\Enums\ScheduledDeliveryCategory;
    use App\Enums\ScheduledDeliveryContentType;
    use App\Enums\ScheduledDeliveryType;
    use App\Models\AfterRegistrationDelivery;

    $delivery = $view->delivery;
    $scheduleType = old('schedule_type', $delivery?->schedule_type ?? ScheduledDeliveryType::NEW_USER_MINUTES);
    $contentType = old('content_type', $delivery?->content_type ?? ScheduledDeliveryContentType::ANNOUNCEMENT);
    $category = ScheduledDeliveryType::categoryOf((string) $scheduleType);
    $params = is_array($delivery?->params) ? $delivery->params : [];
    $param = function (string $key, mixed $default = '') use ($params, $delivery) {
        if (old($key) !== null) {
            return old($key);
        }
        if (array_key_exists($key, $params)) {
            return $params[$key];
        }
        if ($key === 'delay_minutes') {
            return $delivery?->delay_minutes ?? 30;
        }
        return $default;
    };
@endphp

<div class="card mb-4">
    <div class="card-body">
        <div class="form-group">
            <span class="form-label">配信の種類 <span class="text-danger">*</span></span>
            <div class="sd-choice-grid">
                @foreach(ScheduledDeliveryCategory::getValues() as $value => $label)
                    <label class="sd-choice {{ $category === $value ? 'is-selected' : '' }}">
                        <input type="radio" name="category" value="{{ $value }}" {{ $category === $value ? 'checked' : '' }}>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <p class="form-text text-muted">条件達成はスタンプや入会などの到達時、予約は毎月1日に来月誕生日の人へ、のように日時で送ります</p>
        </div>

        <div class="form-group" id="scheduleTypeCondition">
            <span class="form-label">条件</span>
            <ul class="sd-type-list">
                @foreach(ScheduledDeliveryType::conditionValues() as $type)
                    <li>
                        <label>
                            <input type="radio" name="schedule_type" value="{{ $type }}" {{ $scheduleType === $type ? 'checked' : '' }}>
                            {{ ScheduledDeliveryType::getLabel($type) }}
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="form-group" id="scheduleTypeReservation">
            <span class="form-label">予約タイミング</span>
            <ul class="sd-type-list">
                @foreach(ScheduledDeliveryType::reservationValues() as $type)
                    <li>
                        <label>
                            <input type="radio" name="schedule_type" value="{{ $type }}" {{ $scheduleType === $type ? 'checked' : '' }}>
                            {{ ScheduledDeliveryType::getLabel($type) }}
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="sd-params">
            <div class="form-group sd-param" data-types="{{ ScheduledDeliveryType::NEW_USER_MINUTES }}">
                <label class="form-label" for="delay_minutes">入会から何分後</label>
                <select name="delay_minutes" id="delay_minutes" class="form-control">
                    @foreach(AfterRegistrationDelivery::NEW_USER_MINUTE_OPTIONS as $minutes)
                        <option value="{{ $minutes }}" {{ (int) $param('delay_minutes', 30) === $minutes ? 'selected' : '' }}>
                            {{ $minutes === 0 ? '即時' : $minutes.'分後' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group sd-param" data-types="{{ ScheduledDeliveryType::NEW_USER_DAYS }} {{ ScheduledDeliveryType::LAST_VISIT }}">
                <label class="form-label" for="delay_days">何日後</label>
                <input type="number" name="delay_days" id="delay_days" class="form-control" min="1" max="100" value="{{ $param('delay_days', 7) }}">
            </div>
            <div class="form-group sd-param" data-types="{{ ScheduledDeliveryType::VISIT_MILESTONE }}">
                <label class="form-label" for="visit_times">来店回数</label>
                <input type="number" name="visit_times" id="visit_times" class="form-control" min="1" max="100" value="{{ $param('visit_times', 5) }}">
                <label class="form-label mt-2" for="visit_after_days">その何日後</label>
                <input type="number" name="visit_after_days" id="visit_after_days" class="form-control" min="0" max="100" value="{{ $param('visit_after_days', 0) }}">
            </div>
            <div class="form-group sd-param" data-types="{{ ScheduledDeliveryType::RANK_UP_ARRIVAL }}">
                <label class="form-label" for="reach_rank">到達ランク</label>
                <select name="reach_rank" id="reach_rank" class="form-control">
                    <option value="">選択してください</option>
                    @foreach($view->rankOptions as $rank)
                        <option value="{{ $rank['key'] }}" {{ (string) $param('reach_rank') === $rank['key'] ? 'selected' : '' }}>{{ $rank['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group sd-param" data-types="{{ ScheduledDeliveryType::TOTAL_POINTS }}">
                <label class="form-label" for="total_points">累計ポイント</label>
                <input type="number" name="total_points" id="total_points" class="form-control" min="1" value="{{ $param('total_points', 1000) }}">
            </div>
            <div class="form-group sd-param" data-types="{{ ScheduledDeliveryType::TOTAL_STAMPS }}">
                <label class="form-label" for="total_stamps">累計スタンプ数</label>
                <input type="number" name="total_stamps" id="total_stamps" class="form-control" min="1" value="{{ $param('total_stamps', 10) }}">
            </div>
            <div class="form-group sd-param" data-types="{{ ScheduledDeliveryType::BIRTHDAY_DAYS_BEFORE }}">
                <label class="form-label" for="before_days">誕生日の何日前</label>
                <input type="number" name="before_days" id="before_days" class="form-control" min="1" max="100" value="{{ $param('before_days', 7) }}">
            </div>
            <div class="form-group sd-param" data-types="{{ ScheduledDeliveryType::WEEKLY }}">
                <label class="form-label" for="day_of_week">曜日</label>
                <select name="day_of_week" id="day_of_week" class="form-control">
                    @foreach(['日','月','火','水','木','金','土'] as $index => $label)
                        <option value="{{ $index }}" {{ (int) $param('day_of_week', 1) === $index ? 'selected' : '' }}>{{ $label }}曜日</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group sd-param" data-types="{{ ScheduledDeliveryType::MONTHLY }} {{ ScheduledDeliveryType::BIRTHDAY_NEXT_MONTH }} {{ ScheduledDeliveryType::LAST_MONTH_VISITS }}">
                <label class="form-label" for="day_of_month">毎月何日</label>
                <select name="day_of_month" id="day_of_month" class="form-control">
                    @for($day = 1; $day <= 31; $day++)
                        <option value="{{ $day }}" {{ (string) $param('day_of_month', 1) === (string) $day ? 'selected' : '' }}>{{ $day }}日</option>
                    @endfor
                    <option value="last_day" {{ $param('day_of_month') === 'last_day' ? 'selected' : '' }}>月末</option>
                </select>
            </div>
            <div class="form-group sd-param" data-types="{{ ScheduledDeliveryType::LAST_MONTH_VISITS }}">
                <label class="form-label" for="last_month_visit_min">前月の最低来店回数</label>
                <input type="number" name="last_month_visit_min" id="last_month_visit_min" class="form-control" min="1" max="100" value="{{ $param('last_month_visit_min', 1) }}">
            </div>
            <div class="form-group sd-param" id="pushClockFields" data-types="cron">
                <label class="form-label">配信時刻</label>
                <div class="sd-clock">
                    <select name="push_hour" class="form-control">
                        @for($hour = 0; $hour <= 23; $hour++)
                            <option value="{{ $hour }}" {{ (int) old('push_hour', $delivery?->push_hour ?? 10) === $hour ? 'selected' : '' }}>{{ sprintf('%02d', $hour) }}</option>
                        @endfor
                    </select>
                    <span>:</span>
                    <select name="push_minute" class="form-control">
                        @foreach([0, 10, 15, 20, 30, 40, 45, 50] as $minute)
                            <option value="{{ $minute }}" {{ (int) old('push_minute', $delivery?->push_minute ?? 0) === $minute ? 'selected' : '' }}>{{ sprintf('%02d', $minute) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="form-group">
            <span class="form-label">配信内容 <span class="text-danger">*</span></span>
            <div class="sd-choice-grid">
                @foreach(ScheduledDeliveryContentType::getValues() as $value => $label)
                    <label class="sd-choice {{ $contentType === $value ? 'is-selected' : '' }}">
                        <input type="radio" name="content_type" value="{{ $value }}" {{ $contentType === $value ? 'checked' : '' }}>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div id="announcementFields">
            <div class="form-group">
                <label for="title" class="form-label">お知らせタイトル</label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $delivery?->title) }}" maxlength="200">
            </div>
            <div class="form-group">
                <label for="body" class="form-label">お知らせ本文</label>
                <textarea name="body" id="body" class="form-control" rows="4" maxlength="5000">{{ old('body', $delivery?->body) }}</textarea>
            </div>
        </div>
        <div class="form-group">
            <label for="coupon_id" class="form-label" id="couponLabel">同時に配布するクーポン（任意）</label>
            <select name="coupon_id" id="coupon_id" class="form-control">
                <option value="">配布しない</option>
                @foreach($view->coupons as $coupon)
                    <option value="{{ $coupon->id }}" {{ (string) old('coupon_id', $delivery?->coupon_id) === (string) $coupon->id ? 'selected' : '' }}>{{ $coupon->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" id="notifyFields">
            @php
                $notifyOld = old('send_notification');
                $isNotify = $notifyOld === null
                    ? (bool) ($delivery?->send_notification ?? true)
                    : ($notifyOld === '1' || $notifyOld === 1 || $notifyOld === true);
            @endphp
            <label class="checkbox-label">
                <input type="hidden" name="send_notification" value="0">
                <input type="checkbox" name="send_notification" value="1" {{ $isNotify ? 'checked' : '' }}>
                <span>クーポン発行時に通知を送る</span>
            </label>
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $delivery?->is_active ?? true) ? 'checked' : '' }}>
                <span>有効にする</span>
            </label>
        </div>
    </div>
</div>
