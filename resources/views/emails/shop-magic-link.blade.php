<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ショップログイン</title>
</head>
<body>
    <p>{{ $shopName }} へのログイン用リンクです。</p>
    <p>30分以内に次のリンクを開いてください。</p>
    <p><a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
    <p>心当たりがない場合はこのメールを破棄してください。</p>
</body>
</html>
