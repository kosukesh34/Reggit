<?php

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\UseCases\Admin\Account\AccountFormUseCase;
use App\UseCases\Admin\RouteContextResolver;
use Illuminate\Http\Request;

class AccountFormController extends Controller
{
    public function __construct(
        private AccountFormUseCase $formUseCase,
        private RouteContextResolver $routeContextResolver
    ) {
    }

    public function create(Request $request)
    {
        /** @var AdminUser $me */
        $me = $request->user('admin');

        $routeContext = $this->routeContextResolver->resolve($request);
        $view = $this->formUseCase->getCreateView($me, $routeContext);

        return view('admin.accounts.create', ['view' => $view]);
    }

    public function edit(Request $request, int $id)
    {
        /** @var AdminUser $me */
        $me = $request->user('admin');
        $routeContext = $this->routeContextResolver->resolve($request);
        $view = $this->formUseCase->getEditView($me, $id, $routeContext);

        return view('admin.accounts.edit', ['view' => $view]);
    }
}
