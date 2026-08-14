@extends('admin.layout')

@section('title', 'クーポン配布')
@section('page-title', 'クーポン配布')
@section('page-kind', 'coupon-bulk-issue')

@section('content')
@php
    $selectedCouponId = old('coupon_id', request('coupon_id'));
    $oldDeliveryType = old('delivery_type', $view->defaultDeliveryType ?? \App\Enums\CouponDeliveryType::IMMEDIATE_ALL);
    $createUrl = admin_route('admin.coupons.create', ['return' => 'bulk-issue']);
    $hasMeta = ($view->scheduledDeliveries ?? collect())->isNotEmpty() || ($view->hasSmaregiContract ?? false);
@endphp

<div class="coupon-admin">
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.coupons') }}">クーポン</a>
    <span class="breadcrumb-sep">/</span>
    <span>配布</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-gift"></i> クーポン配布</h1>
    <p class="page-header-desc">クーポンを選び、配信条件を指定して配布します。同じクーポンを何度でも配信できます。</p>
</div>

@if(session('success'))
    <div class="alert alert-success" role="alert"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-triangle"></i> {{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-error" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<ol class="bulk-steps" aria-label="配布の手順">
    <li class="bulk-step is-current" data-step="1">
        <span class="bulk-step-num">1</span>
        <span class="bulk-step-label">クーポン</span>
    </li>
    <li class="bulk-step" data-step="2">
        <span class="bulk-step-num">2</span>
        <span class="bulk-step-label">条件</span>
    </li>
    <li class="bulk-step" data-step="3">
        <span class="bulk-step-num">3</span>
        <span class="bulk-step-label">配布</span>
    </li>
</ol>

@if($hasMeta)
<div class="cp-toolbar">
    <span class="cp-stat">登録会員 <strong>{{ number_format($view->memberCount) }}</strong></span>
    @if($view->hasSmaregiContract ?? false)
    <span class="cp-stat">POS連携 <strong>{{ $view->smaregiLinkedCount ?? 0 }}</strong></span>
    @endif
    @if(($view->scheduledDeliveries ?? collect())->isNotEmpty())
    <span class="cp-stat"><i class="fas fa-clock"></i> 予約中 <strong>{{ $view->scheduledDeliveries->count() }}</strong></span>
    @endif
    <span class="cp-toolbar-spacer"></span>
    @if(($view->scheduledDeliveries ?? collect())->isNotEmpty())
    <details>
        <summary>予約一覧を見る</summary>
        <div class="cp-scheduled-list">
            @foreach($view->scheduledDeliveries as $sd)
            <div class="cp-scheduled-item">
                <strong>{{ $sd->coupon?->name ?? $sd->coupon?->code }}</strong>
                <span class="text-muted">{{ $sd->scheduled_at?->format('Y/m/d H:i') }}</span>
                <span class="badge badge-info">{{ $view->deliveryTypeOptions[$sd->delivery_type] ?? $sd->delivery_type }}</span>
            </div>
            @endforeach
        </div>
    </details>
    @endif
    @if($view->hasSmaregiContract ?? false)
    <details id="smaregi-sync">
        <summary><i class="fas fa-exchange-alt"></i> スマレジ POS</summary>
        <div class="cp-smaregi-actions">
            <form action="{{ admin_route('admin.coupons.sync-smaregi') }}" method="POST" onsubmit="return confirm('スマレジから取り込みます。よろしいですか？');">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fas fa-download"></i> 取り込み</button>
            </form>
            <form action="{{ admin_route('admin.coupons.push-smaregi') }}" method="POST" onsubmit="return confirm('未連携をPOSへ反映します。よろしいですか？');">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm" @if(($view->smaregiUnlinkedCount ?? 0) === 0) disabled @endif><i class="fas fa-upload"></i> 反映（{{ $view->smaregiUnlinkedCount ?? 0 }}件）</button>
            </form>
            <form action="{{ admin_route('admin.coupons.full-sync-smaregi') }}" method="POST" onsubmit="return confirm('双方向同期します。よろしいですか？');">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync-alt"></i> 完全同期</button>
            </form>
        </div>
    </details>
    @endif
</div>
@endif

<form action="{{ admin_route('admin.coupons.bulk-issue.store') }}" method="POST" id="bulkIssueForm">
    @csrf
    <input type="hidden" name="coupon_id" id="coupon_id" value="{{ $selectedCouponId }}">
    <input type="hidden" name="delivery_type" id="delivery_type" value="{{ $oldDeliveryType }}">

    <div class="cp-panel">
        <section class="cp-section" id="couponSelectSection">
            <div class="cp-section-head">
                <div>
                    <h2 class="cp-section-title"><span class="cp-section-num">1</span> クーポンを選ぶ</h2>
                    <p class="cp-section-desc">配布するクーポンを1つ選択</p>
                </div>
                <a href="{{ $createUrl }}" class="btn btn-secondary btn-sm"><i class="fas fa-plus"></i> 新規作成</a>
            </div>

            @if($view->coupons->total() === 0 && ($view->couponSearch ?? '') === '')
                <div class="empty-state py-4">
                    <i class="fas fa-ticket-alt"></i>
                    <p>有効なクーポンがありません</p>
                    <p class="empty-state-hint">「新規作成」からクーポンを作成して、この画面に戻ってください</p>
                    <a href="{{ $createUrl }}" class="btn btn-primary mt-2"><i class="fas fa-plus"></i> クーポンを作成</a>
                </div>
            @else
                @if($view->selectedCoupon !== null)
                <div class="coupon-picker-selected" id="couponPickerSelected">
                    <span>選択中: <strong>{{ $view->selectedCoupon->name ?? '—' }}</strong>（{{ $view->selectedCoupon->code }}）</span>
                    <a href="{{ admin_route('admin.coupons.redemptions', ['id' => $view->selectedCoupon->id]) }}" class="btn btn-outline-primary btn-sm">利用履歴</a>
                </div>
                @endif
                <div class="coupon-picker-search-form" id="couponPickerSearchForm">
                    <input type="search" id="couponPickerSearch" class="form-control form-control-sm cp-search" placeholder="名前・コードで絞り込み" value="{{ $view->couponSearch ?? '' }}" autocomplete="off">
                    <button type="button" class="btn btn-secondary btn-sm" id="couponPickerSearchBtn">検索</button>
                    @if(($view->couponSearch ?? '') !== '')
                        <a href="{{ admin_route('admin.coupons.bulk-issue', array_filter(['coupon_id' => $selectedCouponId])) }}" class="btn btn-outline-secondary btn-sm">クリア</a>
                    @endif
                </div>
                @if($view->coupons->total() === 0)
                <div class="empty-state py-3">
                    <p>条件に一致するクーポンがありません</p>
                </div>
                @else
                <div class="coupon-picker-list" id="couponPickerList">
                    @foreach($view->coupons as $c)
                    @php $deliveryCount = (int) ($view->deliveryCountByCouponId[$c->id] ?? 0); @endphp
                    <label class="coupon-picker-card {{ (string)$selectedCouponId === (string)$c->id ? 'is-selected' : '' }}"
                        data-search="{{ strtolower(($c->name ?? '') . ' ' . $c->code) }}">
                        <input type="radio" name="coupon_picker" value="{{ $c->id }}" class="coupon-picker-input" {{ (string)$selectedCouponId === (string)$c->id ? 'checked' : '' }}
                            data-code="{{ $c->code }}"
                            data-name="{{ $c->name }}"
                            data-discount="{{ $c->type === 'percent' ? rtrim(rtrim(number_format((float)$c->value, 2, '.', ''), '0'), '.') . '%' : '¥' . number_format((float)$c->value) }}">
                        <span class="coupon-picker-main">
                            <span class="coupon-picker-name">{{ $c->name ?? '—' }}</span>
                            <span class="coupon-picker-code">{{ $c->code }}</span>
                        </span>
                        <span class="coupon-picker-meta">
                            {{ $c->type === 'percent' ? rtrim(rtrim(number_format((float)$c->value, 2, '.', ''), '0'), '.') . '% OFF' : '¥' . number_format((float)$c->value) . ' OFF' }}
                            @if($view->hasSmaregiContract ?? false)
                                · {{ $c->smaregi_coupon_id ? 'POS済' : '未連携' }}
                            @endif
                            · 配信{{ $deliveryCount }}回
                            · 利用{{ (int) $c->used_count }}回
                        </span>
                        <a href="{{ admin_route('admin.coupons.redemptions', ['id' => $c->id]) }}" class="coupon-picker-redemptions" onclick="event.stopPropagation();">利用履歴</a>
                    </label>
                    @endforeach
                </div>
                {{ $view->coupons->appends(array_filter(['q' => $view->couponSearch ?? '', 'coupon_id' => $selectedCouponId]))->links('vendor.pagination.admin') }}
                @endif
            @endif
        </section>

        <section class="cp-section" id="deliverySection">
            <div class="cp-section-head">
                <div>
                    <h2 class="cp-section-title"><span class="cp-section-num">2</span> 配信条件</h2>
                    <p class="cp-section-desc">配信種別を選んで条件を指定</p>
                </div>
            </div>

            <div id="selectedCouponPreview" class="bulk-selected-coupon {{ $selectedCouponId ? '' : 'is-empty' }}">
                <i class="fas fa-ticket-alt"></i>
                <div>
                    <span class="bulk-selected-label">選択中</span>
                    <strong id="selectedCouponLabel">
                        @if($selectedCouponId)
                            @php $sel = $view->coupons->firstWhere('id', (int)$selectedCouponId); @endphp
                            {{ $sel ? ($sel->name . '（' . $sel->code . '）') : '—' }}
                        @else
                            未選択 — 上でクーポンを選んでください
                        @endif
                    </strong>
                </div>
            </div>

            <div class="form-group mb-0">
                <label class="form-label">配信種別 <span class="text-danger">*</span></label>
                <div class="delivery-mode-grid" id="deliveryModeOptions">
                    <label class="delivery-mode-card">
                        <input type="radio" name="delivery_mode" value="all" data-mode="all">
                        <span class="delivery-mode-card-body">
                            <span class="delivery-mode-icon"><i class="fas fa-users"></i></span>
                            <span class="delivery-mode-title">全員にすぐ配布</span>
                            <span class="delivery-mode-desc">{{ number_format($view->memberCount) }}人</span>
                        </span>
                    </label>
                    <label class="delivery-mode-card">
                        <input type="radio" name="delivery_mode" value="segment" data-mode="segment">
                        <span class="delivery-mode-card-body">
                            <span class="delivery-mode-icon"><i class="fas fa-filter"></i></span>
                            <span class="delivery-mode-title">セグメント配信</span>
                            <span class="delivery-mode-desc">条件で絞り込み</span>
                        </span>
                    </label>
                    <label class="delivery-mode-card">
                        <input type="radio" name="delivery_mode" value="personal" data-mode="personal">
                        <span class="delivery-mode-card-body">
                            <span class="delivery-mode-icon"><i class="fas fa-user"></i></span>
                            <span class="delivery-mode-title">個人宛配信</span>
                            <span class="delivery-mode-desc">指定会員のみ</span>
                        </span>
                    </label>
                    <label class="delivery-mode-card">
                        <input type="radio" name="delivery_mode" value="scheduled" data-mode="scheduled">
                        <span class="delivery-mode-card-body">
                            <span class="delivery-mode-icon"><i class="fas fa-clock"></i></span>
                            <span class="delivery-mode-title">予約配信</span>
                            <span class="delivery-mode-desc">日時を指定</span>
                        </span>
                    </label>
                </div>
            </div>

            <div id="scheduledBlock" class="delivery-option-block is-hidden">
                <div class="form-group mb-3">
                    <label for="scheduled_at" class="form-label">配布日時 <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" style="max-width:280px">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">予約の配信対象 <span class="text-danger">*</span></label>
                    <div class="delivery-type-options" id="scheduledAudienceOptions">
                        <label class="radio-label">
                            <input type="radio" name="scheduled_audience" value="all" {{ old('scheduled_audience', 'all') === 'all' ? 'checked' : '' }}>
                            <span>全員</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="scheduled_audience" value="segment" {{ old('scheduled_audience') === 'segment' ? 'checked' : '' }}>
                            <span>セグメント</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="scheduled_audience" value="personal" {{ old('scheduled_audience') === 'personal' ? 'checked' : '' }}>
                            <span>個人宛</span>
                        </label>
                    </div>
                </div>
            </div>

            <div id="personalBlock" class="delivery-option-block is-hidden">
                <label class="form-label">配信対象の会員</label>
                <p class="form-text text-muted mb-2">会員番号または名前で検索</p>
                <input type="text" id="memberSearch" class="form-control mb-2" placeholder="会員番号・名前で検索..." autocomplete="off">
                <div id="memberSearchResults" class="member-search-results is-hidden"></div>
                <div id="selectedMembers" class="selected-members"></div>
                <div id="targetMemberIdsHidden"></div>
            </div>

            <div id="segmentBlock" class="delivery-option-block is-hidden">
                <label class="form-label">配信の絞り込み</label>
                <p class="form-text text-muted mb-2">プリセットを選ぶか、詳細条件を組み合わせてください</p>

                <div class="segment-preset-chips mb-3" id="segmentPresetChips" role="group" aria-label="配信プリセット">
                    <button type="button" class="segment-preset-chip is-active" data-preset="">条件を自由に設定</button>
                    <button type="button" class="segment-preset-chip" data-preset="rank">指定ランク</button>
                    <button type="button" class="segment-preset-chip" data-preset="rank_up_arrival">ランクアップ達成配信</button>
                    <button type="button" class="segment-preset-chip" data-preset="stamp">スタンプ累計</button>
                    <button type="button" class="segment-preset-chip" data-preset="days_after_visit">最終来店からN日後</button>
                    <button type="button" class="segment-preset-chip" data-preset="birthday">誕生月</button>
                    <button type="button" class="segment-preset-chip" data-preset="new_members">新規会員</button>
                    <button type="button" class="segment-preset-chip" data-preset="inactive">休眠会員</button>
                </div>
                <input type="hidden" name="segment_preset" id="segment_preset" value="{{ old('segment_preset', '') }}">

                <div id="rankBlock" class="segment-preset-panel is-hidden">
                    <label for="target_rank" class="form-label" id="targetRankLabel">対象ランク <span class="text-danger">*</span></label>
                    <select name="target_rank" id="target_rank" class="form-control" style="max-width:280px">
                        <option value="">選択してください</option>
                        @foreach(($view->rankOptions ?? []) as $rank)
                            <option value="{{ $rank['key'] }}" {{ old('target_rank', old('segment_rank')) === $rank['key'] ? 'selected' : '' }}>{{ $rank['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="form-text text-muted" id="targetRankHelp">いまそのランクの会員へ配布します</p>
                </div>

                <div id="stampBlock" class="segment-preset-panel is-hidden">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label for="stamp_card_id" class="form-label">スタンプカード</label>
                            <select name="stamp_card_id" id="stamp_card_id" class="form-control">
                                <option value="">すべてのカード</option>
                                @foreach(($view->stampCards ?? []) as $card)
                                    <option value="{{ $card->id }}" {{ (string) old('stamp_card_id', old('segment_stamp_card_id')) === (string) $card->id ? 'selected' : '' }}>
                                        {{ $card->name }}（目標 {{ $card->required_stamps }}）
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="stamp_count_min" class="form-label">累計スタンプ以上</label>
                            <input type="number" name="stamp_count_min" id="stamp_count_min" class="form-control" value="{{ old('stamp_count_min', old('segment_stamp_count_min')) }}" min="0" placeholder="例: 5">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <label class="checkbox-label mb-2">
                                <input type="checkbox" name="stamp_completed" id="stamp_completed" value="1" {{ old('stamp_completed', old('segment_stamp_completed')) ? 'checked' : '' }}>
                                <span>達成済みのみ</span>
                            </label>
                        </div>
                    </div>
                    <p class="form-text text-muted">カード・枚数・達成のいずれかを指定してください</p>
                </div>

                <div id="daysAfterVisitBlock" class="segment-preset-panel is-hidden">
                    <label for="days_after_last_visit" class="form-label">最終来店から何日後 <span class="text-danger">*</span></label>
                    <div class="d-flex align-items-center gap-2" style="max-width:320px">
                        <input type="number" name="days_after_last_visit" id="days_after_last_visit" class="form-control" value="{{ old('days_after_last_visit', 7) }}" min="1" max="730">
                        <span class="text-muted">日後</span>
                    </div>
                    <p class="form-text text-muted">最終来店日が「今日から見てちょうどN日前」の会員が対象です</p>
                </div>

                <div id="birthdayBlock" class="segment-preset-panel is-hidden">
                    <label for="birthday_month" class="form-label">誕生月</label>
                    <select name="birthday_month" id="birthday_month" class="form-control" style="max-width:200px">
                        @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (int)old('birthday_month', now()->month) === $m ? 'selected' : '' }}>{{ $m }}月</option>
                        @endfor
                    </select>
                </div>
                <div id="newMemberBlock" class="segment-preset-panel is-hidden">
                    <label for="new_member_days" class="form-label">登録から何日以内</label>
                    <select name="new_member_days" id="new_member_days" class="form-control" style="max-width:200px">
                        <option value="7" {{ old('new_member_days', 30) == 7 ? 'selected' : '' }}>7日以内</option>
                        <option value="14" {{ old('new_member_days') == 14 ? 'selected' : '' }}>14日以内</option>
                        <option value="30" {{ old('new_member_days', 30) == 30 ? 'selected' : '' }}>30日以内</option>
                        <option value="90" {{ old('new_member_days') == 90 ? 'selected' : '' }}>90日以内</option>
                    </select>
                </div>
                <div id="inactiveBlock" class="segment-preset-panel is-hidden">
                    <label for="inactive_days" class="form-label">来店・購入がない期間</label>
                    <select name="inactive_days" id="inactive_days" class="form-control" style="max-width:200px">
                        <option value="30" {{ old('inactive_days') == 30 ? 'selected' : '' }}>30日以上</option>
                        <option value="60" {{ old('inactive_days') == 60 ? 'selected' : '' }}>60日以上</option>
                        <option value="90" {{ old('inactive_days', 90) == 90 ? 'selected' : '' }}>90日以上</option>
                        <option value="180" {{ old('inactive_days') == 180 ? 'selected' : '' }}>180日以上</option>
                    </select>
                </div>

                <div id="customSegmentPanel" class="segment-custom-panel">
                    <h3 class="segment-section-title">基本属性</h3>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="segment_channel" class="form-label">登録チャネル</label>
                            <select name="segment_channel" id="segment_channel" class="form-control segment-filter">
                                <option value="">指定しない</option>
                                @foreach(\App\Support\MemberChannel::filterOptions() as $value => $label)
                                    <option value="{{ $value }}" {{ old('segment_channel') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="segment_birthday_month" class="form-label">誕生月</label>
                            <select name="segment_birthday_month" id="segment_birthday_month" class="form-control segment-filter">
                                <option value="">指定しない</option>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ (int) old('segment_birthday_month') === $m ? 'selected' : '' }}>{{ $m }}月</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="segment_sex" class="form-label">性別</label>
                            <select name="segment_sex" id="segment_sex" class="form-control segment-filter">
                                <option value="">指定しない</option>
                                <option value="1" {{ old('segment_sex') === '1' ? 'selected' : '' }}>男性</option>
                                <option value="2" {{ old('segment_sex') === '2' ? 'selected' : '' }}>女性</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="segment_rank" class="form-label">会員ランク</label>
                            <select name="segment_rank" id="segment_rank" class="form-control segment-filter">
                                <option value="">指定しない</option>
                                @foreach(($view->rankOptions ?? []) as $rank)
                                    <option value="{{ $rank['key'] }}" {{ old('segment_rank') === $rank['key'] ? 'selected' : '' }}>{{ $rank['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="segment_visit_count_min" class="form-label">来店回数（以上）</label>
                            <input type="number" name="segment_visit_count_min" id="segment_visit_count_min" class="form-control segment-filter" value="{{ old('segment_visit_count_min') }}" min="0" placeholder="例: 3">
                        </div>
                        <div class="col-md-4">
                            <label for="segment_visit_count_max" class="form-label">来店回数（以下）</label>
                            <input type="number" name="segment_visit_count_max" id="segment_visit_count_max" class="form-control segment-filter" value="{{ old('segment_visit_count_max') }}" min="0" placeholder="例: 10">
                        </div>
                    </div>

                    <h3 class="segment-section-title">来店・ポイント・商品</h3>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="segment_last_visit_mode" class="form-label">最終来店</label>
                            <select name="segment_last_visit_mode" id="segment_last_visit_mode" class="form-control segment-filter">
                                <option value="">指定しない</option>
                                <option value="inactive_days" {{ old('segment_last_visit_mode') === 'inactive_days' ? 'selected' : '' }}>N日以上来店なし</option>
                                <option value="visited_within_days" {{ old('segment_last_visit_mode') === 'visited_within_days' ? 'selected' : '' }}>N日以内に来店あり</option>
                                <option value="days_after" {{ old('segment_last_visit_mode') === 'days_after' ? 'selected' : '' }}>最終来店からちょうどN日後</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="segment_last_visit_days" class="form-label">日数（任意入力可）</label>
                            <input type="number" name="segment_last_visit_days" id="segment_last_visit_days" class="form-control segment-filter" value="{{ old('segment_last_visit_days', 30) }}" min="1" max="730" placeholder="例: 14">
                        </div>
                        <div class="col-md-4">
                            <label for="segment_product_id" class="form-label">購入した商品</label>
                            <select name="segment_product_id" id="segment_product_id" class="form-control segment-filter">
                                <option value="">指定しない</option>
                                @foreach(($view->products ?? []) as $product)
                                    <option value="{{ $product->id }}" {{ (string) old('segment_product_id') === (string) $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="segment_lifetime_points_min" class="form-label">累計付与ポイント（以上）</label>
                            <input type="number" name="segment_lifetime_points_min" id="segment_lifetime_points_min" class="form-control segment-filter" value="{{ old('segment_lifetime_points_min') }}" min="0">
                        </div>
                        <div class="col-md-4">
                            <label for="segment_lifetime_points_max" class="form-label">累計付与ポイント（以下）</label>
                            <input type="number" name="segment_lifetime_points_max" id="segment_lifetime_points_max" class="form-control segment-filter" value="{{ old('segment_lifetime_points_max') }}" min="0">
                        </div>
                    </div>

                    <h3 class="segment-section-title">スタンプ（詳細条件）</h3>
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label for="segment_stamp_card_id" class="form-label">スタンプカード</label>
                            <select name="segment_stamp_card_id" id="segment_stamp_card_id" class="form-control segment-filter">
                                <option value="">指定しない</option>
                                @foreach(($view->stampCards ?? []) as $card)
                                    <option value="{{ $card->id }}" {{ (string) old('segment_stamp_card_id') === (string) $card->id ? 'selected' : '' }}>{{ $card->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="segment_stamp_count_min" class="form-label">累計以上</label>
                            <input type="number" name="segment_stamp_count_min" id="segment_stamp_count_min" class="form-control segment-filter" value="{{ old('segment_stamp_count_min') }}" min="0">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <label class="checkbox-label mb-2">
                                <input type="checkbox" name="segment_stamp_completed" id="segment_stamp_completed" value="1" class="segment-filter" {{ old('segment_stamp_completed') ? 'checked' : '' }}>
                                <span>達成済みのみ</span>
                            </label>
                        </div>
                    </div>

                    <h3 class="segment-section-title">売上条件（任意）</h3>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label for="segment_period_days" class="form-label">対象期間</label>
                            <select name="segment_period_days" id="segment_period_days" class="form-control segment-filter">
                                <option value="7" {{ old('segment_period_days', 30) == 7 ? 'selected' : '' }}>直近7日間</option>
                                <option value="30" {{ old('segment_period_days', 30) == 30 ? 'selected' : '' }}>直近30日間</option>
                                <option value="90" {{ old('segment_period_days') == 90 ? 'selected' : '' }}>直近90日間</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="radio-inline mr-3">
                            <input type="radio" name="segment_condition" value="" {{ old('segment_condition', '') === '' || old('segment_condition') === null ? 'checked' : '' }} class="segment-filter">
                            <span>指定しない</span>
                        </label>
                        <label class="radio-inline mr-3">
                            <input type="radio" name="segment_condition" value="min_amount" {{ old('segment_condition') === 'min_amount' ? 'checked' : '' }} class="segment-filter">
                            <span>購入額以上</span>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="segment_condition" value="no_orders" {{ old('segment_condition') === 'no_orders' ? 'checked' : '' }} class="segment-filter">
                            <span>未購入</span>
                        </label>
                    </div>
                    <div id="segmentMinAmountBlock" class="row" style="display: none;">
                        <div class="col-md-4">
                            <label for="segment_min_amount" class="form-label">最低購入額（円）</label>
                            <input type="number" name="segment_min_amount" id="segment_min_amount" class="form-control segment-filter" value="{{ old('segment_min_amount', 5000) }}" min="0" step="1">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bulk-target-preview" id="targetPreview">
                <i class="fas fa-users"></i>
                <span id="targetPreviewText">クーポンを選択すると発行予定人数が表示されます</span>
                <button type="button" class="btn btn-outline-primary btn-sm ms-auto" id="previewMembersBtn" disabled>対象を確認</button>
            </div>

            <p class="cp-hint"><i class="fas fa-bell"></i> 即時配布は完了後すぐに通知されます。予約配信は指定日時に自動実行されます。</p>

            <div class="cp-actions form-actions">
                <button type="submit" class="btn btn-primary" id="submitDeliveryBtn" disabled>
                    <i class="fas fa-paper-plane"></i> <span id="submitDeliveryLabel">配布する</span>
                </button>
                <a href="{{ admin_route('admin.coupons') }}" class="btn btn-secondary">一覧に戻る</a>
            </div>
        </section>
    </div>
</form>

<section class="cp-history" id="deliveryHistorySection">
    <div class="cp-history-head">
        <h2><i class="fas fa-history"></i> 配信履歴</h2>
        <p>配信ごとの記録。同じクーポンを使い回しても行が増えます。</p>
    </div>
    <div class="card-body">
        @if(count($view->deliveryHistories ?? []) === 0)
        <div class="empty-state py-4">
            <i class="fas fa-history"></i>
            <p>まだ配信履歴がありません</p>
            <p class="empty-state-hint">配布するとここに履歴が表示されます</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table bulk-delivery-history-table">
                <thead>
                    <tr>
                        <th>日時</th>
                        <th>クーポン</th>
                        <th>配信種別</th>
                        <th>配信対象</th>
                        <th>状態</th>
                        <th>発行数</th>
                        <th class="table-actions-cell">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($view->deliveryHistories as $history)
                    <tr>
                        <td>{{ $history['timing_label'] }}</td>
                        <td>
                            <strong>{{ $history['coupon_name'] }}</strong>
                            <span class="d-block text-muted small"><code>{{ $history['coupon_code'] }}</code></span>
                        </td>
                        <td>{{ $history['delivery_type_label'] }}</td>
                        <td class="bulk-delivery-recipients" title="{{ $history['recipients_title'] }}">{{ $history['recipients_label'] }}</td>
                        <td><span class="badge badge-{{ $history['status_badge'] }}">{{ $history['status_label'] }}</span></td>
                        <td>{{ $history['issued_count_label'] }}</td>
                        <td class="table-actions-cell">
                            <div class="table-actions">
                                <a href="{{ admin_route('admin.coupons.deliveries.show', ['id' => $history['id']]) }}" class="btn btn-action btn-edit"><i class="fas fa-eye"></i> 詳細</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>
</div>

<div id="previewMembersModal" class="cp-modal is-hidden" role="dialog" aria-modal="true" aria-labelledby="previewMembersModalTitle">
    <div class="cp-modal-backdrop" data-close-preview-modal></div>
    <div class="cp-modal-panel">
        <div class="cp-modal-head">
            <h3 id="previewMembersModalTitle">配信対象の確認</h3>
            <button type="button" class="btn btn-secondary btn-sm" data-close-preview-modal>閉じる</button>
        </div>
        <p class="cp-modal-note text-muted small" id="previewMembersModalNote"></p>
        <div class="cp-modal-body" id="previewMembersModalBody"></div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/coupons.css') }}?v={{ config('app.asset_version') }}">
@endpush
@push('scripts')
<script>
window.CouponBulkIssueConfig = {
    membersUrl: @json(admin_route('admin.coupons.ajax.members')),
    previewUrl: @json(admin_route('admin.coupons.delivery.preview')),
    previewMembersUrl: @json(admin_route('admin.coupons.delivery.preview-members')),
    bulkIssueUrl: @json(admin_route('admin.coupons.bulk-issue')),
    memberCount: {{ (int)$view->memberCount }},
    initialDeliveryType: @json($oldDeliveryType),
    initialSelectedMembers: @json(old('target_member_ids', [])),
};
</script>
@endpush
@endsection
