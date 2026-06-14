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
    <meta name="project-key" content="{{ $projectKey ?? 'default' }}">
    <meta name="api-base" content="{{ config('app.api_base', '/api') }}">
    @if(app()->environment('local'))
    {{-- ローカル限定: ?demo=1 でデモ会員としてログイン状態を再現し、実UIを確認できる --}}
    <meta name="reggit-demo-line-id" content="{{ 'U'.md5('reggit-demo') }}">
    @endif
    @include('frontend.partials._native-app-config')
    <title>{{ $projectName ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://static.line-scdn.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://js.stripe.com" crossorigin>
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
        
        @unless($isDesignerEmbedPreview)
        <header class="header">
            <div class="header-content">
                @if(isset($frontendLogoUrl) && $frontendLogoUrl !== null && $frontendLogoUrl !== '')
                    <img src="{{ $frontendLogoUrl }}" alt="{{ $frontendHeaderText ?? $projectName ?? 'ロゴ' }}" class="header-logo">
                @endif
                <h1 class="logo">{{ $frontendHeaderText ?? $projectName ?? config('app.name') }}</h1>
                @if(isset($lineFriendAddUrl) && $lineFriendAddUrl !== null && trim($lineFriendAddUrl) !== '')
                <a href="{{ $lineFriendAddUrl }}" target="_blank" rel="noopener noreferrer" class="header-friend-add" id="headerFriendAdd" title="友だち追加でメッセージやクーポンを受け取れます"><i class="fab fa-line"></i> 友だち追加</a>
                @endif
                <button class="cart-btn" id="cartBtn">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </button>
            </div>
        </header>
        @endunless

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

        @unless($isDesignerEmbedPreview)
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
                <div class="order-actions">
                    <button class="order-btn order-btn--mobile" id="orderBtn" disabled><i class="fas fa-credit-card"></i> モバイル注文（今すぐ決済）</button>
                    <button class="order-btn order-btn--in-store" id="inStoreOrderBtn" disabled><i class="fas fa-store"></i> 店内注文（後で決済）</button>
                </div>
            </div>
        </div>

        
        <div class="overlay" id="overlay"></div>
        
        <footer class="main-tabs">
            @foreach($filteredMenu as $i => $item)
            <button type="button" class="main-tab-btn {{ $i === 0 ? 'active' : '' }}" data-tab="{{ $item['tab'] }}">
                <i class="fas {{ $item['icon'] ?? 'fa-circle' }}"></i>
                <span>{{ $item['label'] }}</span>
            </button>
            @endforeach
        </footer>
        @endunless
    </div>

    @if(app()->environment('local') && !($isDesignerEmbedPreview ?? false))
    {{-- ローカル限定: ワンタップでデモ会員のログイン状態を ON/OFF し、実画面を簡単に確認できる --}}
    <button type="button" id="reggitDemoToggle" aria-label="デモ表示を切り替え"
        style="position:fixed;right:14px;bottom:88px;z-index:9999;display:inline-flex;align-items:center;gap:7px;padding:10px 15px;border:none;border-radius:999px;font-size:12px;font-weight:700;color:#fff;background:#1c1917;box-shadow:0 8px 20px rgba(12,10,9,.3);cursor:pointer;">
        <i class="fas fa-flask"></i><span id="reggitDemoToggleLabel">デモ</span>
    </button>
    <script>
    (function () {
        var KEY = 'reggit_demo_preview';
        var btn = document.getElementById('reggitDemoToggle');
        var lbl = document.getElementById('reggitDemoToggleLabel');
        if (!btn) return;
        var on = false;
        try {
            var p = new URLSearchParams(location.search || '');
            if (p.has('demo')) {
                var v = String(p.get('demo') || '').trim();
                on = (v === '' || v === '1' || v === 'true');
            } else if (localStorage.getItem(KEY) === '1') {
                on = true;
            }
        } catch (e) {}
        lbl.textContent = on ? 'デモ: ON' : 'デモ: OFF';
        btn.style.background = on ? '#4f46e5' : '#1c1917';
        btn.addEventListener('click', function () {
            var url = new URL(location.href);
            url.searchParams.set('demo', on ? '0' : '1');
            location.href = url.toString();
        });
    })();
    </script>
    @endif

    @include('frontend.partials._scripts-config')
    @include('frontend.partials._scripts-load')
</body>
</html>
