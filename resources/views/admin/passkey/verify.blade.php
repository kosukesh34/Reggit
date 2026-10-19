@extends('admin.passkey.layout')

@section('title', 'パスキー確認')
@section('heading', '管理画面')
@section('lead', 'パスワードの確認が完了しました。続けてパスキーで本人確認してください。')

@section('root-attrs')
data-admin-passkey-verify
data-confirm-options="{{ route('passkey.confirm-options') }}"
data-confirm-submit="{{ route('passkey.confirm') }}"
@endsection

@section('content')
    <div class="admin-login-form">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif
        <p class="alert alert-error" data-passkey-error hidden role="alert"></p>
        <p class="form-text" data-passkey-status hidden role="status"></p>
        <button type="button" class="btn btn-primary btn-block" data-passkey-verify>パスキーで続行</button>
        <span class="form-text">確認画面が出ない場合は、もう一度ボタンを押してください。</span>
        <form method="POST" action="{{ route('admin.passkey.recovery.email') }}" class="admin-passkey-recovery"
              onsubmit="return confirm('登録メールアドレスに再登録用リンクを送信します。リンクを開くと、今までのパスキーは無効になります。よろしいですか？');">
            @csrf
            <button type="submit" class="admin-passkey-logout">パスキーが使えない場合（メールで再登録）</button>
        </form>
    </div>
@endsection
