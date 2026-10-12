<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>順番待ちを開く</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --line: #06c755;
            --native: #2563eb;
            --border: #e5e7eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: linear-gradient(160deg, #eef2ff 0%, var(--bg) 45%, #ecfeff 100%);
            color: var(--text);
        }
        .panel {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            text-align: center;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 1.35rem;
        }
        p {
            margin: 0 0 20px;
            color: var(--muted);
            line-height: 1.6;
            font-size: 0.95rem;
        }
        .actions {
            display: grid;
            gap: 12px;
        }
        a.btn {
            display: block;
            text-decoration: none;
            border-radius: 12px;
            padding: 14px 16px;
            font-weight: 600;
            color: #fff;
        }
        a.btn-line { background: var(--line); }
        a.btn-native { background: var(--native); }
        .error { color: #b91c1c; }
    </style>
    @if($view->mode === 'native' && $view->nativeSchemeUrl)
        <meta http-equiv="refresh" content="0;url={{ $view->nativeSchemeUrl }}">
    @endif
<link rel="stylesheet" href="{{ asset('css/web-ui.css') }}?v=1">
</head>
<body>
    <div class="panel">
        <nav class="web-page-navigation" aria-label="ページの移動"><x-back-link :href="route('help.index')" label="ヘルプへ" /></nav>
        @if($view->mode === 'error')
            <h1>開けませんでした</h1>
            <p class="error">{{ $view->errorMessage }}</p>
        @elseif($view->mode === 'native')
            <h1>アプリを起動しています</h1>
            <p>自動で開かない場合は下のボタンを押してください。</p>
            <div class="actions">
                <a class="btn btn-native" href="{{ $view->nativeSchemeUrl }}">ネイティブアプリで開く</a>
            </div>
            <script>
                window.location.href = @json($view->nativeSchemeUrl);
            </script>
        @else
            <h1>アプリを選択</h1>
            <p>順番待ちの確認に使うアプリを選んでください。</p>
            <div class="actions">
                @if($view->lineUrl)
                    <a class="btn btn-line" href="{{ $view->lineUrl }}">LINEで開く</a>
                @endif
                @if($view->nativeSchemeUrl)
                    <a class="btn btn-native" href="{{ $view->nativeSchemeUrl }}">ネイティブアプリで開く</a>
                @endif
            </div>
        @endif
    </div>
</body>
</html>
