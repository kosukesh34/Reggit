<!doctype html>
<html lang="ja"><head><meta name="referrer" content="no-referrer"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>注文のお支払い</title><link rel="stylesheet" href="{{ asset('css/web-ui.css') }}?v=1"></head>
<body class="web-standalone"><main>
    <nav class="web-page-navigation" aria-label="ページの移動"><x-back-link :href="$returnUrl" label="注文画面へ戻る" /></nav>
    <h1>{{ $status === 'success' ? 'お支払い状況を確認します' : 'お支払いが中断されました' }}</h1>
    <p>アプリに戻り、注文のお支払い状況を確認してください。受付番号が表示されると注文確定です。</p>
    <p role="status">注文画面へ戻っています。移動しない場合は、上の「注文画面へ戻る」をタップしてください。</p>
    <script>
        window.setTimeout(function () {
            window.location.replace(@json($returnUrl));
        }, 300);
    </script>
</main></body></html>
