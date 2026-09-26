<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\GetReservationCoursesUseCase;
use App\UseCases\Admin\Settings\GetReservationOnlySettingsUseCase;
use App\UseCases\Admin\Settings\StoreReservationCourseUseCase;
use App\UseCases\Admin\Settings\UpdateReservationCourseUseCase;
use App\UseCases\Admin\Settings\UpdateReservationOnlySettingsUseCase;
use Illuminate\Http\Request;

class ReservationsController extends AdminBaseController
{
    private GetReservationOnlySettingsUseCase $getReservationOnlySettingsUseCase;

    private UpdateReservationOnlySettingsUseCase $updateReservationOnlySettingsUseCase;

    public function __construct(
        GetReservationOnlySettingsUseCase $getReservationOnlySettingsUseCase,
        UpdateReservationOnlySettingsUseCase $updateReservationOnlySettingsUseCase,
        private GetReservationCoursesUseCase $getCoursesUseCase,
        private StoreReservationCourseUseCase $storeCourseUseCase,
        private UpdateReservationCourseUseCase $updateCourseUseCase,
    ) {
        $this->getReservationOnlySettingsUseCase = $getReservationOnlySettingsUseCase;
        $this->updateReservationOnlySettingsUseCase = $updateReservationOnlySettingsUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $settings = $this->getReservationOnlySettingsUseCase->handle($projectId);

        $courses = $this->getCoursesUseCase->handle($projectId);

        return view('admin.settings.reservations', compact('settings', 'courses'));
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'reservation_time_slots' => 'nullable|array',
            'reservation_time_slots.*' => 'nullable|string',
            'closed_days' => 'nullable|array',
            'closed_days.*' => 'integer|min:0|max:6',
            'closed_dates' => 'nullable|array',
            'closed_dates.*' => 'nullable|string',
            'advance_booking_days' => 'required|integer|min:1|max:365',
            'reservation_capacity_per_slot' => 'required|integer|min:1|max:999',
        ]);
        $this->updateReservationOnlySettingsUseCase->handle($projectId, $validated);

        return redirect()->route('admin.settings.reservations', $this->buildAdminRouteParams())
            ->with('success', '予約設定を更新しました');
    }

    public function storeCourse(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'price' => 'required|integer|min:0|max:99999999',
            'description' => 'nullable|string|max:1000',
        ]);
        $this->storeCourseUseCase->handle($this->resolveAdminProjectId(), $data);

        return redirect()->route('admin.settings.reservations', $this->buildAdminRouteParams())
            ->with('success', 'コースを追加しました');
    }

    public function updateCourse(Request $request, string $projectKey, int $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'price' => 'required|integer|min:0|max:99999999',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ]);
        $this->updateCourseUseCase->handle($this->resolveAdminProjectId(), $id, $data);

        return redirect()->route('admin.settings.reservations', $this->buildAdminRouteParams())
            ->with('success', 'コースを更新しました');
    }
}
