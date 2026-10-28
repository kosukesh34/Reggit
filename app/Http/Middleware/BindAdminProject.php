<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\ProjectRepository;
use App\Util\AdminContext;
use App\Util\StoreContext;
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

        if ($project === null) {
            abort(404, 'プロジェクトが見つかりません。');
        }

        if ($admin === null || $admin->canAccessProject((int) $project->id) === false) {
            abort(403, 'このプロジェクトへのアクセス権がありません。');
        }

        AdminContext::setCurrentProject($project);
        app(StoreContext::class)->bind($request, (int) $project->id, $admin);
        if ($this->requiresStoreSelection($request)) {
            app(StoreContext::class)->useDefaultForOperation();
        }
        if (app(StoreContext::class)->projectStoreCount > 1 && ! $request->isMethod('GET') && ! $admin->isSystemAdmin() && ! $admin->isProjectAdmin()) {
            $path = (string) $request->route()?->uri();
            $commonSettings = $request->routeIs('admin.rfm.settings.update')
                || ($request->routeIs('admin.settings.*')
                    && ! $request->routeIs('admin.settings.basic.*', 'admin.settings.reservations.update', 'admin.settings.queue.*', 'admin.settings.store-app-credential.*', 'admin.settings.staff.*'));
            if ($commonSettings || preg_match('~/(products|gallery|coupons|stamps|accounts|member-tags|analysis-deliveries|after-registration-deliveries|coupon-deliveries|surveys|promo-popups|acquisition-sources)(/|$)~', $path) || preg_match('~/members/[^/]+$|/members/[^/]+/(tags|transfer-code)(/|$)~', $path)) {
                abort(403, 'プロジェクト共通情報は本部管理者が変更してください。');
            }
        }
        session(['admin_project_id' => $project->id]);
        View::share('currentProject', $project);
        View::share('currentProjectId', $project->id);
        View::share('adminProjectId', $project->id);

        return $next($request);
    }

    private function requiresStoreSelection(Request $request): bool
    {
        if ($request->isMethod('GET') && $request->routeIs('admin.members.*')) {
            return false;
        }
        $path = trim((string) $request->route()?->uri(), '/');
        foreach (['orders', 'reservations', 'queue', 'visits', 'point-transactions', 'stamp-transactions', 'coupon-redemptions'] as $segment) {
            if (preg_match('~(?:^|/)(?:'.$segment.')(?:/|$)~', $path)) {
                return true;
            }
        }

        return false;
    }
}
