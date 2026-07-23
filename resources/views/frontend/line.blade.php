<!DOCTYPE html>
@php
    $isDesignerEmbedPreview = request()->query('designer_live_preview') === '1'
        && request()->query('designer_embed') === '1';

    $configuredAssetVersion = (string) config('app.asset_version', env('APP_VERSION', ''));
    $cssDir = public_path('css/frontend');
    $cssMtime = @filemtime($cssDir . '/style.css') ?: 0;
    $componentsDir = $cssDir . '/components';
    if (is_dir($componentsDir)) {
        foreach (glob($componentsDir . '/*.css') ?: [] as $f) {
            $t = @filemtime($f) ?: 0;
            if ($t > $cssMtime) { $cssMtime = $t; }
        }
    }
    $appJsMtime = @filemtime(public_path('js/frontend/app.js')) ?: 0;
    $layoutJsMtime = @filemtime(public_path('js/frontend/managers/LiffLayoutManager.js')) ?: 0;
    $latestAssetMtime = max($cssMtime, $appJsMtime, $layoutJsMtime);
    $assetVersion = $configuredAssetVersion !== ''
        ? $configuredAssetVersion
        : (string) ($latestAssetMtime > 0 ? $latestAssetMtime : time());
    $host = request()->getHost();
    $useInlineLocalAssets = str_contains($host, 'ngrok-free.dev') || str_contains($host, 'ngrok-free.app');
@endphp
<html lang="ja" @class([
    'designer-live-preview-embed' => $isDesignerEmbedPreview,
])>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="line-liff-id" content="{{ $liffId ?? config('line.liff_id', '') }}">
    <meta name="project-key" content="{{ $projectKey ?? '' }}">
    <meta name="api-base" content="{{ config('app.api_base', '/api') }}">
    @include('frontend.partials._native-app-config')
    <title>{{ $projectName ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://static.line-scdn.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://js.stripe.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Noto+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @include('frontend.partials._head-assets')
    @include('frontend.partials._head-theme')
</head>
<body @class([
    'designer-live-preview-embed' => $isDesignerEmbedPreview,
])>
    <div id="liff-loading" class="liff-loading" aria-live="polite">
        <div class="liff-loading-inner">
            <div class="liff-loading-spinner"></div>
            <p class="liff-loading-text">読み込み中...</p>
        </div>
    </div>

    <div class="app-container" id="app-container">
        
        <div id="react-frontend-chrome-root"></div>

        @php
            $flags = $featureFlags ?? [];
            $tabFeatureMap = [
                'home' => 'feature_home_enabled',
                'products' => 'feature_orders_enabled',
                'orders' => 'feature_orders_enabled',
                'stamps' => 'feature_stamps_enabled',
                'coupons' => 'feature_coupons_enabled',
                'queue' => 'feature_queue_enabled',
                'reservations' => 'feature_reservations_enabled',
                'member' => 'feature_members_enabled',
                'gallery' => 'feature_gallery_enabled',
                'announcements' => 'feature_announcements_enabled',
            ];
            $filteredMenu = array_filter(
                $frontendMenu ?? \App\Models\ShopSetting::defaultFrontendMenuItems(),
                fn($item) => ($flags[$tabFeatureMap[$item['tab']] ?? ''] ?? true)
            );
        @endphp

        <main class="main-content">
            <div id="react-frontend-tabs-root"></div>
        </main>

        @unless($isDesignerEmbedPreview)
        <div id="react-frontend-footer-root"></div>
        @endunless
    </div>
    <div id="react-frontend-root" aria-hidden="true"></div>

    @include('frontend.partials._scripts-config')
    @vite(['resources/js/frontend/main.tsx'])
    @include('frontend.partials._scripts-load')
</body>
</html>
