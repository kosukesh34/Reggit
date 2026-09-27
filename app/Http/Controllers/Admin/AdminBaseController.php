<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminProjectContextResolver;

class AdminBaseController extends Controller
{
    /** 現在の管理コンテキストからプロジェクトIDを解決する */
    protected function resolveAdminProjectId(): int
    {
        return app(AdminProjectContextResolver::class)->projectId();
    }

    /** 現在の管理コンテキストからプロジェクトキーを解決する */
    protected function resolveAdminProjectKey(): string
    {
        return app(AdminProjectContextResolver::class)->projectKey();
    }

    /** 管理画面ルート用のパラメータ配列を組み立てる */
    protected function buildAdminRouteParams(): array
    {
        return ['project_key' => $this->resolveAdminProjectKey()];
    }
}
