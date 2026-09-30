<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>管理画面 - @yield('title', 'Reggit')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    {{-- Blade 管理画面の CSS は Vite（LIFF Designer 用）より後で読み、上書きされないようにする --}}
    @stack('styles-before-vite')
    @viteReactRefresh
    @vite(['resources/js/admin/main.tsx'])
    @php
        $adminCssDir = public_path('css/admin');
        $adminCssMtime = (int) (@filemtime($adminCssDir.'/admin.css') ?: 0);
        $adminComponentsDir = $adminCssDir.'/components';
        if (is_dir($adminComponentsDir)) {
            foreach (glob($adminComponentsDir.'/*.css') ?: [] as $adminCssFile) {
                $componentMtime = (int) (@filemtime($adminCssFile) ?: 0);
                if ($componentMtime > $adminCssMtime) {
                    $adminCssMtime = $componentMtime;
                }
            }
        }
        $adminCssVersion = $adminCssMtime > 0 ? (string) $adminCssMtime : (string) time();
        $adminSidebarCss = (isset($adminSidebarColor) && is_string($adminSidebarColor) && preg_match('/^#[0-9A-Fa-f]{6}$/', $adminSidebarColor) === 1)
            ? $adminSidebarColor
            : null;
        $adminSidebarText = '#ffffff';
        if ($adminSidebarCss !== null) {
            $rgb = array_map(fn ($offset) => hexdec(substr($adminSidebarCss, $offset, 2)) / 255, [1, 3, 5]);
            $linear = array_map(fn ($channel) => $channel <= 0.04045 ? $channel / 12.92 : pow(($channel + 0.055) / 1.055, 2.4), $rgb);
            $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
            $adminSidebarText = $luminance > 0.179 ? '#111827' : '#ffffff';
        }
        $adminPrimaryCss = (isset($adminPrimaryColor) && is_string($adminPrimaryColor) && preg_match('/^#[0-9A-Fa-f]{6}$/', $adminPrimaryColor) === 1)
            ? $adminPrimaryColor
            : null;
    @endphp
    <link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}?v={{ $adminCssVersion }}">
    @if($adminPrimaryCss !== null)
    <style>
        :root {
            --primary: {{ $adminPrimaryCss }};
            --primary-hover: color-mix(in srgb, {{ $adminPrimaryCss }} 82%, #0f172a);
            --primary-light: color-mix(in srgb, {{ $adminPrimaryCss }} 88%, #ffffff);
            --primary-bg: color-mix(in srgb, {{ $adminPrimaryCss }} 12%, #ffffff);
            --primary-bg-hover: color-mix(in srgb, {{ $adminPrimaryCss }} 18%, #ffffff);
        }
    </style>
    @endif
    @if($adminSidebarCss !== null)
    <style>
        .sidebar {
            --sidebar-custom-bg: {{ $adminSidebarCss }};
            --sidebar-custom-text: {{ $adminSidebarText }};
            background: var(--sidebar-custom-bg);
        }
        .sidebar .sidebar-header,
        .sidebar .sidebar-store-switcher-toggle { background: var(--sidebar-custom-bg); }
        .sidebar .sidebar-header h1,
        .sidebar .sidebar-kicker,
        .sidebar .sidebar-store-switcher-toggle,
        .sidebar .nav-group-label,
        .sidebar .nav-item,
        .sidebar .nav-item i { color: var(--sidebar-custom-text); }
        .sidebar .nav-item:hover,
        .sidebar .nav-item.active {
            background: color-mix(in srgb, var(--sidebar-custom-bg) 82%, var(--sidebar-custom-text));
            color: var(--sidebar-custom-text);
        }
        .sidebar .sidebar-header,
        .sidebar .sidebar-store-switcher-toggle,
        .sidebar .nav-group-footer { border-color: color-mix(in srgb, var(--sidebar-custom-bg) 75%, var(--sidebar-custom-text)); }
        .sidebar .nav-separator { background: color-mix(in srgb, var(--sidebar-custom-bg) 75%, var(--sidebar-custom-text)); }
        .sidebar a:focus-visible,
        .sidebar summary:focus-visible { outline-color: var(--sidebar-custom-text); }
    </style>
    @endif
    @stack('styles')
</head>
<body data-page-kind="@yield('page-kind', 'default')">
    <div class="admin-container">

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        @php
                $adminUser = auth('admin')->user();
                $isSystemAdminScreen = request()->routeIs('system-admin.*');
                $navProjects = $projects ?? collect();
                $navStore = ($currentProject ?? null) !== null
                    ? $currentProject
                    : $navProjects->firstWhere('id', $currentProjectId ?? 0);
                $navStoreName = $navStore !== null ? (string) $navStore->name : '店舗管理';
                $navContractId = $navStore !== null
                    ? app(\App\Services\Smaregi\SmaregiApiService::class)->getContractIdForProject((int) $navStore->id)
                    : null;
                $navPlan = $navStore !== null
                    ? (string) ($navStore->plan ?? \App\Enums\ProjectPlan::NORMAL)
                    : \App\Enums\ProjectPlan::NORMAL;
                $showLiffDesigner = ($adminUser === null || $adminUser->hasPermission('liff_designer.manage'))
                    && \App\Support\PlanFeatures::hasFeature($navPlan, \App\Support\PlanFeatures::LIFF_DESIGNER_ADVANCED);
                $showAnalytics = ($adminUser === null || $adminUser->hasPermission('analytics.view'))
                    && \App\Support\PlanFeatures::hasFeature($navPlan, \App\Support\PlanFeatures::ANALYTICS_ADVANCED);
                $showGallery = $navStore !== null
                    && app(\App\Domain\Settings\Repositories\FeatureSettings::class)->isFeatureEnabled('feature_gallery_enabled', (int) $navStore->id);
            @endphp
            <header class="admin-topbar">
                <a class="admin-topbar-brand" href="{{ $isSystemAdminScreen ? route('system-admin.dashboard') : admin_route('admin.dashboard') }}">
                    <span class="admin-topbar-brand-line"><img class="admin-topbar-logo" src="{{ asset('images/reggit-logo.png') }}" alt="Reggit" width="48" height="48"></span>
                    <small>{{ $isSystemAdminScreen ? 'システム管理' : '契約ID: '.($navContractId ?? '未設定') }}</small>
                </a>
                @auth('admin')
                <div class="content-header-actions">
                    @include('admin.partials.store-selector')
                    @if(auth('admin')->user()->isSystemAdmin() && !request()->routeIs('system-admin.*'))
                    <a href="{{ route('system-admin.dashboard') }}" class="btn btn-sm btn-outline-primary" title="システム管理画面へ">
                        <i class="fas fa-shield-alt"></i> <span class="hide-mobile">システム管理</span>
                    </a>
                    @endif
                    <span class="content-header-user" title="{{ auth('admin')->user()->name }}">
                        <i class="fas fa-user-shield"></i>
                        {{ Str::limit(auth('admin')->user()->name, 12) }}
                        <span class="content-header-role">{{ \App\Models\AdminUser::getRoleLabelShort(auth('admin')->user()->role) }}</span>
                    </span>
                    <a href="{{ route('admin.password.edit') }}" class="btn btn-sm btn-outline-secondary" title="パスワード変更"><i class="fas fa-key"></i> <span class="hide-mobile">パスワード変更</span></a>
                    <form action="{{ request()->routeIs('system-admin.*') ? route('system-admin.logout') : route('admin.logout') }}" method="POST" class="content-header-logout-form" onsubmit="return confirm('ログアウトしますか？');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary no-loading">
                            <i class="fas fa-sign-out-alt"></i> <span class="hide-mobile">ログアウト</span>
                        </button>
                    </form>
                </div>
                @endauth
            </header>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                @if($isSystemAdminScreen)
                    <p class="sidebar-kicker">Reggit</p>
                    <h1>システム管理</h1>
                @else
                    <p class="sidebar-kicker">店舗管理</p>
                    @if($navProjects->count() > 1)
                        <details class="sidebar-store-switcher" data-store-switcher>
                            <summary class="sidebar-store-switcher-toggle">
                                <span class="sidebar-store-switcher-current">{{ $navStoreName }}</span>
                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                            </summary>
                            <div class="sidebar-store-switcher-panel">
                                <label class="sr-only" for="sidebar-store-switcher-search">店舗を検索</label>
                                <input type="search" id="sidebar-store-switcher-search" class="sidebar-store-switcher-search" placeholder="店舗名で検索" autocomplete="off">
                                <ul class="sidebar-store-switcher-list">
                                    @foreach($navProjects as $navProject)
                                        <li class="sidebar-store-switcher-item" data-store-search="{{ $navProject->name }} {{ $navProject->project_key }}">
                                            <a
                                                href="{{ route('admin.dashboard', ['project_key' => $navProject->project_key]) }}"
                                                class="sidebar-store-switcher-option{{ (int) $navProject->id === (int) ($currentProjectId ?? 0) ? ' is-current' : '' }}"
                                                @if((int) $navProject->id === (int) ($currentProjectId ?? 0)) aria-current="page" @endif
                                            >{{ $navProject->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="sidebar-store-switcher-empty" hidden>該当する店舗がありません</p>
                            </div>
                        </details>
                    @else
                        <h1>{{ $navStoreName }}</h1>
                    @endif
                @endif
            </div>
            <nav class="sidebar-nav" aria-label="メインメニュー">
                @if($isSystemAdminScreen)
                    <a href="{{ route('system-admin.dashboard') }}" class="nav-item {{ request()->routeIs('system-admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-house"></i>
                        <span>ダッシュボード</span>
                    </a>
                    <a href="{{ route('system-admin.projects.index') }}" class="nav-item {{ request()->routeIs('system-admin.projects*') ? 'active' : '' }}">
                        <i class="fas fa-building"></i>
                        <span>プロジェクト</span>
                    </a>
                    <a href="{{ route('system-admin.accounts.index') }}" class="nav-item {{ request()->routeIs('system-admin.accounts*') ? 'active' : '' }}">
                        <i class="fas fa-user-cog"></i>
                        <span>アカウント</span>
                    </a>
                    <a href="{{ route('system-admin.audit-logs.index') }}" class="nav-item {{ request()->routeIs('system-admin.audit-logs*') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-list"></i>
                        <span>監査ログ</span>
                    </a>
                    <a href="{{ route('system-admin.help-articles.index') }}" class="nav-item {{ request()->routeIs('system-admin.help-articles.*') ? 'active' : '' }}">
                        <i class="fas fa-book"></i>
                        <span>ヘルプ記事</span>
                    </a>
                    <a href="{{ route('system-admin.notices.index') }}" class="nav-item {{ request()->routeIs('system-admin.notices.*') ? 'active' : '' }}"><i class="fas fa-bullhorn"></i><span>店舗向けのお知らせ</span></a>
                    @if($navProjects->isNotEmpty())
                    <div class="nav-separator" role="presentation"></div>
                    <a href="{{ route('admin.dashboard', ['project_key' => $navProjects->first()->project_key]) }}" class="nav-item">
                        <i class="fas fa-store"></i>
                        <span>店舗管理へ</span>
                    </a>
                    @endif
                @else
                    @if($adminUser === null || $adminUser->hasPermission('dashboard.view'))
                    <a href="{{ admin_route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-house"></i>
                        <span>ダッシュボード</span>
                    </a>
                    @endif
                    @if($adminUser === null || $adminUser->hasPermission('orders.view') || $adminUser->hasPermission('reservations.view') || $adminUser->hasPermission('queue.view'))
                    <div class="nav-group">
                        <div class="nav-group-label">運用</div>
                        @if($adminUser === null || $adminUser->hasPermission('orders.view'))
                        <a href="{{ admin_route('admin.orders') }}" class="nav-item {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                            <i class="fas fa-shopping-cart"></i>
                            <span>注文</span>
                        </a>
                        @endif
                        @if($adminUser === null || $adminUser->hasPermission('reservations.view'))
                        <a href="{{ admin_route('admin.reservations') }}" class="nav-item {{ request()->routeIs('admin.reservations*') && !request()->routeIs('admin.settings.reservations*') ? 'active' : '' }}">
                            <i class="fas fa-calendar-check"></i>
                            <span>予約</span>
                        </a>
                        @endif
                        @if($adminUser === null || $adminUser->hasPermission('queue.view'))
                        <a href="{{ admin_route('admin.queue') }}" class="nav-item {{ request()->routeIs('admin.queue*') && !request()->routeIs('admin.settings.queue*') ? 'active' : '' }}">
                            <i class="fas fa-list-ol"></i>
                            <span>順番待ち</span>
                        </a>
                        @endif
                    </div>
                    @endif
                    @if($adminUser === null || $adminUser->hasPermission('products.view'))
                    <a href="{{ admin_route('admin.products') }}" class="nav-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                        <i class="fas fa-utensils"></i>
                        <span>商品</span>
                    </a>
                    @endif
                    @if($adminUser === null || $adminUser->hasPermission('members.view'))
                    <a href="{{ admin_route('admin.members') }}" class="nav-item {{ request()->routeIs('admin.members*') || request()->routeIs('admin.member-tags*') ? 'active' : '' }}">
                        <i class="fas fa-id-card"></i>
                        <span>会員</span>
                    </a>
                    @endif
                    @if($adminUser === null || $adminUser->hasPermission('stamps.view') || $adminUser->hasPermission('coupons.view') || $adminUser->hasPermission('announcements.view'))
                    <div class="nav-group">
                        <div class="nav-group-label">販促</div>
                        @if($adminUser === null || $adminUser->hasPermission('stamps.view'))
                        <a href="{{ admin_route('admin.stamps') }}" class="nav-item {{ request()->routeIs('admin.stamps*') ? 'active' : '' }}">
                            <i class="fas fa-stamp"></i>
                            <span>スタンプ</span>
                        </a>
                        @endif
                        @if($adminUser === null || $adminUser->hasPermission('coupons.view'))
                        <a href="{{ admin_route('admin.coupons') }}" class="nav-item {{ request()->routeIs('admin.coupons*') && !request()->routeIs('admin.settings.coupons*') ? 'active' : '' }}">
                            <i class="fas fa-ticket-alt"></i>
                            <span>クーポン</span>
                        </a>
                        @endif
                        @if($adminUser === null || $adminUser->hasPermission('announcements.view'))
                        <a href="{{ admin_route('admin.announcements') }}" class="nav-item {{ (request()->routeIs('admin.announcements*') && ! request()->routeIs('admin.settings.announcements*')) || request()->routeIs('admin.after-registration-deliveries*') ? 'active' : '' }}">
                            <i class="fas fa-bullhorn"></i>
                            <span>お知らせ</span>
                        </a>
                        @endif
                    </div>
                    @endif
                    @if($showGallery && ($adminUser === null || $adminUser->hasPermission('gallery.view')))
                    <a href="{{ admin_route('admin.gallery.items') }}" class="nav-item {{ request()->routeIs('admin.gallery*') ? 'active' : '' }}">
                        <i class="fas fa-images"></i>
                        <span>ギャラリー</span>
                    </a>
                    @endif
                    @if($showAnalytics || $adminUser === null || $adminUser->hasPermission('members.view'))
                    <div class="nav-group">
                        <div class="nav-group-label">分析</div>
                        @if($showAnalytics)
                        <a href="{{ admin_route('admin.analytics') }}" class="nav-item {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
                            <i class="fas fa-chart-line"></i>
                            <span>分析</span>
                        </a>
                        <a href="{{ admin_route('admin.analysis-deliveries') }}" class="nav-item {{ request()->routeIs('admin.analysis-deliveries*') ? 'active' : '' }}">
                            <i class="fas fa-paper-plane"></i>
                            <span>分析から配信</span>
                        </a>
                        @endif
                        @if($adminUser === null || $adminUser->hasPermission('members.view'))
                        <a href="{{ admin_route('admin.rfm') }}" class="nav-item {{ request()->routeIs('admin.rfm*') ? 'active' : '' }}">
                            <i class="fas fa-chart-pie"></i>
                            <span>RFM分析</span>
                        </a>
                        @endif
                    </div>
                    @endif
                    <div class="nav-separator" role="presentation"></div>
                    @if(
                        $adminUser === null
                        || $adminUser->hasPermission('settings.view')
                        || $showLiffDesigner
                        || $adminUser->hasPermission('reservations.view')
                        || $adminUser->hasPermission('queue.view')
                        || $adminUser->hasPermission('coupons.view')
                        || $adminUser->hasPermission('announcements.view')
                        || $adminUser->hasPermission('gallery.view')
                        || $adminUser->hasPermission('audit_logs.view')
                        || $adminUser->hasPermission('members.manage')
                    )
                    <a href="{{ admin_route('admin.settings.index') }}" class="nav-item {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.stores.*') || request()->routeIs('admin.audit-logs*') ? 'active' : '' }}">
                        <i class="fas fa-cog"></i>
                        <span>設定</span>
                    </a>
                    @endif
                    <div class="nav-group nav-group-footer">
                        <a href="{{ admin_route('admin.help') }}" class="nav-item {{ request()->routeIs('admin.help*') ? 'active' : '' }}" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-circle-question"></i>
                            <span>ヘルプ</span>
                        </a>
                        @php $frontUrl = admin_front_url(); @endphp
                        @if($frontUrl !== null)
                        <a href="{{ $frontUrl }}" class="nav-item" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-external-link-alt"></i>
                            <span>店舗アプリを開く</span>
                        </a>
                        @endif
                    </div>
                @endif
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header content-header-with-actions">
                <div class="content-header-title-wrap">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="メニューを開く">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2>@yield('page-title', 'ダッシュボード')</h2>
                </div>
                @if(
                    ($selectedStore ?? null) !== null
                    && ($canManageStores ?? false)
                    && session('admin_store.'.($currentProjectId ?? '')) === 'all'
                    && (request()->routeIs('admin.queue*') || request()->routeIs('admin.reservations*') || request()->routeIs('admin.orders*'))
                )
                    <span class="content-header-store"><i class="fas fa-store" aria-hidden="true"></i> 表示中の店舗：{{ $selectedStore->name }}</span>
                @endif
                @yield('page-header-actions')
            </header>
            @if(session('success'))
                <div class="alert alert-success" role="alert"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error" role="alert"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error" role="alert">
                    <i class="fas fa-exclamation-circle"></i> 入力に誤りがあります。各項目をご確認ください。
                </div>
            @endif
            <div class="content-body">
                @if((request()->routeIs('admin.settings.*') || request()->routeIs('admin.stores.*')) && !request()->routeIs('admin.settings.index') && !request()->routeIs('admin.settings.liff-designer*'))
                    <div class="settings-page-content">
                        <a href="{{ admin_route('admin.settings.index') }}" class="settings-back-link"><i class="fas fa-arrow-left" aria-hidden="true"></i> 設定一覧</a>
                @yield('content')
                    </div>
                @else
                @yield('content')
                @endif
            </div>
        </main>
    </div>
    <div id="react-admin-root" aria-hidden="true"></div>

    @stack('scripts')
</body>
</html>
