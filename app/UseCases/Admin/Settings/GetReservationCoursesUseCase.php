<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ReservationCourse;
use Illuminate\Database\Eloquent\Collection;

class GetReservationCoursesUseCase
{
    /** @return Collection<int, ReservationCourse> */
    public function handle(int $projectId, bool $activeOnly = false): Collection
    {
        $query = ReservationCourse::query()->where('project_id', $projectId);
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
