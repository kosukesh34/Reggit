<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ReservationCourse;

class UpdateReservationCourseUseCase
{
    /** @param array<string, mixed> $data */
    public function handle(int $projectId, int $courseId, array $data): ReservationCourse
    {
        $course = ReservationCourse::query()
            ->where('project_id', $projectId)
            ->findOrFail($courseId);
        $course->update($data);

        return $course;
    }
}
