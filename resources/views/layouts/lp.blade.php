<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — Reggit</title>
    <meta name="theme-color" content="#00344d">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&family=Noto+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v=60">
</head>
<body class="lp lp-sub">
    <header class="lp-header">
        <div class="lp-container lp-header__inner">
            <a class="lp-brand" href="/" aria-label="Reggit トップへ">
                <img src="{{ asset('images/reggit-logo.png') }}" alt="Reggit" width="72" height="72">

            </a>
            <div class="lp-header__actions">
                <a class="lp-button lp-button--small lp-button--outline" href="{{ route('landing.register.email.show') }}">導入登録</a>
                <a class="lp-button lp-button--small lp-button--dark" href="{{ route('admin.login') }}">管理画面ログイン</a>
            </div>
        </div>
    </header>
    <main>
        @yield('content')
    </main>
    <footer class="lp-footer">
        <div class="lp-container lp-footer__inner">
            <a class="lp-brand" href="/" aria-label="Reggit トップへ"><img src="{{ asset('images/reggit-logo.png') }}" alt="Reggit" width="72" height="72"></a>
            <p>店舗アプリを、LINEでも、専用アプリでも。</p>
            <p>&copy; {{ date('Y') }} Reggit</p>
        </div>
    </footer>
</body>
</html>
