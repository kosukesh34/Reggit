<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — Reggit</title>
    <meta name="theme-color" content="#f4f0e8">
    <link rel="icon" type="image/png" href="{{ asset('images/reggit-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing-home.css') }}?v=13">
</head>
<body class="lp lp-sub">
    <a class="lp-skip" href="#main">本文へ移動</a>
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
    <main id="main">
        @yield('content')
    </main>
    <footer class="lp-footer">
        <div class="lp-container lp-footer__inner">
            <a class="lp-brand" href="/" aria-label="Reggit トップへ"><img src="{{ asset('images/reggit-logo.png') }}" alt="Reggit" width="72" height="72"></a>
            <p>店舗向けLINEミニアプリ・管理システム</p>
            <p>&copy; {{ date('Y') }} Reggit</p>
        </div>
    </footer>
</body>
</html>
