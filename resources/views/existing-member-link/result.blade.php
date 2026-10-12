<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>既存会員連携</title>
<link rel="stylesheet" href="{{ asset('css/web-ui.css') }}?v=1">
</head>
<body class="web-standalone">
    <main>
        <nav class="web-page-navigation" aria-label="ページの移動"><x-back-link :href="route('help.show', ['slug' => 'existing-member-link'])" label="既存会員連携のヘルプへ" /></nav>
        <h1>{{ $isSuccess ? '連携しました' : '連携できませんでした' }}</h1>
        <p>{{ $message }}</p>
    </main>
</body>
</html>
