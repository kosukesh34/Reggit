<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>既存会員連携</title>
</head>
<body>
    <main>
        <h1>{{ $isSuccess ? '連携しました' : '連携できませんでした' }}</h1>
        <p>{{ $message }}</p>
    </main>
</body>
</html>
