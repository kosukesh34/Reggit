<?php

namespace App\Services\Admin;

use App\Enums\ScheduledDeliveryContentType;
use App\Enums\ScheduledDeliveryType;
use App\Models\AfterRegistrationDelivery;
use Illuminate\Support\Collection;

class AfterRegistrationDeliveryAdminService
{
    /** @return Collection<int, AfterRegistrationDelivery> */
    public function getListByProject(int $projectId): Collection
    {
        return AfterRegistrationDelivery::where('project_id', $projectId)
            ->with('coupon:id,name')
            ->orderBy('category')
            ->orderBy('id')
            ->get();
    }

    public function findOrFail(int $projectId, int $id): AfterRegistrationDelivery
    {
        return AfterRegistrationDelivery::where('project_id', $projectId)->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $projectId, array $data): AfterRegistrationDelivery
    {
        return AfterRegistrationDelivery::create($this->buildAttributes($projectId, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $projectId, int $id, array $data): AfterRegistrationDelivery
    {
        $delivery = $this->findOrFail($projectId, $id);
        $attributes = $this->buildAttributes($projectId, $data);
        unset($attributes['project_id']);
        $delivery->update($attributes);

        return $delivery;
    }

    public function delete(int $projectId, int $id): void
    {
        $this->findOrFail($projectId, $id)->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildAttributes(int $projectId, array $data): array
    {
        $scheduleType = (string) ($data['schedule_type'] ?? ScheduledDeliveryType::NEW_USER_MINUTES);
        $contentType = (string) ($data['content_type'] ?? ScheduledDeliveryContentType::ANNOUNCEMENT);
        $couponId = isset($data['coupon_id']) ? (int) $data['coupon_id'] : 0;
        $delayMinutes = (int) ($data['delay_minutes'] ?? 0);
        $params = $this->buildParams($scheduleType, $data, $delayMinutes);

        return [
            'project_id' => $projectId,
            'category' => ScheduledDeliveryType::categoryOf($scheduleType),
            'schedule_type' => $scheduleType,
            'content_type' => $contentType,
            'delay_minutes' => $delayMinutes > 0 ? $delayMinutes : ($params['delay_minutes'] ?? 0),
            'title' => $contentType === ScheduledDeliveryContentType::COUPON_ONLY
                ? ''
                : (string) ($data['title'] ?? ''),
            'body' => $data['body'] ?? null,
            'coupon_id' => $couponId > 0 ? $couponId : null,
            'send_notification' => $this->asBool($data['send_notification'] ?? true),
            'push_hour' => (int) ($data['push_hour'] ?? 10),
            'push_minute' => (int) ($data['push_minute'] ?? 0),
            'params' => $params,
            'is_active' => $this->asBool($data['is_active'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, int|string>
     */
    private function buildParams(string $scheduleType, array $data, int $delayMinutes): array
    {
        $allowed = match ($scheduleType) {
            ScheduledDeliveryType::NEW_USER_MINUTES => ['delay_minutes'],
            ScheduledDeliveryType::NEW_USER_DAYS, ScheduledDeliveryType::LAST_VISIT => ['delay_days'],
            ScheduledDeliveryType::VISIT_MILESTONE => ['visit_times', 'visit_after_days'],
            ScheduledDeliveryType::RANK_UP_ARRIVAL => ['reach_rank'],
            ScheduledDeliveryType::TOTAL_POINTS => ['total_points'],
            ScheduledDeliveryType::TOTAL_STAMPS => ['total_stamps'],
            ScheduledDeliveryType::BIRTHDAY_DAYS_BEFORE => ['before_days'],
            ScheduledDeliveryType::WEEKLY => ['day_of_week'],
            ScheduledDeliveryType::MONTHLY, ScheduledDeliveryType::BIRTHDAY_NEXT_MONTH => ['day_of_month'],
            ScheduledDeliveryType::LAST_MONTH_VISITS => ['day_of_month', 'last_month_visit_min'],
            default => [],
        };

        $params = [];
        foreach ($allowed as $key) {
            if ($key === 'delay_minutes') {
                $params['delay_minutes'] = $delayMinutes;

                continue;
            }
            if (! isset($data[$key]) || $data[$key] === '') {
                continue;
            }
            if ($key === 'reach_rank') {
                $params[$key] = (string) $data[$key];

                continue;
            }
            if ($key === 'day_of_month') {
                $params[$key] = $data[$key] === 'last_day' ? 'last_day' : (int) $data[$key];

                continue;
            }
            $params[$key] = (int) $data[$key];
        }

        return $params;
    }

    private function asBool(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }
}
