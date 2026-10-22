<!doctype html>
<html lang="ja"><head><meta name="referrer" content="no-referrer"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>アプリにログイン</title><link rel="stylesheet" href="{{ asset('css/web-ui.css') }}?v=1"><style>.app-login-open { display: inline-block; min-height: 44px; padding: 12px 20px; border-radius: 12px; background: #00344d; color: #fff; font-weight: 600; text-decoration: none; } .app-login-open:focus-visible { outline: 3px solid #2479a8; outline-offset: 3px; }</style></head>
<body class="web-standalone"><main>
    <h1>アプリにログイン</h1>
    <p>アプリを入れたスマートフォンでこのページを開き、下のボタンをタップしてください。</p>
    <p><a class="app-login-open" href="{{ $appUrl }}">アプリを開いてログイン</a></p>
    <h2>アプリが開かない場合</h2>
    <p>アプリのログイン画面で「会員IDとコードで引き継ぐ」を選び、次の内容を入力してください。コードは30分以内に1回だけ使えます。</p>
    <dl>
        <dt>会員ID</dt><dd><strong>{{ $memberId }}</strong></dd>
        <dt>引き継ぎコード</dt><dd><strong>{{ $code }}</strong></dd>
    </dl>
    <p>心当たりがない場合は、このページを閉じてください。</p>
</main></body></html>
