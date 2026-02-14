<?php

namespace App\Services\Admin;

use App\Models\StampCard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StampAdminService
{
    public function getPaginatedList(int $perPage = 20): LengthAwarePaginator
    {
        return StampCard::orderByDesc('id')->paginate($perPage);
    }

    public function create(int $projectId, array $data): StampCard
    {
        $data['project_id'] = $projectId;
        return StampCard::create($data);
    }

    public function findOrFail(int $id): StampCard
    {
        return StampCard::findOrFail($id);
    }

    public function update(int $id, array $data): StampCard
    {
        $card = StampCard::findOrFail($id);
        $card->update($data);
        return $card;
    }

    public function delete(int $id): void
    {
        StampCard::whereKey($id)->delete();
    }
}
