<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Walletに追加</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 24px; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #f5f5f5; }
        .card { background: #fff; border-radius: 12px; padding: 32px; max-width: 360px; width: 100%; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h1 { font-size: 1.25rem; margin: 0 0 16px; color: #333; text-align: center; }
        p { font-size: 0.875rem; color: #666; margin: 0 0 24px; line-height: 1.5; text-align: center; }
        .btn { display: block; width: 100%; padding: 14px 20px; margin-bottom: 12px; border-radius: 8px; font-size: 1rem; font-weight: 600; text-align: center; text-decoration: none; border: none; cursor: pointer; transition: opacity 0.2s; }
        .btn:active { opacity: 0.9; }
        .btn-primary { background: #000; color: #fff; }
        .btn-secondary { background: #e0e0e0; color: #333; margin-bottom: 0; }
    </style>
<link rel="stylesheet" href="{{ asset('css/web-ui.css') }}?v=1">
</head>
<body>
    <div class="card">
        @if($view->returnUrl)
        <nav class="web-page-navigation" aria-label="ページの移動"><x-back-link :href="$view->returnUrl" :label="$view->returnBackLabel" /></nav>
        @endif
        <h1>Walletに追加</h1>
        <p>下のボタンで会員証を{{ $view->typeLabel }}に追加してください。追加が完了したら「{{ $view->returnBackLabel }}」をタップしてください。</p>
        @if($view->walletUrl)
        <button type="button" class="btn btn-primary" id="walletAddButton">{{ $view->addButtonLabel }}</button>
        @endif
    </div>
    @if($view->walletUrl)
    <script>
        (function () {
            var target = @json($view->walletUrl);
            if (!target) return;

            var opened = false;
            function openWalletOnce() {
                if (opened) return;
                opened = true;
                try {
                    var parsed = new URL(target);
                    if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
                        opened = false;
                        return;
                    }
                    window.location.assign(parsed.href);
                } catch (e) {
                    opened = false;
                    console.warn('wallet-add redirect skipped: invalid url', e);
                }
            }

            var btn = document.getElementById('walletAddButton');
            if (btn) {
                btn.addEventListener('click', openWalletOnce);
            }
            setTimeout(openWalletOnce, 400);
        })();
    </script>
    @endif
</body>
</html>
