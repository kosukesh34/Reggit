{{-- 旧 frontend/style.css は DesignMock 全面刷新後は読み込まない（衝突回避）。会員証・登録フォームだけ個別に読む --}}
@php
    $memberCssVersion = isset($assetVersion) ? $assetVersion : (string) (@filemtime(public_path('css/frontend/components/16-profile-modal.css')) ?: time());
@endphp
<link rel="stylesheet" href="{{ asset('css/frontend/components/12-orders-reservations-2.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/13-member-card-design.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/14-member-card-design-2.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/15-member-card-design-3.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/16-profile-modal.css') }}?v={{ $memberCssVersion }}">
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
