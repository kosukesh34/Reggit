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

        return $project ? $project->project_key : 'default';
    }

    /** 管理画面ルート用のパラメータ配列を組み立てる */
    protected function buildAdminRouteParams(): array
    {
        return ['project_key' => $this->resolveAdminProjectKey()];
    }
}
