<?php

namespace Tests\Support;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;

trait ConfiguresShopLegalNotice
{
    /** @return array<string, string> */
    protected function shopLegalNotice(): array
    {
        return [
            'seller' => '株式会社テスト', 'representative' => '山田 太郎', 'address' => '東京都千代田区1-1',
            'phone' => '03-0000-0000', 'email' => 'shop@example.com', 'extra_fees' => '送料',
            'payment' => 'クレジットカード', 'delivery' => '3営業日以内に発送', 'returns' => '不良品のみ交換',
        ];
    }

    protected function completeShopLegalNotice(int $projectId): void
    {
        app(ShopSettingStore::class)->setValue('shop_legal_notice', $this->shopLegalNotice(), 'json', $projectId);
    }
}
