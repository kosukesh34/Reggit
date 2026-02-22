<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Util\AdminContext;
use App\Util\StrHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class BindProjectFromRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $projectKey = $request->route('project_key')
            ?? $request->header('X-Project-Key')
            ?? $request->query('project_key');
        $projectKey = $this->normalizeProjectKey($projectKey);
        $isExplicitProjectKey = !StrHelper::isEmptyStringOrNull($projectKey);

        if ($isExplicitProjectKey === false) {
            $projectKey = $this->extractProjectKeyFromLiffRedirect($request);
            $projectKey = $this->normalizeProjectKey($projectKey);
            $isExplicitProjectKey = !StrHelper::isEmptyStringOrNull($projectKey);
        }

        if ($isExplicitProjectKey === false) {
            $projectKey = $this->extractProjectKeyFromLiffState($request);
            $projectKey = $this->normalizeProjectKey($projectKey);
            $isExplicitProjectKey = !StrHelper::isEmptyStringOrNull($projectKey);
        }

        if ($isExplicitProjectKey === false) {
            $projectKey = config('app.default_project_key', 'default');
        }

        $project = Project::where('project_key', $projectKey)
            ->where('is_active', true)
            ->first();

        if ($project === null && $isExplicitProjectKey === false) {
            $project = Project::where('is_active', true)->orderBy('id')->first();
        }

        if ($project === null) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'プロジェクトが見つかりません。'], 404);
            }
            abort(404, 'プロジェクトが見つかりません。');
        }

        AdminContext::setCurrentProject($project);
        $request->attributes->set('currentProject', $project);
        View::share('currentProject', $project);
        View::share('currentProjectId', $project->id);
        View::share('adminProjectId', $project->id);

        return $next($request);
    }

    private function extractProjectKeyFromLiffRedirect(Request $request): ?string
    {
        $liffRedirectUri = $request->query('liffRedirectUri');
        if (StrHelper::isEmptyStringOrNull($liffRedirectUri)) {
            return null;
        }
        $decoded = urldecode((string) $liffRedirectUri);
        $parts = parse_url($decoded);
        if ($parts === false || !isset($parts['query'])) {
            return null;
        }
        $queryParams = [];
        parse_str($parts['query'], $queryParams);
        $key = $queryParams['project_key'] ?? null;
        return is_string($key) ? $key : null;
    }

    private function extractProjectKeyFromLiffState(Request $request): ?string
    {
        $state = $request->query('state');
        if (StrHelper::isEmptyStringOrNull($state)) {
            return null;
        }
        $decoded = urldecode((string) $state);
        $query = $decoded;
        if (str_contains($decoded, '?')) {
            $parts = parse_url($decoded);
            if ($parts === false || !isset($parts['query'])) {
                return null;
            }
            $query = $parts['query'];
        }
        $queryParams = [];
        parse_str($query, $queryParams);
        $key = $queryParams['project_key'] ?? null;
        return is_string($key) ? $key : null;
    }

    private function normalizeProjectKey($projectKey): ?string
    {
        if (!is_string($projectKey)) {
            return null;
        }
        $trimmed = trim($projectKey);
        if ($trimmed === '' || $trimmed === 'undefined' || $trimmed === 'null') {
            return null;
        }
        return $trimmed;
    }
}
