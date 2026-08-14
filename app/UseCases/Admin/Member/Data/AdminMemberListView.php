<?php

namespace App\UseCases\Admin\Member\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** 管理画面会員一覧の表示用データ。 */
class AdminMemberListView
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public LengthAwarePaginator $members,
        public array $filters,
        public string $lineInviteUrl,
        public string $lineInviteLabel,
    ) {}
}
