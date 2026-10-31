<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\ProjectRepository;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/** 独自ショップドメインの Host だけでプロジェクトを解決する。メイン Reggit ドメインでは 404。 */
class EnsureCustomShopDomain
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private StoreContext $storeContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $project = $this->projectRepository->findActiveByCustomShopDomain($request->getHost());
        if ($project === null) {
            abort(404);
        }
        if (! $project->isServiceAvailable()) {
            abort(403, 'このプロジェクトは利用停止または解約されています。');
        }

        AdminContext::setCurrentProject($project);
        $this->storeContext->bind($request, (int) $project->id);
        $request->attributes->set('currentProject', $project);
        View::share('currentProject', $project);
        View::share('currentProjectId', $project->id);
        View::share('adminProjectId', $project->id);

        return $next($request);
    }
}
