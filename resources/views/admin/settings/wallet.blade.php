@extends('admin.layout')
@section('title', 'Wallet 画像設定')
@section('page-title', 'Wallet 画像設定')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.basic') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>Wallet 画像</span>
</nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-wallet"></i> Wallet 画像設定</h1>
    <p class="page-header-desc">iPhone（Apple Wallet）・Google Wallet の会員証カードに表示する画像を設定します。未設定の場合はデフォルトまたは .env の設定が使われます。</p>
</div>
@include('admin.settings._context', ['pageKey' => 'wallet'])
@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> 入力内容を確認してください。</div>@endif

<form action="{{ admin_route('admin.settings.wallet.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- Apple Wallet（iPhone） --}}
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fab fa-apple"></i> Apple Wallet（iPhone）</h3>
        </div>
        <div class="card-body">
            <p class="form-text text-muted text-muted-mb-16">会員証を Apple Wallet に追加したときに表示されるアイコン・ロゴです。アップロードすると既存設定を上書きします。</p>
            @if($appleAssetsSet)
                <p class="text-success small mb-2"><i class="fas fa-check"></i> 画像が設定されています</p>
            @endif
            <div class="form-group">
                <label for="wallet_apple_icon" class="form-label">アイコン画像</label>
                <input type="file" name="wallet_apple_icon" id="wallet_apple_icon" class="form-control" accept="image/png,image/jpeg,image/jpg">
                <small class="form-text text-muted">推奨: 正方形 87×87px 以上（PNG/JPEG、2MB以内）。29/58/87px に自動リサイズされます。</small>
            </div>
            <div class="form-group">
                <label for="wallet_apple_logo" class="form-label">ロゴ画像</label>
                <input type="file" name="wallet_apple_logo" id="wallet_apple_logo" class="form-control" accept="image/png,image/jpeg,image/jpg">
                <small class="form-text text-muted">推奨: 横長 320×100px 程度（PNG/JPEG、2MB以内）。160×50 / 320×100 に自動リサイズされます。</small>
            </div>
        </div>
    </div>

    {{-- Google Wallet --}}
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fab fa-google"></i> Google Wallet</h3>
        </div>
        <div class="card-body">
            <p class="form-text text-muted text-muted-mb-16">会員証を Google Wallet に追加したときに表示される画像です。URL またはファイルアップロードで設定できます。Google からアクセスできる公開URLが必要です。</p>
            <div class="form-group">
                <label for="wallet_google_program_logo_url" class="form-label">プログラムロゴ URL</label>
                <input type="url" name="wallet_google_program_logo_url" id="wallet_google_program_logo_url" class="form-control" value="{{ old('wallet_google_program_logo_url', $googleProgramLogoUrl) }}" maxlength="2048" placeholder="https://...">
                <small class="form-text text-muted">または下のファイルで上書き</small>
            </div>
            <div class="form-group">
                <label for="wallet_google_program_logo_file" class="form-label">プログラムロゴ（ファイルで設定）</label>
                <input type="file" name="wallet_google_program_logo_file" id="wallet_google_program_logo_file" class="form-control" accept="image/png,image/jpeg,image/jpg">
                <small class="form-text text-muted">アップロードすると公開URLが自動で設定されます（PNG/JPEG、2MB以内）。</small>
            </div>
            <div class="form-group">
                <label for="wallet_google_hero_image_url" class="form-label">ヒーロー画像 URL</label>
                <input type="url" name="wallet_google_hero_image_url" id="wallet_google_hero_image_url" class="form-control" value="{{ old('wallet_google_hero_image_url', $googleHeroImageUrl) }}" maxlength="2048" placeholder="https://...">
                <small class="form-text text-muted">または下のファイルで上書き</small>
            </div>
            <div class="form-group">
                <label for="wallet_google_hero_file" class="form-label">ヒーロー画像（ファイルで設定）</label>
                <input type="file" name="wallet_google_hero_file" id="wallet_google_hero_file" class="form-control" accept="image/png,image/jpeg,image/jpg">
                <small class="form-text text-muted">アップロードすると公開URLが自動で設定されます（PNG/JPEG、2MB以内）。</small>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
        <a href="{{ admin_route('admin.settings.basic') }}" class="btn btn-secondary">設定に戻る</a>
    </div>
</form>
@endsection
