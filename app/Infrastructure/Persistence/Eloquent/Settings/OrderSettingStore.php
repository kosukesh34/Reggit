<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Domain\Settings\ShopSettingKeys;

/** 注文モード・注文通知・キッチン画面アクセスの設定。 */
class OrderSettingStore
{
    public function __construct(
        private ShopSettingStore $values,
    ) {}

    public function getOrderMode(?int $projectId = null): string
    {
        $value = (string) $this->values->getValue('order_mode', ShopSettingKeys::ORDER_MODE_BOTH, $projectId);
        if (in_array($value, [ShopSettingKeys::ORDER_MODE_BOTH, ShopSettingKeys::ORDER_MODE_MOBILE_ONLY, ShopSettingKeys::ORDER_MODE_IN_STORE_ONLY], true)) {
            return $value;
        }

        return ShopSettingKeys::ORDER_MODE_BOTH;
    }

    public function setOrderMode(string $mode, ?int $projectId = null): void
    {
        if (! in_array($mode, [ShopSettingKeys::ORDER_MODE_BOTH, ShopSettingKeys::ORDER_MODE_MOBILE_ONLY, ShopSettingKeys::ORDER_MODE_IN_STORE_ONLY], true)) {
            $mode = ShopSettingKeys::ORDER_MODE_BOTH;
        }
        $this->values->setValue('order_mode', $mode, 'string', $projectId);
    }

    public function isOrderLineNotifyEnabled(?int $projectId = null): bool
    {
        return (bool) $this->values->getValue('order_line_notify_enabled', true, $projectId);
    }

    public function setOrderLineNotifyEnabled(bool $enabled, ?int $projectId = null): void
    {
        $this->values->setValue('order_line_notify_enabled', $enabled, 'boolean', $projectId);
    }

    public function isOrderReadyLineNotifyEnabled(?int $projectId = null): bool
    {
        return (bool) $this->values->getValue('order_ready_line_notify_enabled', true, $projectId);
    }

    public function setOrderReadyLineNotifyEnabled(bool $enabled, ?int $projectId = null): void
    {
        $this->values->setValue('order_ready_line_notify_enabled', $enabled, 'boolean', $projectId);
    }

    public function getKitchenAccessToken(?int $projectId = null): string
    {
        return trim((string) $this->values->getValue('kitchen_access_token', '', $projectId));
    }

    public function setKitchenAccessToken(?string $token, ?int $projectId = null): void
    {
        $this->values->setValue('kitchen_access_token', $token !== null ? trim($token) : '', 'string', $projectId);
    }
}
