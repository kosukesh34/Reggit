<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>メールアドレスの認証</title>
</head>
<body>
    <p>Reggit の導入登録を受け付けました。</p>
    <p>以下のリンクを開いて、メールアドレスの認証を完了してください。</p>
    <p><a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a></p>
    <p>リンクの有効期限は24時間です。心当たりがない場合はこのメールを破棄してください。</p>
</body>
</html>
