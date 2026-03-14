<?php

namespace App\Services\Admin;

use App\Models\AfterRegistrationDelivery;
use Illuminate\Support\Collection;

class AfterRegistrationDeliveryAdminService
{
    /** @return Collection<int, AfterRegistrationDelivery> */
    public function getListByProject(int $projectId): Collection
    {
        return AfterRegistrationDelivery::where('project_id', $projectId)
            ->with('coupon:id,name')
            ->orderBy('delay_minutes')
            ->get();
    }

    public function findOrFail(int $projectId, int $id): AfterRegistrationDelivery
    {
        return AfterRegistrationDelivery::where('project_id', $projectId)->findOrFail($id);
    }

    /**
     * @param  array{delay_minutes: string, title: string, body?: string, coupon_id?: string, is_active?: string}  $data
     */
    public function create(int $projectId, array $data): AfterRegistrationDelivery
    {
        return AfterRegistrationDelivery::create($this->buildAttributes($projectId, $data));
    }

    /**
     * @param  array{delay_minutes: string, title: string, body?: string, coupon_id?: string, is_active?: string}  $data
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
     * @param  array{delay_minutes: string, title: string, body?: string, coupon_id?: string, is_active?: string}  $data
     * @return array<string, int|string|bool|null>
     */
    private function buildAttributes(int $projectId, array $data): array
    {
        $couponId = isset($data['coupon_id']) ? (int) $data['coupon_id'] : 0;

        return [
            'project_id' => $projectId,
            'delay_minutes' => (int) $data['delay_minutes'],
            'title' => (string) $data['title'],
            'body' => $data['body'] ?? null,
            'coupon_id' => $couponId > 0 ? $couponId : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }
}
