@extends('admin.layout')

@section('title', 'クーポン配布')
@section('page-title', 'クーポン配布')
@section('page-kind', 'coupon-bulk-issue')

@section('content')
@php
    $couponSource = old('coupon_source', request('coupon_source', $coupons->isNotEmpty() ? 'existing' : 'new'));
    $selectedCouponId = old('coupon_id');
    $defaultDeliveryType = old('delivery_type', $defaultDeliveryType);
@endphp

<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.coupons') }}">クーポン</a>
    <span class="breadcrumb-sep">/</span>
    <span>配布</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-gift"></i> クーポン配布</h1>
    <p class="page-header-desc">クーポンを新規作成するか既存クーポンを選び、配信種別に応じて会員へ配布します。</p>
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

<div class="bulk-layout">
    <aside class="bulk-sidebar">
        <div class="bulk-sidebar-card">
            <h3 class="bulk-sidebar-title">サマリー</h3>
            <dl class="bulk-summary-list">
                <div><dt>有効クーポン</dt><dd>{{ $coupons->count() }}件</dd></div>
                <div><dt>登録会員</dt><dd>{{ number_format($memberCount) }}人</dd></div>
                @if($hasSmaregiContract ?? false)
                <div><dt>POS連携済</dt><dd class="text-success">{{ $smaregiLinkedCount ?? 0 }}件</dd></div>
                <div><dt>未連携</dt><dd class="text-warning">{{ $smaregiUnlinkedCount ?? 0 }}件</dd></div>
                @endif
            </dl>
        </div>

        @if(($scheduledDeliveries ?? collect())->isNotEmpty())
        <div class="bulk-sidebar-card">
            <h3 class="bulk-sidebar-title"><i class="fas fa-clock"></i> 予約中</h3>
            <ul class="bulk-scheduled-list">
                @foreach($scheduledDeliveries as $sd)
                <li>
                    <strong>{{ $sd->coupon?->name ?? $sd->coupon?->code }}</strong>
                    <span class="d-block text-muted small">{{ $sd->scheduled_at?->format('Y/m/d H:i') }}</span>
                    <span class="badge badge-info">{{ $deliveryTypeOptions[$sd->delivery_type] ?? $sd->delivery_type }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($hasSmaregiContract ?? false)
        <details class="bulk-sidebar-card bulk-smaregi-details" id="smaregi-sync">
            <summary><i class="fas fa-exchange-alt"></i> スマレジ POS 連携</summary>
            <div class="bulk-smaregi-actions">
                <form action="{{ admin_route('admin.coupons.sync-smaregi') }}" method="POST" onsubmit="return confirm('スマレジから取り込みます。よろしいですか？');">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm btn-block"><i class="fas fa-download"></i> 取り込み</button>
                </form>
                <form action="{{ admin_route('admin.coupons.push-smaregi') }}" method="POST" onsubmit="return confirm('未連携をPOSへ反映します。よろしいですか？');">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm btn-block" @if(($smaregiUnlinkedCount ?? 0) === 0) disabled @endif><i class="fas fa-upload"></i> 反映（{{ $smaregiUnlinkedCount ?? 0 }}件）</button>
                </form>
                <form action="{{ admin_route('admin.coupons.full-sync-smaregi') }}" method="POST" onsubmit="return confirm('双方向同期します。よろしいですか？');">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-sync-alt"></i> 完全同期</button>
                </form>
            </div>
        </details>
        @else
        <div class="bulk-sidebar-card bulk-sidebar-hint">
            <p class="small text-muted mb-0"><i class="fas fa-info-circle"></i> <a href="{{ admin_route('admin.settings.smaregi') }}">スマレジ設定</a>で契約を紐付けると POS へ自動反映できます。</p>
        </div>
        @endif
    </aside>

    <div class="bulk-main">
        {{-- Step 1: クーポン選択 --}}
        <section class="card mb-4">
            <div class="card-header">
                <h2 class="card-title"><span class="bulk-step-badge">1</span> クーポンを選ぶ</h2>
            </div>
            <div class="card-body">
                <div class="coupon-source-tabs" role="tablist">
                    <button type="button" class="coupon-source-tab {{ $couponSource === 'existing' ? 'is-active' : '' }}" data-source="existing" role="tab">
                        <i class="fas fa-ticket-alt"></i>
                        <span>既存クーポンを使う</span>
                        <small>登録済みから選択</small>
                    </button>
                    <button type="button" class="coupon-source-tab {{ $couponSource === 'new' ? 'is-active' : '' }}" data-source="new" role="tab">
                        <i class="fas fa-plus-circle"></i>
                        <span>新規作成</span>
                        <small>その場でクーポンを作る</small>
                    </button>
                </div>

                <div id="panelExisting" class="coupon-source-panel {{ $couponSource === 'existing' ? '' : 'is-hidden' }}">
                    @if($coupons->isEmpty())
                        <div class="empty-state py-4">
                            <i class="fas fa-ticket-alt"></i>
                            <p>有効なクーポンがありません</p>
                            <p class="empty-state-hint">「新規作成」タブからクーポンを作成してください</p>
                        </div>
                    @else
                        <p class="text-muted small mb-3">配布するクーポンを1つ選んでください。</p>
                        <div class="coupon-picker-grid">
                            @foreach($coupons as $c)
                            <label class="coupon-picker-card {{ (string)$selectedCouponId === (string)$c->id ? 'is-selected' : '' }}">
                                <input type="radio" name="coupon_picker" value="{{ $c->id }}" class="coupon-picker-input" {{ (string)$selectedCouponId === (string)$c->id ? 'checked' : '' }}
                                    data-code="{{ $c->code }}"
                                    data-name="{{ $c->name }}"
                                    data-discount="{{ $c->type === 'percent' ? rtrim(rtrim(number_format((float)$c->value, 2, '.', ''), '0'), '.') . '%' : '¥' . number_format((float)$c->value) }}">
                                <span class="coupon-picker-code">{{ $c->code }}</span>
                                <span class="coupon-picker-name">{{ $c->name ?? '—' }}</span>
                                <span class="coupon-picker-meta">
                                    {{ $c->type === 'percent' ? rtrim(rtrim(number_format((float)$c->value, 2, '.', ''), '0'), '.') . '% OFF' : '¥' . number_format((float)$c->value) . ' OFF' }}
                                    @if($hasSmaregiContract ?? false)
                                        · {{ $c->smaregi_coupon_id ? 'POS連携済' : '未連携' }}
                                    @endif
                                </span>
                            </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div id="panelNew" class="coupon-source-panel {{ $couponSource === 'new' ? '' : 'is-hidden' }}">
                    <form action="{{ admin_route('admin.coupons.quick-create') }}" method="POST" id="quickCreateForm">
                        @csrf
                        <input type="hidden" name="coupon_source" value="existing">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="quick_code" class="form-label">コード <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="quick_code" class="form-control" value="{{ old('code') }}" required maxlength="50" placeholder="WELCOME10">
                            </div>
                            <div class="col-md-5">
                                <label for="quick_name" class="form-label">名前 <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="quick_name" class="form-control" value="{{ old('name') }}" required maxlength="255" placeholder="初回10%オフ">
                            </div>
                            <div class="col-md-2">
                                <label for="quick_type" class="form-label">種別</label>
                                <select name="type" id="quick_type" class="form-control">
                                    <option value="percent" {{ old('type', 'percent') === 'percent' ? 'selected' : '' }}>％</option>
                                    <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>円</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="quick_value" class="form-label">値 <span class="text-danger">*</span></label>
                                <input type="number" name="value" id="quick_value" class="form-control" value="{{ old('value', 10) }}" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label for="quick_description" class="form-label">説明</label>
                                <input type="text" name="description" id="quick_description" class="form-control" value="{{ old('description') }}" maxlength="2000" placeholder="任意">
                            </div>
                            <div class="col-md-2">
                                <label for="quick_start_at" class="form-label">開始日</label>
                                <input type="date" name="start_at" id="quick_start_at" class="form-control" value="{{ old('start_at') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="quick_end_at" class="form-label">終了日</label>
                                <input type="date" name="end_at" id="quick_end_at" class="form-control" value="{{ old('end_at') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="quick_use_display" class="form-label">使用時表示</label>
                                <select name="use_display" id="quick_use_display" class="form-control">
                                    @foreach(\App\Models\Coupon::getUseDisplayOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('use_display', \App\Models\Coupon::USE_DISPLAY_BARCODE) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 作成して配布へ@if($hasSmaregiContract ?? false)（POS反映）@endif</button>
                            <a href="{{ admin_route('admin.coupons.create') }}" class="btn btn-link btn-sm">デザイン・詳細設定</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        {{-- Step 2: 配布 --}}
        <section class="card mb-4" id="deliverySection">
            <div class="card-header">
                <h2 class="card-title"><span class="bulk-step-badge">2</span> 配布設定</h2>
                <p class="card-desc text-muted small mb-0 mt-1">配信種別を選び、対象会員を指定して配布または予約します。</p>
            </div>
            <div class="card-body">
                <form action="{{ admin_route('admin.coupons.bulk-issue.store') }}" method="POST" id="bulkIssueForm">
                    @csrf
                    <input type="hidden" name="coupon_id" id="coupon_id" value="{{ $selectedCouponId }}">
                    <input type="hidden" name="coupon_source" id="coupon_source_field" value="{{ $couponSource }}">

                    <div id="selectedCouponPreview" class="bulk-selected-coupon {{ $selectedCouponId ? '' : 'is-empty' }}">
                        <i class="fas fa-ticket-alt"></i>
                        <div>
                            <span class="bulk-selected-label">選択中のクーポン</span>
                            <strong id="selectedCouponLabel">@if($selectedCouponId)@php $sel = $coupons->firstWhere('id', (int)$selectedCouponId); @endphp{{ $sel ? ($sel->name . '（' . $sel->code . '）') : '—' }}@else未選択 — 上でクーポンを選ぶか作成してください@endif</strong>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">配信種別 <span class="text-danger">*</span></label>
                        @foreach($deliveryGroups as $groupKey => $group)
                        <div class="delivery-type-group">
                            <h3 class="delivery-type-group-title"><i class="fas {{ $group['icon'] }}"></i> {{ $group['label'] }}</h3>
                            <div class="delivery-type-grid">
                                @foreach($group['types'] as $type)
                                <label class="delivery-type-card">
                                    <input type="radio" name="delivery_type" value="{{ $type }}" {{ $defaultDeliveryType === $type ? 'checked' : '' }} data-type="{{ $type }}">
                                    <span class="delivery-type-card-body">
                                        <span class="delivery-type-card-title">{{ $deliveryTypeOptions[$type] ?? $type }}</span>
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div id="personalBlock" class="delivery-option-block is-hidden">
                        <label class="form-label">配信対象の会員</label>
                        <input type="text" id="memberSearch" class="form-control mb-2" placeholder="会員番号・名前で検索..." autocomplete="off">
                        <div id="memberSearchResults" class="member-search-results is-hidden"></div>
                        <div id="selectedMembers" class="selected-members"></div>
                        <div id="targetMemberIdsHidden"></div>
                    </div>

                    <div id="segmentBlock" class="delivery-option-block is-hidden">
                        <label class="form-label">売上条件</label>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <select name="segment_period_days" id="segment_period_days" class="form-control">
                                    <option value="7" {{ old('segment_period_days', 30) == 7 ? 'selected' : '' }}>直近7日</option>
                                    <option value="30" {{ old('segment_period_days', 30) == 30 ? 'selected' : '' }}>直近30日</option>
                                    <option value="90" {{ old('segment_period_days') == 90 ? 'selected' : '' }}>直近90日</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="radio-inline mr-3">
                                <input type="radio" name="segment_condition" value="min_amount" {{ old('segment_condition', 'min_amount') === 'min_amount' ? 'checked' : '' }}>
                                購入額が一定以上
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="segment_condition" value="no_orders" {{ old('segment_condition') === 'no_orders' ? 'checked' : '' }}>
                                未購入
                            </label>
                        </div>
                        <div id="segmentMinAmountBlock" class="row">
                            <div class="col-md-4">
                                <input type="number" name="segment_min_amount" id="segment_min_amount" class="form-control" value="{{ old('segment_min_amount', 5000) }}" min="0" placeholder="最低購入額（円）">
                            </div>
                        </div>
                    </div>

                    <div id="attributeSegmentBlock" class="delivery-option-block is-hidden">
                        <label class="form-label">属性条件（AND）</label>
                        <p class="form-text text-muted mb-2">指定した条件をすべて満たす会員に配布します</p>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label for="segment_channel" class="form-label">登録チャネル</label>
                                <select name="segment_channel" id="segment_channel" class="form-control attr-segment-filter">
                                    <option value="">指定しない</option>
                                    @foreach(\App\Support\MemberChannel::filterOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('segment_channel') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="segment_birthday_month" class="form-label">誕生月</label>
                                <select name="segment_birthday_month" id="segment_birthday_month" class="form-control attr-segment-filter">
                                    <option value="">指定しない</option>
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ (int) old('segment_birthday_month') === $m ? 'selected' : '' }}>{{ $m }}月</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="segment_sex" class="form-label">性別</label>
                                <select name="segment_sex" id="segment_sex" class="form-control attr-segment-filter">
                                    <option value="">指定しない</option>
                                    <option value="1" {{ old('segment_sex') === '1' ? 'selected' : '' }}>男性</option>
                                    <option value="2" {{ old('segment_sex') === '2' ? 'selected' : '' }}>女性</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label for="segment_visit_count_min" class="form-label">来店回数（以上）</label>
                                <input type="number" name="segment_visit_count_min" id="segment_visit_count_min" class="form-control attr-segment-filter" value="{{ old('segment_visit_count_min') }}" min="0">
                            </div>
                            <div class="col-md-4">
                                <label for="segment_visit_count_max" class="form-label">来店回数（以下）</label>
                                <input type="number" name="segment_visit_count_max" id="segment_visit_count_max" class="form-control attr-segment-filter" value="{{ old('segment_visit_count_max') }}" min="0">
                            </div>
                            <div class="col-md-4">
                                <label for="segment_rank" class="form-label">会員ランク</label>
                                <input type="text" name="segment_rank" id="segment_rank" class="form-control attr-segment-filter" value="{{ old('segment_rank') }}" maxlength="64">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label for="segment_lifetime_points_min" class="form-label">累計付与ポイント（以上）</label>
                                <input type="number" name="segment_lifetime_points_min" id="segment_lifetime_points_min" class="form-control attr-segment-filter" value="{{ old('segment_lifetime_points_min') }}" min="0">
                            </div>
                            <div class="col-md-4">
                                <label for="segment_lifetime_points_max" class="form-label">累計付与ポイント（以下）</label>
                                <input type="number" name="segment_lifetime_points_max" id="segment_lifetime_points_max" class="form-control attr-segment-filter" value="{{ old('segment_lifetime_points_max') }}" min="0">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label for="segment_last_visit_mode" class="form-label">最終来店</label>
                                <select name="segment_last_visit_mode" id="segment_last_visit_mode" class="form-control attr-segment-filter">
                                    <option value="">指定しない</option>
                                    <option value="inactive_days" {{ old('segment_last_visit_mode') === 'inactive_days' ? 'selected' : '' }}>N日以上来店なし</option>
                                    <option value="visited_within_days" {{ old('segment_last_visit_mode') === 'visited_within_days' ? 'selected' : '' }}>N日以内に来店あり</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="attr_segment_last_visit_days" class="form-label">最終来店の日数</label>
                                <select name="segment_last_visit_days" id="attr_segment_last_visit_days" class="form-control attr-segment-filter">
                                    <option value="7" {{ old('segment_last_visit_days', 30) == 7 ? 'selected' : '' }}>7日</option>
                                    <option value="30" {{ old('segment_last_visit_days', 30) == 30 ? 'selected' : '' }}>30日</option>
                                    <option value="90" {{ old('segment_last_visit_days') == 90 ? 'selected' : '' }}>90日</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="birthdayBlock" class="delivery-option-block is-hidden">
                        <label for="birthday_month" class="form-label">誕生月</label>
                        <select name="birthday_month" id="birthday_month" class="form-control" style="max-width:200px">
                            @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (int)old('birthday_month', now()->month) === $m ? 'selected' : '' }}>{{ $m }}月</option>
                            @endfor
                        </select>
                    </div>

                    <div id="newMemberBlock" class="delivery-option-block is-hidden">
                        <label for="new_member_days" class="form-label">登録から何日以内</label>
                        <select name="new_member_days" id="new_member_days" class="form-control" style="max-width:200px">
                            <option value="7" {{ old('new_member_days', 30) == 7 ? 'selected' : '' }}>7日以内</option>
                            <option value="14" {{ old('new_member_days') == 14 ? 'selected' : '' }}>14日以内</option>
                            <option value="30" {{ old('new_member_days', 30) == 30 ? 'selected' : '' }}>30日以内</option>
                            <option value="90" {{ old('new_member_days') == 90 ? 'selected' : '' }}>90日以内</option>
                        </select>
                    </div>

                    <div id="inactiveBlock" class="delivery-option-block is-hidden">
                        <label for="inactive_days" class="form-label">来店・購入がない期間</label>
                        <select name="inactive_days" id="inactive_days" class="form-control" style="max-width:200px">
                            <option value="30" {{ old('inactive_days') == 30 ? 'selected' : '' }}>30日以上</option>
                            <option value="60" {{ old('inactive_days') == 60 ? 'selected' : '' }}>60日以上</option>
                            <option value="90" {{ old('inactive_days', 90) == 90 ? 'selected' : '' }}>90日以上</option>
                            <option value="180" {{ old('inactive_days') == 180 ? 'selected' : '' }}>180日以上</option>
                        </select>
                    </div>

                    <div id="scheduledBlock" class="delivery-option-block is-hidden">
                        <label for="scheduled_at" class="form-label">配布日時</label>
                        <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" style="max-width:280px">
                    </div>

                    <div class="bulk-target-preview" id="targetPreview">
                        <i class="fas fa-users"></i>
                        <span id="targetPreviewText">対象会員: 計算中...</span>
                    </div>

                    <div class="alert alert-info small" role="status">
                        <i class="fas fa-bell"></i> 即時配布は完了後すぐに通知されます。予約配布は指定日時に自動実行されます。
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitDeliveryBtn" disabled>
                            <i class="fas fa-paper-plane"></i> <span id="submitDeliveryLabel">配布する</span>
                        </button>
                        <a href="{{ admin_route('admin.coupons') }}" class="btn btn-secondary">一覧に戻る</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/coupon-bulk-issue.css') }}?v={{ config('app.asset_version') }}">
@endpush
@push('scripts')
<script>
window.CouponBulkIssueConfig = {
    membersUrl: @json(admin_route('admin.coupons.ajax.members')),
    previewUrl: @json(admin_route('admin.coupons.delivery.preview')),
    memberCount: {{ (int)$memberCount }},
    initialCouponSource: @json($couponSource),
    scheduledTypes: @json($deliveryTypeJsConfig['scheduledTypes']),
    personalTypes: @json($deliveryTypeJsConfig['personalTypes']),
    segmentTypes: @json($deliveryTypeJsConfig['segmentTypes']),
    attributeSegmentTypes: @json($deliveryTypeJsConfig['attributeSegmentTypes']),
    birthdayTypes: @json($deliveryTypeJsConfig['birthdayTypes']),
    newMemberTypes: @json($deliveryTypeJsConfig['newMemberTypes']),
    inactiveTypes: @json($deliveryTypeJsConfig['inactiveTypes']),
    initialSelectedMembers: @json(old('target_member_ids', [])),
};
</script>
@endpush
@endsection
