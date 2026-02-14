<?php

namespace App\UseCases\Admin\Dto;

class SystemAdminDashboardDto
{
    public function __construct(
        public readonly int $projectsCount,
        public readonly int $accountsCount,
        public readonly int $apiTokensCount
    ) {
    }
}
