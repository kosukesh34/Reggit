<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\StoreAppCredential;
use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStoreApp
{
    public function handle(Request $request, Closure $next, string $app): Response
    {
        $admin = $request->user();
        $token = $admin?->currentAccessToken();
        if (! $admin instanceof AdminUser || $token === null || ! $token->can("app:{$app}")) {
            return response()->json(['error' => 'ログインが必要です。'], 401);
        }

        if ($token instanceof PersonalAccessToken && $token->getKey() !== null) {
            $deviceKey = (string) $request->header('X-Device-Key', '');
            $credential = StoreAppCredential::query()
                ->where('personal_access_token_id', $token->id)
                ->where('app', $app)
                ->first();
            if ($credential === null || $deviceKey === '' || ! hash_equals((string) $credential->device_key_hash, hash('sha256', $deviceKey))) {
                return response()->json(['error' => 'この端末の認証は無効です。管理画面から再登録してください。'], 401);
            }
        }

        $projectKey = (string) ($request->route('project_key') ?? $request->query('project_key', ''));
        $project = Project::query()
            ->where('project_key', $projectKey)
            ->where('is_active', true)
            ->first();
        if ($project === null) {
            return response()->json(['error' => 'プロジェクトが見つかりません。'], 404);
        }
        if (! $token->can("project:{$project->id}") || ! $admin->canAccessProject((int) $project->id)) {
            return response()->json(['error' => 'このプロジェクトにはアクセスできません。'], 403);
        }

        AdminContext::setCurrentProject($project);
        $request->attributes->set('currentProject', $project);

        return $next($request);
    }
}
