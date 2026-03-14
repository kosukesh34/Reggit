<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ReservationStatusRequest;
use App\Http\Requests\Admin\ReservationStoreRequest;
use App\UseCases\Admin\Reservation\AdminReservationCompleteUseCase;
use App\UseCases\Admin\Reservation\AdminReservationDestroyUseCase;
use App\UseCases\Admin\Reservation\AdminReservationDetailUseCase;
use App\UseCases\Admin\Reservation\AdminReservationIndexUseCase;
use App\UseCases\Admin\Reservation\AdminReservationStoreUseCase;
use App\UseCases\Admin\Reservation\AdminReservationUpdateStatusUseCase;
use App\UseCases\Admin\Reservation\AdminReservationUpdateUseCase;
use App\Util\AdminStatusLabels;
use Illuminate\Http\Request;

class ReservationController extends AdminBaseController
{
    private AdminReservationIndexUseCase $indexUseCase;

    private AdminReservationDetailUseCase $detailUseCase;

    private AdminReservationUpdateStatusUseCase $updateStatusUseCase;

    private AdminReservationCompleteUseCase $completeUseCase;

    private AdminReservationStoreUseCase $storeUseCase;

    private AdminReservationUpdateUseCase $updateUseCase;

    private AdminReservationDestroyUseCase $destroyUseCase;

    public function __construct(
        AdminReservationIndexUseCase $indexUseCase,
        AdminReservationDetailUseCase $detailUseCase,
        AdminReservationUpdateStatusUseCase $updateStatusUseCase,
        AdminReservationCompleteUseCase $completeUseCase,
        AdminReservationStoreUseCase $storeUseCase,
        AdminReservationUpdateUseCase $updateUseCase,
        AdminReservationDestroyUseCase $destroyUseCase
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->detailUseCase = $detailUseCase;
        $this->updateStatusUseCase = $updateStatusUseCase;
        $this->completeUseCase = $completeUseCase;
        $this->storeUseCase = $storeUseCase;
        $this->updateUseCase = $updateUseCase;
        $this->destroyUseCase = $destroyUseCase;
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

    public function reservationDetail(string $projectKey, $id)
    {
        $reservation = $this->detailUseCase->handle((int) $id);

        return view('admin.reservation-detail', compact('reservation'));
    }

    public function updateReservationStatus(ReservationStatusRequest $request, string $projectKey, $id)
    {
        $status = $request->validated('status');
        $this->updateStatusUseCase->handle((int) $id, $status);

        return redirect()->back()
            ->with('success', '予約ステータスを「'.AdminStatusLabels::reservationStatusLabel($status).'」に更新しました');
    }

    public function completeReservation(string $projectKey, $id)
    {
        $this->completeUseCase->handle((int) $id);

        return redirect()->back()->with('success', '予約を完了しました');
    }

    public function createReservation()
    {
        return view('admin.reservation-create');
    }

    public function storeReservation(ReservationStoreRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->storeUseCase->handle($request->validated(), $projectId);

        return redirect()->route('admin.reservations', $this->buildAdminRouteParams())
            ->with('success', '予約を作成しました');
    }

    public function editReservation(string $projectKey, $id)
    {
        $reservation = $this->detailUseCase->handle((int) $id);

        return view('admin.reservation-edit', compact('reservation'));
    }

    public function updateReservation(ReservationStoreRequest $request, string $projectKey, $id)
    {
        $this->updateUseCase->handle((int) $id, $request->validated());

        return redirect()->route('admin.reservations.detail', array_merge($this->buildAdminRouteParams(), ['id' => $id]))
            ->with('success', '予約を更新しました');
    }

    public function destroyReservation(string $projectKey, $id)
    {
        $this->destroyUseCase->handle((int) $id);

        return redirect()->route('admin.reservations', $this->buildAdminRouteParams())
            ->with('success', '予約を削除しました');
    }
}
