<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AfterRegistrationDeliveryRequest;
use App\UseCases\Admin\AfterRegistrationDelivery\AdminAfterRegistrationDeliveryCreateUseCase;
use App\UseCases\Admin\AfterRegistrationDelivery\AdminAfterRegistrationDeliveryDestroyUseCase;
use App\UseCases\Admin\AfterRegistrationDelivery\AdminAfterRegistrationDeliveryEditUseCase;
use App\UseCases\Admin\AfterRegistrationDelivery\AdminAfterRegistrationDeliveryIndexUseCase;
use App\UseCases\Admin\AfterRegistrationDelivery\AdminAfterRegistrationDeliveryStoreUseCase;
use App\UseCases\Admin\AfterRegistrationDelivery\AdminAfterRegistrationDeliveryUpdateUseCase;

class AfterRegistrationDeliveryController extends AdminBaseController
{
    public function __construct(
        private AdminAfterRegistrationDeliveryIndexUseCase $indexUseCase,
        private AdminAfterRegistrationDeliveryCreateUseCase $createUseCase,
        private AdminAfterRegistrationDeliveryStoreUseCase $storeUseCase,
        private AdminAfterRegistrationDeliveryEditUseCase $editUseCase,
        private AdminAfterRegistrationDeliveryUpdateUseCase $updateUseCase,
        private AdminAfterRegistrationDeliveryDestroyUseCase $destroyUseCase
    ) {}

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $deliveries = $this->indexUseCase->handle($projectId);

        return view('admin.after-registration-deliveries.index', compact('deliveries'));
    }

    public function create()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->createUseCase->handle($projectId);

        return view('admin.after-registration-deliveries.create', compact('view'));
    }

    public function store(AfterRegistrationDeliveryRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->storeUseCase->handle($projectId, $request->validated());

        return redirect()->route('admin.after-registration-deliveries.index', $this->buildAdminRouteParams())
            ->with('success', 'スケジュール配信を追加しました');
    }

    public function edit(string $projectKey, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->editUseCase->handle($projectId, $id);

        return view('admin.after-registration-deliveries.edit', compact('view'));
    }

    public function update(AfterRegistrationDeliveryRequest $request, string $projectKey, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->updateUseCase->handle($projectId, $id, $request->validated());

        return redirect()->route('admin.after-registration-deliveries.index', $this->buildAdminRouteParams())
            ->with('success', 'スケジュール配信を更新しました');
    }

    public function destroy(string $projectKey, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->destroyUseCase->handle($projectId, $id);

        return redirect()->route('admin.after-registration-deliveries.index', $this->buildAdminRouteParams())
            ->with('success', 'スケジュール配信を削除しました');
    }
}
