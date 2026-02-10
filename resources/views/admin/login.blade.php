@php
    $title = '管理画面ログイン';
    $heading = '管理画面';
    // $submitRoute はコントローラから渡される
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Mobile Order</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
</head>
<body class="admin-login-page">
    <div class="admin-login-box">
        <h1><i class="fas fa-cog"></i> {{ $heading }}</h1>
        <p class="admin-login-lead">IDとパスワードでログインしてください</p>

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
                <input type="checkbox" name="remember" id="remember" class="form-check-input" value="1" {{ old('remember', true) ? 'checked' : '' }}>
                <label for="remember" class="form-check-label">ログイン状態を保持する（推奨）</label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">ログイン</button>
        </form>
        <p class="admin-login-switch">
            システム管理者・プロジェクト管理者・店舗管理者は同じ画面からログインできます。
        </p>
    </div>
</body>
</html>
