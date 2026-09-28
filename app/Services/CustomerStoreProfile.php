<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Validation\Rule;

/** Maps the member's designated store to Smaregi's customer storeId. */
class CustomerStoreProfile
{
    public function options(int $projectId): array
    {
        return Store::where('project_id', $projectId)->where('is_active', true)->orderBy('id')->get()
            ->map(fn ($store) => ['value' => (string) $store->id, 'label' => $store->name])->all();
    }

    public function rule(int $projectId)
    {
        return Rule::exists('stores', 'id')->where('project_id', $projectId)->where('is_active', true);
    }

    public function forSmaregi(array $data, int $projectId): array
    {
        if (! empty($data['customer_store_id'])) {
            $externalId = Store::where('project_id', $projectId)->whereKey($data['customer_store_id'])->value('smaregi_store_id');
            if ($externalId !== null && $externalId !== '') {
                $data['smaregi_store_id'] = (string) $externalId;
            }
        }

        return $data;
    }

    public function fromSmaregi(int $projectId, string $externalId): ?int
    {
        if ($externalId === '') {
            return null;
        }
        $id = Store::where('project_id', $projectId)->where('smaregi_store_id', $externalId)->value('id');

        return $id === null ? null : (int) $id;
    }
}
