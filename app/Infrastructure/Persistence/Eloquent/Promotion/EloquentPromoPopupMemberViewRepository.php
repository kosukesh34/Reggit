<?php

namespace App\Infrastructure\Persistence\Eloquent\Promotion;

use App\Domain\Promotion\Repositories\PromoPopupMemberViewRepository;
use Illuminate\Support\Facades\DB;

final class EloquentPromoPopupMemberViewRepository implements PromoPopupMemberViewRepository
{
    public function lastSeenAt(int $memberId, array $popupIds): array
    {
        if ($popupIds === []) {
            return [];
        }

        return DB::table('promo_popup_member_views')->where('member_id', $memberId)->whereIn('promo_popup_id', $popupIds)
            ->pluck('seen_at', 'promo_popup_id')
            ->mapWithKeys(fn ($seenAt, $popupId): array => [(int) $popupId => new \DateTimeImmutable((string) $seenAt)])
            ->all();
    }

    public function record(int $projectId, int $popupId, int $memberId, \DateTimeInterface $seenAt): void
    {
        DB::table('promo_popup_member_views')->upsert(
            [['project_id' => $projectId, 'promo_popup_id' => $popupId, 'member_id' => $memberId, 'seen_at' => $seenAt, 'created_at' => now(), 'updated_at' => now()]],
            ['promo_popup_id', 'member_id'],
            ['seen_at', 'updated_at'],
        );
    }
}
