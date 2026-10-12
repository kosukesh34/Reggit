@php
    $title = 'システム管理画面ログイン';
    $heading = 'システム管理画面';
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Reggit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
</head>
<body class="admin-login-page admin-login-page--system">
    <div class="admin-login-box">
        <nav class="web-page-navigation" aria-label="ページの移動"><x-back-link :href="url('/')" label="Reggit トップへ戻る" /></nav>
        <h1><i class="fas fa-cog"></i> {{ $heading }}</h1>
        <p class="admin-login-lead">システム管理者のIDとパスワードでログインしてください。<br>全プロジェクトにアクセスできます。</p>

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ $submitRoute }}" class="admin-login-form">
            @csrf
            <div class="form-group">
                <label for="login_id">ID</label>
                <input type="text" name="login_id" id="login_id" value="{{ old('login_id') }}" required autofocus
                       class="form-control" autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" name="password" id="password" required
                       class="form-control" autocomplete="current-password">
            </div>
            <div class="form-group form-check">
                <input type="checkbox" name="remember" id="remember" class="form-check-input" value="1" {{ ((bool) old('remember', true)) ? 'checked' : '' }}>
                <label for="remember" class="form-check-label">ログイン状態を保持する</label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">ログイン</button>
        </form>
        <p><a href="{{ route('admin.password.request') }}">パスワードを忘れた場合</a></p>
        <p class="admin-login-switch">
            <a href="{{ route('admin.login') }}"><i class="fas fa-store"></i> 店舗管理ログイン（プロジェクト選択）</a>
        </p>
    </div>
</body>
</html>
