@extends('admin.passkey.layout')

@section('title', 'パスキー登録')
@section('heading', '管理画面')
@section('lead', '続けてパスキーを登録してください。管理画面ではパスワードに加えてパスキーが必要です。')

@section('root-attrs')
data-admin-passkey-enroll
data-register-options="{{ route('passkey.registration-options') }}"
data-register-submit="{{ route('passkey.store') }}"
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
        <div class="form-group">
            <label for="passkey-name">端末名</label>
            <input type="text" id="passkey-name" class="form-control" maxlength="100" required
                   placeholder="例: 会社のパソコン" autocomplete="off"
                   value="{{ old('passkey_name') }}">
            <span class="form-text">あとからログイン設定で変更・削除できます。</span>
        </div>
        <p class="alert alert-error" data-passkey-error hidden role="alert"></p>
        <p class="form-text" data-passkey-status hidden role="status"></p>
        <button type="button" class="btn btn-primary btn-block" data-passkey-register>パスキーを作成して続行</button>
    </div>
@endsection
