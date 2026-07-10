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
@include('admin.settings._context', ['pageKey' => 'smaregi'])

@if(session('success'))
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> {{ session('error') }}</div>
@endif

<div class="mb-4 d-flex flex-wrap gap-2 align-items-center">
    <form action="{{ admin_route('admin.settings.smaregi.sync-products') }}" method="POST" class="d-inline" onsubmit="return confirm('スマレジに登録されている商品をこのプロジェクトの商品一覧に同期します。既存の同名商品は更新されます。よろしいですか？');">
        @csrf
        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fas fa-sync-alt"></i> スマレジから商品を同期</button>
    </form>
    <form action="{{ admin_route('admin.settings.smaregi.full-sync-products') }}" method="POST" class="d-inline" onsubmit="return confirm('DBとスマレジの商品を完全同期します。\n・スマレジにあってDBにない商品 → DBに登録\n・DBにあってスマレジにない商品 → スマレジに登録\nデータ漏れがないように両方を揃えます。よろしいですか？');">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-exchange-alt"></i> 完全同期（双方向）</button>
    </form>
    <a href="{{ admin_route('admin.coupons.bulk-issue') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-ticket-alt"></i> クーポン同期・作成</a>
</div>

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
