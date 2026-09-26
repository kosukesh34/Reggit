<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ReservationStatusRequest;
use App\UseCases\Admin\Reservation\AdminReservationCompleteUseCase;
use App\UseCases\Admin\Reservation\AdminReservationUpdateStatusUseCase;
use App\Util\AdminStatusLabels;
use Illuminate\Http\RedirectResponse;

class ReservationStatusController extends AdminBaseController
{
    public function __construct(
        private AdminReservationUpdateStatusUseCase $updateStatusUseCase,
        private AdminReservationCompleteUseCase $completeUseCase,
    ) {}

    public function update(ReservationStatusRequest $request, string $projectKey, int $id): RedirectResponse
    {
        $status = $request->validated('status');
        $this->updateStatusUseCase->handle($id, $status);

        return back()
            ->with('success', '予約ステータスを「'.AdminStatusLabels::reservationStatusLabel($status).'」に更新しました');
    }

    public function complete(string $projectKey, int $id): RedirectResponse
    {
        $this->completeUseCase->handle($id);

        return back()->with('success', '予約を完了しました');
    }
}
