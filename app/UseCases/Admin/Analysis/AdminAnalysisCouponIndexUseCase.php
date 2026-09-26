<?php

namespace App\UseCases\Admin\Analysis;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Collection;

class AdminAnalysisCouponIndexUseCase
{
    /** @return Collection<int, Coupon> */
    public function handle(int $projectId): Collection
    {
        return Coupon::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
