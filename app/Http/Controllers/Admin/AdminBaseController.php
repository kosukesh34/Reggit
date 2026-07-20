<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Util\AdminContext;

class AdminBaseController extends Controller
{
    /** 現在の管理コンテキストからプロジェクトIDを解決する */
    protected function resolveAdminProjectId(): int
    {
        $id = AdminContext::getCurrentProjectId();
        if ($id !== null) {
            return $id;
        }
        $admin = auth('admin')->user();

        return AdminUser::resolveCurrentProjectId(
            $admin,
            (int) session('admin_project_id'),
            request()->input('project_id') ? (int) request()->input('project_id') : null
        );
    }

    /** 現在の管理コンテキストからプロジェクトキーを解決する */
    protected function resolveAdminProjectKey(): string
    {
        $project = AdminContext::getCurrentProject();
        if ($project !== null && trim((string) $project->project_key) !== '') {
            return (string) $project->project_key;
        }

        $configured = \App\Support\ProjectKey::fromConfig();
        if ($configured !== null) {
            return $configured;
        }

        $defaultKey = \App\Models\Project::where('is_active', true)->orderBy('id')->value('project_key');
        if (! is_string($defaultKey) || trim($defaultKey) === '') {
            throw new \RuntimeException('No active project available for admin context.');
        }

        return $defaultKey;
    }

    /** 管理画面ルート用のパラメータ配列を組み立てる */
    protected function buildAdminRouteParams(): array
    {
        return ['project_key' => $this->resolveAdminProjectKey()];
    }
}
