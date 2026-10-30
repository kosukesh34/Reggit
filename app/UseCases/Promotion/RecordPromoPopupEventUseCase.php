<?php

namespace App\UseCases\Promotion;

use App\Domain\Promotion\PromoPopupEvent;
use App\Domain\Promotion\Repositories\PromoPopupMemberViewRepository;
use App\Domain\Promotion\Repositories\PromoPopupStatsRepository;
use App\Domain\Settings\Repositories\FeatureSettings;

/** アプリでポップアップが表示された・押された・閉じられたことを数える。 */
class RecordPromoPopupEventUseCase
{
    public function __construct(
        private readonly PromoPopupStatsRepository $stats,
        private readonly FeatureSettings $features,
        private readonly PromoPopupMemberViewRepository $views,
    ) {}

    /** @return bool 記録したか（機能が無効・他のプロジェクト・表示していないポップアップなら false） */
    public function handle(int $projectId, int $popupId, string $event, ?int $memberId = null): bool
    {
        $now = now();
        if (! $this->features->isFeatureEnabled('feature_popups_enabled', $projectId) || ! $this->stats->record($projectId, $popupId, $event, $now)) {
            return false;
        }
        if ($event === PromoPopupEvent::VIEW && $memberId !== null) {
            $this->views->record($projectId, $popupId, $memberId, $now);
        }

        return true;
    }
}
