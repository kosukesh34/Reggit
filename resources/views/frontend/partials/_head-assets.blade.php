{{-- 旧 frontend/style.css は DesignMock 全面刷新後は読み込まない（衝突回避）。機能画面の旧クラスは個別に読む --}}
@php
    $memberCssVersion = isset($assetVersion) ? $assetVersion : (string) (@filemtime(public_path('css/frontend/components/16-profile-modal.css')) ?: time());
@endphp
<link rel="stylesheet" href="{{ asset('css/frontend/components/05-overlay-skeleton-toast.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/07-payment-modal.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/09-tabs-sections.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/10-orders.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/11-orders-reservations.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/12-orders-reservations-2.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/13-member-card-design.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/14-member-card-design-2.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/15-member-card-design-3.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/16-profile-modal.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/17-stamp.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/18-liff-builder-gallery.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/19-coupon.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/20-queue.css') }}?v={{ $memberCssVersion }}">
<style>
  #liff-loading { display: none !important; visibility: hidden !important; pointer-events: none !important; }
  body.reggit-liff-ui, .reggit-liff-ui-root { min-height: 100vh; }
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+JP:wght@400;500;700&display=swap">
@if(!(isset($isFixedUi) && $isFixedUi) && isset($liffCustomCss) && $liffCustomCss !== null && $liffCustomCss !== '')
    <style id="liff-custom-style">{!! $liffCustomCss !!}</style>
@endif
