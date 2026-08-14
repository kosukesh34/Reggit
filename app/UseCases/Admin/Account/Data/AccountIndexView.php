<?php

namespace App\UseCases\Admin\Account\Data;

use App\Models\AdminUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** アカウント一覧画面の表示用データ。 */
class AccountIndexView
{
    /**
     * @param  LengthAwarePaginator<int, AdminUser>  $adminUsers
     */
    public function __construct(
        public LengthAwarePaginator $adminUsers,
        public AdminUser $me,
        public string $routePrefix,
    ) {}
}
