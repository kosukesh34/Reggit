<?php

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Http\Support\AuthenticatedAdminResolver;
use App\Http\Support\RouteContextResolver;
use App\UseCases\Admin\Account\AccountDestroyUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountDestroyController extends Controller
{
    public function __construct(
        private AccountDestroyUseCase $destroyUseCase,
        private RouteContextResolver $routeContextResolver,
        private AuthenticatedAdminResolver $authenticatedAdminResolver,
    ) {}

    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $me = $this->authenticatedAdminResolver->resolve($request);
        $result = $this->destroyUseCase->handle($me, $id);
        $routeContext = $this->routeContextResolver->resolveFromRequest($request);
        $flashKey = $result->success ? 'success' : 'error';

        return redirect()->route("{$routeContext->routePrefix}.accounts.index", $routeContext->params)
            ->with($flashKey, $result->message);
    }
}
