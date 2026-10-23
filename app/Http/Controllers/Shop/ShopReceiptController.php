<?php

namespace App\Http\Controllers\Shop;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Shop\ShopOperations;
use App\Util\AdminContext;
use Illuminate\Contracts\View\View;

/** 配送注文の領収書。署名つき URL（注文確認メール・管理画面）からのみ開ける。 */
class ShopReceiptController extends Controller
{
    public function __construct(private ShopOperations $operations) {}

    public function __invoke(string $project_key, int $order): View
    {
        $project = AdminContext::getCurrentProject();
        abort_unless($project !== null, 404);
        $receipt = Order::withoutGlobalScopes()->with('items')->where('project_id', $project->id)
            ->where('order_type', OrderType::SHIPPING)->findOrFail($order);
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

        return view('frontend.shop-receipt', [
            'order' => $receipt,
            'projectName' => (string) $project->name,
            'legal' => $this->operations->legalNotice((int) $project->id),
            'registrationNumber' => $this->operations->invoiceRegistrationNumber((int) $project->id),
            'taxes' => $taxes,
        ]);
    }
}
