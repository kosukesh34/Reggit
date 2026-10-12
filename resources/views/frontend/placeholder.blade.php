<!DOCTYPE html>
<html lang="ja" @class(['native-app-embed' => $isNativeApp ?? false])>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="project-key" content="{{ $projectKey ?? '' }}">
    <title>{{ $projectName ?? config('app.name') }}</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { display: grid; min-height: 100svh; margin: 0; color: #1f2937; background: #fff; place-items: center; }
        main { max-width: 32rem; padding: 2rem; text-align: center; }
        h1 { margin: 0 0 0.75rem; font-size: 1.25rem; }
        p { margin: 0; line-height: 1.7; }
    </style>
<link rel="stylesheet" href="{{ asset('css/web-ui.css') }}?v=1">
</head>
<body>
    <main id="liff-placeholder" aria-labelledby="liff-placeholder-title">
        <nav class="web-page-navigation" aria-label="ページの移動"><x-back-link :href="route('help.index')" label="ヘルプへ" /></nav>
        <h1 id="liff-placeholder-title">ただいま準備中です</h1>
        <p>新しいアプリ体験を準備しています。公開までしばらくお待ちください。</p>
    </main>
</body>
</html>
