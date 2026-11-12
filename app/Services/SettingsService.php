<?php

namespace App\Services;

use App\Domain\Settings\Repositories\OperationalSettings;
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
