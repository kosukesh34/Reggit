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
    // 送る相手。保存済みの配信は、顧客指定 → 絞り込み条件あり → 全会員 の順で判定する
    $audienceType = old('audience_type', match (true) {
        ! empty($params['target_member_ids']) => 'members',
        ! empty($params['segment_criteria']) => 'segment',
        default => 'all',
    });
    $audienceChoices = [
        'all' => ['全会員', 'fa-users'],
        'segment' => ['絞り込み', 'fa-filter'],
        'members' => ['顧客指定', 'fa-user-check'],
    ];
@endphp
@inject('memberIdentifier', 'App\Support\AdminMemberIdentifier')

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
                                <span class="ad-choice-check" aria-hidden="true"><i class="fas fa-check"></i></span>
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
                                <span class="ad-choice-check" aria-hidden="true"><i class="fas fa-check"></i></span>
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
                                <span class="ad-choice-check" aria-hidden="true"><i class="fas fa-check"></i></span>
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
                    @php
                        $weatherTypes = implode(' ', ScheduledDeliveryType::WEATHER_TYPES);
                        $temperatureCondition = $param('temperature_condition', AfterRegistrationDelivery::TEMPERATURE_ABOVE);
                        $weatherTarget = $param('weather_target', $params['target'] ?? '');
                        $requiresApproval = old('requires_approval', $params['requires_approval'] ?? 1);
                        // 文章の途中に置く数値入力。閾値は初期値を持たない（仕様）ので、例は placeholder で見せる
                        $inlineNumber = fn (string $name, int $min, int $max, string $label, string $example) => new \Illuminate\Support\HtmlString(sprintf(
                            '<input type="number" name="%1$s" id="%1$s" class="form-control sd-inline-input" min="%2$d" max="%3$d" value="%4$s" placeholder="例 %5$s" aria-label="%6$s（%2$d〜%3$d）">',
                            e($name), $min, $max, e((string) $param($name)), e($example), e($label),
                        ));
                    @endphp
                    <div class="sd-param sd-weather mb-0" data-types="{{ $weatherTypes }}">
                        <p class="sd-weather-lead sd-param" data-types="{{ ScheduledDeliveryType::WEATHER_RAIN }}"><i class="fas fa-cloud-rain" aria-hidden="true"></i> 店舗の天気予報を15分ごとに確認し、営業時間内に雨が続きそうな日に自動で送ります。クーポンはその営業日だけ使えます。</p>
                        <p class="sd-weather-lead sd-param" data-types="{{ ScheduledDeliveryType::WEATHER_TEMPERATURE }}"><i class="fas fa-temperature-half" aria-hidden="true"></i> 店舗の天気予報を15分ごとに確認し、営業時間内の気温が条件に合う日に自動で送ります。クーポンはその営業日だけ使えます。</p>

                        <section class="sd-weather-step" aria-labelledby="sdWeatherStore">
                            <h3 id="sdWeatherStore"><span>1</span>天気を見る店舗</h3>
                            <select name="store_id" id="store_id" class="form-control" aria-labelledby="sdWeatherStore">
                                <option value="">選択してください</option>
                                @foreach($view->weatherStores as $store)
                                    <option value="{{ $store->id }}" {{ (int) $param('store_id') === $store->id ? 'selected' : '' }}>{{ $store->name }}{{ $store->latitude === null ? '（座標が未設定のため判定しません）' : '' }}</option>
                                @endforeach
                            </select>
                            <small class="form-text">この店舗の位置の予報と営業時間で判定します。位置（座標）は「利用店舗」で設定します。</small>
                            @error('store_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </section>

                        <section class="sd-weather-step" aria-labelledby="sdWeatherCondition">
                            <h3 id="sdWeatherCondition"><span>2</span>送る日の条件</h3>
                            <div class="sd-param" data-types="{{ ScheduledDeliveryType::WEATHER_RAIN }}">
                                <p class="sd-sentence">1時間ごとの降水確率が {{ $inlineNumber('min_precipitation_probability', 10, 100, '降水確率（%）', '50') }} %以上で、雨量が <input type="number" name="min_rain_mm" id="min_rain_mm" class="form-control sd-inline-input" min="0.1" max="50" step="0.1" value="{{ $param('min_rain_mm') }}" placeholder="任意" aria-label="1時間あたりの雨量（mm、0.1〜50、空欄なら見ない）" aria-describedby="minRainMmHint"> mm以上の時間が、{{ $inlineNumber('min_rain_hours', 1, 24, '雨が続く時間（時間）', '2') }} 時間以上続く日</p>
                                <small class="form-text" id="minRainMmHint">雨量は1時間あたりの量で、空欄なら見ません（降水確率だけで判定）。目安：1mm＝傘が必要な雨、3mm＝しっかり降る雨、10mm＝強い雨。</small>
                                <small class="form-text">続く時間を設けると、少しだけ降るにわか雨の日には送りません。</small>
                                @error('min_precipitation_probability')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                @error('min_rain_mm')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                @error('min_rain_hours')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>
                            <div class="sd-param" data-types="{{ ScheduledDeliveryType::WEATHER_TEMPERATURE }}">
                                <div class="sd-radio-cards" role="radiogroup" aria-label="気温の条件">
                                    <label class="sd-radio-card">
                                        <input type="radio" name="temperature_condition" value="{{ AfterRegistrationDelivery::TEMPERATURE_ABOVE }}" {{ $temperatureCondition !== AfterRegistrationDelivery::TEMPERATURE_BELOW ? 'checked' : '' }}>
                                        <i class="fas fa-sun" aria-hidden="true"></i><span><strong>暑い日</strong><small>最高気温が基準以上</small></span>
                                    </label>
                                    <label class="sd-radio-card">
                                        <input type="radio" name="temperature_condition" value="{{ AfterRegistrationDelivery::TEMPERATURE_BELOW }}" {{ $temperatureCondition === AfterRegistrationDelivery::TEMPERATURE_BELOW ? 'checked' : '' }}>
                                        <i class="fas fa-snowflake" aria-hidden="true"></i><span><strong>寒い日</strong><small>最低気温が基準以下</small></span>
                                    </label>
                                </div>
                                <p class="sd-sentence">営業時間内の<span class="sd-temp-above">最高</span><span class="sd-temp-below">最低</span>気温が {{ $inlineNumber('temperature', -20, 45, '基準の気温（℃）', '30') }} ℃<span class="sd-temp-above">以上</span><span class="sd-temp-below">以下</span>になる日</p>
                                @error('temperature_condition')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                @error('temperature')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>
                        </section>

                        <section class="sd-weather-step" aria-labelledby="sdWeatherTime">
                            <h3 id="sdWeatherTime"><span>3</span>送る時間帯</h3>
                            <p class="sd-sentence">開店の {{ $inlineNumber('check_hours_before_open', 0, 3, '判定を始める時刻（開店の何時間前）', '1') }} 時間前から判定を始め、閉店の {{ $inlineNumber('cutoff_hours_before_close', 0, 23, '送る締め切り（閉店の何時間前）', '3') }} 時間前を過ぎたら送りません</p>
                            <small class="form-text">0なら開店と同時に判定を始めます。閉店間際に届いても使えないため、締め切りを設けます。</small>
                            @error('check_hours_before_open')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            @error('cutoff_hours_before_close')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </section>

                        <section class="sd-weather-step" aria-labelledby="sdWeatherLimit">
                            <h3 id="sdWeatherLimit"><span>4</span>回数と対象の会員</h3>
                            <p class="sd-sentence">1週間（月〜日）に最大 {{ $inlineNumber('weekly_limit', 1, 7, '週の上限（回）', '2') }} 回まで</p>
                            @error('weekly_limit')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            <div class="sd-radio-cards" role="radiogroup" aria-label="対象の会員">
                                <label class="sd-radio-card">
                                    <input type="radio" name="weather_target" value="{{ AfterRegistrationDelivery::WEATHER_TARGET_STORE_MEMBERS }}" {{ $weatherTarget !== AfterRegistrationDelivery::WEATHER_TARGET_ALL_MEMBERS ? 'checked' : '' }}>
                                    <i class="fas fa-store" aria-hidden="true"></i><span><strong>この店舗の会員</strong><small>この店舗を利用している会員</small></span>
                                </label>
                                <label class="sd-radio-card">
                                    <input type="radio" name="weather_target" value="{{ AfterRegistrationDelivery::WEATHER_TARGET_ALL_MEMBERS }}" {{ $weatherTarget === AfterRegistrationDelivery::WEATHER_TARGET_ALL_MEMBERS ? 'checked' : '' }}>
                                    <i class="fas fa-users" aria-hidden="true"></i><span><strong>全店舗の会員</strong><small>店舗に関係なく全員</small></span>
                                </label>
                            </div>
                            <label class="ad-pin-option sd-approval">
                                <input type="hidden" name="requires_approval" value="0">
                                <input type="checkbox" name="requires_approval" value="1" {{ (string) $requiresApproval === '1' ? 'checked' : '' }}>
                                <span class="ad-pin-icon" aria-hidden="true"><i class="fas fa-envelope"></i></span>
                                <span><strong>送る前にメールで承認を求める</strong></span>
                                <span class="ad-check-ui" aria-hidden="true"><i class="fas fa-check"></i></span>
                            </label>
                            <small class="form-text">条件に合うと管理者にメールが届き、30分以内に管理画面で承認したときだけ送ります。</small>
                        </section>
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
                    <span class="andel-card-icon" aria-hidden="true"><i class="fas fa-user-group"></i></span>
                    <div><h2 id="sdAudienceTitle">送る相手</h2></div>
                </header>
                <fieldset class="ad-delivery-fieldset">
                    <legend class="sd-legend">きっかけに当てはまる会員のうち、誰に送るか</legend>
                    <div class="ad-timing-types sd-audience-types">
                        @foreach($audienceChoices as $value => [$label, $icon])
                            <label class="ad-timing-choice sd-choice {{ $audienceType === $value ? 'is-selected' : '' }}">
                                <input type="radio" name="audience_type" value="{{ $value }}" {{ $audienceType === $value ? 'checked' : '' }}>
                                <span class="ad-choice-icon" aria-hidden="true"><i class="fas {{ $icon }}"></i></span>
                                <span><strong>{{ $label }}</strong></span>
                                <span class="ad-choice-check" aria-hidden="true"><i class="fas fa-check"></i></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                @error('audience_type')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                <div id="segmentBlock" class="ad-segment-panel sd-audience-panel" data-audience="segment">
                    @include('admin.partials._delivery_segment_filters', [
                        'savedSegment' => is_array($delivery?->params['segment_input'] ?? null) ? $delivery->params['segment_input'] : [],
                    ])
                </div>
                <div class="form-group mb-0 sd-audience-panel" data-audience="members">
                    <div class="sd-label-help">
                        <label class="form-label mb-0" for="target_member_numbers">{{ $memberIdentifier->label() }} <span class="ad-required">必須</span></label>
                        <button type="button" class="admin-help" data-tooltip="{{ $memberIdentifier->label() }}を1行に1つずつ入力します。カンマ区切りでも読み込めます。&#10;&#10;例：&#10;{{ $memberIdentifier->usesId() ? '1234' : 'M00001234' }}&#10;{{ $memberIdentifier->usesId() ? '5678' : 'M00005678' }}&#10;&#10;会員一覧のCSVから{{ $memberIdentifier->label() }}の列をそのまま貼り付けられます。最大{{ \App\Services\Admin\AfterRegistrationDeliveryAdminService::MAX_TARGET_MEMBERS }}人まで。" aria-label="入力の形式を見る">?</button>
                    </div>
                    <textarea name="target_member_numbers" id="target_member_numbers" class="form-control" rows="5" placeholder="{{ $memberIdentifier->usesId() ? '1234' : 'M00001234' }}&#10;{{ $memberIdentifier->usesId() ? '5678' : 'M00005678' }}">{{ old('target_member_numbers', implode("\n", (array) ($params['target_member_numbers'] ?? []))) }}</textarea>
                    @error('target_member_numbers')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
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
                                <span class="ad-choice-check" aria-hidden="true"><i class="fas fa-check"></i></span>
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
            <div class="andel-card" id="sd-period" role="region" aria-labelledby="sdPeriodTitle">
                <header class="andel-card-head">
                    <span class="andel-card-icon" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
                    <div><h2 id="sdPeriodTitle">配信期間</h2></div>
                </header>
                <p class="form-text">この期間だけ配信します（開始日時・終了日時を含む）。空欄なら期限なしです。例：9月中に 10/1 0:00〜10/31 23:59 を設定すると、10月だけ配信されます。</p>
                <div class="ad-filter-grid ad-filter-grid--2">
                    <div class="form-group mb-0">
                        <label class="form-label" for="starts_at">開始日時 <span class="ad-optional">任意</span></label>
                        <input type="datetime-local" name="starts_at" id="starts_at" class="form-control" value="{{ old('starts_at', $delivery?->starts_at?->format('Y-m-d\TH:i')) }}">
                        @error('starts_at')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label" for="ends_at">終了日時 <span class="ad-optional">任意</span></label>
                        <input type="datetime-local" name="ends_at" id="ends_at" class="form-control" value="{{ old('ends_at', $delivery?->ends_at?->format('Y-m-d\TH:i')) }}">
                        @error('ends_at')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
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
