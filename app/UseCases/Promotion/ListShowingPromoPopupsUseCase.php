<?php

namespace App\UseCases\Promotion;

use App\Domain\Promotion\Policies\PromoPopupAudiencePolicy;
use App\Domain\Promotion\Repositories\PopupViewerRepository;
use App\Domain\Promotion\Repositories\PromoPopupMemberViewRepository;
use App\Domain\Promotion\Repositories\PromoPopupRepository;
use App\Domain\Promotion\ValueObjects\PopupViewer;
use App\Domain\Promotion\ValueObjects\PromoPopupContent;
use App\Domain\Settings\Repositories\FeatureSettings;
use App\Domain\Survey\Repositories\SurveyResponseRepository;

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
        private readonly PromoPopupMemberViewRepository $views,
        private readonly SurveyResponseRepository $responses,
    ) {}

    /** @return list<PromoPopupContent> */
    public function handle(int $projectId, ?int $memberId, ?int $selectedStoreId): array
    {
        if (! $this->features->isFeatureEnabled('feature_popups_enabled', $projectId)) {
            return [];
        }
        $viewer = ($memberId !== null ? $this->viewers->findMember($projectId, $memberId, $selectedStoreId) : null)
            ?? PopupViewer::guest($selectedStoreId);
        $shown = array_values(array_filter(
            $this->popups->findShowing($projectId, now(), self::CANDIDATE_LIMIT),
            fn (PromoPopupContent $popup) => $this->audience->isShownTo($popup, $viewer),
        ));
        if ($viewer->isMember && $memberId !== null) {
            $shown = $this->withoutDoneForMember($shown, $memberId);
        }

        return array_slice($shown, 0, self::LIMIT);
    }

    /**
     * 会員は LINE・アプリ・端末をまたいで表示回数を数え、回答済みアンケートへの案内も外す。
     *
     * @param  list<PromoPopupContent>  $popups
     * @return list<PromoPopupContent>
     */
    private function withoutDoneForMember(array $popups, int $memberId): array
    {
        $lastSeen = $this->views->lastSeenAt($memberId, array_map(fn (PromoPopupContent $popup): int => $popup->id, $popups));
        $surveyIds = array_merge(...array_map(fn (PromoPopupContent $popup): array => $popup->hideWhenAnswered ? $popup->surveyIds() : [], $popups));
        $answered = $this->responses->findAnsweredSurveyIds($memberId, array_values(array_unique($surveyIds)));
        $now = now();

        return array_values(array_filter($popups, fn (PromoPopupContent $popup): bool => $popup->isDue($lastSeen[$popup->id] ?? null, $now)
            && ! ($popup->hideWhenAnswered && array_intersect($popup->surveyIds(), $answered) !== [])));
    }
}
