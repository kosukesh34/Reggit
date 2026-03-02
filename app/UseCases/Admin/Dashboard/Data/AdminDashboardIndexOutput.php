<?php

namespace App\UseCases\Admin\Dashboard\Data;

use Illuminate\Database\Eloquent\Collection;

/**
 * 管理画面ダッシュボード一覧 UseCase の出力。不変。
 *
 * @param  array<string, int|float>  $stats
 * @param  Collection<int, \App\Models\Order>  $recentOrders
 */
class AdminDashboardIndexOutput
{
    public function __construct(
        public array $stats,
        public Collection $recentOrders
    ) {}
}
