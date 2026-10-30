<!DOCTYPE html>
@php
    $isDesignerEmbedPreview = request()->query('designer_live_preview') === '1'
        && request()->query('designer_embed') === '1'
        && auth('admin')->check()
        && auth('admin')->user()->canAccessProject((int) (request()->attributes->get('currentProject')?->id ?? \App\Util\AdminContext::getCurrentProject()?->id ?? 0));

    $configuredAssetVersion = (string) config('app.asset_version', env('APP_VERSION', ''));
    $latestAssetMtime = max(
        @filemtime(public_path('build/manifest.json')) ?: 0,
        @filemtime(public_path('css/frontend/components/05-overlay-skeleton-toast.css')) ?: 0,
        @filemtime(public_path('css/frontend/components/07-payment-modal.css')) ?: 0,
    );
    $assetVersion = $configuredAssetVersion !== ''
        ? $configuredAssetVersion
        : (string) ($latestAssetMtime > 0 ? $latestAssetMtime : time());
    $host = request()->getHost();
    $useInlineLocalAssets = str_contains($host, 'ngrok-free.dev') || str_contains($host, 'ngrok-free.app');
    // デモは /demo/liff に分離。ここでは店舗の設定を反映する。
    $isFixedUi = false;
    $isDemoData = $isFixedUi;
@endphp
<html lang="ja" @class([
    'liff-fixed-ui' => $isFixedUi,
    'liff-demo-data' => $isDemoData,
    'designer-live-preview-embed' => $isDesignerEmbedPreview,
])>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    @unless($isDesignerEmbedPreview)
    <meta name="line-login-required" content="true">
    @endunless
    <meta name="line-liff-id" content="{{ $liffId ?? config('line.liff_id', '') }}">
    <meta name="project-key" content="{{ $projectKey ?? '' }}">
    <meta name="api-base" content="{{ config('app.api_base', '/api') }}">
    @include('frontend.partials._native-app-config')
    <title>{{ $projectName ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://static.line-scdn.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://js.stripe.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @include('frontend.partials._head-assets')
    {{-- ネイティブアプリと同じく、画面全体を跳ねさせず本文だけをスクロールし、ダブルタップ拡大とタップ時の色を出さない --}}
    <style>
        html, body { height: 100%; overflow: hidden; overscroll-behavior: none; touch-action: manipulation; -webkit-tap-highlight-color: transparent; }
    </style>
    @if($isDesignerEmbedPreview)
    <style>html, body { background: #f2f5f7 !important; } #liff-loading { display: none !important; } #react-frontend-root { opacity: 1 !important; visibility: visible !important; }</style>
    @endif
</head>
<body class="skip-native reggit-liff-ui{{ $isFixedUi ? ' liff-fixed-ui' : '' }}{{ $isDemoData ? ' liff-demo-data' : '' }}{{ $isDesignerEmbedPreview ? ' designer-live-preview-embed' : '' }}">
    @unless($isDesignerEmbedPreview)
    <div id="liff-loading" class="liff-loading" aria-live="polite">
        <div class="liff-loading-inner">
            <div class="liff-loading-spinner"></div>
            <p class="liff-loading-text">読み込み中...</p>
        </div>
    </div>

    @endunless
    <div class="app-container" id="app-container"></div>
    <div id="react-frontend-root" aria-hidden="true"></div>

    @include('frontend.partials._scripts-config')
    @include('frontend.partials._line-app-assets')
    @include('frontend.partials._scripts-load')
</body>
</html>
