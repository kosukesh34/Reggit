<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロジェクトを選択 - Reggit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
</head>
<body class="admin-login-page">
    <div class="admin-login-box">
        <nav class="web-page-navigation" aria-label="ページの移動"><x-back-link :href="url('/')" label="Reggit トップへ戻る" /></nav>
        <h1><i class="fas fa-store"></i> 店舗管理ログイン</h1>
        <p class="admin-login-lead">ログインするプロジェクト（店舗）を選んでください</p>

        <ul class="admin-project-login-list">
            @forelse($projects as $project)
                <li>
                    <a href="{{ route('admin.project.login', ['project_key' => $project->project_key]) }}" class="admin-project-login-link">
                        <i class="fas fa-chevron-right"></i>
                        <span class="admin-project-login-name">{{ $project->name }}</span>
                        <span class="admin-project-login-key">{{ $project->project_key }}</span>
                    </a>
                </li>
            @empty
                <li class="admin-project-login-empty">有効なプロジェクトがありません。</li>
            @endforelse
        </ul>

        <p class="admin-login-switch">
            <a href="{{ route('admin.login.form') }}"><i class="fas fa-key"></i> ID・パスワードでログイン</a>
        </p>
        <p class="admin-login-switch">
            <a href="{{ route('system-admin.login') }}"><i class="fas fa-cog"></i> システム管理者はこちら</a>
        </p>
    </div>
</body>
</html>
