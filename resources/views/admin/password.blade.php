<!DOCTYPE html>
<html lang="ja">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>パスワード設定 - Reggit</title><link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}"></head>
<body class="admin-login-page"><main class="admin-login-box">
<nav class="web-page-navigation" aria-label="ページの移動"><x-back-link :href="$mode === 'change' ? route('admin.root') : route('admin.login')" :label="$mode === 'change' ? '管理画面へ戻る' : 'ログイン画面へ戻る'" /></nav>
<h1>{{ $mode === 'change' ? 'パスワード変更' : 'パスワード再設定' }}</h1>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form method="POST" action="{{ route($mode === 'forgot' ? 'admin.password.email' : ($mode === 'reset' ? 'admin.password.update' : 'admin.password.change')) }}">
@csrf
@if($mode === 'change')
@method('PUT')
<div class="form-group"><label for="current_password">現在のパスワード</label><input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
@else
<div class="form-group"><label for="email">登録メールアドレス</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email', $email ?? '') }}" autocomplete="email" required></div>
@endif
@if($mode === 'reset')<input type="hidden" name="token" value="{{ $token }}">@endif
@if($mode !== 'forgot')
<div class="form-group"><label for="password">新しいパスワード（8文字以上）</label><input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required></div>
<div class="form-group"><label for="password_confirmation">新しいパスワード確認</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></div>
@endif
<button class="btn btn-primary" type="submit">{{ $mode === 'forgot' ? '再設定リンクを送信' : 'パスワードを変更' }}</button>
</form>
<p></p>
</main></body></html>
