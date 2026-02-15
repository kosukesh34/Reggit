<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>管理画面 - @yield('title', 'Mobile Order')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
    <script src="{{ asset('js/shared/utils/confirmDialog.js') }}"></script>
    <script src="{{ asset('js/admin/admin.js') }}"></script>
    @stack('styles')
</head>
<body>
    <div class="admin-container">
        
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-store"></i> 管理画面</h1>
            </div>
            @php $adminUser = auth('admin')->user(); @endphp
            @if($adminUser && $adminUser->isSystemAdmin())
            <a href="{{ route('system-admin.dashboard') }}" class="nav-item nav-item--system {{ request()->routeIs('system-admin.*') ? 'active' : '' }}">
                <i class="fas fa-shield-alt"></i>
                <span>システム管理</span>
            </a>
            @endif
            <nav class="sidebar-nav" aria-label="メインメニュー">
                <div class="nav-group">
                    <div class="nav-group-label">メニュー</div>
                    @if(!$adminUser || $adminUser->hasPermission('dashboard.view'))
                    <a href="{{ admin_route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-th-large"></i>
                        <span>ダッシュボード</span>
                    </a>
                    @endif
                </div>
                @if(!$adminUser || $adminUser->hasPermission('orders.view') || $adminUser->hasPermission('products.view'))
                <div class="nav-group">
                    <div class="nav-group-label">注文・商品</div>
                    @if(!$adminUser || $adminUser->hasPermission('orders.view'))
                    <a href="{{ admin_route('admin.orders') }}" class="nav-item {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>注文（モバイル・店内）</span>
                    </a>
                    @endif
                    @if(!$adminUser || $adminUser->hasPermission('products.view'))
                    <a href="{{ admin_route('admin.products') }}" class="nav-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                        <i class="fas fa-utensils"></i>
                        <span>商品管理</span>
                    </a>
                    @endif
                </div>
                @endif
                @if(!$adminUser || $adminUser->hasPermission('members.view'))
                <div class="nav-group">
                    <div class="nav-group-label">会員・ポイント</div>
                    <a href="{{ admin_route('admin.members') }}" class="nav-item {{ request()->routeIs('admin.members*') ? 'active' : '' }}">
                        <i class="fas fa-id-card"></i>
                        <span>会員管理</span>
                    </a>
                </div>
                @endif
                @if(!$adminUser || $adminUser->hasPermission('reservations.view') || $adminUser->hasPermission('queue.view'))
                <div class="nav-group">
                    <div class="nav-group-label">予約・順番待ち</div>
                    @if(!$adminUser || $adminUser->hasPermission('reservations.view'))
                    <a href="{{ admin_route('admin.reservations') }}" class="nav-item {{ request()->routeIs('admin.reservations*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-check"></i>
                        <span>予約</span>
                    </a>
                    @endif
                    @if(!$adminUser || $adminUser->hasPermission('queue.view'))
                    <a href="{{ admin_route('admin.queue') }}" class="nav-item {{ request()->routeIs('admin.queue*') ? 'active' : '' }}">
                        <i class="fas fa-list-ol"></i>
                        <span>順番待ち</span>
                    </a>
                    @endif
                </div>
                @endif
                @if(!$adminUser || $adminUser->hasPermission('stamps.view') || $adminUser->hasPermission('coupons.view'))
                <div class="nav-group">
                    <div class="nav-group-label">スタンプ・クーポン</div>
                    @if(!$adminUser || $adminUser->hasPermission('stamps.view'))
                    <a href="{{ admin_route('admin.stamps') }}" class="nav-item {{ request()->routeIs('admin.stamps*') ? 'active' : '' }}">
                        <i class="fas fa-stamp"></i>
                        <span>スタンプ</span>
                    </a>
                    @endif
                    @if(!$adminUser || $adminUser->hasPermission('coupons.view'))
                    <a href="{{ admin_route('admin.coupons') }}" class="nav-item {{ request()->routeIs('admin.coupons*') ? 'active' : '' }}">
                        <i class="fas fa-ticket-alt"></i>
                        <span>クーポン</span>
                    </a>
                    @endif
                </div>
                @endif
                @if(!$adminUser || $adminUser->hasPermission('announcements.view'))
                <div class="nav-group">
                    <div class="nav-group-label">お知らせ</div>
                    <a href="{{ admin_route('admin.announcements') }}" class="nav-item {{ request()->routeIs('admin.announcements*') ? 'active' : '' }}">
                        <i class="fas fa-bullhorn"></i>
                        <span>お知らせ</span>
                    </a>
                </div>
                @endif
                @if(!$adminUser || $adminUser->hasPermission('analytics.view'))
                <div class="nav-group">
                    <div class="nav-group-label">分析</div>
                    <a href="{{ admin_route('admin.analytics') }}" class="nav-item {{ request()->routeIs('admin.analytics*') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        <span>分析</span>
                    </a>
                </div>
                @endif
                @if(!$adminUser || $adminUser->hasPermission('gallery.view'))
                <div class="nav-group">
                    <div class="nav-group-label">ギャラリー</div>
                    <a href="{{ admin_route('admin.gallery.categories') }}" class="nav-item {{ request()->routeIs('admin.gallery*') ? 'active' : '' }}">
                        <i class="fas fa-images"></i>
                        <span>ギャラリー</span>
                    </a>
                </div>
                @endif
                @if(!$adminUser || $adminUser->hasPermission('settings.view') || $adminUser->hasPermission('liff_designer.manage'))
                <div class="nav-group">
                    <div class="nav-group-label">設定</div>
                    @if(!$adminUser || $adminUser->hasPermission('settings.view'))
                    <a href="{{ admin_route('admin.settings.basic') }}" class="nav-item {{ request()->routeIs('admin.settings.basic*') ? 'active' : '' }}">
                        <i class="fas fa-sliders-h"></i>
                        <span>基本設定</span>
                    </a>
                    <a href="{{ admin_route('admin.settings.advanced') }}" class="nav-item {{ request()->routeIs('admin.settings.advanced*') ? 'active' : '' }}">
                        <i class="fas fa-palette"></i>
                        <span>詳細設定（配色）</span>
                    </a>
                    @endif
                    @if(!$adminUser || $adminUser->hasPermission('liff_designer.manage'))
                    <a href="{{ admin_route('admin.settings.liff-designer') }}" class="nav-item {{ request()->routeIs('admin.settings.liff-designer*') ? 'active' : '' }}">
                        <i class="fas fa-paint-brush"></i>
                        <span>LIFFデザイン</span>
                    </a>
                    @endif
                </div>
                @endif
                <div class="nav-group nav-group-footer">
                    <a href="/" class="nav-item">
                        <i class="fas fa-external-link-alt"></i>
                        <span>フロントに戻る</span>
                    </a>
                </div>
            </nav>
        </aside>

        
        <main class="main-content">
            <header class="content-header content-header-with-actions">
                <h2>@yield('page-title', 'ダッシュボード')</h2>
                @auth('admin')
                <div class="content-header-actions">
                    @if(auth('admin')->user()->isSystemAdmin() && !request()->routeIs('system-admin.*'))
                    <a href="{{ route('system-admin.dashboard') }}" class="btn btn-sm btn-outline-primary" title="システム管理画面へ">
                        <i class="fas fa-shield-alt"></i> システム管理
                    </a>
                    @endif
                    <span class="content-header-user" title="{{ auth('admin')->user()->name }}">
                        <i class="fas fa-user-shield"></i>
                        {{ Str::limit(auth('admin')->user()->name, 12) }}
                        <span class="content-header-role">{{ \App\Models\AdminUser::getRoleLabelShort(auth('admin')->user()->role) }}</span>
                    </span>
                    <form action="{{ request()->routeIs('system-admin.*') ? route('system-admin.logout') : route('admin.logout') }}" method="POST" class="content-header-logout-form" onsubmit="return confirm('ログアウトしますか？');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">ログアウト</button>
                    </form>
                </div>
                @endauth
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
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>


