<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\Project;
use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->resolveToken($request);
        if ($token === null) {
            return response()->json(['success' => false, 'message' => 'APIトークンが必要です'], 401);
        }

        $hash = hash('sha256', $token);
        $apiToken = ApiToken::where('token_hash', $hash)->first();

        if ($apiToken === null || $apiToken->is_active === false) {
            return response()->json(['success' => false, 'message' => 'APIトークンが無効です'], 401);
        }

        if ($apiToken->expires_at !== null && $apiToken->expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'APIトークンの有効期限が切れています'], 401);
        }

        if ($apiToken->scope === 'analysis' && ! $request->is('api/v1/*/analysis/*')) {
            return response()->json(['success' => false, 'message' => 'このトークンは分析API専用です'], 403);
        }

        $project = Project::find($apiToken->project_id);
        if ($project === null || ! $project->isServiceAvailable()) {
            return response()->json(['success' => false, 'message' => 'プロジェクトが無効です'], 403);
        }

        $requestProjectKey = $request->header('X-Project-Key') ?? $request->query('project_key');
        if ($requestProjectKey && $requestProjectKey !== $project->project_key) {
            return response()->json(['success' => false, 'message' => 'プロジェクトキーが一致しません'], 403);
        }

        $apiToken->forceFill(['last_used_at' => now()])->save();
        AdminContext::setCurrentProject($project);
        $request->attributes->set('apiToken', $apiToken);

        return $next($request);
    }

    private function resolveToken(Request $request): ?string
    {
        $bearer = $request->bearerToken();
        if ($bearer !== null && $bearer !== '') {
            return $bearer;
        }
        $header = $request->header('X-Api-Token');

        return ($header !== null && $header !== '') ? $header : null;
    }
}
