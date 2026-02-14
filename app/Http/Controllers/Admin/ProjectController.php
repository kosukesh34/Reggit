<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectStoreRequest;
use App\Http\Requests\Admin\ProjectUpdateRequest;
use App\Models\Project;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::orderBy('name')->paginate(20);
        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('admin.projects.create');
    }

    public function store(ProjectStoreRequest $request)
    {
        Project::create($request->toProjectPayload());
        return redirect()->route('system-admin.projects.index')->with('success', 'プロジェクトを作成しました');
    }

    public function edit(int $id)
    {
        $project = Project::findOrFail($id);
        return view('admin.projects.edit', compact('project'));
    }

    public function update(ProjectUpdateRequest $request, int $id)
    {
        $project = Project::findOrFail($id);
        $project->update($request->toProjectPayload());
        return redirect()->route('system-admin.projects.index')->with('success', 'プロジェクトを更新しました');
    }
}
