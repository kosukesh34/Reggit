<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** URL の project_key と API トークンのプロジェクトが一致することを保証する。 */
class CheckApiProject
{
    public function handle(Request $request, Closure $next): Response
    {
        $projectKey = trim((string) $request->route('project_key'));
        $project = Project::query()
            ->where('project_key', $projectKey)
            ->where('is_active', true)
            ->first();

        if ($project === null) {
            return response()->json(['success' => false, 'message' => 'プロジェクトが見つかりません'], 404);
        }

        $tokenProjectId = AdminContext::getCurrentProjectId();
        if ($tokenProjectId !== null && $tokenProjectId !== (int) $project->id) {
            return response()->json(['success' => false, 'message' => 'プロジェクトキーが一致しません'], 403);
        }

        AdminContext::setCurrentProject($project);
        $request->attributes->set('currentProject', $project);

        return $next($request);
    }
}
