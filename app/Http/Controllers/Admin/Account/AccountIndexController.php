<?php

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Http\Support\AuthenticatedAdminResolver;
use App\Http\Support\RouteContextResolver;
use App\UseCases\Admin\Account\AccountIndexUseCase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AccountIndexController extends Controller
{
    public function __construct(
        private AccountIndexUseCase $indexUseCase,
        private RouteContextResolver $routeContextResolver,
        private AuthenticatedAdminResolver $authenticatedAdminResolver,
    ) {}

    public function __invoke(Request $request): View
    {
        $me = $this->authenticatedAdminResolver->resolve($request);
        $routeContext = $this->routeContextResolver->resolveFromRequest($request);

        return view('admin.accounts.index', [
            'view' => $this->indexUseCase->handle($me, $routeContext),
        ]);
    }
}
