<?php

namespace App\Domain\Promotion\Policies;

use App\Domain\Promotion\PromoPopupAudience;
use App\Domain\Promotion\ValueObjects\PopupViewer;
use App\Domain\Promotion\ValueObjects\PromoPopupContent;

/** ポップアップを、その人・その店舗で表示してよいか。 */
final class PromoPopupAudiencePolicy
{
    public function isShownTo(PromoPopupContent $popup, PopupViewer $viewer): bool
    {
        return $this->matchesStore($popup, $viewer) && $this->matchesAudience($popup, $viewer);
    }

    private function matchesStore(PromoPopupContent $popup, PopupViewer $viewer): bool
    {
        // 店舗を絞ったポップアップは、利用中の店舗が分かる人にだけ出す
        return $popup->storeIds === null || ($viewer->storeId !== null && in_array($viewer->storeId, $popup->storeIds, true));
    }

    private function matchesAudience(PromoPopupContent $popup, PopupViewer $viewer): bool
    {
        return match ($popup->audience) {
            PromoPopupAudience::MEMBERS => $viewer->isMember,
            PromoPopupAudience::GUESTS => ! $viewer->isMember,
            // タグとランクはどちらかに当てはまれば出す（両方とも未指定なら会員全員）
            PromoPopupAudience::SEGMENT => $viewer->isMember && (
                ($popup->audienceTagIds === [] && $popup->audienceRanks === [])
                || array_intersect($popup->audienceTagIds, $viewer->tagIds) !== []
                || ($viewer->rank !== null && in_array($viewer->rank, $popup->audienceRanks, true))
            ),
            default => true,
        };
    }
}
