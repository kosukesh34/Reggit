<?php

namespace App\UseCases\Admin\Queue\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 管理画面順番待ち一覧 UseCase の出力。不変。
 *
 * @param  Collection<int, int>  $waitingQueueNumbers
 */
class AdminQueueIndexOutput
{
    public function __construct(
        public LengthAwarePaginator $entries,
        public int $waitingCount,
        public int $estimatedWaitMinutes,
        public Collection $waitingQueueNumbers
    ) {}
}
