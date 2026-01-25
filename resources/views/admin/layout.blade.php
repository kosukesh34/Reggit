<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理画面 - @yield('title', 'Mobile Order')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="{{ asset('js/utils/confirmDialog.js') }}"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
</head>
<body>
    <div class="admin-container">
        
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-cog"></i> 管理画面</h1>
            </div>
            <nav class="sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>ダッシュボード</span>
                </a>
                <a href="{{ route('admin.products') }}" class="nav-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                    <i class="fas fa-hamburger"></i>
                    <span>商品管理</span>
                </a>
                <a href="{{ route('admin.orders') }}" class="nav-item {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                    <i class="fas fa-shopping-bag"></i>
                    <span>注文管理</span>
                </a>
                <a href="{{ route('admin.members') }}" class="nav-item {{ request()->routeIs('admin.members*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>会員管理</span>
                </a>
                <a href="/" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>フロントに戻る</span>
                </a>
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


