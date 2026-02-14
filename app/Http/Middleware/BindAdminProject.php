<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class BindAdminProject
{
    public function handle(Request $request, Closure $next): Response
    {
        $projectKey = $request->route('project_key');
        if (!$projectKey) {
            return $next($request);
        }

        $project = Project::where('project_key', $projectKey)->where('is_active', true)->first();
        if (!$project) {
            abort(404, 'プロジェクトが見つかりません。');
        }

        $admin = $request->user('admin');
        if (!$admin || !$admin->canAccessProject((int) $project->id)) {
            abort(403, 'このプロジェクトへのアクセス権がありません。');
        }

        AdminContext::setCurrentProject($project);
        session(['admin_project_id' => $project->id]);
        View::share('currentProject', $project);
        View::share('currentProjectId', $project->id);
        View::share('adminProjectId', $project->id);

        return $next($request);
    }
}
