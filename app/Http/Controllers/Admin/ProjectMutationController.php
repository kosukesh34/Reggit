<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectStoreRequest;
use App\Http\Requests\Admin\ProjectUpdateRequest;
use App\UseCases\Admin\Project\ProjectStoreUseCase;
use App\UseCases\Admin\Project\ProjectUpdateUseCase;
use Illuminate\Http\RedirectResponse;

class ProjectMutationController extends Controller
{
    public function __construct(
        private ProjectStoreUseCase $storeUseCase,
        private ProjectUpdateUseCase $updateUseCase,
    ) {}

    public function store(ProjectStoreRequest $request): RedirectResponse
    {
        $this->storeUseCase->handle(
            $request->toProjectPayload(),
            $request->toProjectSettingsPayload(),
            $request->file('frontend_logo'),
        );

        return $this->redirectToIndex()->with('success', 'プロジェクトを作成しました');
    }

    public function update(ProjectUpdateRequest $request, int $id): RedirectResponse
    {
        $this->updateUseCase->handle(
            $id,
            $request->toProjectPayload(),
            $request->toProjectSettingsPayload(),
        );

        return $this->redirectToIndex()->with('success', 'プロジェクトを更新しました');
    }

    private function redirectToIndex(): RedirectResponse
    {
        return redirect()->route('system-admin.projects.index');
    }
}
