@extends('admin.layout')

@section('title', 'スマレジ連携')
@section('page-title', 'スマレジ連携')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>スマレジ連携</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-plug"></i> スマレジ・プラットフォームAPI 連携</h1>
    <p class="page-header-desc">スマレジのPOS・会員データと連携するための設定です。スマレジ・デベロッパーズでアプリを登録し、以下のURLを設定してください。</p>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif

<div class="card mb-4">
    <div class="card-header"><i class="fas fa-link"></i> スマレジ・デベロッパーズで設定するURL</div>
    <div class="card-body">
        <p class="text-muted small">現在のアプリのベースURL: <code>{{ $view->appUrl }}</code></p>
        <dl class="smaregi-url-list">
            <dt>利用者契約通知先URL（必須）</dt>
            <dd><code class="smaregi-url">{{ $view->idpUrl }}</code></dd>
            <dt>Webhook送信先エンドポイント</dt>
            <dd><code class="smaregi-url">{{ $view->webhookUrl }}</code></dd>
        </dl>
        <p class="form-text text-muted">契約開始・プラン変更時にスマレジから通知が送られ、契約IDが保存されます。Webhookはイベント通知用です（任意）。</p>
    </div>
</div>

<form action="{{ admin_route('admin.settings.smaregi.update') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-header"><i class="fas fa-store"></i> このプロジェクトに紐づける契約</div>
        <div class="card-body">
            <p class="form-section-desc">スマレジ・デベロッパーズの管理画面左上などで確認できる契約IDを入力してください。</p>
            <div class="form-group">
                <label class="form-label" for="smaregi_contract_id">契約ID</label>
                <input type="text" name="smaregi_contract_id" id="smaregi_contract_id" class="form-control" value="{{ old('smaregi_contract_id', $view->linkedContractId) }}" placeholder="例: sb_xxxxxxxx">
            </div>
            <div class="form-actions mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
            </div>
        </div>
    </div>
</form>
@endsection
