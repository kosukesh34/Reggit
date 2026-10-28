<?php

namespace App\Services;

use App\Domain\Settings\Repositories\OperationalSettings;
use App\Domain\Settings\ShopSettingKeys;
use App\Util\AdminContext;

class SettingsService
{
    private const HEX_REGEX = '/^#[0-9A-Fa-f]{6}$/';

    /** @var OperationalSettings */
    private $shopSettingRepository;

    public function __construct(OperationalSettings $shopSettingRepository)
    {
        $this->shopSettingRepository = $shopSettingRepository;
    }

    public function getBasicSettings(?int $projectId = null): array
    {
        $projectId = AdminContext::resolveProjectId($projectId);
        $repo = $this->shopSettingRepository;

        return [
            'businessHours' => $repo->getBusinessHours($projectId),
            'timeSlots' => $repo->getReservationTimeSlots($projectId),
            'closedDays' => $repo->getClosedDays($projectId),
            'closedDates' => $repo->getClosedDates($projectId),
            'advanceDays' => $repo->getAdvanceBookingDays($projectId),
            'reservationCapacity' => $repo->getReservationCapacityPerSlot($projectId),
            'orderMode' => $repo->getOrderMode($projectId),
            'orderLineNotifyEnabled' => $repo->isOrderLineNotifyEnabled($projectId),
            'orderReadyLineNotifyEnabled' => $repo->isOrderReadyLineNotifyEnabled($projectId),
        ];
    }

    public function getAdvancedSettings(?int $projectId = null): array
    {
        $projectId = AdminContext::resolveProjectId($projectId);

        return ['lineTheme' => $this->shopSettingRepository->getLineThemeColors($projectId)];
    }

    public function updateReservationSettings(array $validated, ?int $projectId = null): void
    {
        $projectId = AdminContext::resolveProjectId($projectId);
        $repo = $this->shopSettingRepository;

        $repo->setValue('business_hours_start', $validated['business_hours_start'], 'time', $projectId);
        $repo->setValue('business_hours_end', $validated['business_hours_end'], 'time', $projectId);

        // 基本設定画面からは予約詳細項目を送らないため、送信された場合のみ更新する。
        if (array_key_exists('reservation_time_slots', $validated) && is_array($validated['reservation_time_slots'])) {
            $repo->setValue('reservation_time_slots', $validated['reservation_time_slots'], 'json', $projectId);
        }

        if (array_key_exists('closed_days', $validated) && is_array($validated['closed_days'])) {
            $closedDays = array_values(array_map('intval', $validated['closed_days']));
            $repo->setValue('closed_days', $closedDays, 'json', $projectId);
        }

        if (array_key_exists('closed_dates', $validated) && is_array($validated['closed_dates'])) {
            $closedDates = array_values(array_filter($validated['closed_dates'], function ($date) {
                return $date !== null && $date !== '';
            }));
            $repo->setValue('closed_dates', $closedDates, 'json', $projectId);
        }

        if (array_key_exists('advance_booking_days', $validated) && $validated['advance_booking_days'] !== null) {
            $repo->setValue('advance_booking_days', (int) $validated['advance_booking_days'], 'integer', $projectId);
        }

        if (array_key_exists('reservation_capacity_per_slot', $validated) && $validated['reservation_capacity_per_slot'] !== null) {
            $repo->setValue('reservation_capacity_per_slot', (int) $validated['reservation_capacity_per_slot'], 'integer', $projectId);
        }

        $repo->setOrderMode((string) ($validated['order_mode'] ?? ShopSettingKeys::ORDER_MODE_BOTH), $projectId);
        $repo->setOrderLineNotifyEnabled(
            (bool) ($validated['order_line_notify_enabled'] ?? false),
            $projectId
        );
        $repo->setOrderReadyLineNotifyEnabled(
            (bool) ($validated['order_ready_line_notify_enabled'] ?? false),
            $projectId
        );
    }

    /** 予約設定のみ取得（予約メニュー下の設定用） */
    public function getReservationOnlySettings(?int $projectId = null): array
    {
        $projectId = AdminContext::resolveProjectId($projectId);
        $repo = $this->shopSettingRepository;

        return [
            'timeSlots' => $repo->getReservationTimeSlots($projectId),
            'closedDays' => $repo->getClosedDays($projectId),
            'closedDates' => $repo->getClosedDates($projectId),
            'advanceDays' => $repo->getAdvanceBookingDays($projectId),
            'reservationCapacity' => $repo->getReservationCapacityPerSlot($projectId),
            'rules' => $repo->getReservationRules($projectId),
            'reminder' => $repo->getReservationReminderConfig($projectId),
        ];
    }

    /** 予約設定のみ更新（営業時間は基本設定で管理） */
    public function updateReservationOnlySettings(array $validated, ?int $projectId = null): void
    {
        $projectId = AdminContext::resolveProjectId($projectId);
        $repo = $this->shopSettingRepository;

        $repo->setValue('reservation_time_slots', $validated['reservation_time_slots'] ?? [], 'json', $projectId);
        $repo->setValue('closed_days', $validated['closed_days'] ?? [], 'json', $projectId);
        $closedDates = array_values(array_filter($validated['closed_dates'] ?? [], fn ($date) => $date !== null && $date !== ''));
        $repo->setValue('closed_dates', $closedDates, 'json', $projectId);
        $repo->setValue('advance_booking_days', (int) ($validated['advance_booking_days'] ?? 30), 'integer', $projectId);
        $repo->setValue('reservation_capacity_per_slot', (int) ($validated['reservation_capacity_per_slot'] ?? 20), 'integer', $projectId);
        foreach (['reservation_capacity_by_people' => 'boolean', 'reservation_max_party_size' => 'integer', 'reservation_same_day' => 'boolean', 'reservation_lead_minutes' => 'integer', 'reservation_change_deadline_hours' => 'integer'] as $key => $type) {
            if (array_key_exists($key, $validated)) {
                $repo->setValue($key, $type === 'boolean' ? (bool) $validated[$key] : (int) $validated[$key], $type, $projectId);
            }
        }
        $repo->setValue('reservation_reminder_hours', (int) ($validated['reservation_reminder_hours'] ?? 0), 'integer', $projectId);
        $repo->setValue('reservation_reminder_second_hours', (int) ($validated['reservation_reminder_second_hours'] ?? 0), 'integer', $projectId);
        $message = trim((string) ($validated['reservation_reminder_message'] ?? ''));
        $repo->setValue('reservation_reminder_message', $message !== '' ? $message : null, 'string', $projectId);
    }

    public function updateLineThemeSettings(array $validated, ?int $projectId = null): void
    {
        $projectId = AdminContext::resolveProjectId($projectId);
        $keys = [
            'line_primary_color' => 'line_primary_color',
            'line_primary_dark' => 'line_primary_dark',
            'line_success_color' => 'line_success_color',
            'line_danger_color' => 'line_danger_color',
        ];
        foreach ($keys as $key) {
            if (isset($validated[$key]) && $validated[$key] !== '' && preg_match(self::HEX_REGEX, $validated[$key]) === 1) {
                $this->shopSettingRepository->setValue($key, $validated[$key], 'string', $projectId);
            }
        }
    }

    /** @return array{smaregi_fields: array<string, mixed>, custom_fields: list<array<string, mixed>>, member_registration_mode: string} */
    public function getProfileSettings(?int $projectId = null): array
    {
        $projectId = AdminContext::resolveProjectId($projectId);
        $repo = $this->shopSettingRepository;

        $settings = $repo->getProfileFieldSettings($projectId);
        $settings['member_registration_mode'] = $repo->getMemberRegistrationMode($projectId);

        return $settings;
    }
}
