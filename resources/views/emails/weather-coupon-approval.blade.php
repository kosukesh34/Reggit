<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>雨の日の配信の承認のお願い</title>
</head>
<body>
    <p>{{ $storeName }} で雨の予報が出ました（営業時間内の最大降水確率 {{ $maxProbability }}%）。</p>
    <p>雨の日の配信「{{ $couponName }}」を送る場合は、{{ $expiresAt }} までに管理画面で承認してください。期限を過ぎると、この回は送りません。</p>
    <p>今日は送らない場合は、管理画面で「今日は配らない」を選ぶと、今日はこのメールが届かなくなります。</p>
    <p><a href="{{ $approvalUrl }}">{{ $approvalUrl }}</a></p>
    <p>このリンクから承認するには、管理画面へのログインが必要です。</p>
</body>
</html>
