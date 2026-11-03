@extends('admin.layout')
@section('title', '決済設定')
@section('page-title', '決済設定')
@section('content')
<div class="page-header">
    <h1 class="page-header-title">決済設定</h1>
    <p class="page-header-desc">このプロジェクトのStripeアカウントへ売上が入金されます。各決済方法はStripe側でも利用申請・有効化してください。</p>
</div>
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form method="POST" action="{{ admin_route('admin.settings.payments.update') }}" autocomplete="off">
    @csrf
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-credit-card"></i> このプロジェクトのお支払い方法</h3></div>
        <div class="card-body">
            <div class="form-group">
                <span class="form-label">接続アカウント</span>
                <p class="mb-0">{{ $accountId ?? '未登録' }}</p>
            </div>
            <div class="form-group">
                <label for="publishable_key" class="form-label">Stripe公開キー</label>
                <input class="form-control" id="publishable_key" name="publishable_key" value="{{ old('publishable_key', $publishableKey) }}" required>
            </div>
            <div class="form-group">
                <label for="secret_key" class="form-label">Stripe秘密キー {{ $hasSecret ? '（登録済み・空欄で維持）' : '' }}</label>
                <input class="form-control" id="secret_key" name="secret_key" type="password" autocomplete="new-password" @required(!$hasSecret)>
            </div>
            <div class="form-group">
                <label for="webhook_secret" class="form-label">Webhook署名キー {{ $hasSecret ? '（登録済み・空欄で維持）' : '' }}</label>
                <input class="form-control" id="webhook_secret" name="webhook_secret" type="password" autocomplete="new-password" @required(!$hasSecret)>
                <small class="form-text text-muted">秘密情報はサーバーで暗号化して保存します。登録済みの秘密キーは表示されません。</small>
            </div>
            <div class="form-group" role="group" aria-labelledby="payment_methods_label">
                <span id="payment_methods_label" class="form-label">お客様に表示するブランド</span>
                @foreach(['card' => 'クレジットカード', 'paypay' => 'PayPay', 'apple_pay' => 'Apple Pay', 'google_pay' => 'Google Pay', 'cash' => '店頭で支払う'] as $value => $label)
                    <label class="checkbox-label checkbox-block"><input type="checkbox" name="methods[]" value="{{ $value }}" @checked(in_array($value, old('methods', $methods), true))> {{ $label }}</label>
                @endforeach
                <small class="form-text text-muted">Apple PayはiOS・macOS、Google PayはAndroid・その他のOSに表示します。実際の利用可否はStripe決済画面で端末・ブラウザーとウォレットの設定に応じて判定されます。</small>
            </div>
            <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> 保存する</button>
        </div>
    </div>
</form>
@if($webhookUrl)
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-link"></i> Stripeに登録するWebhook</h3></div>
        <div class="card-body">
            <p><code>{{ $webhookUrl }}</code></p>
            <p class="form-text">イベント：payment_intent.succeeded（サブスクを販売する場合は invoice.paid・customer.subscription.updated・customer.subscription.deleted も）。設定更新でURLが変わります。進行中の注文がある間は旧URLも維持してください。</p>
        </div>
    </div>
@endif
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-percent"></i> ブランド・手数料の目安</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table"><thead><tr><th>ブランド</th><th>公開標準手数料</th><th>導入状況</th></tr></thead><tbody>
                <tr><td>カード / Apple Pay / Google Pay</td><td>3.6%</td><td>対応</td></tr>
                <tr><td>PayPay</td><td>3.98%</td><td>対応・Stripe側の有効化が必要</td></tr>
                <tr><td>d払い / au PAY / 楽天ペイ / メルペイ / AEON Pay</td><td>Stripeに要確認</td><td>先行提供。利用申請・承認後に連携を追加</td></tr>
            </tbody></table>
        </div>
        <p class="form-text">2026年10月7日確認。PayPayのデジタルコンテンツ事業向けは9.48%。実際の料金は各アカウントの契約に従います。</p>
        <p class="form-text"><a href="https://stripe.com/jp/pricing" target="_blank" rel="noopener">Stripe公式料金</a> / <a href="https://stripe.com/jp/newsroom/news/stripe-marks-10-years-in-japan-with-new-products-to-accelerate-the-growth-of-digital-businesses" target="_blank" rel="noopener">先行提供ブランドの案内</a></p>
    </div>
</div>
@endsection
