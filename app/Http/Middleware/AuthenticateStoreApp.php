<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\StoreAppCredential;
use App\Util\AdminContext;
use App\Util\StoreContext;
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
            $credential = StoreAppCredential::withoutGlobalScopes()
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
            ->where('service_status', 'active')
            ->first();
        if ($project === null) {
            return response()->json(['error' => 'プロジェクトが見つかりません。'], 404);
        }
        if (! $token->can("project:{$project->id}") || ! $admin->canAccessProject((int) $project->id)) {
            return response()->json(['error' => 'このプロジェクトにはアクセスできません。'], 403);
        }

        if (isset($credential)) {
            abort_unless((int) $credential->project_id === (int) $project->id && $credential->store_id !== null, 403);
            $request->merge(['store_id' => $credential->store_id]);
        }
        AdminContext::setCurrentProject($project);
        app(StoreContext::class)->bind($request, (int) $project->id, $admin);
        app(StoreContext::class)->resolveForWrite((int) $project->id);
        $request->attributes->set('currentProject', $project);
        $request->attributes->set('authenticated_store_app', $app);

        return $next($request);
    }
}
