<?php

namespace App\UseCases\Admin\Order\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** 管理画面注文一覧 UseCase の出力。不変。 */
class AdminOrderIndexOutput
{
    public function __construct(
        public LengthAwarePaginator $orders,
        public array $statusCounts
    ) {}
}
