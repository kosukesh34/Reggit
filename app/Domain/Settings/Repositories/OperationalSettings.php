<?php

namespace App\Domain\Settings\Repositories;

interface OperationalSettings extends KitchenAccessSettings, OrderNotificationSettings, ProfileSettings, ReservationSettings, SettingValueWriter
{
    /** @return array{start: string, end: string} */
    public function getBusinessHours(int $projectId): array;

    /** @return list<int> */
    public function getClosedDays(int $projectId): array;

    /** @return list<string> */
    public function getClosedDates(int $projectId): array;

    public function getOrderMode(int $projectId): string;

    public function getShopShippingFee(int $projectId): int;

    public function getShopShippingFeeForPrefecture(int $projectId, string $prefecture): int;

    /** @return array<string, int> */
    public function getShopShippingFeesByPrefecture(int $projectId): array;

    public function setOrderMode(string $mode, int $projectId): void;

    public function setOrderLineNotifyEnabled(bool $enabled, int $projectId): void;

    public function setOrderReadyLineNotifyEnabled(bool $enabled, int $projectId): void;

    /** @return array{primary: string, primary_rgb: string, primary_dark: string, success: string, danger: string} */
    public function getLineThemeColors(int $projectId): array;
}
