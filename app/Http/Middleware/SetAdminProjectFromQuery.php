<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\ProjectRepository;
use App\Util\StrHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAdminProjectFromQuery
{
    public function __construct(
        private ProjectRepository $projectRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $projectKey = $request->query('project_key');
        if (StrHelper::isEmptyStringOrNull($projectKey)) {
            return $next($request);
        }

        $project = $this->projectRepository->findActiveByProjectKey($projectKey);
        if ($project !== null) {
            session(['admin_project_id' => $project->id]);
        }

        return $next($request);
    }
}
