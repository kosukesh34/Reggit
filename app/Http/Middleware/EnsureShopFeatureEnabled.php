<?php

namespace App\Http\Middleware;

use App\Domain\Settings\Repositories\OrderSettings;
use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopFeatureEnabled
{
    public function __construct(
        private OrderSettings $orderSettings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $projectId = AdminContext::requireCurrentProjectId();
        if (! $this->orderSettings->isFeatureEnabled('feature_shop_enabled', $projectId)) {
            abort(404, 'ショップは利用できません。');
        }

        return $next($request);
    }
}
