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
    // アンケート結果などから「このタグの会員へ配信」で開いたときは、そのタグを選んだ状態にする
    $selectedTagIds = collect((array) old('segment_tag_ids', request()->query('segment_tag_ids', [])))->map(fn ($id) => (int) $id)->filter()->unique()->values();
    $selectedRfmSegments = collect((array) old('segment_rfm_segments', []))->map(fn ($key) => (string) $key)->filter()->unique()->values();
    $legacyDeliveryType = old('delivery_type', request()->query('delivery_type') === \App\Models\Announcement::DELIVERY_TYPE_SEGMENT ? \App\Models\Announcement::DELIVERY_TYPE_SEGMENT : '');
    $oldAudienceType = old('audience_type', $legacyDeliveryType === \App\Models\Announcement::DELIVERY_TYPE_SCHEDULED
        ? \App\Models\Announcement::DELIVERY_TYPE_IMMEDIATE_BULK
        : ($legacyDeliveryType ?: \App\Models\Announcement::DELIVERY_TYPE_IMMEDIATE_BULK));
    $oldDeliveryTiming = old('delivery_timing', $legacyDeliveryType === \App\Models\Announcement::DELIVERY_TYPE_SCHEDULED
        ? \App\Models\Announcement::DELIVERY_TIMING_SCHEDULED
        : \App\Models\Announcement::DELIVERY_TIMING_IMMEDIATE);
    $advancedSegmentOpen = collect([
        'segment_visit_count_min', 'segment_visit_count_max', 'segment_lifetime_points_min',
        'segment_lifetime_points_max', 'segment_last_visit_mode', 'segment_rank',
        'segment_product_id', 'segment_coupon_id', 'segment_coupon_status', 'segment_condition',
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

        <form action="{{ admin_route('admin.announcements.delivery.store') }}" method="POST" id="deliveryForm" enctype="multipart/form-data">
        @csrf
        <div class="ad-studio">
            <div class="ad-studio-main">
                <div class="andel-card" role="region" id="delivery-content" aria-label="お知らせの内容">
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
                        @include('admin.announcements._images', ['currentImages' => []])
                        @error('image_file')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>

                @include('admin.partials._delivery_audience')
                @include('admin.partials._delivery_timing')

                <div class="andel-card" role="region" id="delivery-coupon" aria-label="クーポン設定">
                    @if(($view->coupons ?? collect())->isEmpty())
                        <div class="ad-empty-coupon">
                            <span><i class="fas fa-ticket-alt" aria-hidden="true"></i></span>
                            <div><strong>配布できるクーポンがありません</strong><p>先に有効なクーポンを作成してください。</p></div>
                            <a href="{{ admin_route('admin.coupons.create') }}" class="btn btn-outline-primary">クーポンを作成</a>
                        </div>
                    @else
                        <label class="ad-pin-option">
                            <input type="checkbox" id="attachCoupon" {{ $hasSelectedCoupon ? 'checked' : '' }}>
                            <span class="ad-pin-icon" aria-hidden="true"><i class="fas fa-ticket-alt"></i></span>
                            <span><strong>クーポンを配布する</strong></span>
                            <span class="ad-check-ui" aria-hidden="true"><i class="fas fa-check"></i></span>
                        </label>
                        <div class="ad-coupon-panel" id="couponPanel" {{ $hasSelectedCoupon ? '' : 'hidden' }}>
                            @include('admin.partials._delivery_coupon_list', [
                                'selectedIds' => $selectedCouponIds,
                                'inputsDisabled' => ! $hasSelectedCoupon,
                            ])
                        </div>
                    @endif
                </div>

                <div class="andel-card" role="region" id="delivery-options" aria-label="掲載設定">
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
                        <x-back-link :href="admin_route('admin.announcements')" label="キャンセル" />
                        <button type="submit" class="btn btn-primary ad-submit no-loading">
                            <span><i class="fas fa-paper-plane" aria-hidden="true"></i> 配信内容を確認する</span>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="ad-submit-note"><i class="fas fa-circle-info" aria-hidden="true"></i> 確認画面で内容を最終チェックしてから配信します</p>
            </div>
        </div>

        @include('admin.partials._delivery_review')
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
