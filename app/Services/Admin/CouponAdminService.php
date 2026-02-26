<?php

namespace App\Services\Admin;

use App\Models\Coupon;
use App\Models\StampCard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CouponAdminService
{
    public function getPaginatedList(int $projectId, int $perPage = 20): LengthAwarePaginator
    {
        return Coupon::where('project_id', $projectId)
            ->with('stampCard')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getActiveStampCardsForSelect(int $projectId): Collection
    {
        return StampCard::where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function create(int $projectId, array $data): Coupon
    {
        $data['project_id'] = $projectId;
        return Coupon::create($data);
    }

    /** @param int|null $projectId 指定時はそのプロジェクトのクーポンのみ取得（他プロジェクトは404） */
    public function findOrFail(int $id, ?int $projectId = null): Coupon
    {
        $query = Coupon::with('stampCard');
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }
        return $query->findOrFail($id);
    }

    public function update(int $id, array $data, ?int $projectId = null): Coupon
    {
        $coupon = $this->findOrFail($id, $projectId);
        $coupon->update($data);
        return $coupon;
    }

    public function delete(int $id, ?int $projectId = null): void
    {
        $coupon = $this->findOrFail($id, $projectId);
        $coupon->delete();
    }
}
