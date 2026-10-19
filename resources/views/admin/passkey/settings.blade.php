@extends('admin.layout')

@section('title', 'ログイン設定')
@section('page-title', 'ログイン設定')

@section('content')
<div data-admin-passkey-settings>
    <div class="page-header">
        <h1 class="page-header-title"><i class="fas fa-shield-alt"></i> ログイン設定</h1>
    </div>

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

    @php
        $currentLoginIp = (string) request()->ip();
        $loginAllowedIps = old('login_allowed_ips');
        if (! is_array($loginAllowedIps)) {
            $loginAllowedIps = \App\Support\AdminLoginIpAllowlist::parse(auth('admin')->user()->login_allowed_ips);
        }
    @endphp
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-shield-alt"></i> ログインセキュリティ</h2>
        </div>
        <div class="card-body">
            <p class="form-section-desc">ログインできるIPと、ログイン成功時のメール通知を設定します。</p>
            <form method="POST" action="{{ route('admin.login-security.update') }}" class="admin-login-settings-form" data-login-security-form data-unsaved-warn>
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">許可するIPアドレス</label>
                    <div class="admin-login-current-ip" data-login-current-ip="{{ $currentLoginIp }}" data-login-current-ip-url="{{ route('admin.login-security.current-ip') }}">
                        <span class="form-text mb-0">現在の接続IP: <code data-login-current-ip-value>{{ $currentLoginIp !== '' ? $currentLoginIp : '取得できませんでした' }}</code></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-login-ip-refresh title="サーバーから現在のIPを再取得">
                            <i class="fas fa-sync-alt"></i> IPを調べる
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-login-ip-copy {{ $currentLoginIp === '' ? 'disabled' : '' }}>
                            コピー
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-login-ip-add-current {{ $currentLoginIp === '' ? 'disabled' : '' }}>
                            <i class="fas fa-plus"></i> このIPを追加
                        </button>
                    </div>
                    <div id="loginAllowedIpsContainer" class="admin-login-ip-list" data-login-ip-list>
                        @forelse($loginAllowedIps as $ip)
                            <div class="admin-login-ip-item">
                                <input type="text" name="login_allowed_ips[]" class="form-control" value="{{ $ip }}" placeholder="例: 203.0.113.10" autocomplete="off">
                                <button type="button" class="btn btn-sm btn-danger" data-login-ip-remove aria-label="削除"><i class="fas fa-times"></i></button>
                            </div>
                        @empty
                        @endforelse
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" data-login-ip-add><i class="fas fa-plus"></i> IPを追加</button>
                    <span class="form-text">未登録のときはどこからでもログインできます。1件以上登録すると、登録したIPからのみログインできます。範囲指定（例: 203.0.113.0/24）も入力可能です。</span>
                </div>
                <div class="form-group form-check">
                    <input type="hidden" name="login_notify_by_email" value="0">
                    <input type="checkbox" class="form-check-input" id="login_notify_by_email" name="login_notify_by_email" value="1"
                           {{ old('login_notify_by_email', auth('admin')->user()->login_notify_by_email) ? 'checked' : '' }}>
                    <label for="login_notify_by_email" class="form-check-label">ログイン時に登録メールへ通知する</label>
                    <span class="form-text d-block">初期状態はオフです。オンにすると、ログイン成功時に登録メールへ通知します。</span>
                </div>
                <button class="btn btn-primary" type="submit">セキュリティ設定を保存</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-lock"></i> パスワード変更</h2>
        </div>
        <div class="card-body">
            <p class="form-section-desc">管理画面ログイン用のパスワードを変更します。</p>
            <form method="POST" action="{{ route('admin.password.change') }}" class="admin-login-settings-form" data-unsaved-warn>
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="current_password" class="form-label">現在のパスワード</label>
                    <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">新しいパスワード（8文字以上）</label>
                    <input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                </div>
                <div class="form-group">
                    <label for="password_confirmation" class="form-label">新しいパスワード確認</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                </div>
                <button class="btn btn-primary" type="submit">パスワードを変更</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-fingerprint"></i> 登録済みのパスキー</h2>
        </div>
        <div class="card-body">
            <p class="form-section-desc">ログイン時はパスワードのあとにパスキー確認が必要です。利用する端末ごとに登録できます。</p>

            @if($passkeys->isEmpty())
                <p class="form-section-desc">登録済みのパスキーはありません。</p>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>端末名</th>
                                <th>登録日</th>
                                <th>最終利用</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($passkeys as $passkey)
                                <tr>
                                    <td>
                                        {{ $passkey->name }}
                                        @if($passkey->authenticator)
                                            <span class="form-text">({{ $passkey->authenticator }})</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($passkey->created_at)->format('Y-m-d H:i') }}</td>
                                    <td>{{ optional($passkey->last_used_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('passkey.destroy', $passkey) }}" onsubmit="return confirm('「{{ $passkey->name }}」を削除しますか？');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">削除</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-plus"></i> パスキーを追加</h2>
        </div>
        <div class="card-body">
            <p class="form-section-desc">追加したい端末で開くリンクを発行します（有効期限30分）。その端末でIDとパスワードでログインすると、パスキー登録画面が開きます。既存のパスキーは消えません。</p>
            <form method="POST" action="{{ route('admin.passkey.remote-enroll.create') }}">
                @csrf
                <button type="submit" class="btn btn-primary">追加用リンクを発行</button>
            </form>
            @if(session('remote_enroll_url'))
                <div class="admin-passkey-remote" data-passkey-remote-enroll>
                    <div class="form-group mt-2">
                        <label class="form-label" for="remote-enroll-url">追加用リンク</label>
                        <input type="text" id="remote-enroll-url" class="form-control" readonly
                               value="{{ session('remote_enroll_url') }}" data-remote-enroll-url>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-remote-enroll-copy>リンクをコピー</button>
                    <div class="admin-passkey-remote-qr" data-remote-enroll-qr aria-label="パスキー追加QRコード"></div>
                    <p class="form-text">QRコードを追加したい端末で読み取るか、リンクを開いてください。</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/admin/passkeys.ts'])
@endpush
