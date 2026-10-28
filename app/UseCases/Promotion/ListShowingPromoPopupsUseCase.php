<?php

namespace App\UseCases\Promotion;

use App\Domain\Promotion\Policies\PromoPopupAudiencePolicy;
use App\Domain\Promotion\Repositories\PopupViewerRepository;
use App\Domain\Promotion\Repositories\PromoPopupRepository;
use App\Domain\Promotion\ValueObjects\PopupViewer;
use App\Domain\Promotion\ValueObjects\PromoPopupContent;
use App\Domain\Settings\Repositories\FeatureSettings;

/** LIFF・ネイティブアプリの起動時に出す宣伝ポップアップの候補（その人・その店舗向けのもの）。 */
class ListShowingPromoPopupsUseCase
{
    /** 表示相手で絞る前に読む件数 */
    private const CANDIDATE_LIMIT = 50;

    private const LIMIT = 10;

    public function __construct(
        private readonly PromoPopupRepository $popups,
        private readonly PopupViewerRepository $viewers,
        private readonly PromoPopupAudiencePolicy $audience,
        private readonly FeatureSettings $features,
    ) {}

    /** @return list<PromoPopupContent> */
    public function handle(int $projectId, ?int $memberId, ?int $selectedStoreId): array
    {
        if (! $this->features->isFeatureEnabled('feature_popups_enabled', $projectId)) {
            return [];
        }
        $viewer = ($memberId !== null ? $this->viewers->findMember($projectId, $memberId, $selectedStoreId) : null)
            ?? PopupViewer::guest($selectedStoreId);
        $shown = array_filter(
            $this->popups->findShowing($projectId, now(), self::CANDIDATE_LIMIT),
            fn (PromoPopupContent $popup) => $this->audience->isShownTo($popup, $viewer),
        );

        return array_slice(array_values($shown), 0, self::LIMIT);
    }
}
