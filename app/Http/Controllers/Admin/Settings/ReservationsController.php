<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class ReservationsController extends AdminBaseController
{
    public function __construct(private SettingsService $settingsService) {}

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $settings = $this->settingsService->getReservationOnlySettings($projectId);
        return view('admin.settings.reservations', compact('settings'));
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
        $this->settingsService->updateReservationOnlySettings($validated, $projectId);
        return redirect()->route('admin.settings.reservations', $this->buildAdminRouteParams())
            ->with('success', '予約設定を更新しました');
    }
}
