@extends('admin.layout')

@section('title', 'LINE接続・通知API')
@section('page-title', 'LINE接続・通知API')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.index') }}">店舗設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>LINE接続・通知API</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fab fa-line"></i> LINE接続・通知API</h1>
</div>


<div class="card mb-4">
    <div class="card-header"><i class="fas fa-link"></i> このプロジェクトの LIFF エントリURL</div>
    <div class="card-body">
        <p class="form-text text-muted mb-2">LINE Developers の Endpoint URL などに設定する参考URLです。</p>
        <code class="smaregi-url">{{ $view->liffEntryUrl }}</code>
    </div>
</div>

<form action="{{ admin_route('admin.settings.liff-connection.update') }}" method="POST">
    @csrf
    <div class="card mb-4">
        <div class="card-header"><i class="fab fa-line"></i> LIFF接続情報</div>
        <div class="card-body">
            <div class="form-group">
                <label for="line_liff_id" class="form-label">LIFF ID <span class="text-danger">*</span></label>
                <input type="text" name="line_liff_id" id="line_liff_id" class="form-control" value="{{ old('line_liff_id', $view->lineLiffId) }}" required maxlength="128" placeholder="例: 2008962885-44xGRSQL">
                @error('line_liff_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="line_liff_url" class="form-label">LIFF URL <span class="text-danger">*</span></label>
                <input type="url" name="line_liff_url" id="line_liff_url" class="form-control" value="{{ old('line_liff_url', $view->lineLiffUrl) }}" required maxlength="2048" placeholder="例: https://miniapp.line.me/2008962885-44xGRSQL">
                @error('line_liff_url')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="line_friend_add_url" class="form-label">LINE友だち追加URL</label>
                <input type="url" name="line_friend_add_url" id="line_friend_add_url" class="form-control" value="{{ old('line_friend_add_url', $view->lineFriendAddUrl) }}" maxlength="2048" placeholder="例: https://line.me/R/ti/p/@xxxx">
                <p class="form-text text-muted">会員管理の招待QRに優先表示されます。未設定時は LIFF URL を使います。</p>
                @error('line_friend_add_url')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fas fa-key"></i> Messaging API</div>
        <div class="card-body">
            <p class="form-text text-muted mb-3">お知らせ配信・プッシュ通知などで利用します。値はプロジェクト単位で暗号化保存されます。</p>
            <div class="form-group">
                <label for="line_channel_access_token" class="form-label">チャネルアクセストークン @if(!$view->hasChannelAccessToken)<span class="text-danger">*</span>@endif</label>
                <input type="password" name="line_channel_access_token" id="line_channel_access_token" class="form-control" value="{{ old('line_channel_access_token') }}" maxlength="1024" autocomplete="new-password" @if(!$view->hasChannelAccessToken) required @endif placeholder="{{ $view->hasChannelAccessToken ? '変更する場合のみ入力' : 'Messaging API のチャネルアクセストークン' }}">
                @if($view->hasChannelAccessToken)
                    <p class="form-text text-muted">登録済みです。変更する場合のみ再入力してください。</p>
                @endif
                @error('line_channel_access_token')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="line_channel_secret" class="form-label">チャネルシークレット</label>
                <input type="password" name="line_channel_secret" id="line_channel_secret" class="form-control" value="{{ old('line_channel_secret') }}" maxlength="255" autocomplete="new-password" placeholder="{{ $view->hasChannelSecret ? '変更する場合のみ入力' : 'Messaging API のチャネルシークレット（任意）' }}">
                @if($view->hasChannelSecret)
                    <p class="form-text text-muted">登録済みです。変更する場合のみ再入力してください。</p>
                @endif
                @error('line_channel_secret')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
            </div>
        </div>
    </div>
</form>
@endsection
