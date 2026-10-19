<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Reggit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
    @vite(['resources/js/admin/passkeys.ts'])
</head>
<body class="admin-login-page">
    <div class="admin-login-box" {!! trim($__env->yieldContent('root-attrs')) !!}>
        <h1><i class="fas fa-cog"></i> @yield('heading')</h1>
        <p class="admin-login-lead">@yield('lead')</p>
        @yield('content')
        <div class="admin-login-switch">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="admin-passkey-logout">ログアウトしてやり直す</button>
            </form>
        </div>
    </div>
</body>
</html>
