<?php

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountUpdateRequest;
use App\UseCases\Admin\Account\AccountUpdateUseCase;
use App\UseCases\Admin\Account\Data\AccountUpdateInput;
use App\UseCases\Admin\RouteContextResolver;

class AccountUpdateController extends Controller
{
    public function __construct(
        private AccountUpdateUseCase $updateUseCase,
        private RouteContextResolver $routeContextResolver
    ) {}

    public function __invoke(AccountUpdateRequest $request, int $id)
    {
        $input = new AccountUpdateInput(
            $request->toAccountPayload(),
            $request->toProjectIds(),
            $request->toPermissions(),
            $request->validated('password'),
            $request->filled('password')
        );
        $this->updateUseCase->handle($id, $input);

        $routeContext = $this->routeContextResolver->resolve($request);

        return redirect()->route("{$routeContext->routePrefix}.accounts.index", $routeContext->params)
            ->with('success', 'アカウントを更新しました');
    }
}
