<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\SystemAdminDashboardUseCase;
use Illuminate\View\View;

class SystemAdminDashboardController extends AdminBaseController
{
    public function dashboard(SystemAdminDashboardUseCase $useCase): View
    {
        $dto = $useCase->handle();

        return view('admin.system-admin.dashboard', [
            'projectsCount' => $dto->projectsCount,
            'accountsCount' => $dto->accountsCount,
            'apiTokensCount' => $dto->apiTokensCount,
        ]);
    }
}
