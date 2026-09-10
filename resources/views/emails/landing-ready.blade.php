<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>店舗の準備ができました</title>
</head>
<body>
    <p>{{ $storeName }} の店舗を発行しました。システム管理者の操作は不要です。</p>
    <p>店舗アプリ（LIFF）:<br><a href="{{ $liffUrl }}">{{ $liffUrl }}</a></p>
    <p>管理画面:<br><a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
    <p>ログインID: {{ $loginId }}</p>
    <p>パスワード: {{ $password }}</p>
    <p>パスワードはこのメールと、登録完了画面にだけ表示されます。</p>
</body>
</html>
