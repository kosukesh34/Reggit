<?php

namespace App\Services;

use App\Models\ShopSetting;

class SettingsService
{
    private const HEX_REGEX = '/^#[0-9A-Fa-f]{6}$/';

    public function getBasicSettings(): array
    {
        return [
            'businessHours' => ShopSetting::getBusinessHours(),
            'timeSlots' => ShopSetting::getReservationTimeSlots(),
            'closedDays' => ShopSetting::getClosedDays(),
            'closedDates' => ShopSetting::getClosedDates(),
            'advanceDays' => ShopSetting::getAdvanceBookingDays(),
            'reservationCapacity' => ShopSetting::getReservationCapacityPerSlot(),
        ];
    }

    public function getAdvancedSettings(): array
    {
        return ['lineTheme' => ShopSetting::getLineThemeColors()];
    }

    public function updateReservationSettings(array $validated): void
    {
        ShopSetting::setValue('business_hours_start', $validated['business_hours_start'], 'time');
        ShopSetting::setValue('business_hours_end', $validated['business_hours_end'], 'time');
        ShopSetting::setValue('reservation_time_slots', $validated['reservation_time_slots'], 'json');
        ShopSetting::setValue('closed_days', $validated['closed_days'] ?? [], 'json');
        $closedDates = array_values(array_filter($validated['closed_dates'] ?? [], function ($date) {
            return $date !== null && $date !== '';
        }));
        ShopSetting::setValue('closed_dates', $closedDates, 'json');
        ShopSetting::setValue('advance_booking_days', $validated['advance_booking_days'], 'integer');
        ShopSetting::setValue('reservation_capacity_per_slot', $validated['reservation_capacity_per_slot'], 'integer');
    }

    public function updateLineThemeSettings(array $validated): void
    {
        $keys = [
            'line_primary_color' => 'line_primary_color',
            'line_primary_dark' => 'line_primary_dark',
            'line_success_color' => 'line_success_color',
            'line_danger_color' => 'line_danger_color',
        ];
        foreach ($keys as $key) {
            if (isset($validated[$key]) && $validated[$key] !== '' && preg_match(self::HEX_REGEX, $validated[$key]) === 1) {
                ShopSetting::setValue($key, $validated[$key], 'string');
            }
        }
    }
}
