<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAdminProjectFromQuery
{
    public function handle(Request $request, Closure $next): Response
    {
        $projectKey = $request->query('project_key');
        if (\App\Util\StrHelper::isEmptyStringOrNull($projectKey)) {
            return $next($request);
        }

        $project = Project::where('project_key', $projectKey)->where('is_active', true)->first();
        if ($project !== null) {
            session(['admin_project_id' => $project->id]);
        }

        return $next($request);
    }
}
