<?php

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Http\Support\AuthenticatedAdminResolver;
use App\Http\Support\RouteContextResolver;
use App\UseCases\Admin\Account\AccountFormUseCase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AccountFormController extends Controller
{
    public function __construct(
        private AccountFormUseCase $formUseCase,
        private RouteContextResolver $routeContextResolver,
        private AuthenticatedAdminResolver $authenticatedAdminResolver,
    ) {}

    public function create(Request $request): View
    {
        $me = $this->authenticatedAdminResolver->resolve($request);

        $routeContext = $this->routeContextResolver->resolveFromRequest($request);
        $view = $this->formUseCase->getCreateView($me, $routeContext);

        return view('admin.accounts.create', ['view' => $view]);
    }

    public function edit(Request $request, int $id): View
    {
        $me = $this->authenticatedAdminResolver->resolve($request);
        $routeContext = $this->routeContextResolver->resolveFromRequest($request);
        $view = $this->formUseCase->getEditView($me, $id, $routeContext);

        return view('admin.accounts.edit', ['view' => $view]);
    }
}
