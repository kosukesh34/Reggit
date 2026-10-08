<!doctype html>
<html lang="ja"><head><meta name="referrer" content="no-referrer"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>注文のお支払い</title></head>
<body><main>
    <h1>{{ $status === 'success' ? 'お支払い状況を確認します' : 'お支払いが中断されました' }}</h1>
    <p>アプリに戻り、注文のお支払い状況を確認してください。受付番号が表示されると注文確定です。</p>
    <a href="{{ $returnUrl }}">注文画面に戻る</a>
</main></body></html>
