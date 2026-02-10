<?php

namespace App\Services\Admin;

use App\Models\Coupon;
use App\Models\StampCard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CouponAdminService
{
    public function getPaginatedList(int $perPage = 20): LengthAwarePaginator
    {
        return Coupon::orderByDesc('id')->paginate($perPage);
    }

    public function getActiveStampCardsForSelect(): Collection
    {
        return StampCard::where('is_active', true)->orderBy('name')->get();
    }

    public function create(int $projectId, array $data): Coupon
    {
        $data['project_id'] = $projectId;
        return Coupon::create($data);
    }

    public function findOrFail(int $id): Coupon
    {
        return Coupon::findOrFail($id);
    }

    public function update(int $id, array $data): Coupon
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update($data);
        return $coupon;
    }

    public function delete(int $id): void
    {
        Coupon::whereKey($id)->delete();
    }
}
