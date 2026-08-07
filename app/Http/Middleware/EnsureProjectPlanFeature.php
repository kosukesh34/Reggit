<?php

namespace App\Http\Middleware;

use App\Enums\ProjectPlan;
use App\Support\PlanFeatures;
use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * プロジェクトプランに必要な機能が含まれるかチェックする。
 * 使い方: ->middleware('admin.plan:lottery')
 */
class EnsureProjectPlanFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $project = AdminContext::getCurrentProject();
        if ($project === null) {
            abort(403, 'プロジェクトが選択されていません。');
        }

        $plan = $project->plan ?? ProjectPlan::NORMAL;
        if (PlanFeatures::hasFeature($plan, $feature) === false) {
            abort(403, 'この機能は現在のプランでは利用できません。');
        }

        return $next($request);
    }
}
