@extends('admin.layout')

@section('title', 'クーポン配信')
@section('page-title', 'クーポン配信')
@section('page-kind', 'coupon-delivery')

@section('page-header-actions')
    <button type="submit" form="deliveryForm" class="btn btn-primary ad-header-submit no-loading" id="deliverySubmitButton">
        <i class="fas fa-paper-plane" aria-hidden="true"></i> <span id="deliverySubmitLabel">配信内容を確認する</span>
    </button>
@endsection

@section('content')
@php
    $selectedCouponId = (int) old('coupon_id', old('delivery_coupon_id', request('coupon_id')));
    $selectedMemberIds = collect((array) old('target_member_ids', []))->map(fn ($id) => (int) $id)->filter()->values();
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
                            <span class="form-label">クーポン <span class="ad-required">必須</span></span>
                            <div class="ad-coupon-panel">
                                @include('admin.partials._delivery_coupon_list', [
                                    'inputType' => 'radio',
                                    'inputName' => 'coupon_id',
                                    'selectedIds' => collect([$selectedCouponId])->filter(),
                                    'disableExpired' => true,
                                ])
                            </div>
                            <p class="ad-field-help"><a href="{{ admin_route('admin.coupons.create') }}">新規作成</a></p>
                        </div>
                    @endif
                </div>

                @include('admin.partials._delivery_audience', ['isCouponDelivery' => true])
                @include('admin.partials._delivery_timing')

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

            <div class="ad-delivery-actions" aria-label="最終確認へ進む">
                    <div id="deliveryFormError" class="ad-form-error" role="alert" hidden></div>
                    <div class="ad-form-footer">
                        <a href="{{ admin_route('admin.coupons') }}" class="btn btn-secondary">キャンセル</a>
                        <button type="submit" class="btn btn-primary ad-submit no-loading">
                            <span><i class="fas fa-paper-plane" aria-hidden="true"></i> 配信内容を確認する</span>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="ad-submit-note"><i class="fas fa-circle-info" aria-hidden="true"></i> 確認画面で内容を最終チェックしてから配信します</p>
            </div>
        </div>

        @include('admin.partials._delivery_review', ['isCouponDelivery' => true])
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
