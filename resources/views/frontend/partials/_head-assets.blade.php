@php
    $memberCssVersion = isset($assetVersion) ? $assetVersion : (string) (@filemtime(public_path('css/frontend/components/05-overlay-skeleton-toast.css')) ?: time());
@endphp
<link rel="stylesheet" href="{{ asset('css/frontend/components/05-overlay-skeleton-toast.css') }}?v={{ $memberCssVersion }}">
<link rel="stylesheet" href="{{ asset('css/frontend/components/07-payment-modal.css') }}?v={{ $memberCssVersion }}">
<style>
  #liff-loading { position: fixed; inset: 0; z-index: 2147483647; display: flex; align-items: center; justify-content: center; background: #173f35; color: #fff; }
  #liff-loading.liff-loading--hidden { display: none; }
  .liff-loading-inner { padding: 24px; text-align: center; max-width: 360px; }
  body.reggit-liff-ui, .reggit-liff-ui-root { min-height: 100vh; }
  body.reggit-liff-ui:not(.liff-ui-ready):not(.designer-live-preview-embed)::before {
    content: "";
    position: fixed;
    inset: 0;
    z-index: 2147483646;
    background: #ffffff;
    pointer-events: auto;
  }
</style>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
