<?php

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Http\Support\RouteContextResolver;
use App\Models\AdminUser;
use App\UseCases\Admin\Account\AccountIndexUseCase;
use App\UseCases\Admin\Account\Data\AccountIndexView;
use Illuminate\Http\Request;

class AccountIndexController extends Controller
{
    public function __construct(
        private AccountIndexUseCase $indexUseCase,
        private RouteContextResolver $routeContextResolver
    ) {}

    public function __invoke(Request $request)
    {
        /** @var AdminUser $me */
        $me = $request->user('admin');

        $result = $this->indexUseCase->handle($me);
        $routeContext = $this->routeContextResolver->resolveFromRequest($request);

        return view('admin.accounts.index', ['view' => new AccountIndexView(
            $result['adminUsers'],
            $me,
            $routeContext->routePrefix,
        )]);
    }
}
