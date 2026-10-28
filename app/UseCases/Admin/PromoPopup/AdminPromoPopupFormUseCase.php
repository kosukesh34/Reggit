<?php

namespace App\UseCases\Admin\PromoPopup;

use App\Domain\Settings\Repositories\MemberCardPreviewSettings;
use App\Domain\Survey\Repositories\SurveyRepository;
use App\Domain\Survey\ValueObjects\SurveyDefinition;
use App\Services\Admin\PromoPopupAdminService;
use App\Support\AppScreenCatalog;
use App\UseCases\Admin\PromoPopup\Data\AdminPromoPopupFormView;

/** 作成（popupId が NULL）・編集画面に出す内容と選択肢。 */
class AdminPromoPopupFormUseCase
{
    public function __construct(
        private readonly PromoPopupAdminService $popups,
        private readonly MemberCardPreviewSettings $rankSettings,
        private readonly SurveyRepository $surveys,
    ) {}

    public function handle(int $projectId, ?int $popupId): AdminPromoPopupFormView
    {
        $ranks = [];
        foreach ($this->rankSettings->getMemberRankSettings($projectId) as $rank) {
            if (($rank['is_active'] ?? true) && isset($rank['key'])) {
                $ranks[(string) $rank['key']] = (string) ($rank['label'] ?? $rank['key']);
            }
        }
        $stores = $this->popups->storeNames($projectId);

        return new AdminPromoPopupFormView(
            popup: $popupId !== null ? $this->popups->find($projectId, $popupId) : null,
            tags: $this->popups->tagNames($projectId),
            ranks: $ranks,
            stores: count($stores) > 1 ? $stores : [],
            linkOptions: $this->linkOptions($projectId),
        );
    }

    /** @return array<string, string> アプリの画面と、受付中のアンケート */
    private function linkOptions(int $projectId): array
    {
        $options = AppScreenCatalog::paths();
        foreach ($this->surveys->findActive($projectId) as $survey) {
            /** @var SurveyDefinition $survey */
            $options[$survey->appPath()] = 'アンケート「'.$survey->title.'」';
        }

        return $options;
    }
}
