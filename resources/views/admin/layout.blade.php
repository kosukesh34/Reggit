<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理画面 - @yield('title', 'Mobile Order')</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="admin-container">
        
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>管理画面</h1>
            </div>
            <nav class="sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span>📊</span> ダッシュボード
                </a>
                <a href="{{ route('admin.products') }}" class="nav-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                    <span>🍔</span> 商品管理
                </a>
                <a href="{{ route('admin.orders') }}" class="nav-item {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                    <span>📦</span> 注文管理
                </a>
                <a href="{{ route('admin.members') }}" class="nav-item {{ request()->routeIs('admin.members*') ? 'active' : '' }}">
                    <span>👥</span> 会員管理
                </a>
                <a href="/" class="nav-item">
                    <span>🏠</span> フロントに戻る
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


