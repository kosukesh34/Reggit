<?php

namespace App\Http\Middleware;

use App\Domain\Settings\Repositories\KitchenAccessSettings;
use App\Models\Project;
use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * キッチンモニター用。店舗ごとに発行した秘密トークンを検証する。
 */
class AuthenticateKitchenToken
{
    public function __construct(private KitchenAccessSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $projectKey = $request->route('project_key')
            ?? $request->header('X-Project-Key')
            ?? $request->query('project_key');
        $projectKey = is_string($projectKey) ? trim($projectKey) : '';
        if ($projectKey === '' || $projectKey === 'undefined' || $projectKey === 'null') {
            return response()->json(['error' => 'プロジェクトが見つかりません。'], 404);
        }

        $project = Project::query()
            ->where('project_key', $projectKey)
            ->where('is_active', true)
            ->first();
        if ($project === null) {
            return response()->json(['error' => 'プロジェクトが見つかりません。'], 404);
        }

        $expected = $this->settings->getKitchenAccessToken((int) $project->id);
        $provided = (string) $request->header('X-Store-Token', '');
        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['error' => '店舗端末の認証が必要です'], 401);
        }

        AdminContext::setCurrentProject($project);
        $request->attributes->set('currentProject', $project);

        return $next($request);
    }
}
