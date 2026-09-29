@extends('admin.layout')

@section('title', 'お知らせ配信')
@section('page-title', 'お知らせ配信')
@section('page-kind', 'announcement-delivery')

@section('page-header-actions')
    <button type="submit" form="deliveryForm" class="btn btn-primary ad-header-submit no-loading" id="deliverySubmitButton">
        <i class="fas fa-paper-plane" aria-hidden="true"></i> <span id="deliverySubmitLabel">配信内容を確認する</span>
    </button>
@endsection

@section('content')
@php
    $selectedCouponIds = collect((array) old('delivery_coupon_ids', request('coupon_id') ? [request('coupon_id')] : []))
        ->map(fn ($id) => (int) $id)->filter()->unique()->values();
    $hasSelectedCoupon = $selectedCouponIds->isNotEmpty();
    $selectedMemberIds = collect((array) old('target_member_ids', []))->map(fn ($id) => (int) $id)->filter()->values();
    $selectedBirthdayMonths = collect((array) old(
        'segment_birthday_months',
        filled(old('segment_birthday_month')) ? [old('segment_birthday_month')] : []
    ))->map(fn ($month) => (int) $month)->filter(fn ($month) => $month >= 1 && $month <= 12)->unique()->values();
    $selectedTagIds = collect((array) old('segment_tag_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();
    $selectedRfmSegments = collect((array) old('segment_rfm_segments', []))->map(fn ($key) => (string) $key)->filter()->unique()->values();
    $legacyDeliveryType = old('delivery_type', '');
    $oldAudienceType = old('audience_type', $legacyDeliveryType === \App\Models\Announcement::DELIVERY_TYPE_SCHEDULED
        ? \App\Models\Announcement::DELIVERY_TYPE_IMMEDIATE_BULK
        : ($legacyDeliveryType ?: \App\Models\Announcement::DELIVERY_TYPE_IMMEDIATE_BULK));
    $oldDeliveryTiming = old('delivery_timing', $legacyDeliveryType === \App\Models\Announcement::DELIVERY_TYPE_SCHEDULED
        ? \App\Models\Announcement::DELIVERY_TIMING_SCHEDULED
        : \App\Models\Announcement::DELIVERY_TIMING_IMMEDIATE);
    $advancedSegmentOpen = collect([
        'segment_visit_count_min', 'segment_visit_count_max', 'segment_lifetime_points_min',
        'segment_lifetime_points_max', 'segment_last_visit_mode', 'segment_rank',
        'segment_product_id', 'segment_condition',
    ])->contains(fn ($field) => filled(old($field)));
    $announcementPreviewBaseUrl = admin_front_url();
    $announcementPreviewUrl = $announcementPreviewBaseUrl !== null
        ? $announcementPreviewBaseUrl.(str_contains($announcementPreviewBaseUrl, '?') ? '&' : '?').'designer_live_preview=1&designer_embed=1&announcement_form_preview=1#/announcements'
        : null;
    $lineAccountName = (string) (($currentProject ?? null)?->name ?? '店舗公式アカウント');
@endphp

<div class="announcement-delivery-admin">
    <nav class="breadcrumb" aria-label="パンくず">
        <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
        <span class="breadcrumb-sep">/</span>
        <a href="{{ admin_route('admin.announcements') }}">お知らせ</a>
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

    <div class="ad-form-guide">
        <div><h2>お知らせを作成して配信</h2><p>内容と届け先を設定し、最後に確認画面で配信を確定します。</p></div>
        <nav aria-label="配信設定の手順">
            <ol class="ad-form-steps">
                <li><a href="#delivery-content"><span>1</span>内容を作成</a></li>
                <li><a href="#delivery-audience"><span>2</span>対象・日時</a></li>
                <li><a href="#delivery-options"><span>3</span>通知・掲載</a></li>
            </ol>
        </nav>
    </div>

    <p class="alert alert-info">配信店舗：{{ $selectedStore?->name ?? '全店舗' }}。対象者にはアプリ内のお知らせを表示し、通知設定に応じてLINE・Pushを送信します。</p>
        <form action="{{ admin_route('admin.announcements.delivery.store') }}" method="POST" id="deliveryForm" enctype="multipart/form-data">
        @csrf
        <div class="ad-studio">
            <div class="ad-studio-main">
                <div class="andel-card" role="region" id="delivery-content" aria-label="お知らせの内容">
                    <header class="andel-card-head">
                        <span class="andel-card-icon" aria-hidden="true"><i class="fas fa-pen"></i></span>
                        <div><p>STEP 1</p><h2>お知らせの内容</h2><span>会員に表示するタイトル・本文・画像を入力してください。</span></div>
                    </header>
                    <div class="form-group">
                        <div class="ad-label-row">
                            <label for="title" class="form-label">タイトル <span class="ad-required">必須</span></label>
                            <span id="announcementTitleCounter">0 / 200</span>
                        </div>
                        <input type="text" name="title" id="title" class="form-control ad-control-lg" value="{{ old('title') }}" required maxlength="200" placeholder="例：週末限定キャンペーンのお知らせ">
                        @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <div class="ad-label-row">
                            <label for="body" class="form-label">本文 <span class="ad-optional">任意</span></label>
                            <span id="announcementBodyCounter">0 / 5,000</span>
                        </div>
                        <textarea name="body" id="body" class="form-control" rows="6" maxlength="5000" placeholder="キャンペーン内容や利用方法をご案内しましょう。">{{ old('body') }}</textarea>
                        @error('body')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="ad-media-field">
                        @include('admin.partials._frontend_logo_dropzone', [
                            'logoInputName' => 'image_file',
                            'logoClearName' => 'image_file_clear',
                            'logoFieldId' => 'ann_image',
                            'logoCurrentUrl' => '',
                            'logoLabel' => 'お知らせの画像（任意）',
                            'logoHelp' => '',
                            'logoCompact' => false,
                        ])
                        @error('image_file')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="andel-card" role="region" id="delivery-audience" aria-label="届け先と配信タイミング">
                    <header class="andel-card-head">
                        <span class="andel-card-icon" aria-hidden="true"><i class="fas fa-users"></i></span>
                        <div><p>STEP 2</p><h2>配信対象と日時</h2><span>いつ、誰に届けるかを選びます。対象者一覧で会員を確認できます。</span></div>
                    </header>
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
                            <div><strong>絞り込み条件</strong><small>必要なカテゴリを開いて条件を選択</small></div>
                            <span id="segmentPreview"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> 人数を確認中</span>
                        </div>
                    <section class="ad-confirm-conditions" id="deliverySummaryConditionBlock" aria-labelledby="deliverySummaryConditionTitle" hidden>
                        <div class="ad-confirm-conditions-head">
                            <span id="deliverySummaryConditionTitle"><i class="fas fa-filter" aria-hidden="true"></i> 選択中の条件</span>

                        </div>
                        <div class="ad-condition-chips" id="deliverySummaryConditions" aria-live="polite"></div>

                    </section>
                        <fieldset class="ad-match-mode">
                            <legend>異なる条件項目の組み合わせ</legend>
                            <div class="ad-match-mode-options">
                                <label><input type="radio" name="segment_match_mode" value="all" class="segment-filter" {{ old('segment_match_mode', 'all') === 'all' ? 'checked' : '' }}><span><strong>項目間は AND</strong><small>例：誕生月にも、タグにも一致</small></span></label>
                                <label><input type="radio" name="segment_match_mode" value="any" class="segment-filter" {{ old('segment_match_mode') === 'any' ? 'checked' : '' }}><span><strong>項目間は OR</strong><small>例：誕生月か、タグのどちらかに一致</small></span></label>
                            </div>
                            <p>この設定は異なる項目の間だけに適用します。1つの項目で複数選べる誕生月・タグなどは、そのうち1つに一致すれば該当します。性別・売上条件などの単一選択項目には、項目内の AND/OR はありません。空欄は対象外です。</p>
                        </fieldset>
                        <details class="ad-details ad-segment-details" @if(filled(old('segment_channel')) || filled(old('segment_sex')) || $selectedBirthdayMonths->isNotEmpty() || $selectedRfmSegments->isNotEmpty() || $selectedTagIds->isNotEmpty()) open @endif>
                            <summary><span><i class="fas fa-user" aria-hidden="true"></i> 会員の属性</span><small>誕生月・タグなど</small></summary>
                            <div class="ad-segment-advanced">
                        <div class="ad-filter-grid">
                            <div class="form-group mb-0">
                                <div class="ad-filter-label"><label for="segment_channel" class="form-label">登録チャネル</label></div>
                                <select name="segment_channel" id="segment_channel" class="form-control segment-filter">
                                    <option value="">指定しない</option>
                                    @foreach(\App\Support\MemberChannel::filterOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('segment_channel') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <fieldset class="ad-multi-filter mb-0">
                                <legend class="form-label">誕生月 <small>複数選択可・選んだ月のいずれかに一致</small></legend>
                                <div class="ad-multi-filter-options ad-multi-filter-options--months">
                                    @for($m = 1; $m <= 12; $m++)
                                        <label><input type="checkbox" name="segment_birthday_months[]" value="{{ $m }}" data-label="{{ $m }}月" class="segment-filter" {{ $selectedBirthdayMonths->contains($m) ? 'checked' : '' }}><span>{{ $m }}月</span></label>
                                    @endfor
                                </div>
                            </fieldset>
                            <div class="form-group mb-0">
                                <div class="ad-filter-label"><label for="segment_sex" class="form-label">性別</label></div>
                                <select name="segment_sex" id="segment_sex" class="form-control segment-filter">
                                    <option value="">指定しない</option>
                                    <option value="1" {{ old('segment_sex') === '1' ? 'selected' : '' }}>男性</option>
                                    <option value="2" {{ old('segment_sex') === '2' ? 'selected' : '' }}>女性</option>
                                </select>
                            </div>
                            <fieldset class="ad-multi-filter ad-filter-group-wide mb-0">
                                <legend class="form-label">RFMセグメント <small>複数選択可・選んだ区分のいずれかに一致</small></legend>
                                <div class="ad-multi-filter-options ad-multi-filter-options--tags">
                                    @foreach($view->rfmSegments as $rfmKey => $rfm)
                                        <label style="--ad-tag-color: {{ $rfm['color'] }}"><input type="checkbox" name="segment_rfm_segments[]" value="{{ $rfmKey }}" data-label="{{ $rfm['label'] }}" class="segment-filter" {{ $selectedRfmSegments->contains($rfmKey) ? 'checked' : '' }}><span><i aria-hidden="true"></i>{{ $rfm['label'] }}</span></label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <fieldset class="ad-multi-filter ad-filter-group-wide mb-0">
                                <legend class="form-label">会員タグ <small>複数選択可・選んだタグのいずれかに一致</small></legend>
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


                            </div>
                        </details>

                        <details class="ad-details ad-segment-details" @if($advancedSegmentOpen) open @endif>
                            <summary>
                                <span><i class="fas fa-sliders" aria-hidden="true"></i> 来店・購入</span>
                                <small>来店回数・購入額など</small>
                            </summary>
                            <div class="ad-segment-advanced">
                                <div class="ad-filter-grid">
                                    <div class="form-group mb-0"><div class="ad-filter-label"><label for="segment_visit_count_min" class="form-label">来店回数（以上）</label></div><input type="number" name="segment_visit_count_min" id="segment_visit_count_min" class="form-control segment-filter" value="{{ old('segment_visit_count_min') }}" min="0" placeholder="例：3"></div>
                                    <div class="form-group mb-0"><label for="segment_visit_count_max" class="form-label">来店回数（以下）</label><input type="number" name="segment_visit_count_max" id="segment_visit_count_max" class="form-control segment-filter" value="{{ old('segment_visit_count_max') }}" min="0" placeholder="例：10"></div>
                                    <div class="form-group mb-0"><div class="ad-filter-label"><label for="segment_rank" class="form-label">会員ランク</label></div><input type="text" name="segment_rank" id="segment_rank" class="form-control segment-filter" value="{{ old('segment_rank') }}" maxlength="64" placeholder="例：gold"></div>
                                </div>
                                <div class="ad-filter-grid ad-filter-grid--2">
                                    <div class="form-group mb-0"><div class="ad-filter-label"><label for="segment_lifetime_points_min" class="form-label">累計付与ポイント（以上）</label></div><input type="number" name="segment_lifetime_points_min" id="segment_lifetime_points_min" class="form-control segment-filter" value="{{ old('segment_lifetime_points_min') }}" min="0" placeholder="例：1000"></div>
                                    <div class="form-group mb-0"><label for="segment_lifetime_points_max" class="form-label">累計付与ポイント（以下）</label><input type="number" name="segment_lifetime_points_max" id="segment_lifetime_points_max" class="form-control segment-filter" value="{{ old('segment_lifetime_points_max') }}" min="0" placeholder="例：5000"></div>
                                </div>
                                <div class="ad-filter-grid ad-filter-grid--2">
                                    <div class="form-group mb-0">
                                        <div class="ad-filter-label"><label for="segment_last_visit_mode" class="form-label">最終来店</label></div>
                                        <select name="segment_last_visit_mode" id="segment_last_visit_mode" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            <option value="inactive_days" {{ old('segment_last_visit_mode') === 'inactive_days' ? 'selected' : '' }}>N日以上来店なし</option>
                                            <option value="visited_within_days" {{ old('segment_last_visit_mode') === 'visited_within_days' ? 'selected' : '' }}>N日以内に来店あり</option>
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
                                    <div class="ad-filter-label"><label for="segment_product_id" class="form-label">購入した商品</label></div>
                                    <select name="segment_product_id" id="segment_product_id" class="form-control segment-filter">
                                        <option value="">指定しない</option>
                                        @foreach(($view->products ?? []) as $product)
                                            <option value="{{ $product->id }}" {{ (string) old('segment_product_id') === (string) $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="ad-field-help">アプリ注文・スマレジPOSで購入した会員に絞り込みます。</p>
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
                                        <legend class="form-label">売上条件 <small>1つだけ選択</small></legend>
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
                            <details class="ad-details ad-segment-details ad-profile-category" @if(filled(old('segment_profile'))) open @endif>
                                <summary><span><i class="fas fa-id-card" aria-hidden="true"></i> 追加プロフィール項目</span><small>{{ count($view->profileFields) }}項目</small></summary>
                                <div class="ad-segment-advanced"><div class="ad-filter-grid">
                                    @forelse($view->profileFields as $profileField)
                                        @php
                                            $profileKey = (string) ($profileField['key'] ?? '');
                                            $profileLabel = (string) ($profileField['label'] ?? $profileKey);
                                            $profileValues = array_values((array) ($profileField['values'] ?? []));
                                            $profileOld = (array) old('segment_profile.'.$profileKey, []);
                                        @endphp
                                        @if($profileKey !== '')
                                            <div class="form-group mb-0 ad-profile-filter" data-profile-label="{{ $profileLabel }}">
                                                <div class="ad-filter-label"><label class="form-label" for="segment_profile_{{ $profileKey }}">{{ $profileLabel }}</label></div>
                                                @if($profileValues !== [])
                                                    <div class="ad-multi-filter-options">
                                                        @foreach($profileValues as $value)
                                                            <label><input type="checkbox" name="segment_profile[{{ $profileKey }}][]" value="{{ $value }}" class="segment-filter" {{ in_array((string) $value, array_map('strval', $profileOld), true) ? 'checked' : '' }}><span>{{ $value }}</span></label>
                                                        @endforeach
                                                    </div>
                                                    <p class="ad-field-help">複数選択可・いずれかに一致</p>
                                                @else
                                                    <input id="segment_profile_{{ $profileKey }}" name="segment_profile[{{ $profileKey }}]" class="form-control segment-filter" value="{{ old('segment_profile.'.$profileKey) }}" placeholder="{{ $profileLabel }}の値を入力" maxlength="255">
                                                    <p class="ad-field-help">入力値と完全一致</p>
                                                @endif
                                            </div>
                                        @endif
                                    @empty
                                        <div class="ad-profile-empty"><p>この店舗には追加プロフィール項目がまだありません。</p><a href="{{ admin_route('admin.settings.profile') }}">プロフィール設定で項目を追加する <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
                                    @endforelse
                                </div></div>
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

                <div class="andel-card" role="region" id="delivery-coupon" aria-label="クーポン設定">
                    <header class="andel-card-head">
                        <span class="andel-card-icon" aria-hidden="true"><i class="fas fa-ticket-alt"></i></span>
                        <div><p>任意</p><h2>クーポンを一緒に配布</h2><span>特典を付けたい場合に設定してください。</span></div>
                    </header>
                    @if(($view->coupons ?? collect())->isEmpty())
                        <div class="ad-empty-coupon">
                            <span><i class="fas fa-ticket-alt" aria-hidden="true"></i></span>
                            <div><strong>配布できるクーポンがありません</strong><p>先に有効なクーポンを作成してください。</p></div>
                            <a href="{{ admin_route('admin.coupons.create') }}" class="btn btn-outline-primary">クーポンを作成</a>
                        </div>
                    @else
                        <label class="ad-coupon-switch">
                            <input type="checkbox" id="attachCoupon" {{ $hasSelectedCoupon ? 'checked' : '' }}>
                            <span class="ad-switch-ui" aria-hidden="true"></span>
                            <span><strong>このお知らせでクーポンを配布する</strong><small>選択した各クーポンを、対象会員に1枚ずつ付与します</small></span>
                        </label>
                        <div class="ad-coupon-panel" id="couponPanel" {{ $hasSelectedCoupon ? '' : 'hidden' }}>
                            <div class="form-group mb-0">
                                <label for="delivery_coupon_id" class="form-label">配布するクーポン <span class="ad-required">必須</span></label>
                                <select name="delivery_coupon_ids[]" id="delivery_coupon_id" class="form-control" multiple size="{{ min(8, max(3, $view->coupons->count())) }}" {{ $hasSelectedCoupon ? '' : 'disabled' }}>
                                    @foreach($view->coupons as $coupon)
                                        @php
                                            $benefit = $coupon->type === 'percent'
                                                ? rtrim(rtrim((string) $coupon->value, '0'), '.').'％OFF'
                                                : number_format((float) $coupon->value).'円OFF';
                                            $period = $coupon->end_at ? $coupon->end_at->format('Y/m/d').'まで' : '期限なし';
                                        @endphp
                                        <option value="{{ $coupon->id }}" data-benefit="{{ $benefit }}" data-period="{{ $period }}" {{ $selectedCouponIds->contains((int) $coupon->id) ? 'selected' : '' }}>{{ $coupon->name }}</option>
                                    @endforeach
                                </select>
                                <p class="ad-field-help">複数選ぶ場合は、Macでは⌘、WindowsではCtrlを押しながらクリックしてください。</p>
                            </div>
                            <div class="ad-selected-coupon" id="selectedCouponPreview" hidden>
                                <span><i class="fas fa-ticket-alt" aria-hidden="true"></i></span>
                                <div><strong id="selectedCouponName"></strong><small><span id="selectedCouponBenefit"></span></small></div>
                                <span class="ad-coupon-ready"><i class="fas fa-check" aria-hidden="true"></i> 配布対象</span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="andel-card" role="region" id="delivery-options" aria-label="掲載設定">
                    <header class="andel-card-head">
                        <span class="andel-card-icon" aria-hidden="true"><i class="fas fa-bell"></i></span>
                        <div><p>STEP 3</p><h2>通知と掲載方法</h2><span>会員への通知と、アプリ内での表示方法を設定します。</span></div>
                    </header>
                    <div class="ad-option-grid">
                        <label class="ad-pin-option">
                            <input type="hidden" name="is_pinned" value="0">
                            <input type="checkbox" name="is_pinned" id="is_pinned" value="1" {{ old('is_pinned') ? 'checked' : '' }}>
                            <span class="ad-pin-icon"><i class="fas fa-thumbtack" aria-hidden="true"></i></span>
                            <span><strong>お知らせを固定表示</strong><small>一覧の上部に優先して表示します</small></span>
                            <span class="ad-check-ui"><i class="fas fa-check" aria-hidden="true"></i></span>
                        </label>
                        @php
                            $notifyOld = old('send_notification');
                            $isNotify = $notifyOld === null
                                ? true
                                : ($notifyOld === '1' || $notifyOld === 1 || $notifyOld === true);
                        @endphp
                        <label class="ad-pin-option">
                            <input type="hidden" name="send_notification" value="0">
                            <input type="checkbox" name="send_notification" id="send_notification" value="1" {{ $isNotify ? 'checked' : '' }}>
                            <span class="ad-pin-icon"><i class="fas fa-bell" aria-hidden="true"></i></span>
                            <span><strong>LINE・Push通知を送る</strong><small>オフにするとアプリ内掲載のみになります</small></span>
                            <span class="ad-check-ui"><i class="fas fa-check" aria-hidden="true"></i></span>
                        </label>
                        <div class="ad-expiry-option">
                            <span class="ad-pin-icon"><i class="far fa-calendar-xmark" aria-hidden="true"></i></span>
                            <div class="form-group mb-0">
                                <label for="expires_at" class="form-label">掲載終了日 <span class="ad-optional">任意</span></label>
                                <input type="date" name="expires_at" id="expires_at" class="form-control" value="{{ old('expires_at') }}">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="ad-delivery-actions" aria-label="最終確認へ進む">
                    <div id="deliveryFormError" class="ad-form-error" role="alert" hidden></div>
                    <div class="ad-form-footer">
                        <a href="{{ admin_route('admin.announcements') }}" class="btn btn-secondary">キャンセル</a>
                        <button type="submit" class="btn btn-primary ad-submit no-loading">
                            <span><i class="fas fa-paper-plane" aria-hidden="true"></i> 配信内容を確認する</span>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="ad-submit-note"><i class="fas fa-circle-info" aria-hidden="true"></i> 確認画面で内容を最終チェックしてから配信します</p>
            </div>
        </div>

        <div class="ad-review-modal" id="deliveryReviewModal" hidden>
            <button type="button" class="ad-review-backdrop" data-close-delivery-review aria-label="確認画面を閉じる"></button>
            <section class="ad-review-dialog" role="dialog" aria-modal="true" aria-labelledby="deliveryReviewTitle">
                <header class="ad-review-head">
                    <div>
                        <span>最終確認</span>
                        <h2 id="deliveryReviewTitle">配信内容の最終確認</h2>
                        <p>内容・対象者・通知方法を確認してから配信してください。</p>
                    </div>
                    <button type="button" class="ad-review-close" data-close-delivery-review aria-label="閉じる"><i class="fas fa-times" aria-hidden="true"></i></button>
                </header>

                <div class="ad-review-body" id="deliveryReviewBody">
                    <details class="ad-details ad-channel-preview-details">
                        <summary>配信後の見え方をプレビュー</summary>
                        <div class="ad-channel-previews" aria-label="配信後の見え方">
                        <section class="ad-channel-preview-card">
                            <header><span><i class="fas fa-mobile-screen-button" aria-hidden="true"></i> 会員アプリ</span><small>実際の画面</small></header>
                            @if($announcementPreviewUrl !== null)
                                <div class="ad-live-device-shell">
                                    <span class="ad-live-device-button ad-live-device-button--volume" aria-hidden="true"></span>
                                    <span class="ad-live-device-button ad-live-device-button--power" aria-hidden="true"></span>
                                    <div class="ad-live-device-frame">
                                        <iframe
                                            id="announcementLivePreview"
                                            title="実際の会員アプリのお知らせ画面"
                                            data-src="{{ $announcementPreviewUrl }}"
                                            src="about:blank"
                                        ></iframe>
                                    </div>
                                </div>
                            @else
                                <div class="ad-live-preview-unavailable"><i class="fas fa-mobile-screen" aria-hidden="true"></i><span>会員アプリのプレビューを取得できませんでした</span></div>
                            @endif
                        </section>

                        <section class="ad-channel-preview-card">
                            <header><span><i class="fab fa-line" aria-hidden="true"></i> LINE</span><small>トーク画面</small></header>
                            <div class="ad-line-device">
                                <div class="ad-line-status"><span>9:41</span><span><i class="fas fa-signal" aria-hidden="true"></i> <i class="fas fa-wifi" aria-hidden="true"></i> <i class="fas fa-battery-full" aria-hidden="true"></i></span></div>
                                <div class="ad-line-header"><i class="fas fa-chevron-left" aria-hidden="true"></i><div><strong>{{ $lineAccountName }}</strong><small>公式アカウント</small></div><i class="fas fa-bars" aria-hidden="true"></i></div>
                                <div class="ad-line-chat">
                                    <time>今日</time>
                                    <div class="ad-line-message-row">
                                        <span class="ad-line-avatar">{{ mb_substr($lineAccountName, 0, 1) }}</span>
                                        <div class="ad-line-flex-message">
                                            <div class="ad-line-hero" id="deliveryLineImageWrap" hidden><img id="deliveryLineImage" src="" alt=""></div>
                                            <div class="ad-line-flex-body">
                                                <strong id="deliveryLineTitle">お知らせのタイトル</strong>
                                                <p id="deliveryLineBody">お知らせの本文が表示されます。</p>
                                            </div>
                                            <div class="ad-line-flex-footer"><span>お知らせを見る</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                    </details>

                    <div class="ad-review-grid">
                        <section class="ad-review-card">
                            <h3><i class="fas fa-paper-plane" aria-hidden="true"></i> 配信設定</h3>
                            <dl>
                                <div><dt>届け先</dt><dd><button type="button" class="ad-review-audience-button" id="deliveryReviewAudienceButton" aria-expanded="false" aria-controls="deliveryReviewAudiencePanel"><span id="deliveryReviewAudienceLabel"></span><i class="fas fa-chevron-down" aria-hidden="true"></i></button></dd></div>
                                <div><dt>配信日時</dt><dd id="deliveryReviewTiming"></dd></div>
                                <div id="deliveryReviewResolveRow" hidden><dt>対象者の確定</dt><dd id="deliveryReviewResolve"></dd></div>
                                <div><dt>クーポン</dt><dd id="deliveryReviewCoupon"></dd></div>
                                <div><dt>固定表示</dt><dd id="deliveryReviewPinned"></dd></div>
                            </dl>
                        </section>

                        <section class="ad-review-card ad-review-notifications">
                            <h3><i class="fas fa-bell" aria-hidden="true"></i> 通知予定</h3>
                            <p class="ad-review-notify-off" id="deliveryReviewNotifyOff" hidden>通知は送りません。アプリ内のお知らせには掲載されます。</p>
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
                        <p>条件ごとに指定した AND / OR の順で対象者を判定します。</p>
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
    'membersUrl' => admin_route('admin.announcements.ajax.members'),
    'segmentPreviewUrl' => admin_route('admin.announcements.segment.preview'),
    'audiencePreviewUrl' => admin_route('admin.announcements.audience.preview'),
    'memberCount' => $view->memberCount,
    'selectedMemberIds' => $selectedMemberIds,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/announcement-delivery.css') }}?v={{ (int) @filemtime(public_path('css/admin/announcement-delivery.css')) }}">
@endpush
@endsection
