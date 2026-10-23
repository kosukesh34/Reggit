<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>領収書 {{ $order->formattedOrderNumber() }} | {{ $projectName }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #111827; background: #f3f4f6; line-height: 1.6; }
        .sheet { max-width: 720px; margin: 24px auto; padding: 40px 32px; background: #fff; }
        h1 { margin: 0 0 24px; font-size: 26px; letter-spacing: .4em; text-align: center; }
        .meta { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; font-size: 13px; }
        .addressee { margin: 24px 0 8px; font-size: 20px; border-bottom: 1px solid #111827; display: inline-block; min-width: 60%; }
        .amount { margin: 16px 0; padding: 12px; font-size: 28px; font-weight: 700; text-align: center; border: 2px solid #111827; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 13px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        td.num, th.num { text-align: right; }
        .issuer { margin-top: 24px; font-size: 13px; text-align: right; white-space: pre-line; }
        .refunded { padding: 12px; color: #991b1b; background: #fee2e2; text-align: center; font-weight: 700; }
        .actions { text-align: center; margin: 16px; }
        @media print { body { background: #fff; } .sheet { margin: 0; padding: 0; } .actions { display: none; } }
    </style>
</head>
<body>
<div class="actions"><button type="button" onclick="window.print()">印刷・PDFで保存</button></div>
<main class="sheet">
    @if($order->refunded_at)
        <p class="refunded">この注文は返金済みのため、領収書は無効です。</p>
    @endif
    <h1>領収書</h1>
    <div class="meta">
        <span>注文番号：{{ $order->formattedOrderNumber() }}</span>
        <span>発行日：{{ now()->format('Y年n月j日') }}</span>
    </div>
    <p class="addressee">{{ $order->shipping_name }} 様</p>
    <p class="amount">¥{{ number_format((int) $order->total_amount) }}-（税込）</p>
    <p>但し、お品代として（ご注文日 {{ $order->created_at->format('Y年n月j日') }}）。上記正に領収いたしました。</p>

    <table>
        <thead><tr><th>品名</th><th class="num">数量</th><th class="num">税率</th><th class="num">金額（税込）</th></tr></thead>
        <tbody>
        @foreach($order->items as $item)
            <tr><td>{{ $item->product_name }}{{ (int) $item->tax_rate === 8 ? ' ※' : '' }}</td><td class="num">{{ $item->quantity }}</td><td class="num">{{ (int) $item->tax_rate }}%</td><td class="num">¥{{ number_format((int) $item->price * (int) $item->quantity) }}</td></tr>
        @endforeach
        @if((int) $order->shipping_fee > 0)
            <tr><td>送料</td><td class="num">1</td><td class="num">10%</td><td class="num">¥{{ number_format((int) $order->shipping_fee) }}</td></tr>
        @endif
        @if((int) $order->points_used > 0)
            <tr><td>ポイント値引き</td><td></td><td></td><td class="num">-¥{{ number_format((int) $order->points_used) }}</td></tr>
        @endif
        </tbody>
    </table>
    <table>
        <thead><tr><th>税率区分</th><th class="num">対象額（税込）</th><th class="num">うち消費税</th></tr></thead>
        <tbody>
        @foreach($taxes as $tax)
            <tr><td>{{ $tax['rate'] }}%対象{{ $tax['rate'] === 8 ? '（※軽減税率対象）' : '' }}</td><td class="num">¥{{ number_format($tax['amount']) }}</td><td class="num">¥{{ number_format($tax['tax']) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <p class="issuer">{{ $legal['seller'] !== '' ? $legal['seller'] : $projectName }}
@if($legal['address'] !== ''){{ $legal['address'] }}
@endif
@if($legal['phone'] !== '')TEL {{ $legal['phone'] }}
@endif
@if($registrationNumber !== '')登録番号 {{ $registrationNumber }}@endif</p>
</main>
</body>
</html>
