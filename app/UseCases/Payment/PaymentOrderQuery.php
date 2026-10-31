<?php

namespace App\UseCases\Payment;

use App\Models\Order;
use App\Models\Scopes\StoreAccessScope;

/** 決済画面・決済確認で扱う注文の取得。 */
class PaymentOrderQuery
{
    /** 署名つき URL で開くカード決済画面の注文。ブラウザのログインとは独立して店舗の閲覧範囲を外して探す */
    public function forCardPayment(int $projectId, int $orderId): Order
    {
        return Order::withoutGlobalScope(StoreAccessScope::class)
            ->where('project_id', $projectId)->findOrFail($orderId);
    }

    /** 本人の注文だけを返す */
    public function forUser(int $projectId, int $userId, int $orderId): ?Order
    {
        return Order::where('project_id', $projectId)->where('user_id', $userId)->find($orderId);
    }
}
