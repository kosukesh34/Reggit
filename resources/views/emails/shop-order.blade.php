@php
    $intro = [
        'confirmed' => 'ご注文を承りました。発送の準備ができ次第、あらためてご連絡します。',
        'shipped' => 'ご注文の商品を発送しました。到着まで今しばらくお待ちください。',
        'refunded' => 'ご注文をキャンセルし、お支払い金額を返金しました。カード会社の処理により、ご利用明細への反映まで日数がかかる場合があります。',
    ][$event] ?? '';
    $itemsTotal = $order->items->sum(fn ($item) => (int) $item->price * (int) $item->quantity);
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>{{ \App\Mail\ShopOrderMail::SUBJECTS[$event] ?? 'ご注文について' }}</title>
</head>
<body style="font-family: sans-serif; color: #1f2937; line-height: 1.7;">
    <p>{{ $order->shipping_name }} 様</p>
    <p>{{ $shopName }}をご利用いただきありがとうございます。<br>{{ $intro }}</p>

    <h3>ご注文内容</h3>
    <p>注文番号：{{ $order->formattedOrderNumber() }}<br>ご注文日時：{{ $order->created_at->format('Y/m/d H:i') }}</p>
    <table cellpadding="4" style="border-collapse: collapse;">
        @foreach($order->items as $item)
            <tr><td>{{ $item->product_name }} × {{ $item->quantity }}</td><td align="right">¥{{ number_format((int) $item->price * (int) $item->quantity) }}</td></tr>
        @endforeach
        <tr><td>商品合計</td><td align="right">¥{{ number_format($itemsTotal) }}</td></tr>
        <tr><td>送料</td><td align="right">¥{{ number_format((int) $order->shipping_fee) }}</td></tr>
        @if((int) $order->points_used > 0)
            <tr><td>ポイント利用</td><td align="right">-¥{{ number_format((int) $order->points_used) }}</td></tr>
        @endif
        <tr><td><strong>お支払い金額（税込）</strong></td><td align="right"><strong>¥{{ number_format((int) $order->total_amount) }}</strong></td></tr>
        @if($event === 'refunded')
            <tr><td><strong>返金額</strong></td><td align="right"><strong>¥{{ number_format((int) $order->refund_amount) }}</strong></td></tr>
        @endif
    </table>

    <h3>お届け先</h3>
    <p>〒{{ $order->shipping_postal_code }} {{ $order->shipping_prefecture }}{{ $order->shipping_address }}<br>{{ $order->shipping_name }} 様（{{ $order->shipping_phone }}）</p>
    @if($order->delivery_date || $order->delivery_time_slot)
        <p>お届け希望：{{ $order->delivery_date?->format('Y/m/d') ?? '日付指定なし' }} {{ $order->delivery_time_slot }}</p>
    @endif

    @if($event === 'shipped' && $order->shipping_tracking_number)
        <h3>配送状況</h3>
        <p>配送業者：{{ \App\Services\Shop\ShopOperations::carrierLabel($order->shipping_carrier) ?: '—' }}<br>
        お問い合わせ番号：{{ $order->shipping_tracking_number }}
        @if($trackingUrl)<br><a href="{{ $trackingUrl }}">配送状況を確認する</a>@endif</p>
    @endif

    @if($event !== 'refunded')
        <p><a href="{{ $receiptUrl }}">領収書を表示する</a></p>
    @endif

    <hr>
    <p style="font-size: 12px; color: #6b7280;">
        {{ $legal['seller'] ?? $shopName }}<br>
        @if(($legal['address'] ?? '') !== ''){{ $legal['address'] }}<br>@endif
        @if(($legal['phone'] ?? '') !== '')電話：{{ $legal['phone'] }}<br>@endif
        @if(($legal['email'] ?? '') !== '')メール：{{ $legal['email'] }}<br>@endif
        このメールは送信専用です。お問い合わせは上記の連絡先へお願いします。
    </p>
</body>
</html>
