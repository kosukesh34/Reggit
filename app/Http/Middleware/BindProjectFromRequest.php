<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class BindProjectFromRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $projectKey = $request->route('project_key')
            ?? $request->header('X-Project-Key')
            ?? $request->query('project_key');

        if ($projectKey === null || $projectKey === '') {
            $projectKey = config('app.default_project_key', 'default');
        }

        $project = Project::where('project_key', $projectKey)
            ->where('is_active', true)
            ->first();

        if (!$project) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'プロジェクトが見つかりません。'], 404);
            }
            abort(404, 'プロジェクトが見つかりません。');
        }

        AdminContext::setCurrentProject($project);
        $request->attributes->set('currentProject', $project);
        View::share('currentProject', $project);
        View::share('currentProjectId', $project->id);
        View::share('adminProjectId', $project->id);

        return $next($request);
    }
}
