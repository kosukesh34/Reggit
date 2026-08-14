@extends('admin.layout')
@section('title', 'Wallet 画像設定')
@section('page-title', 'Wallet 画像設定')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.index') }}">設定</a>
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
            @include('admin.partials._frontend_logo_dropzone', [
                'logoFieldId' => 'wallet_apple_icon',
                'logoInputName' => 'wallet_apple_icon',
                'logoClearName' => 'wallet_apple_icon_clear',
                'logoLabel' => 'アイコン画像',
                'logoCurrentUrl' => (string) ($view->appleIconUrl ?? ''),
                'logoAccept' => 'image/png,image/jpeg,image/jpg',
                'logoHint' => 'PNG / JPG 2MBまで（推奨: 正方形 87×87px 以上）',
                'logoHelp' => '29/58/87px に自動リサイズされます。',
            ])
            @include('admin.partials._frontend_logo_dropzone', [
                'logoFieldId' => 'wallet_apple_logo',
                'logoInputName' => 'wallet_apple_logo',
                'logoClearName' => 'wallet_apple_logo_clear',
                'logoLabel' => 'ロゴ画像',
                'logoCurrentUrl' => (string) ($view->appleLogoUrl ?? ''),
                'logoAccept' => 'image/png,image/jpeg,image/jpg',
                'logoHint' => 'PNG / JPG 2MBまで（推奨: 横長 320×100px 程度）',
                'logoHelp' => '160×50 / 320×100 に自動リサイズされます。',
            ])
        </div>
    </div>

    {{-- Google Wallet --}}
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fab fa-google"></i> Google Wallet</h3>
        </div>
        <div class="card-body">
            <p class="form-text text-muted text-muted-mb-16">会員証を Google Wallet に追加したときに表示される画像です。アップロードすると公開URLが自動で設定されます。</p>
            @include('admin.partials._frontend_logo_dropzone', [
                'logoFieldId' => 'wallet_google_program_logo_file',
                'logoInputName' => 'wallet_google_program_logo_file',
                'logoClearName' => 'wallet_google_program_logo_clear',
                'logoLabel' => 'プログラムロゴ',
                'logoCurrentUrl' => (string) ($view->googleProgramLogoUrl ?? ''),
                'logoAccept' => 'image/png,image/jpeg,image/jpg',
                'logoHint' => 'PNG / JPG 2MBまで',
                'logoHelp' => 'カード上部に表示されるロゴです。',
            ])
            @include('admin.partials._frontend_logo_dropzone', [
                'logoFieldId' => 'wallet_google_hero_file',
                'logoInputName' => 'wallet_google_hero_file',
                'logoClearName' => 'wallet_google_hero_clear',
                'logoLabel' => 'ヒーロー画像',
                'logoCurrentUrl' => (string) ($view->googleHeroImageUrl ?? ''),
                'logoAccept' => 'image/png,image/jpeg,image/jpg',
                'logoHint' => 'PNG / JPG 2MBまで',
                'logoHelp' => 'カード中央に表示される画像です。',
            ])
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
        <a href="{{ admin_route('admin.settings.index') }}" class="btn btn-secondary">設定に戻る</a>
    </div>
</form>
@endsection
