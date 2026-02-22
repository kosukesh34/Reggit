<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="line-liff-id" content="{{ config('line.liff_id', '') }}">
    <meta name="project-key" content="{{ $projectKey ?? 'default' }}">
    <meta name="api-base" content="{{ config('app.api_base', '/api') }}">
    <title>{{ $projectName ?? 'モバイルオーダー' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/frontend/style.css') }}?v={{ time() }}">
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
        window.__API_BASE__ = document.querySelector('meta[name="api-base"]')?.getAttribute('content') || '/api';
        window.__WALLET_PASS_ENABLED__ = {{ ($walletPassEnabled ?? false) ? 'true' : 'false' }};
        window.__WALLET_PASS_ADD_BUTTON_IMAGE__ = "{{ asset('images/add-to-apple-wallet-logo.png') }}";
        window.__LIFF_BUILDER__ = @json($liffBuilderBlocks ?? []);
        window.__LIFF_TAB_LAYOUTS__ = @json($liffTabLayouts ?? []);
        window.__LIFF_TAB_SETTINGS__ = @json($liffTabSettings ?? []);
    </script>
    <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/versions/2.22.3/sdk.js"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    @php $v = '?v=' . time(); @endphp
    <script src="{{ asset('js/shared/constants.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/shared/utils/apiClient.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/shared/utils/domHelper.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/shared/utils/tooltip.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/shared/utils/toast.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/shared/utils/confirmDialog.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/managers/CartManager.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/managers/ProductManager.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/managers/OrderManager.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/managers/MemberManager.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/managers/StampsManager.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/managers/CouponsManager.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/managers/QueueManager.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/managers/AnnouncementManager.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/managers/GalleryManager.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/managers/LiffLayoutManager.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/managers/CheckoutManager.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/managers/ReservationManager.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/stripe.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/liff-init.js') }}{{ $v }}"></script>
    <script src="{{ asset('js/frontend/app.js') }}{{ $v }}"></script>
</body>
</html>
