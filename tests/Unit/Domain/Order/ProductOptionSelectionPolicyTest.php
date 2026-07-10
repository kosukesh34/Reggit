<?php

namespace Tests\Unit\Domain\Order;

use App\Domain\Order\Policies\ProductAvailabilityPolicy;
use App\Domain\Order\Policies\ProductOptionSelectionPolicy;
use App\Domain\Order\ValueObjects\ProductOptionChoice;
use App\Domain\Order\ValueObjects\ProductOptionGroupSpec;
use Tests\TestCase;

class ProductOptionSelectionPolicyTest extends TestCase
{
    /**
     * @return list<ProductOptionGroupSpec>
     */
    private function sampleGroups(): array
    {
        return [
            new ProductOptionGroupSpec(
                1,
                'サイズ',
                ProductOptionGroupSpec::SELECTION_SINGLE,
                true,
                1,
                null,
                [
                    new ProductOptionChoice(10, 'M', 0.0, 1),
                    new ProductOptionChoice(11, 'L', 100.0, 1),
                ],
            ),
            new ProductOptionGroupSpec(
                2,
                'トッピング',
                ProductOptionGroupSpec::SELECTION_MULTIPLE,
                false,
                0,
                2,
                [
                    new ProductOptionChoice(20, 'チーズ', 50.0, 2),
                    new ProductOptionChoice(21, 'ベーコン', 80.0, 2),
                    new ProductOptionChoice(22, '卵', 40.0, 2),
                ],
            ),
        ];
    }

    public function test_resolve_必須オプションと追加料金を計算できること(): void
    {
        [$delta, $summary] = (new ProductOptionSelectionPolicy)->resolve(
            'バーガー',
            $this->sampleGroups(),
            [11, 20],
        );

        $this->assertSame(150.0, $delta);
        $this->assertSame('L, チーズ', $summary);
    }

    public function test_resolve_必須未選択は拒否されること(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('「サイズ」は必須です');

        (new ProductOptionSelectionPolicy)->resolve('バーガー', $this->sampleGroups(), []);
    }

    public function test_resolve_単一選択に複数は拒否されること(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('1つだけ選択できます');

        (new ProductOptionSelectionPolicy)->resolve('バーガー', $this->sampleGroups(), [10, 11]);
    }

    public function test_resolve_他商品のオプションは拒否されること(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('オプションが不正です');

        (new ProductOptionSelectionPolicy)->resolve('バーガー', $this->sampleGroups(), [10, 999]);
    }

    public function test_resolve_複数選択の上限を超えると拒否されること(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('最大2個までです');

        (new ProductOptionSelectionPolicy)->resolve('バーガー', $this->sampleGroups(), [10, 20, 21, 22]);
    }

    public function test_assertAvailable_在庫不足は拒否されること(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('在庫が不足しています');

        (new ProductAvailabilityPolicy)->assertAvailable('バーガー', true, 1, 2);
    }

    public function test_assertAvailable_利用不可は拒否されること(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('現在利用できません');

        (new ProductAvailabilityPolicy)->assertAvailable('バーガー', false, null, 1);
    }
}
