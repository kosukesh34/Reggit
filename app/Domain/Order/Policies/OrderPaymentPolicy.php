<?php

namespace App\Domain\Order\Policies;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use Closure;

/** 注文の支払い方法・ポイント利用・支払額の制約。満たさなければ OrderOperationException。 */
final class OrderPaymentPolicy
{
    /**
     * @param  list<string>|null  $allowedBrands  プロジェクトの決済設定で有効な支払い方法（設定なしは null）
     * @param  Closure(): bool  $isDefaultProject  決済設定なしでオンライン決済するときだけ確認する
     * @param  Closure(): bool  $pointsEnabled  ポイントを使うときだけ確認する
     * @return array{brand: string, final_amount: float}
     */
    public function resolve(
        string $orderType,
        string $paymentMethod,
        mixed $requestedBrand,
        ?array $allowedBrands,
        Closure $isDefaultProject,
        float $totalAmount,
        int $pointsUsed,
        Closure $pointsEnabled,
    ): array {
        $brand = $paymentMethod === PaymentMethod::STRIPE ? ($requestedBrand ?? 'card') : 'cash';
        if (in_array($orderType, [OrderType::MOBILE, OrderType::SHIPPING], true)
            && $allowedBrands !== null
            && ! in_array($brand, $allowedBrands, true)) {
            throw new OrderOperationException('このお支払い方法は現在利用できません');
        }
        if ($allowedBrands === null && ($brand !== 'card' && $brand !== 'cash')) {
            throw new OrderOperationException('このお支払い方法は現在利用できません');
        }
        if ($paymentMethod === PaymentMethod::STRIPE && $allowedBrands === null && ! $isDefaultProject()) {
            throw new OrderOperationException('このプロジェクトの決済設定を登録してください');
        }
        if ($pointsUsed > $totalAmount) {
            throw new OrderOperationException('ポイント利用額は注文金額以下にしてください');
        }
        if ($pointsUsed > 0 && ! $pointsEnabled()) {
            throw new OrderOperationException('ポイントは現在利用できません');
        }
        $finalAmount = max(0, $totalAmount - $pointsUsed);
        if ($paymentMethod === PaymentMethod::STRIPE && $finalAmount < 1) {
            throw new OrderOperationException('オンライン決済の金額は1円以上必要です。ポイント利用額を減らしてください');
        }
        if ($brand === 'paypay' && ($finalAmount < 50 || $finalAmount > 1000000)) {
            throw new OrderOperationException('PayPayのお支払い金額は50円〜100万円です');
        }

        return ['brand' => $brand, 'final_amount' => $finalAmount];
    }
}
