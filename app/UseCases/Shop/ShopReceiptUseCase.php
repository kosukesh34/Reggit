<?php

namespace App\UseCases\Shop;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;

class ShopReceiptUseCase
{
    /**
     * 決済済みの配送注文と、税率ごとの対象額（税込）・消費税額。
     *
     * @return array{order: Order, taxes: list<array{rate: int, amount: int, tax: int}>}
     */
    public function handle(int $projectId, int $orderId): array
    {
        $receipt = Order::withoutGlobalScopes()->with('items')->where('project_id', $projectId)
            ->where('order_type', OrderType::SHIPPING)->findOrFail($orderId);
        abort_if($receipt->status === OrderStatus::PENDING, 404);

        // 税率ごとの対象額（税込）。送料は標準税率。
        $byRate = [];
        foreach ($receipt->items as $item) {
            $rate = (int) ($item->tax_rate ?? 10);
            $byRate[$rate] = ($byRate[$rate] ?? 0) + (int) $item->price * (int) $item->quantity;
        }
        if ((int) $receipt->shipping_fee > 0) {
            $byRate[10] = ($byRate[10] ?? 0) + (int) $receipt->shipping_fee;
        }
        krsort($byRate);
        // ponytail: ポイント値引きは税率別に按分せず値引き行で表示する。按分が必要になったら税率ごとに比例配分する。
        $taxes = array_map(fn (int $amount, int $rate): array => [
            'rate' => $rate,
            'amount' => $amount,
            'tax' => intdiv($amount * $rate, 100 + $rate),
        ], $byRate, array_keys($byRate));

        return ['order' => $receipt, 'taxes' => $taxes];
    }
}
