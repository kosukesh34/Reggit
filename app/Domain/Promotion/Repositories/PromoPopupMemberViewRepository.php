<?php

namespace App\Domain\Promotion\Repositories;

interface PromoPopupMemberViewRepository
{
    /**
     * @param  list<int>  $popupIds
     * @return array<int, \DateTimeImmutable> ポップアップIDごとの、その会員に最後に表示した日時
     */
    public function lastSeenAt(int $memberId, array $popupIds): array;

    public function record(int $projectId, int $popupId, int $memberId, \DateTimeInterface $seenAt): void;
}
