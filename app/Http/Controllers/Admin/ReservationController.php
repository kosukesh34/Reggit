<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ReservationStatusRequest;
use App\Services\Admin\ReservationAdminService;
use App\Util\AdminStatusLabels;
use Illuminate\Http\Request;

class ReservationController extends AdminBaseController
{
    public function __construct(
        private ReservationAdminService $reservationAdminService
    ) {
    }

    public function reservations(Request $request)
    {
        $payload = $this->reservationAdminService->getReservationIndexPayload(
            $request->query('status'),
            $request->query('date'),
            $this->resolveAdminProjectId(),
            (int) $request->query('per_page', 20)
        );
        return view('admin.reservations', $payload);
    }

    public function reservationDetail($id)
    {
        $reservation = $this->reservationAdminService->findOrFail((int) $id);
        return view('admin.reservation-detail', compact('reservation'));
    }

    public function updateReservationStatus(ReservationStatusRequest $request, $id)
    {
        $status = $request->validated('status');
        $this->reservationAdminService->updateStatus((int) $id, $status);
        return redirect()->back()
            ->with('success', '予約ステータスを「' . AdminStatusLabels::reservationStatusLabel($status) . '」に更新しました');
    }

    public function completeReservation($id)
    {
        $this->reservationAdminService->complete((int) $id);
        return redirect()->back()->with('success', '予約を完了しました');
    }
}
