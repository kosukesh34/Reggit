<?php

namespace App\Services;

use App\Models\ShopSetting;

class SettingsService
{
    private const HEX_REGEX = '/^#[0-9A-Fa-f]{6}$/';

    public function getBasicSettings(?int $projectId = null): array
    {
        $projectId = $projectId ?? 1;

        return [
            'businessHours' => ShopSetting::getBusinessHours($projectId),
            'timeSlots' => ShopSetting::getReservationTimeSlots($projectId),
            'closedDays' => ShopSetting::getClosedDays($projectId),
            'closedDates' => ShopSetting::getClosedDates($projectId),
            'advanceDays' => ShopSetting::getAdvanceBookingDays($projectId),
            'reservationCapacity' => ShopSetting::getReservationCapacityPerSlot($projectId),
            'orderMode' => ShopSetting::getOrderMode($projectId),
        ];
    }

    public function getAdvancedSettings(?int $projectId = null): array
    {
        $projectId = $projectId ?? 1;

        return ['lineTheme' => ShopSetting::getLineThemeColors($projectId)];
    }

    public function updateReservationSettings(array $validated, ?int $projectId = null): void
    {
        $projectId = $projectId ?? 1;
        ShopSetting::setValue('business_hours_start', $validated['business_hours_start'], 'time', $projectId);
        ShopSetting::setValue('business_hours_end', $validated['business_hours_end'], 'time', $projectId);

        // 基本設定画面からは予約詳細項目を送らないため、送信された場合のみ更新する。
        if (array_key_exists('reservation_time_slots', $validated) && is_array($validated['reservation_time_slots'])) {
            ShopSetting::setValue('reservation_time_slots', $validated['reservation_time_slots'], 'json', $projectId);
        }

        if (array_key_exists('closed_days', $validated) && is_array($validated['closed_days'])) {
            $closedDays = array_values(array_map('intval', $validated['closed_days']));
            ShopSetting::setValue('closed_days', $closedDays, 'json', $projectId);
        }

        if (array_key_exists('closed_dates', $validated) && is_array($validated['closed_dates'])) {
            $closedDates = array_values(array_filter($validated['closed_dates'], function ($date) {
                return $date !== null && $date !== '';
            }));
            ShopSetting::setValue('closed_dates', $closedDates, 'json', $projectId);
        }

        if (array_key_exists('advance_booking_days', $validated) && $validated['advance_booking_days'] !== null) {
            ShopSetting::setValue('advance_booking_days', (int) $validated['advance_booking_days'], 'integer', $projectId);
        }

        if (array_key_exists('reservation_capacity_per_slot', $validated) && $validated['reservation_capacity_per_slot'] !== null) {
            ShopSetting::setValue('reservation_capacity_per_slot', (int) $validated['reservation_capacity_per_slot'], 'integer', $projectId);
        }

        ShopSetting::setOrderMode((string) ($validated['order_mode'] ?? ShopSetting::ORDER_MODE_BOTH), $projectId);
    }

    /** 予約設定のみ取得（予約メニュー下の設定用） */
    public function getReservationOnlySettings(?int $projectId = null): array
    {
        $projectId = $projectId ?? 1;

        return [
            'timeSlots' => ShopSetting::getReservationTimeSlots($projectId),
            'closedDays' => ShopSetting::getClosedDays($projectId),
            'closedDates' => ShopSetting::getClosedDates($projectId),
            'advanceDays' => ShopSetting::getAdvanceBookingDays($projectId),
            'reservationCapacity' => ShopSetting::getReservationCapacityPerSlot($projectId),
        ];
    }

    /** 予約設定のみ更新（営業時間は基本設定で管理） */
    public function updateReservationOnlySettings(array $validated, ?int $projectId = null): void
    {
        $projectId = $projectId ?? 1;
        ShopSetting::setValue('reservation_time_slots', $validated['reservation_time_slots'] ?? [], 'json', $projectId);
        ShopSetting::setValue('closed_days', $validated['closed_days'] ?? [], 'json', $projectId);
        $closedDates = array_values(array_filter($validated['closed_dates'] ?? [], fn ($date) => $date !== null && $date !== ''));
        ShopSetting::setValue('closed_dates', $closedDates, 'json', $projectId);
        ShopSetting::setValue('advance_booking_days', (int) ($validated['advance_booking_days'] ?? 30), 'integer', $projectId);
        ShopSetting::setValue('reservation_capacity_per_slot', (int) ($validated['reservation_capacity_per_slot'] ?? 20), 'integer', $projectId);
    }

    public function updateLineThemeSettings(array $validated, ?int $projectId = null): void
    {
        $projectId = $projectId ?? 1;
        $keys = [
            'line_primary_color' => 'line_primary_color',
            'line_primary_dark' => 'line_primary_dark',
            'line_success_color' => 'line_success_color',
            'line_danger_color' => 'line_danger_color',
        ];
        foreach ($keys as $key) {
            if (isset($validated[$key]) && $validated[$key] !== '' && preg_match(self::HEX_REGEX, $validated[$key]) === 1) {
                ShopSetting::setValue($key, $validated[$key], 'string', $projectId);
            }
        }
    }

    public function getMenuSettings(?int $projectId = null): array
    {
        $projectId = $projectId ?? 1;
        $defaults = ShopSetting::defaultFrontendMenuItems();
        $availableTabs = collect($defaults)->map(function ($item) {
            return [
                'tab' => $item['tab'],
                'label' => $item['label'],
            ];
        })->values()->all();
        $availableIcons = collect($defaults)->pluck('icon')->unique()->values()->all();

        return [
            'menuItems' => ShopSetting::getFrontendMenuItems($projectId),
            'availableTabs' => $availableTabs,
            'availableIcons' => $availableIcons,
        ];
    }

    public function updateMenuSettings(array $validated, ?int $projectId = null): void
    {
        $projectId = $projectId ?? 1;
        $items = collect($validated['menu_items'] ?? [])
            ->map(function ($item) {
                return [
                    'tab' => $item['tab'] ?? '',
                    'label' => $item['label'] ?? null,
                    'icon' => $item['icon'] ?? null,
                    'enabled' => isset($item['enabled']) ? (bool) $item['enabled'] : false,
                    'order' => isset($item['order']) ? (int) $item['order'] : 999,
                ];
            })
            ->values()
            ->all();

        ShopSetting::setFrontendMenuItems($items, $projectId);
    }

    /** @return array{smaregi_fields: array<string, mixed>, custom_fields: list<array<string, mixed>>, member_registration_mode: string} */
    public function getProfileSettings(?int $projectId = null): array
    {
        $projectId = $projectId ?? 1;
        $settings = ShopSetting::getProfileFieldSettings($projectId);
        $settings['member_registration_mode'] = ShopSetting::getMemberRegistrationMode($projectId);

        return $settings;
    }

    /** @param array<string, bool> $featureFlags */
    public function updateFeatureFlags(array $featureFlags, ?int $projectId = null): void
    {
        ShopSetting::setFeatureFlags($featureFlags, $projectId ?? 1);
    }
}
