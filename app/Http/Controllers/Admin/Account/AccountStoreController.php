<?php

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountStoreRequest;
use App\Http\Support\RouteContextResolver;
use App\UseCases\Admin\Account\AccountStoreUseCase;
use App\UseCases\Admin\Account\Data\AccountStoreInput;

class AccountStoreController extends Controller
{
    public function __construct(
        private AccountStoreUseCase $storeUseCase,
        private RouteContextResolver $routeContextResolver
    ) {}

    public function __invoke(AccountStoreRequest $request)
    {
        $input = new AccountStoreInput(
            $request->toAccountPayload(),
            $request->toProjectIds(),
            $request->toPermissions()
        );
        $this->storeUseCase->handle($input);

        $routeContext = $this->routeContextResolver->resolveFromRequest($request);

        return redirect()->route("{$routeContext->routePrefix}.accounts.index", $routeContext->params)
            ->with('success', 'アカウントを作成しました');
    }
}
