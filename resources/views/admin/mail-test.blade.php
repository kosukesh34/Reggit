@extends('admin.layout')

@section('title', 'メール送信テスト')
@section('page-title', 'メール送信テスト')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-envelope"></i> SMTP テスト送信</h3>
    </div>
    <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <dl class="admin-detail-list">
            <dt>SMTP ホスト</dt>
            <dd>{{ $mailHost !== '' ? $mailHost : '（未設定）' }}</dd>
            <dt>送信元</dt>
            <dd>{{ $fromName !== '' ? $fromName.' ' : '' }}&lt;{{ $fromAddress !== '' ? $fromAddress : '未設定' }}&gt;</dd>
        </dl>

        <div class="system-admin-welcome" style="margin-top: 16px;">
            <p class="system-admin-welcome-text">
                「送信しました」は Xserver の SMTP が受け付けた状態です。Gmail など外部アドレスに届かない場合は、サーバーパネルの <strong>DMARC設定</strong>（reggit.jp）を追加してください。同一ドメイン（reggit@reggit.jp）への送信は Webメールで受信できます。
            </p>
        </div>

        <form method="POST" action="{{ route('system-admin.mail-test.send') }}" class="admin-form">
            @csrf
            <div class="form-group">
                <label for="to" class="form-label">送信先メールアドレス</label>
                <input type="email" name="to" id="to" class="form-control" value="{{ old('to') }}" required placeholder="example@example.com">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> テスト送信</button>
        </form>
    </div>
</div>
@endsection
