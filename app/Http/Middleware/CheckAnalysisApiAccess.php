<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAnalysisApiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->attributes->get('apiToken');
        $project = $request->attributes->get('currentProject');
        if (! $token instanceof ApiToken || $token->scope !== 'analysis' || ! $project instanceof Project || ! $project->analysis_api_enabled) {
            return response()->json(['success' => false, 'message' => '分析APIは利用できません'], 403);
        }
        if ($request->isMethod('POST') && ! $project->analysis_delivery_enabled) {
            return response()->json(['success' => false, 'message' => '分析からの配信は無効です'], 403);
        }

        return $next($request);
    }
}
