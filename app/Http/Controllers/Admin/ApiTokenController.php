<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApiTokenStoreRequest;
use App\Http\Requests\Admin\ApiTokenUpdateRequest;
use App\UseCases\Admin\ApiToken\ApiTokenCreateUseCase;
use App\UseCases\Admin\ApiToken\ApiTokenEditUseCase;
use App\UseCases\Admin\ApiToken\ApiTokenIndexUseCase;
use App\UseCases\Admin\ApiToken\ApiTokenRevokeUseCase;
use App\UseCases\Admin\ApiToken\ApiTokenRotateUseCase;
use App\UseCases\Admin\ApiToken\ApiTokenStoreUseCase;
use App\UseCases\Admin\ApiToken\ApiTokenUpdateUseCase;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function __construct(
        private ApiTokenIndexUseCase $indexUseCase,
        private ApiTokenCreateUseCase $createUseCase,
        private ApiTokenEditUseCase $editUseCase,
        private ApiTokenStoreUseCase $storeUseCase,
        private ApiTokenUpdateUseCase $updateUseCase,
        private ApiTokenRevokeUseCase $revokeUseCase,
        private ApiTokenRotateUseCase $rotateUseCase
    ) {
    }

    public function index()
    {
        $tokens = $this->indexUseCase->handle();
        return view('admin.api-tokens.index', compact('tokens'));
    }

    public function create()
    {
        $projects = $this->createUseCase->handle();
        return view('admin.api-tokens.create', compact('projects'));
    }

    public function edit(int $id)
    {
        $token = $this->editUseCase->handle($id);
        return view('admin.api-tokens.edit', compact('token'));
    }

    public function store(ApiTokenStoreRequest $request)
    {
        $issued = $this->storeUseCase->handle($request->toPayload());

        return redirect()
            ->route('system-admin.api-tokens.index')
            ->with('success', 'APIトークンを発行しました')
            ->with('issued_token_plain', $issued->plain);
    }

    public function update(ApiTokenUpdateRequest $request, int $id)
    {
        $this->updateUseCase->handle($id, $request->toPayload());

        return redirect()->route('system-admin.api-tokens.index')->with('success', 'APIトークンを更新しました');
    }

    public function revoke(int $id)
    {
        $this->revokeUseCase->handle($id);
        return redirect()->route('system-admin.api-tokens.index')->with('success', 'APIトークンを失効しました');
    }

    public function rotate(int $id)
    {
        $issued = $this->rotateUseCase->handle($id);

        return redirect()
            ->route('system-admin.api-tokens.index')
            ->with('success', 'APIトークンを再発行しました')
            ->with('issued_token_plain', $issued->plain);
    }
}
