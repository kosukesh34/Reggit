<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * キッチンモニター用。WaitingSystem と同様に project_key のみで店舗を特定する（トークン不要）。
 */
class AuthenticateKitchenToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $projectKey = $request->route('project_key')
            ?? $request->header('X-Project-Key')
            ?? $request->query('project_key');
        $projectKey = is_string($projectKey) ? trim($projectKey) : '';
        if ($projectKey === '' || $projectKey === 'undefined' || $projectKey === 'null') {
            return response()->json(['error' => 'プロジェクトが見つかりません。'], 404);
        }

        $project = Project::query()
            ->where('project_key', $projectKey)
            ->where('is_active', true)
            ->first();
        if ($project === null) {
            return response()->json(['error' => 'プロジェクトが見つかりません。'], 404);
        }

        AdminContext::setCurrentProject($project);
        $request->attributes->set('currentProject', $project);

        return $next($request);
    }
}
