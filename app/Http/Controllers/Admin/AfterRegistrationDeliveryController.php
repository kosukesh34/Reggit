<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\AfterRegistrationDelivery\AdminAfterRegistrationDeliveryCreateUseCase;
use App\UseCases\Admin\AfterRegistrationDelivery\AdminAfterRegistrationDeliveryEditUseCase;
use App\UseCases\Admin\AfterRegistrationDelivery\AdminAfterRegistrationDeliveryIndexUseCase;

class AfterRegistrationDeliveryController extends AdminBaseController
{
    public function __construct(
        private AdminAfterRegistrationDeliveryIndexUseCase $indexUseCase,
        private AdminAfterRegistrationDeliveryCreateUseCase $createUseCase,
        private AdminAfterRegistrationDeliveryEditUseCase $editUseCase,
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

    public function edit(string $projectKey, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->editUseCase->handle($projectId, $id);

        return view('admin.after-registration-deliveries.edit', compact('view'));
    }
}
