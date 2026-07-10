<?php

namespace App\UseCases\Announcement\Data;

use App\Models\User;

/**
 * お知らせ既読更新 UseCase への入力。不変。
 */
class AnnouncementMarkReadInput
{
    public function __construct(
        public readonly User $user,
        public readonly int $announcementId,
    ) {}
}
