<?php

namespace App\UseCases\Admin\Member\Data;

/**
 * 管理画面会員更新 UseCase の入力。不変。
 *
 * @param  array<string, string|null>  $userData  name, kana, email, phone
 * @param  array<string, mixed>  $memberData  member_number, status, rank, birthday, address, ...
 * @param  array{type: 'add'|'subtract'|'set', points: int, description: string|null}|null  $pointAdjustment
 */
class AdminMemberUpdateInput
{
    public function __construct(
        public int $projectId,
        public int $memberId,
        public array $userData,
        public array $memberData,
        public ?array $pointAdjustment,
        public bool $profileExtraIsPatch = false,
    ) {}
}
