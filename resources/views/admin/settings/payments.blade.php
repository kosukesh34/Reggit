@extends('admin.layout')
@section('title', '決済設定')
@section('page-title', '決済設定')
@section('content')
<div class="card"><div class="card-body">
    <h2>このプロジェクトのお支払い方法</h2>
    <p>このプロジェクトのStripeアカウントへ売上が入金されます。各決済方法はStripe側でも利用申請・有効化してください。</p>
    @if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ admin_route('admin.settings.payments.update') }}" autocomplete="off">
        @csrf
        <p>接続アカウント：{{ $accountId ?? '未登録' }}</p>
        <div class="form-group"><label for="publishable_key">Stripe公開キー</label><input class="form-control" id="publishable_key" name="publishable_key" value="{{ old('publishable_key', $publishableKey) }}" required></div>
        <div class="form-group"><label for="secret_key">Stripe秘密キー {{ $hasSecret ? '（登録済み・空欄で維持）' : '' }}</label><input class="form-control" id="secret_key" name="secret_key" type="password" autocomplete="new-password" @required(!$hasSecret)></div>
        <div class="form-group"><label for="webhook_secret">Webhook署名キー {{ $hasSecret ? '（登録済み・空欄で維持）' : '' }}</label><input class="form-control" id="webhook_secret" name="webhook_secret" type="password" autocomplete="new-password" @required(!$hasSecret)></div>
        <fieldset><legend>お客様に表示するブランド</legend>
            @foreach(['card' => 'クレジットカード', 'paypay' => 'PayPay', 'apple_pay' => 'Apple Pay', 'google_pay' => 'Google Pay', 'cash' => '店頭で支払う'] as $value => $label)
                <p><label><input type="checkbox" name="methods[]" value="{{ $value }}" @checked(in_array($value, old('methods', $methods), true))> {{ $label }}</label></p>
            @endforeach
        </fieldset>
        <p>Apple PayはiOS・macOS、Google PayはAndroid・その他のOSに表示します。実際の利用可否はStripe決済画面で端末・ブラウザーとウォレットの設定に応じて判定されます。</p>
        <button class="btn btn-primary" type="submit">保存する</button>
    </form>
    @if($webhookUrl)<h3>Stripeに登録するWebhook</h3><p><code>{{ $webhookUrl }}</code></p><p>イベント：payment_intent.succeeded（サブスクを販売する場合は invoice.paid・customer.subscription.updated・customer.subscription.deleted も）。設定更新でURLが変わります。進行中の注文がある間は旧URLも維持してください。</p>@endif
    <h3>ブランド・手数料の目安</h3>
    <table class="table"><thead><tr><th>ブランド</th><th>公開標準手数料</th><th>導入状況</th></tr></thead><tbody>
        <tr><td>カード / Apple Pay / Google Pay</td><td>3.6%</td><td>対応</td></tr>
        <tr><td>PayPay</td><td>3.98%</td><td>対応・Stripe側の有効化が必要</td></tr>
        <tr><td>d払い / au PAY / 楽天ペイ / メルペイ / AEON Pay</td><td>Stripeに要確認</td><td>先行提供。利用申請・承認後に連携を追加</td></tr>
    </tbody></table>
    <p>2026年10月7日確認。PayPayのデジタルコンテンツ事業向けは9.48%。実際の料金は各アカウントの契約に従います。</p>
    <p><a href="https://stripe.com/jp/pricing" target="_blank" rel="noopener">Stripe公式料金</a> / <a href="https://stripe.com/jp/newsroom/news/stripe-marks-10-years-in-japan-with-new-products-to-accelerate-the-growth-of-digital-businesses" target="_blank" rel="noopener">先行提供ブランドの案内</a></p>
    <p>秘密情報はサーバーで暗号化して保存します。登録済みの秘密キーは表示されません。</p>
</div></div>
@endsection
