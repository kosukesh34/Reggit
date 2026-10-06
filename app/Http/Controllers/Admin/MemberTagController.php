<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\MemberTag\AdminMemberTagDestroyUseCase;
use App\UseCases\Admin\MemberTag\AdminMemberTagIndexUseCase;
use App\UseCases\Admin\MemberTag\AdminMemberTagStoreUseCase;
use Illuminate\Http\Request;

class MemberTagController extends AdminBaseController
{
    private AdminMemberTagIndexUseCase $indexUseCase;

    private AdminMemberTagStoreUseCase $storeUseCase;

    private AdminMemberTagDestroyUseCase $destroyUseCase;

    public function __construct(
        AdminMemberTagIndexUseCase $indexUseCase,
        AdminMemberTagStoreUseCase $storeUseCase,
        AdminMemberTagDestroyUseCase $destroyUseCase
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->storeUseCase = $storeUseCase;
        $this->destroyUseCase = $destroyUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $tags = $this->indexUseCase->handle($projectId);

        return view('admin.member-tags', ['tags' => $tags]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $projectId = $this->resolveAdminProjectId();
        $this->storeUseCase->handle($projectId, $validated['name'], $validated['color']);

        return redirect()->route('admin.member-tags', $this->buildAdminRouteParams())
            ->with('success', 'タグを作成しました');
    }

    public function destroy(string $projectKey, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->destroyUseCase->handle($projectId, $id);

        return redirect()->route('admin.member-tags', $this->buildAdminRouteParams())
            ->with('success', 'タグを削除しました');
    }
}
