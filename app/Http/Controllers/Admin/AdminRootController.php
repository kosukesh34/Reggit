<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\UseCases\Admin\Auth\ResolveAdminRootProjectUseCase;
use Illuminate\Http\RedirectResponse;

class AdminRootController extends Controller
{
    public function __construct(private ResolveAdminRootProjectUseCase $resolveAdminRootProjectUseCase) {}

    public function __invoke(): RedirectResponse
    {
        if (! auth('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $projectKey = $this->resolveAdminRootProjectUseCase->handle();

        if ($projectKey === null || $projectKey === '') {
            return redirect()->route('admin.login');
        }

        return redirect()->route('admin.dashboard', ['project_key' => $projectKey]);
    }
}
