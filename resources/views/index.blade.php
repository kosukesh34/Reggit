<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="line-liff-id" content="{{ $liffId ?? config('line.liff_id', '') }}">
    <meta name="project-key" content="{{ $projectKey ?? 'default' }}">
    <meta name="api-base" content="{{ config('app.api_base', '/api') }}">
    <title>{{ $projectName ?? 'モバイルオーダー' }}</title>
    <link rel="preconnect" href="https://static.line-scdn.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://js.stripe.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @php
        $configuredAssetVersion = (string) config('app.asset_version', env('APP_VERSION', ''));
        $cssMtime = @filemtime(public_path('css/frontend/style.css')) ?: null;
        $assetVersion = $configuredAssetVersion !== '' ? $configuredAssetVersion : (string) ($cssMtime ?? time());
        $host = request()->getHost();
        $useInlineLocalAssets = str_contains($host, 'ngrok-free.dev') || str_contains($host, 'ngrok-free.app');
    @endphp
    @if($useInlineLocalAssets)
        @php $inlineCss = @file_get_contents(public_path('css/frontend/style.css')) ?: ''; @endphp
        <style id="inline-frontend-style">{!! $inlineCss !!}</style>
    @else
        <link rel="stylesheet" href="/css/frontend/style.css?v={{ urlencode($assetVersion) }}">
    @endif
    @if(isset($liffCustomCss) && $liffCustomCss !== null && $liffCustomCss !== '')
        <style id="liff-custom-style">{!! $liffCustomCss !!}</style>
    @endif
    @isset($lineTheme)
    <style>
    :root {
        --primary-color: {{ $lineTheme['primary'] }};
        --primary-rgb: {{ $lineTheme['primary_rgb'] ?? '0, 0, 0' }};
        --primary-dark: {{ $lineTheme['primary_dark'] }};
        --success-color: {{ $lineTheme['success'] }};
        --success-dark: {{ $lineTheme['success'] }};
        --danger: {{ $lineTheme['danger'] }};
    }
    </style>
    @endisset
</head>
<body>
    <div id="liff-loading" class="liff-loading" aria-live="polite">
        <div class="liff-loading-inner">
            <div class="liff-loading-spinner"></div>
            <p class="liff-loading-text">読み込み中...</p>
        </div>
    </div>

    <div class="app-container" id="app-container">
        
        <header class="header">
            <div class="header-content">
                @if(isset($frontendLogoUrl) && $frontendLogoUrl !== null && $frontendLogoUrl !== '')
                    <img src="{{ $frontendLogoUrl }}" alt="{{ $frontendHeaderText ?? $projectName ?? 'ロゴ' }}" class="header-logo" style="max-height:36px; margin-right:8px;">
                @endif
                <h1 class="logo">{{ $frontendHeaderText ?? $projectName ?? 'モバイルオーダー' }}</h1>
                <button class="cart-btn" id="cartBtn">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </button>
            </div>
        </header>

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
            ];
            $filteredMenu = array_filter(
                $frontendMenu ?? \App\Models\ShopSetting::defaultFrontendMenuItems(),
                fn($item) => ($flags[$tabFeatureMap[$item['tab']] ?? ''] ?? true)
            );
        @endphp
        <nav class="banner-menu" aria-label="メニュー">
            @foreach($filteredMenu as $i => $item)
            <button type="button" class="banner-menu-btn {{ $loop->first ? 'active' : '' }}" data-tab="{{ $item['tab'] }}" aria-label="{{ $item['label'] }}">
                <i class="fas {{ $item['icon'] ?? 'fa-circle' }}"></i>
                <span>{{ $item['label'] }}</span>
            </button>
            @endforeach
        </nav>

        
        <main class="main-content">
            <div id="announcementsSection" class="announcements-section" aria-label="お知らせ"></div>

            @php $firstTab = ($filteredMenu[0]['tab'] ?? ($frontendMenu ?? \App\Models\ShopSetting::defaultFrontendMenuItems())[0]['tab'] ?? 'products'); @endphp
            <div class="tab-content {{ $firstTab === 'home' ? 'active' : '' }}" id="homeTab">
                <div class="tab-section" id="homeSection" data-tab="home"></div>
            </div>
            <div class="tab-content {{ $firstTab === 'products' ? 'active' : '' }}" id="productsTab">
                <div class="tab-section" id="productsSection" data-tab="products"></div>
            </div>

            
            <div class="tab-content {{ $firstTab === 'orders' ? 'active' : '' }}" id="ordersTab">
                <div class="tab-section" id="ordersSection" data-tab="orders"></div>
            </div>

            <div class="tab-content {{ $firstTab === 'stamps' ? 'active' : '' }}" id="stampsTab">
                <div class="tab-section" id="stampsSection" data-tab="stamps"></div>
            </div>

            <div class="tab-content {{ $firstTab === 'coupons' ? 'active' : '' }}" id="couponsTab">
                <div class="tab-section" id="couponsSection" data-tab="coupons"></div>
            </div>

            <div class="tab-content {{ $firstTab === 'queue' ? 'active' : '' }}" id="queueTab">
                <div class="tab-section" id="queueSection" data-tab="queue"></div>
            </div>

            <div class="tab-content {{ $firstTab === 'member' ? 'active' : '' }}" id="memberTab">
                <div class="tab-section" id="memberSection" data-tab="member"></div>
            </div>

            <div class="tab-content {{ $firstTab === 'reservations' ? 'active' : '' }}" id="reservationsTab">
                <div class="tab-section" id="reservationsSection" data-tab="reservations"></div>
            </div>

            <div class="tab-content {{ $firstTab === 'gallery' ? 'active' : '' }}" id="galleryTab">
                <div class="tab-section" id="gallerySection" data-tab="gallery"></div>
            </div>
        </main>

        
        <div class="cart-sidebar" id="cartSidebar">
            <div class="cart-header">
                <h2><i class="fas fa-shopping-bag"></i> 注文内容</h2>
                <button class="close-btn" id="closeCartBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="cart-items" id="cartItems">
                <div class="empty-cart">カートは空です</div>
            </div>
            <div class="cart-footer">
                <div class="cart-total">
                    <span>合計: </span>
                    <span class="total-price" id="totalPrice">¥0</span>
                </div>
                <button class="order-btn" id="orderBtn" disabled><i class="fas fa-check-circle"></i> 注文する</button>
            </div>
        </div>

        
        <div class="overlay" id="overlay"></div>
        
        <div id="profileEditModal" class="member-modal" aria-hidden="true">
            <div class="member-modal-backdrop" data-close="profileEdit"></div>
            <div class="member-modal-content" id="profileEditModalContent"></div>
        </div>

        <footer class="main-tabs">
            @foreach($filteredMenu as $i => $item)
            <button type="button" class="main-tab-btn {{ $i === 0 ? 'active' : '' }}" data-tab="{{ $item['tab'] }}">
                <i class="fas {{ $item['icon'] ?? 'fa-circle' }}"></i>
                <span>{{ $item['label'] }}</span>
            </button>
            @endforeach
        </footer>
    </div>

    <meta name="stripe-key" content="{{ env('STRIPE_KEY', '') }}">
    <script>
        window.__PROJECT_KEY__ = "{{ $projectKey ?? 'default' }}";
        (function () {
            var apiBaseMeta = document.querySelector('meta[name="api-base"]');
            window.__API_BASE__ = (apiBaseMeta ? apiBaseMeta.getAttribute('content') : null) || '/api';
        })();
        window.__WALLET_PASS_ENABLED__ = {{ ($walletPassEnabled ?? false) ? 'true' : 'false' }};
        window.__WALLET_PASS_ADD_BUTTON_IMAGE__ = "/images/add-to-apple-wallet-logo.png";
        window.__LIFF_BUILDER__ = @json($liffBuilderBlocks ?? []);
        window.__LIFF_TAB_LAYOUTS__ = @json($liffTabLayouts ?? []);
        window.__LIFF_TAB_SETTINGS__ = @json($liffTabSettings ?? []);
        window.__INLINE_LOCAL_ASSETS__ = {{ $useInlineLocalAssets ? 'true' : 'false' }};
        window.__clientDebugLog = function (eventName, payloadObj) {
            try {
                var payload = '';
                if (payloadObj && typeof payloadObj === 'object' && payloadObj !== null) {
                    payload = JSON.stringify(payloadObj);
                }
                var body = {
                    event: String(eventName || 'unknown'),
                    project_key: String(window.__PROJECT_KEY__ || ''),
                    path: String(window.location.pathname || ''),
                    ts: String(Date.now()),
                    ua: (navigator && navigator.userAgent) ? String(navigator.userAgent) : '',
                    payload: payload
                };
                var url = '/api/client-debug';
                if (navigator && typeof navigator.sendBeacon === 'function') {
                    var blob = new Blob([JSON.stringify(body)], { type: 'application/json' });
                    navigator.sendBeacon(url, blob);
                } else if (typeof fetch === 'function') {
                    fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(body),
                        keepalive: true,
                        credentials: 'same-origin'
                    }).catch(function () {
                        var i = new Image();
                        i.src = url
                            + '?event=' + encodeURIComponent(body.event)
                            + '&project_key=' + encodeURIComponent(body.project_key)
                            + '&path=' + encodeURIComponent(body.path)
                            + '&ts=' + encodeURIComponent(body.ts)
                            + '&ua=' + encodeURIComponent(body.ua)
                            + '&payload=' + encodeURIComponent(body.payload);
                    });
                } else {
                    var i = new Image();
                    i.src = url
                        + '?event=' + encodeURIComponent(body.event)
                        + '&project_key=' + encodeURIComponent(body.project_key)
                        + '&path=' + encodeURIComponent(body.path)
                        + '&ts=' + encodeURIComponent(body.ts)
                        + '&ua=' + encodeURIComponent(body.ua)
                        + '&payload=' + encodeURIComponent(body.payload);
                }
            } catch (e) { /* ignore */ }
        };
        window.__clientDebugLog('page_init', { href: window.location.href });
        window.__clientDebugLog('asset_mode', { inlineLocalAssets: window.__INLINE_LOCAL_ASSETS__ });
        window.addEventListener('error', function (ev) {
            try {
                window.__clientDebugLog('window_error', {
                    message: ev && ev.message ? String(ev.message) : '',
                    filename: ev && ev.filename ? String(ev.filename) : '',
                    lineno: ev && typeof ev.lineno !== 'undefined' ? String(ev.lineno) : '',
                    colno: ev && typeof ev.colno !== 'undefined' ? String(ev.colno) : ''
                });
            } catch (e) { /* ignore */ }
        });
        window.addEventListener('unhandledrejection', function (ev) {
            try {
                var reason = '';
                if (ev && typeof ev.reason === 'string') {
                    reason = ev.reason;
                } else if (ev && ev.reason && ev.reason.message) {
                    reason = String(ev.reason.message);
                } else {
                    reason = 'unknown';
                }
                window.__clientDebugLog('unhandled_rejection', { reason: reason });
            } catch (e) { /* ignore */ }
        });
    </script>
    @php $v = '?v=' . urlencode($assetVersion); @endphp
    <script defer charset="utf-8" src="https://static.line-scdn.net/liff/edge/versions/2.22.3/sdk.js"></script>
    <script defer src="https://js.stripe.com/v3/"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    @php
        $localScriptPaths = [
            'js/shared/constants.js',
            'js/shared/utils/apiClient.js',
            'js/shared/utils/domHelper.js',
            'js/shared/utils/tooltip.js',
            'js/shared/utils/toast.js',
            'js/shared/utils/confirmDialog.js',
            'js/frontend/managers/CartManager.js',
            'js/frontend/managers/ProductManager.js',
            'js/frontend/managers/OrderManager.js',
            'js/frontend/managers/MemberManager.js',
            'js/frontend/managers/StampsManager.js',
            'js/frontend/managers/CouponsManager.js',
            'js/frontend/managers/QueueManager.js',
            'js/frontend/managers/AnnouncementManager.js',
            'js/frontend/managers/GalleryManager.js',
            'js/frontend/managers/LiffLayoutManager.js',
            'js/frontend/managers/CheckoutManager.js',
            'js/frontend/managers/ReservationManager.js',
            'js/frontend/stripe.js',
            'js/frontend/liff-init.js',
            'js/frontend/app.js',
        ];
    @endphp
    @if($useInlineLocalAssets)
        @foreach($localScriptPaths as $scriptPath)
            @php $inlineJs = @file_get_contents(public_path($scriptPath)) ?: ''; @endphp
            @if($inlineJs !== '')
                <script data-inline-path="{{ $scriptPath }}">{!! $inlineJs !!}</script>
            @endif
        @endforeach
    @else
        <script defer src="/js/shared/constants.js{{ $v }}"></script>
        <script defer src="/js/shared/utils/apiClient.js{{ $v }}"></script>
        <script defer src="/js/shared/utils/domHelper.js{{ $v }}"></script>
        <script defer src="/js/shared/utils/tooltip.js{{ $v }}"></script>
        <script defer src="/js/shared/utils/toast.js{{ $v }}"></script>
        <script defer src="/js/shared/utils/confirmDialog.js{{ $v }}"></script>
        <script defer src="/js/frontend/managers/CartManager.js{{ $v }}"></script>
        <script defer src="/js/frontend/managers/ProductManager.js{{ $v }}"></script>
        <script defer src="/js/frontend/managers/OrderManager.js{{ $v }}"></script>
        <script defer src="/js/frontend/managers/MemberManager.js{{ $v }}"></script>
        <script defer src="/js/frontend/managers/StampsManager.js{{ $v }}"></script>
        <script defer src="/js/frontend/managers/CouponsManager.js{{ $v }}"></script>
        <script defer src="/js/frontend/managers/QueueManager.js{{ $v }}"></script>
        <script defer src="/js/frontend/managers/AnnouncementManager.js{{ $v }}"></script>
        <script defer src="/js/frontend/managers/GalleryManager.js{{ $v }}"></script>
        <script defer src="/js/frontend/managers/LiffLayoutManager.js{{ $v }}"></script>
        <script defer src="/js/frontend/managers/CheckoutManager.js{{ $v }}"></script>
        <script defer src="/js/frontend/managers/ReservationManager.js{{ $v }}"></script>
        <script defer src="/js/frontend/stripe.js{{ $v }}"></script>
        <script defer src="/js/frontend/liff-init.js{{ $v }}"></script>
        <script defer src="/js/frontend/app.js{{ $v }}"></script>
    @endif
    <script>
        (function () {
            function appendStylesheet(href) {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                document.head.appendChild(link);
            }

            function ensureStylesLoaded() {
                if (window.__INLINE_LOCAL_ASSETS__ === true) return;
                var loadingEl = document.getElementById('liff-loading');
                if (!loadingEl || typeof window.getComputedStyle !== 'function') return;
                var style = window.getComputedStyle(loadingEl);
                if (style && style.position === 'fixed') {
                    if (window.__clientDebugLog) window.__clientDebugLog('css_ok', { position: style.position });
                    return;
                }
                if (window.__clientDebugLog) window.__clientDebugLog('css_fallback_start', { detectedPosition: style ? style.position : 'unknown' });
                appendStylesheet('/public/css/frontend/style.css{{ $v }}');
                appendStylesheet('{{ asset('css/frontend/style.css') }}{{ $v }}');
            }

            function scriptCandidates(path) {
                return [
                    path,
                    '/public' + path
                ];
            }

            function loadScriptWithCandidates(candidates, index, done) {
                if (index >= candidates.length) {
                    if (window.__clientDebugLog) window.__clientDebugLog('script_all_candidates_failed', { candidates: candidates });
                    done(false);
                    return;
                }
                var script = document.createElement('script');
                script.src = candidates[index];
                script.defer = true;
                script.onload = function () {
                    if (window.__clientDebugLog) window.__clientDebugLog('script_loaded', { src: script.src });
                    done(true);
                };
                script.onerror = function () {
                    if (window.__clientDebugLog) window.__clientDebugLog('script_load_failed', { src: script.src });
                    loadScriptWithCandidates(candidates, index + 1, done);
                };
                document.body.appendChild(script);
            }

            function loadScriptsSequentially(paths, current) {
                if (current >= paths.length) return;
                loadScriptWithCandidates(scriptCandidates(paths[current]), 0, function () {
                    loadScriptsSequentially(paths, current + 1);
                });
            }

            function ensureScriptsLoaded() {
                if (window.__INLINE_LOCAL_ASSETS__ === true) return;
                if (window.__LIFF_APP_BOOTSTRAP__) {
                    if (window.__clientDebugLog) window.__clientDebugLog('app_bootstrap_already_done', {});
                    return;
                }
                if (window.__clientDebugLog) window.__clientDebugLog('script_fallback_start', {});
                var paths = [
                    '/js/shared/constants.js{{ $v }}',
                    '/js/shared/utils/apiClient.js{{ $v }}',
                    '/js/shared/utils/domHelper.js{{ $v }}',
                    '/js/shared/utils/tooltip.js{{ $v }}',
                    '/js/shared/utils/toast.js{{ $v }}',
                    '/js/shared/utils/confirmDialog.js{{ $v }}',
                    '/js/frontend/managers/CartManager.js{{ $v }}',
                    '/js/frontend/managers/ProductManager.js{{ $v }}',
                    '/js/frontend/managers/OrderManager.js{{ $v }}',
                    '/js/frontend/managers/MemberManager.js{{ $v }}',
                    '/js/frontend/managers/StampsManager.js{{ $v }}',
                    '/js/frontend/managers/CouponsManager.js{{ $v }}',
                    '/js/frontend/managers/QueueManager.js{{ $v }}',
                    '/js/frontend/managers/AnnouncementManager.js{{ $v }}',
                    '/js/frontend/managers/GalleryManager.js{{ $v }}',
                    '/js/frontend/managers/LiffLayoutManager.js{{ $v }}',
                    '/js/frontend/managers/CheckoutManager.js{{ $v }}',
                    '/js/frontend/managers/ReservationManager.js{{ $v }}',
                    '/js/frontend/stripe.js{{ $v }}',
                    '/js/frontend/liff-init.js{{ $v }}',
                    '/js/frontend/app.js{{ $v }}'
                ];
                loadScriptsSequentially(paths, 0);
            }

            setTimeout(function () {
                if (window.__clientDebugLog) window.__clientDebugLog('fallback_timer_fired', {});
                ensureStylesLoaded();
                ensureScriptsLoaded();
            }, 2500);
        })();
    </script>
</body>
</html>
