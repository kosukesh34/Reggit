<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ReservationCourse;

class StoreReservationCourseUseCase
{
    /** @param array<string, mixed> $data */
    public function handle(int $projectId, array $data): ReservationCourse
    {
        return ReservationCourse::create($data + ['project_id' => $projectId]);
    }
}
