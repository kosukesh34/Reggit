<?php

namespace App\Domain\Promotion\Repositories;

use App\Domain\Promotion\ValueObjects\PopupViewer;

interface PopupViewerRepository
{
    /** 会員のランク・タグ・対象店舗。退会済みなどで見つからなければ NULL */
    public function findMember(int $projectId, int $memberId, ?int $selectedStoreId): ?PopupViewer;
}
