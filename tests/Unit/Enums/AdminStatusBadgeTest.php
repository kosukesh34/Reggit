<?php

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use PHPUnit\Framework\TestCase;

class AdminStatusBadgeTest extends TestCase
{
    public function test_注文ステータスは全て日本語ラベルとバッジ色を持つこと(): void
    {
        $this->assertSame('発送済み', OrderStatus::getLabel(OrderStatus::SHIPPED));
        $this->assertSame('warning', OrderStatus::badgeColor(OrderStatus::PENDING));
        $this->assertSame('info', OrderStatus::badgeColor(OrderStatus::SHIPPED));
        $this->assertSame('success', OrderStatus::badgeColor(OrderStatus::COMPLETED));
        $this->assertSame('danger', OrderStatus::badgeColor(OrderStatus::CANCELLED));
    }

    public function test_予約ステータスのラベルが管理画面の絞り込みと一致すること(): void
    {
        $this->assertSame('確認済み', ReservationStatus::getLabel(ReservationStatus::CONFIRMED));
        $this->assertSame('warning', ReservationStatus::badgeColor(ReservationStatus::PENDING));
        $this->assertSame('info', ReservationStatus::badgeColor('unknown'));
    }
}
