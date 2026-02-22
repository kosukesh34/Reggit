<?php

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountStoreRequest;
use App\UseCases\Admin\Account\AccountStoreUseCase;
use App\UseCases\Admin\Account\Data\AccountStoreInput;
use App\UseCases\Admin\RouteContextResolver;

class AccountStoreController extends Controller
{
    public function __construct(
        private AccountStoreUseCase $storeUseCase,
        private RouteContextResolver $routeContextResolver
    ) {
    }

    public function __invoke(AccountStoreRequest $request)
    {
        $input = new AccountStoreInput(
            $request->toAccountPayload(),
            $request->toProjectIds(),
            $request->toPermissions()
        );
        $this->storeUseCase->handle($input);

        $routeContext = $this->routeContextResolver->resolve($request);
        return redirect()->route("{$routeContext->routePrefix}.accounts.index", $routeContext->params)
            ->with('success', 'アカウントを作成しました');
    }
}
