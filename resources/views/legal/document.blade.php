<!DOCTYPE html>
<html lang="ja" @class(['native-app-embed' => request()->query('embed') === '1'])>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }} | {{ $project->name }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #1f2937; background: #fff; line-height: 1.7; }
        main { max-width: 720px; margin: 0 auto; padding: 24px 16px 48px; }
        .store { margin: 0 0 4px; color: #6b7280; font-size: 13px; }
        h1 { margin: 0 0 8px; font-size: 28px; line-height: 1.3; }
        .updated { margin: 0 0 24px; color: #9ca3af; font-size: 12px; }
        section { margin: 0 0 20px; }
        h2 { margin: 0 0 6px; font-size: 16px; }
        p { margin: 0; color: #374151; font-size: 14px; }
        html.native-app-embed .legal-page-navigation { display: none; }
    </style>
<link rel="stylesheet" href="{{ asset('css/web-ui.css') }}?v=1">
</head>
<body>
<main>
    <nav class="web-page-navigation legal-page-navigation" aria-label="ページの移動"><x-back-link :href="route('liff.project', ['project_key' => $project->project_key]).'#/more'" label="店舗アプリへ戻る" /></nav>
    <p class="store">{{ $project->name }}</p>
    <h1>{{ $title }}</h1>
    <p class="updated">最終更新日：{{ $updatedAt }}</p>
    @foreach($sections as $section)
        <section>
            <h2>{{ $section['title'] }}</h2>
            <p>{{ $section['body'] }}</p>
        </section>
    @endforeach
</main>
</body>
</html>
