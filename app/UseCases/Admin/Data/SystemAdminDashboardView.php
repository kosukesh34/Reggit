<?php

namespace App\UseCases\Admin\Data;

/** システム管理ダッシュボードの表示用データ。不変。 */
class SystemAdminDashboardView
{
    public function __construct(
        public readonly int $projectsCount,
        public readonly int $accountsCount,
        public readonly int $apiTokensCount
    ) {}
}
