<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ReservationCourse;
use Illuminate\Database\Eloquent\Collection;

class GetReservationCoursesUseCase
{
    /** @return Collection<int, ReservationCourse> */
    public function handle(int $projectId): Collection
    {
        return ReservationCourse::query()
            ->where('project_id', $projectId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
