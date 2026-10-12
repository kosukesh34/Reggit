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
    $categoryIcon = [
        ScheduledDeliveryCategory::CONDITION => 'fa-flag',
        ScheduledDeliveryCategory::RESERVATION => 'fa-calendar',
    ];
    $contentLabel = [
        ScheduledDeliveryContentType::ANNOUNCEMENT => 'お知らせ',
        ScheduledDeliveryContentType::COUPON_ONLY => 'クーポンのみ',
    ];
    $isCouponOnly = $contentType === ScheduledDeliveryContentType::COUPON_ONLY;
@endphp

<div class="announcement-delivery-admin scheduled-delivery-admin {{ $isCouponOnly ? 'is-coupon-only' : '' }}">
    <div class="ad-studio">
        <div class="ad-studio-main">
            <div class="andel-card" id="sd-trigger" role="region" aria-labelledby="sdTriggerTitle">
                <header class="andel-card-head">
                    <span class="andel-card-icon" aria-hidden="true"><i class="fas fa-bolt"></i></span>
                    <div><h2 id="sdTriggerTitle">きっかけ</h2></div>
                </header>
                <fieldset class="ad-delivery-fieldset">
                    <legend class="sd-legend">配信の種類</legend>
                    <div class="ad-timing-types">
                        @foreach(ScheduledDeliveryCategory::getValues() as $value => $label)
                            <label class="ad-timing-choice sd-choice {{ $category === $value ? 'is-selected' : '' }}">
                                <input type="radio" name="category" value="{{ $value }}" {{ $category === $value ? 'checked' : '' }}>
                                <span class="ad-choice-icon" aria-hidden="true"><i class="fas {{ $categoryIcon[$value] ?? 'fa-circle' }}"></i></span>
                                <span><strong>{{ $label }}</strong></span>
                                <span class="ad-choice-check">選択中</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>

            <div class="andel-card" id="sd-timing" role="region" aria-labelledby="sdTimingTitle">
                <header class="andel-card-head">
                    <span class="andel-card-icon" aria-hidden="true"><i class="fas fa-clock"></i></span>
                    <div><h2 id="sdTimingTitle">タイミング</h2></div>
                </header>

                <fieldset class="ad-delivery-fieldset" id="scheduleTypeCondition">
                    <legend class="sd-legend">条件</legend>
                    <div class="sd-type-grid">
                        @foreach(ScheduledDeliveryType::conditionValues() as $type)
                            <label class="ad-timing-choice sd-choice sd-type-choice {{ $scheduleType === $type ? 'is-selected' : '' }}">
                                <input type="radio" name="schedule_type" value="{{ $type }}" {{ $scheduleType === $type ? 'checked' : '' }}>
                                <span><strong>{{ ScheduledDeliveryType::getLabel($type) }}</strong></span>
                                <span class="ad-choice-check">選択中</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="ad-delivery-fieldset" id="scheduleTypeReservation">
                    <legend class="sd-legend">予約</legend>
                    <div class="sd-type-grid">
                        @foreach(ScheduledDeliveryType::reservationValues() as $type)
                            <label class="ad-timing-choice sd-choice sd-type-choice {{ $scheduleType === $type ? 'is-selected' : '' }}">
                                <input type="radio" name="schedule_type" value="{{ $type }}" {{ $scheduleType === $type ? 'checked' : '' }}>
                                <span><strong>{{ ScheduledDeliveryType::getLabel($type) }}</strong></span>
                                <span class="ad-choice-check">選択中</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="sd-params">
                    <div class="form-group sd-param mb-0" data-types="{{ ScheduledDeliveryType::NEW_USER_MINUTES }}">
                        <label class="form-label" for="delay_minutes">入会から何分後</label>
                        <select name="delay_minutes" id="delay_minutes" class="form-control">
                            @foreach(AfterRegistrationDelivery::NEW_USER_MINUTE_OPTIONS as $minutes)
                                <option value="{{ $minutes }}" {{ (int) $param('delay_minutes', 30) === $minutes ? 'selected' : '' }}>
                                    {{ $minutes === 0 ? '即時' : $minutes.'分後' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group sd-param mb-0" data-types="{{ ScheduledDeliveryType::NEW_USER_DAYS }} {{ ScheduledDeliveryType::LAST_VISIT }}">
                        <label class="form-label" for="delay_days">何日後</label>
                        <input type="number" name="delay_days" id="delay_days" class="form-control" min="1" max="100" value="{{ $param('delay_days', 7) }}">
                    </div>
                    <div class="form-group sd-param mb-0" data-types="{{ ScheduledDeliveryType::VISIT_MILESTONE }}">
                        <label class="form-label" for="visit_times">来店回数</label>
                        <input type="number" name="visit_times" id="visit_times" class="form-control" min="1" max="100" value="{{ $param('visit_times', 5) }}">
                        <label class="form-label mt-2" for="visit_after_days">その何日後</label>
                        <input type="number" name="visit_after_days" id="visit_after_days" class="form-control" min="0" max="100" value="{{ $param('visit_after_days', 0) }}">
                    </div>
                    <div class="form-group sd-param mb-0" data-types="{{ ScheduledDeliveryType::RANK_UP_ARRIVAL }}">
                        <label class="form-label" for="reach_rank">到達ランク</label>
                        <select name="reach_rank" id="reach_rank" class="form-control">
                            <option value="">選択してください</option>
                            @foreach($view->rankOptions as $rank)
                                <option value="{{ $rank['key'] }}" {{ (string) $param('reach_rank') === $rank['key'] ? 'selected' : '' }}>{{ $rank['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group sd-param mb-0" data-types="{{ ScheduledDeliveryType::TOTAL_POINTS }}">
                        <label class="form-label" for="total_points">累計ポイント</label>
                        <input type="number" name="total_points" id="total_points" class="form-control" min="1" value="{{ $param('total_points', 1000) }}">
                    </div>
                    <div class="form-group sd-param mb-0" data-types="{{ ScheduledDeliveryType::TOTAL_STAMPS }}">
                        <label class="form-label" for="total_stamps">累計スタンプ数</label>
                        <input type="number" name="total_stamps" id="total_stamps" class="form-control" min="1" value="{{ $param('total_stamps', 10) }}">
                    </div>
                    <div class="form-group sd-param mb-0" data-types="{{ ScheduledDeliveryType::BIRTHDAY_DAYS_BEFORE }}">
                        <label class="form-label" for="before_days">誕生日の何日前</label>
                        <input type="number" name="before_days" id="before_days" class="form-control" min="1" max="100" value="{{ $param('before_days', 7) }}">
                    </div>
                    <div class="form-group sd-param mb-0" data-types="{{ ScheduledDeliveryType::WEEKLY }}">
                        <label class="form-label" for="day_of_week">曜日</label>
                        <select name="day_of_week" id="day_of_week" class="form-control">
                            @foreach(['日','月','火','水','木','金','土'] as $index => $label)
                                <option value="{{ $index }}" {{ (int) $param('day_of_week', 1) === $index ? 'selected' : '' }}>{{ $label }}曜日</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group sd-param mb-0" data-types="{{ ScheduledDeliveryType::MONTHLY }} {{ ScheduledDeliveryType::BIRTHDAY_NEXT_MONTH }} {{ ScheduledDeliveryType::LAST_MONTH_VISITS }}">
                        <label class="form-label" for="day_of_month">毎月何日</label>
                        <select name="day_of_month" id="day_of_month" class="form-control">
                            @for($day = 1; $day <= 31; $day++)
                                <option value="{{ $day }}" {{ (string) $param('day_of_month', 1) === (string) $day ? 'selected' : '' }}>{{ $day }}日</option>
                            @endfor
                            <option value="last_day" {{ $param('day_of_month') === 'last_day' ? 'selected' : '' }}>月末</option>
                        </select>
                    </div>
                    <div class="form-group sd-param mb-0" data-types="{{ ScheduledDeliveryType::LAST_MONTH_VISITS }}">
                        <label class="form-label" for="last_month_visit_min">前月の最低来店回数</label>
                        <input type="number" name="last_month_visit_min" id="last_month_visit_min" class="form-control" min="1" max="100" value="{{ $param('last_month_visit_min', 1) }}">
                    </div>
                    <div class="form-group sd-param mb-0" id="pushClockFields" data-types="cron">
                        <span class="form-label" id="pushClockLabel">配信時刻</span>
                        <div class="sd-clock" role="group" aria-labelledby="pushClockLabel">
                            <select name="push_hour" class="form-control" aria-label="時">
                                @for($hour = 0; $hour <= 23; $hour++)
                                    <option value="{{ $hour }}" {{ (int) old('push_hour', $delivery?->push_hour ?? 10) === $hour ? 'selected' : '' }}>{{ sprintf('%02d', $hour) }}</option>
                                @endfor
                            </select>
                            <span aria-hidden="true">:</span>
                            <select name="push_minute" class="form-control" aria-label="分">
                                @foreach([0, 10, 15, 20, 30, 40, 45, 50] as $minute)
                                    <option value="{{ $minute }}" {{ (int) old('push_minute', $delivery?->push_minute ?? 0) === $minute ? 'selected' : '' }}>{{ sprintf('%02d', $minute) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="andel-card" id="sd-audience" role="region" aria-labelledby="sdAudienceTitle">
                <header class="andel-card-head">
                    <span class="andel-card-icon" aria-hidden="true"><i class="fas fa-filter"></i></span>
                    <div><h2 id="sdAudienceTitle">対象の条件</h2></div>
                </header>
                <div id="segmentBlock" class="ad-segment-panel">
                    @include('admin.partials._delivery_segment_filters', [
                        'savedSegment' => is_array($delivery?->params['segment_input'] ?? null) ? $delivery->params['segment_input'] : [],
                    ])
                </div>
            </div>

            <div class="andel-card" id="sd-content" role="region" aria-labelledby="sdContentTitle">
                <header class="andel-card-head">
                    <span class="andel-card-icon" aria-hidden="true"><i class="fas fa-paper-plane"></i></span>
                    <div><h2 id="sdContentTitle">届ける内容</h2></div>
                </header>
                <fieldset class="ad-delivery-fieldset">
                    <legend class="sd-legend">種類</legend>
                    <div class="ad-timing-types">
                        @foreach(ScheduledDeliveryContentType::getValues() as $value => $label)
                            <label class="ad-timing-choice sd-choice {{ $contentType === $value ? 'is-selected' : '' }}">
                                <input type="radio" name="content_type" value="{{ $value }}" {{ $contentType === $value ? 'checked' : '' }}>
                                <span><strong>{{ $contentLabel[$value] ?? $label }}</strong></span>
                                <span class="ad-choice-check">選択中</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div id="announcementFields">
                    <div class="form-group">
                        <label for="title" class="form-label">お知らせタイトル <span class="ad-required">必須</span></label>
                        <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $delivery?->title) }}" maxlength="200" placeholder="例：ご入会ありがとうございます">
                        @error('title')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="body" class="form-label">お知らせ本文 <span class="ad-optional">任意</span></label>
                        <textarea name="body" id="body" class="form-control" rows="4" maxlength="5000" placeholder="会員に伝えたい内容を書いてください。">{{ old('body', $delivery?->body) }}</textarea>
                        @error('body')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="form-group">
                    <label for="coupon_id" class="form-label">
                        <span id="couponLabel">{{ $isCouponOnly ? '配布するクーポン' : '同時に配布するクーポン' }}</span>
                        <span class="ad-optional sd-coupon-optional">任意</span>
                        <span class="ad-required sd-coupon-required">必須</span>
                    </label>
                    <select name="coupon_id" id="coupon_id" class="form-control">
                        <option value="">配布しない</option>
                        @foreach($view->coupons as $coupon)
                            <option value="{{ $coupon->id }}" {{ (string) old('coupon_id', $delivery?->coupon_id) === (string) $coupon->id ? 'selected' : '' }}>{{ $coupon->name }}</option>
                        @endforeach
                    </select>
                    @error('coupon_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="ad-option-grid">
                    <div id="notifyFields">
                        @php
                            $notifyOld = old('send_notification');
                            $isNotify = $notifyOld === null
                                ? (bool) ($delivery?->send_notification ?? true)
                                : ($notifyOld === '1' || $notifyOld === 1 || $notifyOld === true);
                        @endphp
                        <label class="ad-pin-option">
                            <input type="hidden" name="send_notification" value="0">
                            <input type="checkbox" name="send_notification" value="1" {{ $isNotify ? 'checked' : '' }}>
                            <span class="ad-pin-icon" aria-hidden="true"><i class="fas fa-bell"></i></span>
                            <span><strong>発行時に通知する</strong></span>
                            <span class="ad-check-ui" aria-hidden="true"><i class="fas fa-check"></i></span>
                        </label>
                    </div>
                    <label class="ad-pin-option">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $delivery?->is_active ?? true) ? 'checked' : '' }}>
                        <span class="ad-pin-icon" aria-hidden="true"><i class="fas fa-toggle-on"></i></span>
                        <span><strong>配信を有効にする</strong></span>
                        <span class="ad-check-ui" aria-hidden="true"><i class="fas fa-check"></i></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="ad-delivery-actions">
            <div class="ad-form-footer">

                <button type="submit" class="btn btn-primary">
                    @if($delivery)
                        <i class="fas fa-save" aria-hidden="true"></i> 更新する
                    @else
                        <i class="fas fa-plus" aria-hidden="true"></i> 追加する
                    @endif
                </button>
            </div>
        </div>
    </div>
</div>
