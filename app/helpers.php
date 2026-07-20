<?php

use App\Models\Project;
use App\Util\AdminContext;
use App\Util\StrHelper;

if (! function_exists('admin_front_url')) {
    /**
     * 管理画面から各プロジェクトのエンドユーザー向け LIFF 画面 URL を返す。
     */
    function admin_front_url(?string $projectKey = null): ?string
    {
        if (StrHelper::isEmptyStringOrNull($projectKey)) {
            $routeProjectKey = request()?->route('project_key');
            if (is_string($routeProjectKey) && $routeProjectKey !== '') {
                $projectKey = $routeProjectKey;
            }
        }
        if (StrHelper::isEmptyStringOrNull($projectKey)) {
            $projectKey = AdminContext::getCurrentProject()?->project_key ?? null;
        }
        if (StrHelper::isEmptyStringOrNull($projectKey)) {
            return null;
        }

        return route('liff.project', ['project_key' => $projectKey]);
    }
}

if (! function_exists('admin_route')) {
    /**
     * 管理画面用の route ヘルパー。project_key を自動で補完する。
     */
    function admin_route(string $name, array $params = [], bool $absolute = true): string
    {
        if (! array_key_exists('project_key', $params)) {
            $projectKey = null;
            $routeProjectKey = request()?->route('project_key');
            if (is_string($routeProjectKey) && $routeProjectKey !== '') {
                $projectKey = $routeProjectKey;
            }
            if (StrHelper::isEmptyStringOrNull($projectKey)) {
                $queryProjectKey = request()?->query('project_key');
                if (is_string($queryProjectKey) && $queryProjectKey !== '') {
                    $projectKey = $queryProjectKey;
                }
            }
            if (StrHelper::isEmptyStringOrNull($projectKey)) {
                $projectKey = AdminContext::getCurrentProject()?->project_key ?? null;
            }
            if (StrHelper::isEmptyStringOrNull($projectKey)) {
                $projectId = session('admin_project_id');
                if ($projectId !== null) {
                    $projectKey = Project::where('id', (int) $projectId)->value('project_key');
                }
            }
            if (! StrHelper::isEmptyStringOrNull($projectKey)) {
                $params['project_key'] = $projectKey;
            } else {
                $defaultKey = Project::where('is_active', true)->orderBy('id')->value('project_key');
                $params['project_key'] = $defaultKey ?? (config('app.default_project_key') ?: 'default');
            }
        }

        return route($name, $params, $absolute);
    }
}
