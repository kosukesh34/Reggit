<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;

class AdminRootController extends Controller
{
    public function __invoke()
    {
        if (! auth('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $admin = auth('admin')->user();
        $first = Project::whereIn('id', $admin->getAllowedProjectIds())
            ->where('is_active', true)
            ->orderBy('name')
            ->first();
        $projectKey = $first
            ? $first->project_key
            : (config('app.default_project_key') ?: Project::where('is_active', true)->orderBy('id')->value('project_key'));

        if ($projectKey === null || $projectKey === '') {
            return redirect()->route('admin.login');
        }

        return redirect()->route('admin.dashboard', ['project_key' => $projectKey]);
    }
}
