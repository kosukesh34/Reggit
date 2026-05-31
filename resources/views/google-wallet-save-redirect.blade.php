<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Google Wallet に追加</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 24px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 32px;
            max-width: 360px;
            width: 100%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-align: center;
        }
        h1 { font-size: 1.125rem; margin: 0 0 12px; color: #333; }
        p { font-size: 0.875rem; color: #666; margin: 0 0 20px; line-height: 1.5; }
        .btn {
            display: inline-block;
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            background: #1a73e8;
            color: #fff;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Google Wallet を開いています…</h1>
        <p>自動的に移動しない場合は、下のボタンをタップしてください。</p>
        <button type="button" class="btn" id="googleWalletOpenButton">Google Wallet に追加</button>
    </div>
    <script>
        (function () {
            var target = {!! json_encode($targetUrl, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) !!};
            if (!target) return;

            function openWalletOnce() {
                try {
                    var parsed = new URL(target);
                    if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
                        return;
                    }
                    window.location.assign(parsed.href);
                } catch (e) {
                    console.warn('google-wallet redirect skipped: invalid url', e);
                }
            }

            var btn = document.getElementById('googleWalletOpenButton');
            if (btn) {
                btn.addEventListener('click', openWalletOnce);
            }
            setTimeout(openWalletOnce, 300);
        })();
    </script>
</body>
</html>
