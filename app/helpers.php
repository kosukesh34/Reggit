<?php

use App\Util\AdminContext;
use App\Models\Project;

if (!function_exists('admin_route')) {
    /**
     * 管理画面用の route ヘルパー。project_key を自動で補完する。
     */
    function admin_route(string $name, array $params = [], bool $absolute = true): string
    {
        if (!array_key_exists('project_key', $params)) {
            $projectKey = null;
            $routeProjectKey = request()?->route('project_key');
            if (is_string($routeProjectKey) && $routeProjectKey !== '') {
                $projectKey = $routeProjectKey;
            }
            if (!$projectKey) {
                $queryProjectKey = request()?->query('project_key');
                if (is_string($queryProjectKey) && $queryProjectKey !== '') {
                    $projectKey = $queryProjectKey;
                }
            }
            if (!$projectKey) {
                $projectKey = AdminContext::getCurrentProject()?->project_key ?? null;
            }
            if (!$projectKey) {
                $projectId = session('admin_project_id');
                if ($projectId) {
                    $projectKey = Project::where('id', (int) $projectId)->value('project_key');
                }
            }
            if ($projectKey) {
                $params['project_key'] = $projectKey;
            } else {
                $defaultKey = Project::where('is_active', true)->orderBy('id')->value('project_key');
                if ($defaultKey !== null) {
                    $params['project_key'] = $defaultKey;
                }
            }
        }
        return route($name, $params, $absolute);
    }
}
