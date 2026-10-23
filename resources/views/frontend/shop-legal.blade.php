<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>特定商取引法に基づく表記 | {{ $projectName }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #1f2937; background: #fff; line-height: 1.7; }
        main { max-width: 760px; margin: 0 auto; padding: 24px 16px 48px; }
        .store { margin: 0 0 4px; color: #6b7280; font-size: 13px; }
        h1 { margin: 0 0 24px; font-size: 24px; line-height: 1.3; }
        dl { margin: 0; border-top: 1px solid #e5e7eb; }
        .row { display: grid; grid-template-columns: minmax(120px, 220px) 1fr; gap: 8px 16px; padding: 14px 0; border-bottom: 1px solid #e5e7eb; }
        dt { font-weight: 700; font-size: 14px; }
        dd { margin: 0; font-size: 14px; white-space: pre-line; overflow-wrap: anywhere; }
        .notice { padding: 16px; border-radius: 8px; background: #fef3c7; color: #92400e; }
        @media (max-width: 560px) { .row { grid-template-columns: 1fr; gap: 2px; } }
    </style>
    <link rel="stylesheet" href="{{ asset('css/web-ui.css') }}?v=1">
</head>
<body>
<main>
    <nav class="web-page-navigation" aria-label="ページの移動"><x-back-link :href="$shopUrl" label="ショップへ戻る" /></nav>
    <p class="store">{{ $projectName }}</p>
    <h1>特定商取引法に基づく表記</h1>
    @if(in_array('', $notice, true))
        <p class="notice" role="status">表記を準備中です。準備が整うまでご注文はお受けできません。</p>
    @endif
    <dl>
        @foreach($labels as $key => $label)
            @if($notice[$key] !== '')
                <div class="row"><dt>{{ $label }}</dt><dd>{{ $notice[$key] }}</dd></div>
            @endif
        @endforeach
        <div class="row"><dt>販売価格</dt><dd>各商品ページに税込価格で表示しています。</dd></div>
    </dl>
</main>
</body>
</html>
