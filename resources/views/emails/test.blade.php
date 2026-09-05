<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Reggit メール送信テスト</title>
</head>
<body>
    <p>{{ $bodyMessage }}</p>
    <p>送信日時: {{ now()->format('Y-m-d H:i:s') }}</p>
</body>
</html>
