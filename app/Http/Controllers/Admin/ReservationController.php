<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ReservationStatusRequest;
use App\UseCases\Admin\Reservation\AdminReservationCompleteUseCase;
use App\UseCases\Admin\Reservation\AdminReservationDetailUseCase;
use App\UseCases\Admin\Reservation\AdminReservationIndexUseCase;
use App\UseCases\Admin\Reservation\AdminReservationUpdateStatusUseCase;
use App\Util\AdminStatusLabels;
use Illuminate\Http\Request;

class ReservationController extends AdminBaseController
{
    private AdminReservationIndexUseCase $indexUseCase;

    private AdminReservationDetailUseCase $detailUseCase;

    private AdminReservationUpdateStatusUseCase $updateStatusUseCase;

    private AdminReservationCompleteUseCase $completeUseCase;

    public function __construct(
        AdminReservationIndexUseCase $indexUseCase,
        AdminReservationDetailUseCase $detailUseCase,
        AdminReservationUpdateStatusUseCase $updateStatusUseCase,
        AdminReservationCompleteUseCase $completeUseCase
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->detailUseCase = $detailUseCase;
        $this->updateStatusUseCase = $updateStatusUseCase;
        $this->completeUseCase = $completeUseCase;
    }

    public function reservations(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->indexUseCase->handle(
            $request->query('status'),
            $request->query('date'),
            $projectId
        );

        $payload = [
            'reservations' => $output->reservations,
            'statusCounts' => $output->statusCounts,
            'selectedDate' => $output->selectedDate,
            'selectedDateLabel' => $output->selectedDateLabel,
            'dateStatusCounts' => $output->dateStatusCounts,
            'timeSlotSummary' => $output->timeSlotSummary,
            'reservationCapacity' => $output->reservationCapacity,
        ];
        $output->reservations->appends($request->query());

        return view('admin.reservations', $payload);
    }

    public function reservationDetail($id)
    {
        $reservation = $this->detailUseCase->handle((int) $id);

        return view('admin.reservation-detail', compact('reservation'));
    }

    public function updateReservationStatus(ReservationStatusRequest $request, $id)
    {
        $status = $request->validated('status');
        $this->updateStatusUseCase->handle((int) $id, $status);

        return redirect()->back()
            ->with('success', '予約ステータスを「'.AdminStatusLabels::reservationStatusLabel($status).'」に更新しました');
    }

    public function completeReservation($id)
    {
        $this->completeUseCase->handle((int) $id);

        return redirect()->back()->with('success', '予約を完了しました');
    }
}
