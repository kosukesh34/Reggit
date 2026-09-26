<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AfterRegistrationDeliveryRequest;
use App\UseCases\Admin\AfterRegistrationDelivery\AdminAfterRegistrationDeliveryDestroyUseCase;
use App\UseCases\Admin\AfterRegistrationDelivery\AdminAfterRegistrationDeliveryStoreUseCase;
use App\UseCases\Admin\AfterRegistrationDelivery\AdminAfterRegistrationDeliveryUpdateUseCase;
use Illuminate\Http\RedirectResponse;

class AfterRegistrationDeliveryMutationController extends AdminBaseController
{
    public function __construct(
        private AdminAfterRegistrationDeliveryStoreUseCase $storeUseCase,
        private AdminAfterRegistrationDeliveryUpdateUseCase $updateUseCase,
        private AdminAfterRegistrationDeliveryDestroyUseCase $destroyUseCase,
    ) {}

    public function store(AfterRegistrationDeliveryRequest $request): RedirectResponse
    {
        $this->storeUseCase->handle($this->resolveAdminProjectId(), $request->validated());

        return $this->redirectToIndex()->with('success', 'スケジュール配信を追加しました');
    }

    public function update(AfterRegistrationDeliveryRequest $request, string $projectKey, int $id): RedirectResponse
    {
        $this->updateUseCase->handle($this->resolveAdminProjectId(), $id, $request->validated());

        return $this->redirectToIndex()->with('success', 'スケジュール配信を更新しました');
    }

    public function destroy(string $projectKey, int $id): RedirectResponse
    {
        $this->destroyUseCase->handle($this->resolveAdminProjectId(), $id);

        return $this->redirectToIndex()->with('success', 'スケジュール配信を削除しました');
    }

    private function redirectToIndex(): RedirectResponse
    {
        return redirect()->route('admin.after-registration-deliveries.index', $this->buildAdminRouteParams());
    }
}
