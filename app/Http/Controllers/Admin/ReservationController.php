<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ReservationStoreRequest;
use App\UseCases\Admin\Reservation\AdminReservationDestroyUseCase;
use App\UseCases\Admin\Reservation\AdminReservationDetailUseCase;
use App\UseCases\Admin\Reservation\AdminReservationIndexUseCase;
use App\UseCases\Admin\Reservation\AdminReservationStoreUseCase;
use App\UseCases\Admin\Reservation\AdminReservationUpdateUseCase;
use App\UseCases\Admin\Settings\GetReservationCoursesUseCase;
use Illuminate\Http\Request;

class ReservationController extends AdminBaseController
{
    public function __construct(
        private AdminReservationIndexUseCase $indexUseCase,
        private AdminReservationDetailUseCase $detailUseCase,
        private AdminReservationStoreUseCase $storeUseCase,
        private AdminReservationUpdateUseCase $updateUseCase,
        private AdminReservationDestroyUseCase $destroyUseCase,
        private GetReservationCoursesUseCase $getReservationCoursesUseCase,
    ) {}

    public function reservations(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->indexUseCase->handle(
            $request->query('status'),
            $request->query('date'),
            $projectId
        );

        $output->reservations->appends($request->query());

        return view('admin.reservations', ['view' => $output]);
    }

    public function reservationDetail(string $projectKey, $id)
    {
        $reservation = $this->detailUseCase->handle((int) $id);

        return view('admin.reservation-detail', compact('reservation'));
    }

    public function createReservation()
    {
        $courses = $this->getReservationCoursesUseCase->handle($this->resolveAdminProjectId(), activeOnly: true);

        return view('admin.reservation-create', compact('courses'));
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
        $courses = $this->getReservationCoursesUseCase->handle($this->resolveAdminProjectId(), activeOnly: true);

        return view('admin.reservation-edit', compact('reservation', 'courses'));
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
