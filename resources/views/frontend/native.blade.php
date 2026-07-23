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
    // /liff のみ固定デモUI。/liff/{project_key} は LIFF Designer 設定を反映する
    $isFixedUi = request()->route('project_key') === null;
    $isDemoData = $isFixedUi;
@endphp
<html lang="ja" @class([
    'native-app-embed' => true,
    'liff-fixed-ui' => $isFixedUi,
    'liff-demo-data' => $isDemoData,
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
    {{-- ネイティブアプリ（iOS/Android）: Android WebView では初回 100vh が 0 になりやすいため、px フォールバックを指定 --}}
    <style id="native-app-inline">
        #liff-loading { display: none !important; visibility: hidden !important; opacity: 0 !important; pointer-events: none !important; }
        html.native-app-embed, body.native-app-embed { height: 100%; min-height: 100vh; margin: 0; padding: 0; box-sizing: border-box; overflow: hidden !important; touch-action: manipulation !important; }
        .app-container { display: flex !important; flex-direction: column !important; min-height: 100vh !important; height: 100% !important; background: #fff !important; visibility: visible !important; box-sizing: border-box !important; overflow: hidden !important; }
        .header { display: block !important; visibility: visible !important; background: #000 !important; color: #fff !important; flex-shrink: 0 !important; }
        .main-content { visibility: visible !important; flex: 1 1 auto !important; min-height: 0 !important; overflow: hidden !important; display: flex !important; flex-direction: column !important; }
        #react-frontend-tabs-root { display: flex !important; flex-direction: column !important; flex: 1 1 auto !important; min-height: 0 !important; height: 100% !important; max-height: 100% !important; overflow: hidden !important; width: 100% !important; box-sizing: border-box !important; }
        .main-tabs { display: none !important; visibility: hidden !important; height: 0 !important; overflow: hidden !important; pointer-events: none !important; }
        .tab-content.active { display: flex !important; flex-direction: column !important; flex: 1 1 auto !important; min-height: 0 !important; overflow-y: auto !important; overflow-x: hidden !important; -webkit-overflow-scrolling: touch !important; touch-action: pan-y !important; }
        .tab-content.active .tab-section { flex: 1 1 auto !important; min-height: 0 !important; height: auto !important; max-height: none !important; overflow-y: auto !important; overflow-x: hidden !important; -webkit-overflow-scrolling: touch !important; touch-action: pan-y !important; background: #fff !important; padding-bottom: 16px !important; box-sizing: border-box !important; }
        .main-content { padding-bottom: 0 !important; }
    </style>
</head>
<body class="native-app-embed {{ $isFixedUi ? 'liff-fixed-ui' : '' }} {{ $isDemoData ? 'liff-demo-data' : '' }} {{ $isDesignerEmbedPreview ? 'designer-live-preview-embed' : '' }}">
    <script>
    (function() {
        window.__MOBILE_APP_MODE__ = true;
        var logMax = 50;
        var logLines = [];
        window.__mobileOrderLog = function(msg, data) {
            var line = (typeof msg === 'string' ? msg : JSON.stringify(msg)) + (data !== undefined ? ' ' + (typeof data === 'object' ? JSON.stringify(data) : String(data)) : '');
            var ts = new Date().toISOString().substr(11, 12);
            console.log('[Reggit]', ts, msg, data !== undefined ? data : '');
            logLines.push(ts + ' ' + line);
            if (logLines.length > logMax) logLines.shift();
        };
        document.documentElement.classList.add('native-app-embed');
        (function() {
            var s = document.createElement('style');
            s.id = 'native-app-hide-loading';
            s.textContent = 'html.native-app-embed #liff-loading { display: none !important; visibility: hidden !important; opacity: 0 !important; pointer-events: none !important; }' +
                'html.native-app-embed .app-container { display: flex !important; visibility: visible !important; min-height: 100vh !important; background: #fff !important; overflow: hidden !important; }' +
                'html.native-app-embed .main-content { min-height: 0 !important; overflow: hidden !important; padding-bottom: 0 !important; }' +
                'html.native-app-embed .header { display: block !important; visibility: visible !important; }' +
                'html.native-app-embed .main-tabs { display: none !important; visibility: hidden !important; height: 0 !important; pointer-events: none !important; }';
            (document.head || document.documentElement).appendChild(s);
        })();
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('native-app-embed');
            var loadingEl = document.getElementById('liff-loading');
            if (loadingEl) {
                loadingEl.classList.add('liff-loading--hidden');
                loadingEl.style.display = 'none';
            }
            __mobileOrderLog('DOMContentLoaded', { path: window.location.pathname });
            setTimeout(function() {
                if (typeof window.app === 'undefined' && typeof document.dispatchEvent === 'function') {
                    __mobileOrderLog('dispatch liff-ready (fallback)');
                    document.dispatchEvent(new CustomEvent('liff-ready'));
                }
            }, 100);
        });
    })();
    </script>
    <div id="liff-loading" class="liff-loading" aria-live="polite" style="display: none !important; visibility: hidden !important;">
        <div class="liff-loading-inner">
            <div class="liff-loading-spinner"></div>
            <p class="liff-loading-text">読み込み中...</p>
        </div>
    </div>

    <div class="app-container" id="app-container" style="display: flex; flex-direction: column; min-height: max(100vh, 800px); height: 100%; background: #fff; visibility: visible; box-sizing: border-box;">
        
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
            // デモ固定UIのメニューは scripts-config 側で独立定義する
            $filteredMenu = $isFixedUi
                ? []
                : array_filter(
                    $frontendMenu ?? \App\Models\ShopSetting::defaultFrontendMenuItems(),
                    fn($item) => ($flags[$tabFeatureMap[$item['tab']] ?? ''] ?? true)
                );
        @endphp

        <main class="main-content" style="flex: 1 1 auto; min-height: max(500px, 50vh); overflow: auto; background: #fff; visibility: visible; -webkit-overflow-scrolling: touch; box-sizing: border-box;">
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
