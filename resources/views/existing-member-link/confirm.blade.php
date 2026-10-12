<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>既存会員連携の確認</title>
<link rel="stylesheet" href="{{ asset('css/web-ui.css') }}?v=1">
</head>
<body class="web-standalone">
    <main>
        <nav class="web-page-navigation" aria-label="ページの移動"><x-back-link :href="route('help.show', ['slug' => 'existing-member-link'])" label="既存会員連携のヘルプへ" /></nav>
        <h1>既存会員を連携しますか？</h1>
        <p>店舗に登録された会員情報を、連携を申請したアプリのアカウントに結びつけます。</p>
        <p>申請したアプリ・ブラウザで確認してください。申請に心当たりがない場合は、このページを閉じてください。</p>
        <p>ネイティブアプリから申請した場合は、メールのリンクをコピーして、アプリの「既存会員連携」画面に貼り付けてください。</p>
        <form method="POST" action="{{ route('existing-member-link.complete', ['token' => $token]) }}">
            @csrf
            <button type="submit">会員情報を連携する</button>
        </form>
    </main>
</body>
</html>
