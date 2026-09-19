@extends('admin.layout')

@section('title', 'クーポン配信')
@section('page-title', 'クーポン配信')
@section('page-kind', 'coupon-delivery')

@section('content')
@php
    $selectedCouponId = (int) old('coupon_id', old('delivery_coupon_id', request('coupon_id')));
    $hasSelectedCoupon = $selectedCouponId > 0 && ($view->coupons ?? collect())->contains('id', $selectedCouponId);
    $selectedMemberIds = collect((array) old('target_member_ids', []))->map(fn ($id) => (int) $id)->filter()->values();
    $selectedBirthdayMonths = collect((array) old(
        'segment_birthday_months',
        filled(old('segment_birthday_month')) ? [old('segment_birthday_month')] : []
    ))->map(fn ($month) => (int) $month)->filter(fn ($month) => $month >= 1 && $month <= 12)->unique()->values();
    $selectedTagIds = collect((array) old('segment_tag_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();
    $selectedRfmSegments = collect((array) old('segment_rfm_segments', []))->map(fn ($key) => (string) $key)->filter()->unique()->values();
    $legacyDeliveryType = old('delivery_type', '');
    $oldAudienceType = old('audience_type', match (true) {
        in_array($legacyDeliveryType, \App\Enums\CouponDeliveryType::personalValues(), true) => 'personal',
        in_array($legacyDeliveryType, array_merge(
            \App\Enums\CouponDeliveryType::attributeSegmentValues(),
            \App\Enums\CouponDeliveryType::segmentValues(),
            \App\Enums\CouponDeliveryType::birthdayValues(),
            \App\Enums\CouponDeliveryType::newMemberValues(),
            \App\Enums\CouponDeliveryType::inactiveValues(),
            \App\Enums\CouponDeliveryType::rankValues(),
            \App\Enums\CouponDeliveryType::stampValues(),
            \App\Enums\CouponDeliveryType::daysAfterVisitValues(),
            [\App\Enums\CouponDeliveryType::EVENT_RANK_UP_ARRIVAL],
        ), true) => 'segment',
        default => 'immediate_bulk',
    });
    $oldDeliveryTiming = old('delivery_timing', in_array($legacyDeliveryType, \App\Enums\CouponDeliveryType::scheduledValues(), true)
        ? 'scheduled'
        : 'immediate');
    $advancedSegmentOpen = collect([
        'segment_visit_count_min', 'segment_visit_count_max', 'segment_lifetime_points_min',
        'segment_lifetime_points_max', 'segment_last_visit_mode', 'segment_sex', 'segment_rank',
        'segment_product_id', 'segment_condition', 'stamp_card_id', 'stamp_count_min',
    ])->contains(fn ($field) => filled(old($field))) || (bool) old('event_rank_up');
@endphp

<div class="announcement-delivery-admin">
    <nav class="breadcrumb" aria-label="パンくず">
        <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
        <span class="breadcrumb-sep">/</span>
        <a href="{{ admin_route('admin.coupons') }}">クーポン</a>
        <span class="breadcrumb-sep">/</span>
        <span>新規配信</span>
    </nav>

    @if($errors->any())
        <div class="alert alert-error" role="alert">
            <strong>入力内容を確認してください</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ admin_route('admin.coupons.bulk-issue.store') }}" method="POST" id="deliveryForm">
        @csrf
        <input type="hidden" name="delivery_type" id="delivery_type" value="{{ old('delivery_type', $view->defaultDeliveryType) }}">
        <div class="ad-studio">
            <div class="ad-studio-main">
                <div class="andel-card" role="region" id="delivery-content" aria-label="クーポン">
                    @if(($view->coupons ?? collect())->isEmpty())
                        <div class="ad-empty-coupon">
                            <span><i class="fas fa-ticket-alt" aria-hidden="true"></i></span>
                            <div><strong>配布できるクーポンがありません</strong><p>先に有効なクーポンを作成してください。</p></div>
                            <a href="{{ admin_route('admin.coupons.create') }}" class="btn btn-outline-primary">クーポンを作成</a>
                        </div>
                    @else
                        <div class="form-group mb-0">
                            <label for="delivery_coupon_id" class="form-label">クーポン <span class="ad-required">必須</span></label>
                            <select name="coupon_id" id="delivery_coupon_id" class="form-control ad-control-lg" required>
                                <option value="">選択してください</option>
                                @foreach($view->coupons as $coupon)
                                    @php
                                        $isExpired = $coupon->end_at !== null && $coupon->end_at->isPast();
                                        $benefit = $coupon->type === 'percent'
                                            ? rtrim(rtrim((string) $coupon->value, '0'), '.').'％OFF'
                                            : number_format((float) $coupon->value).'円OFF';
                                        $period = $coupon->end_at ? $coupon->end_at->format('Y/m/d').'まで' : '期限なし';
                                    @endphp
                                    <option value="{{ $coupon->id }}" data-benefit="{{ $benefit }}" data-period="{{ $period }}" {{ $selectedCouponId === (int) $coupon->id ? 'selected' : '' }} {{ $isExpired ? 'disabled' : '' }}>{{ $coupon->name ?? '—' }}（{{ $coupon->code }}）{{ $isExpired ? ' — 期限切れです' : '' }}</option>
                                @endforeach
                            </select>
                            <p class="ad-field-help"><a href="{{ admin_route('admin.coupons.create') }}">新規作成</a></p>
                        </div>
                        <div class="ad-selected-coupon" id="selectedCouponPreview" hidden>
                            <span><i class="fas fa-ticket-alt" aria-hidden="true"></i></span>
                            <div><strong id="selectedCouponName"></strong><small><span id="selectedCouponBenefit"></span>・<span id="selectedCouponPeriod"></span></small></div>
                            <span class="ad-coupon-ready"><i class="fas fa-check" aria-hidden="true"></i> 配布対象</span>
                        </div>
                    @endif
                </div>

                <div class="andel-card" role="region" id="delivery-audience" aria-label="届け先と配信タイミング">
                    <fieldset class="ad-delivery-fieldset ad-timing-fieldset">
                        <legend>配信タイミング <span class="ad-required">必須</span></legend>
                        <div class="ad-timing-types">
                            <label class="ad-timing-choice {{ $oldDeliveryTiming === \App\Models\Announcement::DELIVERY_TIMING_IMMEDIATE ? 'is-selected' : '' }}">
                                <input type="radio" name="delivery_timing" value="immediate" {{ $oldDeliveryTiming === \App\Models\Announcement::DELIVERY_TIMING_IMMEDIATE ? 'checked' : '' }}>
                                <span class="ad-choice-icon"><i class="fas fa-bolt" aria-hidden="true"></i></span>
                                <span><strong>即時配信</strong><small>確認後、すぐに配信します</small></span>
                                <span class="ad-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
                            </label>
                            <label class="ad-timing-choice {{ $oldDeliveryTiming === \App\Models\Announcement::DELIVERY_TIMING_SCHEDULED ? 'is-selected' : '' }}">
                                <input type="radio" name="delivery_timing" value="scheduled" {{ $oldDeliveryTiming === \App\Models\Announcement::DELIVERY_TIMING_SCHEDULED ? 'checked' : '' }}>
                                <span class="ad-choice-icon"><i class="far fa-calendar" aria-hidden="true"></i></span>
                                <span><strong>予約配信</strong><small>指定した日時に配信します</small></span>
                                <span class="ad-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
                            </label>
                        </div>
                    </fieldset>

                    <div id="scheduledBlock" class="ad-context-panel" hidden>
                        <span class="ad-context-icon"><i class="far fa-calendar" aria-hidden="true"></i></span>
                        <div class="form-group mb-0">
                            <label for="scheduled_at" class="form-label">配信日時 <span class="ad-required">必須</span></label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}">
                            <p class="ad-field-help">対象者は「今の一覧で固定」するか「配信時に条件へ一致する人へ送る」かを選べます。</p>
                        </div>
                    </div>

                    <fieldset class="ad-delivery-fieldset ad-audience-fieldset">
                        <legend>配信対象 <span class="ad-required">必須</span></legend>
                        <div class="ad-delivery-types ad-delivery-types--audience">
                            <label class="ad-delivery-choice {{ $oldAudienceType === 'immediate_bulk' ? 'is-selected' : '' }}">
                                <input type="radio" name="audience_type" value="immediate_bulk" {{ $oldAudienceType === 'immediate_bulk' ? 'checked' : '' }}>
                                <span class="ad-choice-icon"><i class="fas fa-users" aria-hidden="true"></i></span>
                                <span><strong>全会員</strong><small>現在登録されている会員全員</small></span>
                                <span class="ad-choice-badge">{{ number_format($view->memberCount) }}人</span>
                            </label>
                            <label class="ad-delivery-choice {{ $oldAudienceType === 'personal' ? 'is-selected' : '' }}">
                                <input type="radio" name="audience_type" value="personal" {{ $oldAudienceType === 'personal' ? 'checked' : '' }}>
                                <span class="ad-choice-icon"><i class="fas fa-user-check" aria-hidden="true"></i></span>
                                <span><strong>会員を選ぶ</strong><small>指定した会員だけに配信</small></span>
                                <span class="ad-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
                            </label>
                            <label class="ad-delivery-choice {{ $oldAudienceType === 'segment' ? 'is-selected' : '' }}">
                                <input type="radio" name="audience_type" value="segment" {{ $oldAudienceType === 'segment' ? 'checked' : '' }}>
                                <span class="ad-choice-icon"><i class="fas fa-filter" aria-hidden="true"></i></span>
                                <span><strong>条件で絞る</strong><small>属性・RFM・来店・購入条件で配信</small></span>
                                <span class="ad-choice-badge" id="segmentChoiceCount">人数を確認</span>
                            </label>
                        </div>
                    </fieldset>

                    <div id="personalTargetBlock" class="ad-context-panel ad-context-panel--stack" hidden>
                        <div class="ad-context-title">
                            <span class="ad-context-icon"><i class="fas fa-user-plus" aria-hidden="true"></i></span>
                            <div><strong>配信する会員を選択</strong><small>会員番号または名前で検索できます</small></div>
                        </div>
                        <div class="ad-member-search">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" id="memberSearch" class="form-control" placeholder="会員番号・名前で検索" autocomplete="off">
                        </div>
                        <div id="memberSearchResults" class="ad-member-results" hidden></div>
                        <div id="selectedMembers" class="ad-selected-members"></div>
                        <div id="targetMemberIdsHidden"></div>
                    </div>

                    <div id="segmentBlock" class="ad-segment-panel" hidden>
                        <div class="ad-segment-head">
                            <div><strong>絞り込み条件</strong><small>指定した条件をすべて満たす会員へ配信します（AND条件）</small></div>
                            <span id="segmentPreview"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> 人数を確認中</span>
                        </div>
                        <div class="ad-filter-grid">
                            <div class="form-group mb-0">
                                <label for="segment_channel" class="form-label">登録チャネル</label>
                                <select name="segment_channel" id="segment_channel" class="form-control segment-filter">
                                    <option value="">指定しない</option>
                                    @foreach(\App\Support\MemberChannel::filterOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('segment_channel') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <fieldset class="ad-multi-filter mb-0">
                                <legend class="form-label">誕生月 <small>複数選択可</small></legend>
                                <div class="ad-multi-filter-options ad-multi-filter-options--months">
                                    @for($m = 1; $m <= 12; $m++)
                                        <label><input type="checkbox" name="segment_birthday_months[]" value="{{ $m }}" data-label="{{ $m }}月" class="segment-filter" {{ $selectedBirthdayMonths->contains($m) ? 'checked' : '' }}><span>{{ $m }}月</span></label>
                                    @endfor
                                </div>
                            </fieldset>
                            <div class="form-group mb-0">
                                <label for="segment_sex" class="form-label">性別</label>
                                <select name="segment_sex" id="segment_sex" class="form-control segment-filter">
                                    <option value="">指定しない</option>
                                    <option value="1" {{ old('segment_sex') === '1' ? 'selected' : '' }}>男性</option>
                                    <option value="2" {{ old('segment_sex') === '2' ? 'selected' : '' }}>女性</option>
                                </select>
                            </div>
                            <fieldset class="ad-multi-filter ad-filter-group-wide mb-0">
                                <legend class="form-label">RFMセグメント <small>複数選択可・いずれかに一致</small></legend>
                                <div class="ad-multi-filter-options ad-multi-filter-options--tags">
                                    @foreach($view->rfmSegments as $rfmKey => $rfm)
                                        <label style="--ad-tag-color: {{ $rfm['color'] }}"><input type="checkbox" name="segment_rfm_segments[]" value="{{ $rfmKey }}" data-label="{{ $rfm['label'] }}" class="segment-filter" {{ $selectedRfmSegments->contains($rfmKey) ? 'checked' : '' }}><span><i aria-hidden="true"></i>{{ $rfm['label'] }}</span></label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <fieldset class="ad-multi-filter ad-filter-group-wide mb-0">
                                <legend class="form-label">会員タグ <small>複数選択可・いずれかのタグに一致</small></legend>
                                @if(($view->memberTags ?? collect())->isEmpty())
                                    <p class="ad-multi-filter-empty">会員タグはまだ登録されていません。</p>
                                @else
                                    <div class="ad-multi-filter-options ad-multi-filter-options--tags">
                                        @foreach($view->memberTags as $tag)
                                            <label style="--ad-tag-color: {{ $tag->color }}"><input type="checkbox" name="segment_tag_ids[]" value="{{ $tag->id }}" data-label="{{ $tag->name }}" class="segment-filter" {{ $selectedTagIds->contains((int) $tag->id) ? 'checked' : '' }}><span><i aria-hidden="true"></i>{{ $tag->name }} <small>{{ number_format($tag->members_count ?? 0) }}人</small></span></label>
                                        @endforeach
                                    </div>
                                @endif
                            </fieldset>
                        </div>

                        <details class="ad-details ad-segment-details" @if($advancedSegmentOpen) open @endif>
                            <summary>
                                <span><i class="fas fa-sliders" aria-hidden="true"></i> 来店・購入状況でさらに絞り込む</span>
                                <small>来店回数、ポイント、最終来店、商品、売上</small>
                            </summary>
                            <div class="ad-segment-advanced">
                                <div class="ad-filter-grid">
                                    <div class="form-group mb-0"><label for="segment_visit_count_min" class="form-label">来店回数（以上）</label><input type="number" name="segment_visit_count_min" id="segment_visit_count_min" class="form-control segment-filter" value="{{ old('segment_visit_count_min') }}" min="0" placeholder="例：3"></div>
                                    <div class="form-group mb-0"><label for="segment_visit_count_max" class="form-label">来店回数（以下）</label><input type="number" name="segment_visit_count_max" id="segment_visit_count_max" class="form-control segment-filter" value="{{ old('segment_visit_count_max') }}" min="0" placeholder="例：10"></div>
                                    <div class="form-group mb-0"><label for="segment_rank" class="form-label">会員ランク</label><input type="text" name="segment_rank" id="segment_rank" class="form-control segment-filter" value="{{ old('segment_rank') }}" maxlength="64" placeholder="例：gold"></div>
                                </div>
                                <div class="ad-filter-grid ad-filter-grid--2">
                                    <div class="form-group mb-0"><label for="segment_lifetime_points_min" class="form-label">累計付与ポイント（以上）</label><input type="number" name="segment_lifetime_points_min" id="segment_lifetime_points_min" class="form-control segment-filter" value="{{ old('segment_lifetime_points_min') }}" min="0" placeholder="例：1000"></div>
                                    <div class="form-group mb-0"><label for="segment_lifetime_points_max" class="form-label">累計付与ポイント（以下）</label><input type="number" name="segment_lifetime_points_max" id="segment_lifetime_points_max" class="form-control segment-filter" value="{{ old('segment_lifetime_points_max') }}" min="0" placeholder="例：5000"></div>
                                </div>
                                <div class="ad-filter-grid ad-filter-grid--2">
                                    <div class="form-group mb-0">
                                        <label for="segment_last_visit_mode" class="form-label">最終来店</label>
                                        <select name="segment_last_visit_mode" id="segment_last_visit_mode" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            <option value="inactive_days" {{ old('segment_last_visit_mode') === 'inactive_days' ? 'selected' : '' }}>N日以上来店なし</option>
                                            <option value="visited_within_days" {{ old('segment_last_visit_mode') === 'visited_within_days' ? 'selected' : '' }}>N日以内に来店あり</option>
                                            <option value="days_after" {{ old('segment_last_visit_mode') === 'days_after' ? 'selected' : '' }}>最終来店からちょうどN日後</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="segment_last_visit_days" class="form-label">日数</label>
                                        <select name="segment_last_visit_days" id="segment_last_visit_days" class="form-control segment-filter">
                                            @foreach([7, 30, 60, 90, 180] as $days)
                                                <option value="{{ $days }}" {{ (int) old('segment_last_visit_days', 30) === $days ? 'selected' : '' }}>{{ $days }}日</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label for="segment_product_id" class="form-label">購入した商品</label>
                                    <select name="segment_product_id" id="segment_product_id" class="form-control segment-filter">
                                        <option value="">指定しない</option>
                                        @foreach(($view->products ?? []) as $product)
                                            <option value="{{ $product->id }}" {{ (string) old('segment_product_id') === (string) $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="ad-field-help">アプリ注文・スマレジPOSで購入した会員に絞り込みます。</p>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="ad-pin-option" style="margin:0">
                                        <input type="checkbox" name="event_rank_up" id="event_rank_up" value="1" class="segment-filter" {{ old('event_rank_up') || $legacyDeliveryType === \App\Enums\CouponDeliveryType::EVENT_RANK_UP_ARRIVAL ? 'checked' : '' }}>
                                        <span><strong>ランクアップ達成で自動配布</strong><small>指定ランクへ昇格したときに発行します</small></span>
                                    </label>
                                </div>
                                <div class="ad-filter-grid">
                                    <div class="form-group mb-0">
                                        <label for="stamp_card_id" class="form-label">スタンプカード</label>
                                        <select name="stamp_card_id" id="stamp_card_id" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            @foreach(($view->stampCards ?? []) as $card)
                                                <option value="{{ $card->id }}" {{ (string) old('stamp_card_id', old('segment_stamp_card_id')) === (string) $card->id ? 'selected' : '' }}>{{ $card->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="stamp_count_min" class="form-label">累計スタンプ以上</label>
                                        <input type="number" name="stamp_count_min" id="stamp_count_min" class="form-control segment-filter" value="{{ old('stamp_count_min', old('segment_stamp_count_min')) }}" min="0">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="checkbox-label"><input type="checkbox" name="stamp_completed" id="stamp_completed" value="1" class="segment-filter" {{ old('stamp_completed', old('segment_stamp_completed')) ? 'checked' : '' }}> 達成済みのみ</label>
                                    </div>
                                </div>
                                <div class="ad-sales-filter">
                                    <div class="form-group mb-0">
                                        <label for="segment_period_days" class="form-label">売上の対象期間</label>
                                        <select name="segment_period_days" id="segment_period_days" class="form-control segment-filter">
                                            <option value="7" {{ (int) old('segment_period_days', 30) === 7 ? 'selected' : '' }}>直近7日間</option>
                                            <option value="30" {{ (int) old('segment_period_days', 30) === 30 ? 'selected' : '' }}>直近30日間</option>
                                            <option value="90" {{ (int) old('segment_period_days', 30) === 90 ? 'selected' : '' }}>直近90日間</option>
                                        </select>
                                    </div>
                                    <fieldset>
                                        <legend class="form-label">売上条件</legend>
                                        <label><input type="radio" name="segment_condition" value="" {{ old('segment_condition', '') === '' ? 'checked' : '' }} class="segment-filter"> 指定しない</label>
                                        <label><input type="radio" name="segment_condition" value="min_amount" {{ old('segment_condition') === 'min_amount' ? 'checked' : '' }} class="segment-filter"> 購入額以上</label>
                                        <label><input type="radio" name="segment_condition" value="no_orders" {{ old('segment_condition') === 'no_orders' ? 'checked' : '' }} class="segment-filter"> 未購入</label>
                                    </fieldset>
                                    <div id="segmentMinAmountBlock" class="form-group mb-0" hidden>
                                        <label for="segment_min_amount" class="form-label">最低購入額</label>
                                        <div class="ad-input-suffix"><input type="number" name="segment_min_amount" id="segment_min_amount" class="form-control segment-filter" value="{{ old('segment_min_amount', 5000) }}" min="0" step="1"><span>円</span></div>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>

                    <div id="audienceResolveBlock" class="ad-context-panel ad-context-panel--stack" hidden>
                        <div class="ad-context-title">
                            <span class="ad-context-icon"><i class="fas fa-user-lock" aria-hidden="true"></i></span>
                            <div><strong>対象者の確定タイミング</strong><small>予約配信では、今の一覧で固定するか、配信時刻に条件をやり直すかを選べます</small></div>
                        </div>
                        @php
                            $oldResolveMode = old('audience_resolve_mode', \App\Enums\AnnouncementAudienceResolveMode::SNAPSHOT);
                        @endphp
                        <div class="ad-timing-types">
                            <label class="ad-timing-choice {{ $oldResolveMode === \App\Enums\AnnouncementAudienceResolveMode::SNAPSHOT ? 'is-selected' : '' }}">
                                <input type="radio" name="audience_resolve_mode" value="{{ \App\Enums\AnnouncementAudienceResolveMode::SNAPSHOT }}" {{ $oldResolveMode === \App\Enums\AnnouncementAudienceResolveMode::SNAPSHOT ? 'checked' : '' }}>
                                <span class="ad-choice-icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
                                <span><strong>今の対象者で固定する</strong><small>この画面で確認した会員一覧のまま配信します</small></span>
                                <span class="ad-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
                            </label>
                            <label class="ad-timing-choice {{ $oldResolveMode === \App\Enums\AnnouncementAudienceResolveMode::AT_SEND ? 'is-selected' : '' }}">
                                <input type="radio" name="audience_resolve_mode" value="{{ \App\Enums\AnnouncementAudienceResolveMode::AT_SEND }}" {{ $oldResolveMode === \App\Enums\AnnouncementAudienceResolveMode::AT_SEND ? 'checked' : '' }}>
                                <span class="ad-choice-icon"><i class="fas fa-rotate" aria-hidden="true"></i></span>
                                <span><strong>配信時に条件を再評価する</strong><small>予約時刻に条件へ一致する会員へ配信します</small></span>
                                <span class="ad-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
                            </label>
                        </div>
                    </div>

                    <details class="ad-audience-preview" id="audiencePreviewDetails">
                        <summary class="ad-audience-preview-head">
                            <div><strong><i class="fas fa-list" aria-hidden="true"></i> 配信対象者一覧</strong><small id="audiencePreviewHint">この一覧の対象者を配信時点で保存します</small></div>
                            <span id="audiencePreviewCount">確認中…</span>
                        </summary>
                        <div id="audiencePreviewPanel">
                            <div class="ad-audience-preview-table-wrap">
                                <table class="ad-audience-preview-table">
                                    <thead><tr><th>会員名</th><th>タグ</th><th>会員番号</th><th>通知可否</th><th>チャネル</th><th>ランク</th></tr></thead>
                                    <tbody id="audiencePreviewRows"><tr><td colspan="6" class="ad-audience-loading">対象者を読み込んでいます…</td></tr></tbody>
                                </table>
                            </div>
                            <div class="ad-audience-pagination">
                                <button type="button" class="btn btn-sm btn-secondary" id="audiencePrevPage" disabled><i class="fas fa-chevron-left"></i> 前へ</button>
                                <span id="audiencePageLabel">1 / 1ページ</span>
                                <button type="button" class="btn btn-sm btn-secondary" id="audienceNextPage" disabled>次へ <i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </details>
                </div>

                @php
                    $notifyOld = old('send_notification');
                    $isNotify = $notifyOld === null
                        ? true
                        : ($notifyOld === '1' || $notifyOld === 1 || $notifyOld === true);
                @endphp
                <div class="andel-card" role="region" id="delivery-options" aria-label="通知設定">
                    <label class="ad-pin-option">
                        <input type="hidden" name="send_notification" value="0">
                        <input type="checkbox" name="send_notification" id="send_notification" value="1" {{ $isNotify ? 'checked' : '' }}>
                        <span class="ad-pin-icon"><i class="fas fa-bell" aria-hidden="true"></i></span>
                        <span><strong>クーポン発行時に通知を送る</strong><small>オフにすると発行のみで、LINE・Pushは送りません</small></span>
                        <span class="ad-check-ui"><i class="fas fa-check" aria-hidden="true"></i></span>
                    </label>
                </div>

            </div>

            <aside class="ad-studio-aside" aria-label="配信内容の確認">
                <div class="ad-confirm-panel">
                    <div class="ad-confirm-head"><span>入力内容</span><i class="fas fa-circle-check" aria-hidden="true"></i></div>
                    <dl>
                        <div><dt><i class="fas fa-users" aria-hidden="true"></i> 届け先</dt><dd><a class="ad-summary-audience-link" href="#delivery-audience"><span id="deliverySummaryAudience">{{ number_format($view->memberCount) }}人（全会員）</span><small>一覧を見る <i class="fas fa-chevron-down" aria-hidden="true"></i></small></a></dd></div>
                        <div><dt><i class="far fa-clock" aria-hidden="true"></i> 配信日時</dt><dd id="deliverySummaryTiming">今すぐ</dd></div>
                        <div id="deliverySummaryResolveRow" hidden><dt><i class="fas fa-user-lock" aria-hidden="true"></i> 対象者</dt><dd id="deliverySummaryResolve">今の一覧で固定</dd></div>
                        <div><dt><i class="fas fa-bell" aria-hidden="true"></i> 通知</dt><dd id="deliverySummaryNotifications">通知先を確認中…</dd></div>
                        <div><dt><i class="fas fa-ticket-alt" aria-hidden="true"></i> クーポン</dt><dd id="deliverySummaryCoupon">未選択</dd></div>
                    </dl>
                    <section class="ad-confirm-conditions" id="deliverySummaryConditionBlock" aria-labelledby="deliverySummaryConditionTitle" hidden>
                        <div class="ad-confirm-conditions-head">
                            <span id="deliverySummaryConditionTitle"><i class="fas fa-filter" aria-hidden="true"></i> 絞り込み条件</span>
                            <a href="#delivery-audience"><i class="fas fa-pen" aria-hidden="true"></i> 変更</a>
                        </div>
                        <div class="ad-condition-chips" id="deliverySummaryConditions" aria-live="polite"></div>
                        <p><i class="fas fa-circle-info" aria-hidden="true"></i> すべての条件に一致する会員へ配信します</p>
                    </section>
                    <div id="deliveryFormError" class="ad-form-error" role="alert" hidden></div>
                    <div class="ad-form-footer">
                        <a href="{{ admin_route('admin.coupons') }}" class="btn btn-secondary">キャンセル</a>
                        <button type="submit" class="btn btn-primary ad-submit no-loading" id="deliverySubmitButton">
                            <span><i class="fas fa-eye" aria-hidden="true"></i> <span id="deliverySubmitLabel">確認画面へ</span></span>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="ad-submit-note"><i class="fas fa-circle-info" aria-hidden="true"></i> 確認画面で内容を最終チェックしてから配信します</p>
                </div>
            </aside>
        </div>

        <div class="ad-review-modal" id="deliveryReviewModal" hidden>
            <button type="button" class="ad-review-backdrop" data-close-delivery-review aria-label="確認画面を閉じる"></button>
            <section class="ad-review-dialog" role="dialog" aria-modal="true" aria-labelledby="deliveryReviewTitle">
                <header class="ad-review-head">
                    <div>
                        <span>FINAL CHECK</span>
                        <h2 id="deliveryReviewTitle">配信内容の最終確認</h2>
                        <p>内容・対象者・通知方法を確認してから配信してください。</p>
                    </div>
                    <button type="button" class="ad-review-close" data-close-delivery-review aria-label="閉じる"><i class="fas fa-times" aria-hidden="true"></i></button>
                </header>

                <div class="ad-review-body" id="deliveryReviewBody">
                    <div class="ad-review-grid">
                        <section class="ad-review-card">
                            <h3><i class="fas fa-paper-plane" aria-hidden="true"></i> 配信設定</h3>
                            <dl>
                                <div><dt>届け先</dt><dd><button type="button" class="ad-review-audience-button" id="deliveryReviewAudienceButton" aria-expanded="false" aria-controls="deliveryReviewAudiencePanel"><span id="deliveryReviewAudienceLabel"></span><i class="fas fa-chevron-down" aria-hidden="true"></i></button></dd></div>
                                <div><dt>配信日時</dt><dd id="deliveryReviewTiming"></dd></div>
                                <div id="deliveryReviewResolveRow" hidden><dt>対象者の確定</dt><dd id="deliveryReviewResolve"></dd></div>
                                <div><dt>クーポン</dt><dd id="deliveryReviewCoupon"></dd></div>
                            </dl>
                        </section>

                        <section class="ad-review-card ad-review-notifications">
                            <h3><i class="fas fa-bell" aria-hidden="true"></i> 通知予定</h3>
                            <p class="ad-review-notify-off" id="deliveryReviewNotifyOff" hidden>通知は送りません。クーポンの発行のみ行います。</p>
                            <div class="ad-review-channel-list" id="deliveryReviewChannelList">
                                <div class="ad-review-channel ad-review-channel--line">
                                    <span><i class="fab fa-line" aria-hidden="true"></i> LINE</span>
                                    <strong id="deliveryReviewLineCount">0人</strong>
                                </div>
                                <div class="ad-review-channel ad-review-channel--push">
                                    <span><i class="fas fa-mobile-screen-button" aria-hidden="true"></i> Push通知</span>
                                    <strong id="deliveryReviewPushCount">0人</strong>
                                </div>
                            </div>
                            <p id="deliveryReviewNotifyNote">両方を利用している会員には、LINEとPush通知の両方を送ります。</p>
                            <p class="ad-review-unreachable" id="deliveryReviewOptedOut" hidden></p>
                            <p class="ad-review-unreachable" id="deliveryReviewUnreachable" hidden></p>
                        </section>
                    </div>

                    <section class="ad-review-audience-panel" id="deliveryReviewAudiencePanel" hidden>
                        <header><div><strong><i class="fas fa-users" aria-hidden="true"></i> 配信予定ユーザー</strong><small>現在の条件で配信対象になる会員です</small></div><button type="button" id="deliveryReviewAudienceClose" aria-label="配信予定ユーザーを閉じる"><i class="fas fa-times" aria-hidden="true"></i></button></header>
                        <div class="ad-audience-preview-table-wrap">
                            <table class="ad-audience-preview-table">
                                <thead><tr><th>会員名</th><th>タグ</th><th>会員番号</th><th>通知可否</th><th>チャネル</th><th>ランク</th></tr></thead>
                                <tbody id="deliveryReviewAudienceRows"><tr><td colspan="6" class="ad-audience-loading"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> 対象者を確認中…</td></tr></tbody>
                            </table>
                        </div>
                        <div class="ad-audience-pagination">
                            <button type="button" class="btn btn-sm btn-secondary" id="deliveryReviewAudiencePrev"><i class="fas fa-chevron-left" aria-hidden="true"></i> 前へ</button>
                            <span id="deliveryReviewAudiencePageLabel">1 / 1ページ</span>
                            <button type="button" class="btn btn-sm btn-secondary" id="deliveryReviewAudienceNext">次へ <i class="fas fa-chevron-right" aria-hidden="true"></i></button>
                        </div>
                    </section>

                    <section class="ad-review-card ad-review-conditions" id="deliveryReviewConditionBlock" hidden>
                        <h3><i class="fas fa-filter" aria-hidden="true"></i> 絞り込み条件</h3>
                        <div class="ad-condition-chips" id="deliveryReviewConditions"></div>
                        <p>すべての条件に一致する会員が対象です。</p>
                    </section>

                    <div class="ad-review-warning" id="deliveryReviewWarning">
                        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                        <div><strong id="deliveryReviewWarningTitle"></strong><span id="deliveryReviewWarningText"></span></div>
                    </div>
                </div>

                <footer class="ad-review-actions">
                    <button type="button" class="btn btn-secondary" data-close-delivery-review><i class="fas fa-arrow-left" aria-hidden="true"></i> 内容を修正する</button>
                    <button type="button" class="btn btn-primary" id="deliveryReviewConfirm"><i class="fas fa-paper-plane" aria-hidden="true"></i> <span id="deliveryReviewConfirmLabel">この内容で配信する</span></button>
                </footer>
            </section>
        </div>

        <div class="ad-member-detail-modal" id="audienceMemberDetailModal" hidden>
            <button type="button" class="ad-member-detail-backdrop" data-close-audience-member-detail aria-label="会員情報を閉じる"></button>
            <section class="ad-member-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="audienceMemberDetailTitle">
                <header>
                    <div class="ad-member-detail-identity">
                        <span id="audienceMemberDetailAvatar" aria-hidden="true">—</span>
                        <div><small>配信予定ユーザー</small><h2 id="audienceMemberDetailTitle">会員情報</h2><p id="audienceMemberDetailNumber">—</p></div>
                    </div>
                    <button type="button" data-close-audience-member-detail aria-label="会員情報を閉じる"><i class="fas fa-times" aria-hidden="true"></i></button>
                </header>
                <div class="ad-member-detail-body">
                    <div class="ad-member-detail-sendability">
                        <span id="audienceMemberDetailSendableIcon"><i class="fas fa-bell" aria-hidden="true"></i></span>
                        <div><strong id="audienceMemberDetailSendable">通知可否</strong><small id="audienceMemberDetailReason"></small></div>
                    </div>
                    <div class="ad-member-detail-tags" id="audienceMemberDetailTags"></div>
                    <dl>
                        <div><dt>通知設定</dt><dd id="audienceMemberDetailPreference">—</dd></div>
                        <div><dt>登録チャネル</dt><dd id="audienceMemberDetailChannel">—</dd></div>
                        <div><dt>ランク</dt><dd id="audienceMemberDetailRank">—</dd></div>
                        <div><dt>保有ポイント</dt><dd id="audienceMemberDetailPoints">0pt</dd></div>
                        <div><dt>生年月日</dt><dd id="audienceMemberDetailBirthday">—</dd></div>
                        <div><dt>性別</dt><dd id="audienceMemberDetailSex">—</dd></div>
                        <div><dt>最終来店</dt><dd id="audienceMemberDetailLastVisit">—</dd></div>
                        <div><dt>電話番号</dt><dd id="audienceMemberDetailPhone">—</dd></div>
                        <div class="ad-member-detail-wide"><dt>メールアドレス</dt><dd id="audienceMemberDetailEmail">—</dd></div>
                    </dl>
                </div>
                <footer><button type="button" class="btn btn-secondary" data-close-audience-member-detail>閉じる</button></footer>
            </section>
        </div>
    </form>
</div>

<script type="application/json" id="announcementDeliveryConfig">{!! json_encode([
    'membersUrl' => admin_route('admin.coupons.ajax.members'),
    'segmentPreviewUrl' => admin_route('admin.coupons.segment.preview'),
    'audiencePreviewUrl' => admin_route('admin.coupons.audience.preview'),
    'memberCount' => $view->memberCount,
    'selectedMemberIds' => $selectedMemberIds,
    'mode' => 'coupon',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/announcement-delivery.css') }}?v={{ (int) @filemtime(public_path('css/admin/announcement-delivery.css')) }}">
@endpush
@endsection
