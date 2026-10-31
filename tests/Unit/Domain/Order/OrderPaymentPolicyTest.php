<?php

namespace Tests\Unit\Domain\Order;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Order\Policies\OrderPaymentPolicy;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderPaymentPolicyTest extends TestCase
{
    public function test_ポイントを差し引いた支払額と支払い方法を返すこと(): void
    {
        $result = $this->resolve(PaymentMethod::STRIPE, 'paypay', ['card', 'paypay'], total: 1000.0, points: 100);

        $this->assertSame(['brand' => 'paypay', 'final_amount' => 900.0], $result);
    }

    public function test_設定や既定プロジェクトはそれが必要なときだけ確かめること(): void
    {
        $calls = 0;
        $result = (new OrderPaymentPolicy)->resolve(
            OrderType::MOBILE, PaymentMethod::CASH, null, null,
            function () use (&$calls): bool {
                $calls++;

                return false;
            },
            500.0, 0,
            function () use (&$calls): bool {
                $calls++;

                return false;
            },
        );

        $this->assertSame('cash', $result['brand']);
        $this->assertSame(0, $calls);
    }

    /** @return iterable<string, array{string, ?string, ?list<string>, bool, float, int, bool, string}> */
    public static function rejectedPayments(): iterable
    {
        yield '設定で無効な支払い方法' => [PaymentMethod::STRIPE, 'paypay', ['card'], true, 1000.0, 0, true, 'このお支払い方法は現在利用できません'];
        yield '既定以外のプロジェクトで決済設定なし' => [PaymentMethod::STRIPE, 'card', null, false, 1000.0, 0, true, 'このプロジェクトの決済設定を登録してください'];
        yield '注文額を超えるポイント' => [PaymentMethod::CASH, null, null, true, 100.0, 101, true, 'ポイント利用額は注文金額以下にしてください'];
        yield 'ポイント利用が無効' => [PaymentMethod::CASH, null, null, true, 100.0, 10, false, 'ポイントは現在利用できません'];
        yield 'オンライン決済が0円' => [PaymentMethod::STRIPE, 'card', ['card'], true, 100.0, 100, true, 'オンライン決済の金額は1円以上必要です。ポイント利用額を減らしてください'];
        yield 'PayPay の下限未満' => [PaymentMethod::STRIPE, 'paypay', ['paypay'], true, 49.0, 0, true, 'PayPayのお支払い金額は50円〜100万円です'];
    }

    /** @param list<string>|null $allowed */
    #[DataProvider('rejectedPayments')]
    public function test_制約を満たさない支払いは理由つきで拒否すること(string $method, ?string $brand, ?array $allowed, bool $default, float $total, int $points, bool $pointsEnabled, string $message): void
    {
        $this->expectException(OrderOperationException::class);
        $this->expectExceptionMessage($message);

        $this->resolve($method, $brand, $allowed, $total, $points, $default, $pointsEnabled);
    }

    /** @param list<string>|null $allowed */
    private function resolve(string $method, ?string $brand, ?array $allowed, float $total, int $points, bool $default = true, bool $pointsEnabled = true): array
    {
        return (new OrderPaymentPolicy)->resolve(OrderType::MOBILE, $method, $brand, $allowed, fn (): bool => $default, $total, $points, fn (): bool => $pointsEnabled);
    }
}
