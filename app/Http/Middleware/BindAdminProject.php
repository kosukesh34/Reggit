<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\ProjectRepository;
use App\Util\AdminContext;
use App\Util\StrHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class BindAdminProject
{
    public function __construct(
        private ProjectRepository $projectRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $projectKey = $request->route('project_key');
        if (StrHelper::isEmptyStringOrNull($projectKey)) {
            return $next($request);
        }

        $admin = $request->user('admin');
        $project = $this->projectRepository->findActiveByProjectKey($projectKey);

        // project_key が "default" で該当が無い場合、管理者がアクセス可能な先頭プロジェクトを使用する
        if ($project === null && $projectKey === 'default' && $admin !== null) {
            $project = $this->projectRepository->findFirstActiveByIds($admin->getAllowedProjectIds());
        }

        if ($project === null) {
            abort(404, 'プロジェクトが見つかりません。');
        }

        if ($admin === null || $admin->canAccessProject((int) $project->id) === false) {
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
