<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectStoreRequest;
use App\Http\Requests\Admin\ProjectUpdateRequest;
use App\UseCases\Admin\Project\ProjectEditUseCase;
use App\UseCases\Admin\Project\ProjectIndexUseCase;
use App\UseCases\Admin\Project\ProjectStoreUseCase;
use App\UseCases\Admin\Project\ProjectUpdateUseCase;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectIndexUseCase $indexUseCase,
        private ProjectStoreUseCase $storeUseCase,
        private ProjectEditUseCase $editUseCase,
        private ProjectUpdateUseCase $updateUseCase
    ) {
    }

    public function index()
    {
        $projects = $this->indexUseCase->handle();
        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('admin.projects.create');
    }

    public function store(ProjectStoreRequest $request)
    {
        $this->storeUseCase->handle($request->toProjectPayload());
        return redirect()->route('system-admin.projects.index')->with('success', 'プロジェクトを作成しました');
    }

    public function edit(int $id)
    {
        $project = $this->editUseCase->handle($id);
        return view('admin.projects.edit', compact('project'));
    }

    public function update(ProjectUpdateRequest $request, int $id)
    {
        $this->updateUseCase->handle($id, $request->toProjectPayload());
        return redirect()->route('system-admin.projects.index')->with('success', 'プロジェクトを更新しました');
    }
}
