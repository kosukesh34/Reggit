<?php

namespace App\UseCases\Admin\Account\Dto;

use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Collection;

class AccountFormViewDto
{
    /** @param Collection<int, \App\Models\Project> $projects */
    /** @param array<int, string> $creatableRoles */
    /** @param array<string, string> $allPermissions */
    /** @param array<string, array<int, string>> $permissionGroups */
    /** @param array<int, string> $currentPermissions */
    public function __construct(
        public ?AdminUser $adminUser,
        public Collection $projects,
        public array $creatableRoles,
        public array $allPermissions,
        public array $permissionGroups,
        public array $currentPermissions,
        public AdminUser $me,
        public string $routePrefix,
        public array $routeParams
    ) {
    }
}
