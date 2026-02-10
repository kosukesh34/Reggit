<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>管理画面 - @yield('title', 'Mobile Order')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
    @if(isset($adminSidebarColor) && $adminSidebarColor)
    <style>
        .sidebar { background: {{ $adminSidebarColor }}; }
    </style>
    @endif
    @if(isset($adminHeaderColor) && $adminHeaderColor)
    <style>
        .content-header { background: {{ $adminHeaderColor }}; }
    </style>
    @endif
    @if(isset($adminPrimaryColor) && $adminPrimaryColor)
    <style>
        :root {
            --primary: {{ $adminPrimaryColor }};
            --primary-hover: color-mix(in srgb, {{ $adminPrimaryColor }} 85%, #000000);
            --primary-light: color-mix(in srgb, {{ $adminPrimaryColor }} 70%, #ffffff);
            --primary-bg: color-mix(in srgb, {{ $adminPrimaryColor }} 12%, #ffffff);
        }
    </style>
    @endif
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
                @php
                    $au = auth('admin')->user();
                @endphp
                @if(!request()->routeIs('system-admin.*') && isset($currentProject))
                <div class="nav-group nav-group-collapsible">
                    <button type="button" class="nav-group-label" data-nav-group-toggle>
                        <span>メニュー</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    @if(!$au || $au->hasPermission('dashboard.view'))
                    <a href="{{ admin_route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-th-large"></i>
                        <span>ダッシュボード</span>
                    </a>
                    @endif
                </div>

                {{-- 注文・商品 --}}
                @if(!$au || $au->hasAnyPermission(['orders.view', 'orders.update', 'products.view', 'products.manage']))
                <div class="nav-group nav-group-collapsible">
                    <button type="button" class="nav-group-label" data-nav-group-toggle>
                        <span>注文・商品</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    @if(!$au || $au->hasPermission('orders.view'))
                    <a href="{{ admin_route('admin.orders') }}" class="nav-item {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>注文（モバイル・店内）</span>
                    </a>
                    @endif
                    @if(!$au || $au->hasPermission('products.view'))
                    <a href="{{ admin_route('admin.products') }}" class="nav-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                        <i class="fas fa-utensils"></i>
                        <span>商品管理</span>
                    </a>
                    @endif
                </div>
                @endif

                {{-- 分析 --}}
                @if(!$au || $au->hasPermission('analytics.view'))
                <div class="nav-group nav-group-collapsible">
                    <button type="button" class="nav-group-label" data-nav-group-toggle>
                        <span>分析</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <a href="{{ admin_route('admin.analytics') }}" class="nav-item {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        <span>分析ダッシュボード</span>
                    </a>
                </div>
                @endif

                {{-- 会員・ポイント --}}
                @if(!$au || $au->hasAnyPermission(['members.view', 'members.manage']))
                <div class="nav-group nav-group-collapsible">
                    <button type="button" class="nav-group-label" data-nav-group-toggle>
                        <span>会員・ポイント</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <a href="{{ admin_route('admin.members') }}" class="nav-item {{ request()->routeIs('admin.members*') ? 'active' : '' }}">
                        <i class="fas fa-id-card"></i>
                        <span>会員管理</span>
                    </a>
                </div>
                @endif

                {{-- 予約・順番待ち --}}
                @if(!$au || $au->hasAnyPermission(['reservations.view', 'reservations.update', 'queue.view', 'queue.update']))
                <div class="nav-group nav-group-collapsible">
                    <button type="button" class="nav-group-label" data-nav-group-toggle>
                        <span>予約・順番待ち</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    @if(!$au || $au->hasPermission('reservations.view'))
                    <a href="{{ admin_route('admin.reservations') }}" class="nav-item {{ request()->routeIs('admin.reservations*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-check"></i>
                        <span>予約</span>
                    </a>
                    @endif
                    @if(!$au || $au->hasPermission('queue.view'))
                    <a href="{{ admin_route('admin.queue') }}" class="nav-item {{ request()->routeIs('admin.queue*') ? 'active' : '' }}">
                        <i class="fas fa-list-ol"></i>
                        <span>順番待ち</span>
                    </a>
                    @endif
                </div>
                @endif

                {{-- スタンプ・クーポン --}}
                @if(!$au || $au->hasAnyPermission(['stamps.view', 'stamps.manage', 'coupons.view', 'coupons.manage']))
                <div class="nav-group nav-group-collapsible">
                    <button type="button" class="nav-group-label" data-nav-group-toggle>
                        <span>スタンプ・クーポン</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    @if(!$au || $au->hasPermission('stamps.view'))
                    <a href="{{ admin_route('admin.stamps') }}" class="nav-item {{ request()->routeIs('admin.stamps*') ? 'active' : '' }}">
                        <i class="fas fa-stamp"></i>
                        <span>スタンプ</span>
                    </a>
                    @endif
                    @if(!$au || $au->hasPermission('coupons.view'))
                    <a href="{{ admin_route('admin.coupons') }}" class="nav-item {{ request()->routeIs('admin.coupons*') ? 'active' : '' }}">
                        <i class="fas fa-ticket-alt"></i>
                        <span>クーポン</span>
                    </a>
                    @endif
                </div>
                @endif

                {{-- ギャラリー --}}
                @if(!$au || $au->hasAnyPermission(['gallery.view', 'gallery.manage']))
                <div class="nav-group nav-group-collapsible">
                    <button type="button" class="nav-group-label" data-nav-group-toggle>
                        <span>ギャラリー</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <a href="{{ admin_route('admin.gallery.categories') }}" class="nav-item {{ request()->routeIs('admin.gallery.categories*') ? 'active' : '' }}">
                        <i class="fas fa-tags"></i>
                        <span>カテゴリ</span>
                    </a>
                    <a href="{{ admin_route('admin.gallery.items') }}" class="nav-item {{ request()->routeIs('admin.gallery.items*') ? 'active' : '' }}">
                        <i class="fas fa-images"></i>
                        <span>画像一覧</span>
                    </a>
                </div>
                @endif

                {{-- お知らせ --}}
                @if(!$au || $au->hasAnyPermission(['announcements.view', 'announcements.manage']))
                <div class="nav-group nav-group-collapsible">
                    <button type="button" class="nav-group-label" data-nav-group-toggle>
                        <span>お知らせ</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <a href="{{ admin_route('admin.announcements') }}" class="nav-item {{ request()->routeIs('admin.announcements*') ? 'active' : '' }}">
                        <i class="fas fa-bullhorn"></i>
                        <span>お知らせ</span>
                    </a>
                </div>
                @endif

                {{-- 設定 --}}
                @if(!$au || $au->hasAnyPermission(['settings.view', 'settings.manage']))
                <div class="nav-group nav-group-collapsible">
                    <button type="button" class="nav-group-label" data-nav-group-toggle>
                        <span>設定</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <a href="{{ admin_route('admin.settings.basic') }}" class="nav-item {{ request()->routeIs('admin.settings.basic*') ? 'active' : '' }}">
                        <i class="fas fa-sliders-h"></i>
                        <span>基本設定</span>
                    </a>
                    <a href="{{ admin_route('admin.settings.menu') }}" class="nav-item {{ request()->routeIs('admin.settings.menu*') ? 'active' : '' }}">
                        <i class="fas fa-bars"></i>
                        <span>メニュー設定</span>
                    </a>
                    <a href="{{ admin_route('admin.settings.ranks') }}" class="nav-item {{ request()->routeIs('admin.settings.ranks*') ? 'active' : '' }}">
                        <i class="fas fa-medal"></i>
                        <span>ランク・ポイント</span>
                    </a>
                    <a href="{{ admin_route('admin.settings.advanced') }}" class="nav-item {{ request()->routeIs('admin.settings.advanced*') ? 'active' : '' }}">
                        <i class="fas fa-palette"></i>
                        <span>詳細設定（配色）</span>
                    </a>
                    <a href="{{ admin_route('admin.settings.features') }}" class="nav-item {{ request()->routeIs('admin.settings.features*') ? 'active' : '' }}">
                        <i class="fas fa-toggle-on"></i>
                        <span>機能設定</span>
                    </a>
                    <a href="{{ admin_route('admin.settings.liff-designer') }}" class="nav-item {{ request()->routeIs('admin.settings.liff-designer*') ? 'active' : '' }}">
                        <i class="fas fa-palette"></i>
                        <span>LIFFデザイン</span>
                    </a>
                </div>
                @endif

                {{-- アカウント管理（project_admin / admin のみ） --}}
                @if($au && !$au->isStaff())
                <div class="nav-group nav-group-collapsible">
                    <button type="button" class="nav-group-label" data-nav-group-toggle>
                        <span>管理</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <a href="{{ admin_route('admin.accounts.index') }}" class="nav-item {{ request()->routeIs('admin.accounts*') ? 'active' : '' }}">
                        <i class="fas fa-user-cog"></i>
                        <span>アカウント管理</span>
                    </a>
                </div>
                @endif

                @endif
                @if(request()->routeIs('system-admin.*'))
                    @if(($projects ?? collect())->isNotEmpty())
                    <div class="nav-group nav-group-collapsible">
                        <button type="button" class="nav-group-label" data-nav-group-toggle>
                            <span>プロジェクトのダッシュボード</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        @foreach($projects as $p)
                            <a href="{{ url('/admin/' . $p->project_key) }}" class="nav-item">
                                <i class="fas fa-th-large"></i>
                                <span>{{ $p->name }}</span>
                            </a>
                        @endforeach
                    </div>
                    @endif
                    <div class="nav-group nav-group-collapsible">
                        <button type="button" class="nav-group-label" data-nav-group-toggle>
                            <span>システム</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <a href="{{ route('system-admin.projects.index') }}" class="nav-item {{ request()->routeIs('system-admin.projects*') ? 'active' : '' }}">
                            <i class="fas fa-building"></i>
                            <span>プロジェクト管理</span>
                        </a>
                        <a href="{{ route('system-admin.api-tokens.index') }}" class="nav-item {{ request()->routeIs('system-admin.api-tokens*') ? 'active' : '' }}">
                            <i class="fas fa-key"></i>
                            <span>APIトークン</span>
                        </a>
                        <a href="{{ route('system-admin.audit-logs.index') }}" class="nav-item {{ request()->routeIs('system-admin.audit-logs*') ? 'active' : '' }}">
                            <i class="fas fa-clipboard-list"></i>
                            <span>監査ログ</span>
                        </a>
                        <a href="{{ route('system-admin.accounts.index') }}" class="nav-item {{ request()->routeIs('system-admin.accounts*') ? 'active' : '' }}">
                            <i class="fas fa-user-cog"></i>
                            <span>アカウント管理</span>
                        </a>
                    </div>
                @endif
                <div class="nav-group nav-group-footer">
                    <a href="{{ isset($currentProject) ? '/?project_key=' . $currentProject->project_key : '/' }}" class="nav-item">
                        <i class="fas fa-external-link-alt"></i>
                        <span>フロントに戻る</span>
                    </a>
                </div>
            </nav>
        </aside>

        
        <main class="main-content">
            <header class="content-header content-header-with-actions">
                <h2>@yield('page-title', 'ダッシュボード')</h2>
                <div class="content-header-actions">
                    @auth('admin')
                        @if(!request()->routeIs('system-admin.*') && ($projects ?? collect())->isNotEmpty() && isset($currentProject))
                            <label class="admin-project-switcher-label">表示プロジェクト:</label>
                            <select class="form-control form-control-sm admin-project-select" onchange="if(this.value) window.location.href='{{ url('/admin') }}/'+this.value">
                                @foreach($projects ?? [] as $p)
                                    <option value="{{ $p->project_key }}" {{ ($currentProject->id ?? 0) == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->project_key }})</option>
                                @endforeach
                            </select>
                        @endif
                        @if($isSystemAdmin ?? false)
                            @if(request()->routeIs('system-admin.*') && ($projects ?? collect())->isNotEmpty())
                            <label class="admin-project-switcher-label">ダッシュボード:</label>
                            <select class="form-control form-control-sm admin-project-select" onchange="if(this.value) window.location.href='{{ url('/admin') }}/'+this.value" style="max-width:200px;">
                                <option value="">— 選択 —</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->project_key }}">{{ $p->name }} ({{ $p->project_key }})</option>
                                @endforeach
                            </select>
                            @endif
                            <a href="{{ route('system-admin.projects.index') }}" class="btn btn-sm btn-outline-secondary">プロジェクト管理</a>
                            <a href="{{ route('system-admin.api-tokens.index') }}" class="btn btn-sm btn-outline-secondary">APIトークン</a>
                            <a href="{{ route('system-admin.audit-logs.index') }}" class="btn btn-sm btn-outline-secondary">監査ログ</a>
                            <a href="{{ route('system-admin.accounts.index') }}" class="btn btn-sm btn-outline-secondary">アカウント管理</a>
                        @endif
                        <form action="{{ route(request()->routeIs('system-admin.*') ? 'system-admin.logout' : 'admin.logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">ログアウト</button>
                        </form>
                        <span class="admin-user-name">{{ $adminUser->name ?? '' }}（{{ \App\Models\AdminUser::getRoleLabelShort($adminUser->role ?? '') }}）</span>
                    @endauth
                </div>
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
