<?php

namespace App\UseCases\Admin\Account\Data;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

/**
 * アカウント作成・編集フォーム画面用の表示データ。
 *
 * @param  Collection<int, Project>  $projects
 * @param  array<int, string>  $creatableRoles
 * @param  array<string, string>  $allPermissions
 * @param  array<string, array<int, string>>  $permissionGroups
 * @param  array<int, string>  $currentPermissions
 */
class AccountFormView
{
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
    ) {}
}
