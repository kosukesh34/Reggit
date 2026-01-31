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
</head>
<body>
    <div class="admin-container">
        
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-store"></i> 管理画面</h1>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-group">
                    <div class="nav-group-label">メニュー</div>
                    <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-th-large"></i>
                        <span>ダッシュボード</span>
                    </a>
                </div>
                <div class="nav-group">
                    <div class="nav-group-label">注文・商品</div>
                    <a href="{{ route('admin.orders') }}" class="nav-item {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>注文（モバイル・店内）</span>
                    </a>
                    <a href="{{ route('admin.products') }}" class="nav-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                        <i class="fas fa-utensils"></i>
                        <span>商品管理</span>
                    </a>
                </div>
                <div class="nav-group">
                    <div class="nav-group-label">会員・ポイント</div>
                    <a href="{{ route('admin.members') }}" class="nav-item {{ request()->routeIs('admin.members*') ? 'active' : '' }}">
                        <i class="fas fa-id-card"></i>
                        <span>会員管理</span>
                    </a>
                </div>
                <div class="nav-group">
                    <div class="nav-group-label">予約・順番待ち</div>
                    <a href="{{ route('admin.reservations') }}" class="nav-item {{ request()->routeIs('admin.reservations*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-check"></i>
                        <span>予約</span>
                    </a>
                    <a href="{{ route('admin.queue') }}" class="nav-item {{ request()->routeIs('admin.queue*') ? 'active' : '' }}">
                        <i class="fas fa-list-ol"></i>
                        <span>順番待ち</span>
                    </a>
                </div>
                <div class="nav-group">
                    <div class="nav-group-label">スタンプ・クーポン</div>
                    <a href="{{ route('admin.stamps') }}" class="nav-item {{ request()->routeIs('admin.stamps*') ? 'active' : '' }}">
                        <i class="fas fa-stamp"></i>
                        <span>スタンプ</span>
                    </a>
                    <a href="{{ route('admin.coupons') }}" class="nav-item {{ request()->routeIs('admin.coupons*') ? 'active' : '' }}">
                        <i class="fas fa-ticket-alt"></i>
                        <span>クーポン</span>
                    </a>
                </div>
                <div class="nav-group">
                    <div class="nav-group-label">お知らせ</div>
                    <a href="{{ route('admin.announcements') }}" class="nav-item {{ request()->routeIs('admin.announcements*') ? 'active' : '' }}">
                        <i class="fas fa-bullhorn"></i>
                        <span>お知らせ</span>
                    </a>
                </div>
                <div class="nav-group">
                    <div class="nav-group-label">設定</div>
                    <a href="{{ route('admin.settings.basic') }}" class="nav-item {{ request()->routeIs('admin.settings.basic*') ? 'active' : '' }}">
                        <i class="fas fa-sliders-h"></i>
                        <span>基本設定</span>
                    </a>
                    <a href="{{ route('admin.settings.advanced') }}" class="nav-item {{ request()->routeIs('admin.settings.advanced*') ? 'active' : '' }}">
                        <i class="fas fa-palette"></i>
                        <span>詳細設定（配色）</span>
                    </a>
                </div>
                <div class="nav-group nav-group-footer">
                    <a href="/" class="nav-item">
                        <i class="fas fa-external-link-alt"></i>
                        <span>フロントに戻る</span>
                    </a>
                </div>
            </nav>
        </aside>

        
        <main class="main-content">
            <header class="content-header">
                <h2>@yield('page-title', 'ダッシュボード')</h2>
            </header>
            
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <div class="content-body">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>


