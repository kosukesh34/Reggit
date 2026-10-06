<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="stripe-key" content="{{ $stripeKey }}">
    <meta name="referrer" content="no-referrer">
    <title>カードでお支払い</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f6f7f9; color: #17202a; font: 16px/1.6 system-ui, sans-serif; }
        main { max-width: 440px; margin: 48px auto; padding: 28px 24px; background: white; border-radius: 16px; }
        h1 { font-size: 22px; margin: 0 0 16px; }
        .amount { font-size: 32px; font-weight: 700; margin: 0 0 24px; }
        #card-element { border: 1px solid #b9c1cb; border-radius: 8px; padding: 16px 12px; }
        button { width: 100%; margin-top: 24px; padding: 14px; border: 0; border-radius: 8px; background: #17694c; color: white; font: inherit; font-weight: 700; cursor: pointer; }
        button:disabled { opacity: .5; cursor: wait; }
        #payment-error { color: #b42318; min-height: 26px; margin-top: 12px; }
        .cancel { display: block; text-align: center; margin-top: 16px; color: #4b5563; }
        @media (max-width: 480px) { main { margin: 24px 16px; } }
    </style>
    @vite('resources/js/payment/card.ts')
</head>
<body>
<main>
    <h1>カードでお支払い</h1>
    <p class="amount">¥{{ number_format($amount) }}</p>
    <form id="card-payment-form" data-client-secret="{{ $clientSecret }}" data-success-url="{{ $successUrl }}">
        <div id="card-element" aria-label="カード情報"></div>
        <div id="payment-error" role="alert" aria-live="polite"></div>
        <button id="pay-button" type="submit" disabled>¥{{ number_format($amount) }} を支払う</button>
    </form>
    <a class="cancel" href="{{ $cancelUrl }}">アプリに戻る</a>
</main>
</body>
</html>
