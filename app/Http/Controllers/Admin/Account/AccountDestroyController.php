<?php

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\UseCases\Admin\Account\AccountDestroyUseCase;
use App\UseCases\Admin\RouteContextResolver;
use Illuminate\Http\Request;

class AccountDestroyController extends Controller
{
    public function __construct(
        private AccountDestroyUseCase $destroyUseCase,
        private RouteContextResolver $routeContextResolver
    ) {
    }

    public function __invoke(Request $request, int $id)
    {
        /** @var AdminUser $me */
        $me = $request->user('admin');
        $result = $this->destroyUseCase->handle($me, $id);
        $routeContext = $this->routeContextResolver->resolve($request);
        $flashKey = $result->success ? 'success' : 'error';

        return redirect()->route("{$routeContext->routePrefix}.accounts.index", $routeContext->params)
            ->with($flashKey, $result->message);
    }
}
