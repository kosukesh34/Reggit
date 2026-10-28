<?php

namespace App\UseCases\Survey;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Domain\Survey\Repositories\SurveyRepository;
use App\Domain\Survey\Repositories\SurveyResponseRepository;
use App\Domain\Survey\SurveyInviteTrigger;
use App\Domain\Survey\ValueObjects\SurveyDefinition;
use App\Services\PersonalAnnouncementSender;
use App\Util\LogHelper;
use Carbon\CarbonImmutable;

/** 来店・注文・予約・会員登録などのきっかけから N 日後の会員へ、アンケートの回答依頼を1回だけ送る（日次）。 */
class SendSurveyInvitationsUseCase
{
    /** 指定日数後の日から、さかのぼって送る日数 */
    private const CATCH_UP_DAYS = 6;

    public function __construct(
        private readonly SurveyRepository $surveys,
        private readonly SurveyResponseRepository $responses,
        private readonly PersonalAnnouncementSender $announcements,
        private readonly FeatureSettings $features,
        private readonly OrderSettings $points,
    ) {}

    /** @return int 送った件数 */
    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $sent = 0;
        foreach ($this->surveys->findActiveWithInvitation() as $survey) {
            // 依頼はお知らせで届けるため、アンケートとお知らせの両方が有効なプロジェクトだけに送る
            if (! $survey->isOpen($now) || ! $this->features->isFeatureEnabled('feature_surveys_enabled', $survey->projectId)
                || ! $this->features->isFeatureEnabled('feature_announcements_enabled', $survey->projectId)) {
                continue;
            }
            // 実行が止まった日の分も拾えるよう少し前まで見る。依頼済みは一意キーで除くので重複しない
            $until = $now->subDays($survey->inviteDelayDays)->endOfDay();
            // ただし作成前のきっかけまでさかのぼって送らない
            $from = max(
                $until->subDays(self::CATCH_UP_DAYS)->startOfDay(),
                CarbonImmutable::instance($survey->createdAt)->subDays($survey->inviteDelayDays)->startOfDay(),
            );
            $memberIds = $this->responses->findTriggeredMemberIdsWithoutResponse($survey->projectId, $survey->id, (string) $survey->inviteTrigger, $from, $until);
            foreach ($memberIds as $memberId) {
                if ($this->invite($survey, $memberId)) {
                    $sent++;
                }
            }
        }

        return $sent;
    }

    private function invite(SurveyDefinition $survey, int $memberId): bool
    {
        if (! $this->responses->claimInvitation($survey->projectId, $survey->id, $memberId)) {
            return false;
        }
        try {
            $this->announcements->send($survey->projectId, $memberId, $survey->title, $this->body($survey), $survey->appPath(), 'アンケートに答える');

            return true;
        } catch (\Throwable $e) {
            // 1件の失敗で他の会員を止めず、この会員は次の実行で送り直す
            $this->responses->releaseInvitation($survey->id, $memberId);
            LogHelper::error('[SurveyInvitation] send failed', ['project_id' => $survey->projectId, 'survey_id' => $survey->id, 'member_id' => $memberId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function body(SurveyDefinition $survey): string
    {
        $thanks = match ($survey->inviteTrigger) {
            SurveyInviteTrigger::ORDER => 'ご注文ありがとうございました。',
            SurveyInviteTrigger::REGISTRATION => '会員登録ありがとうございました。',
            default => 'ご来店ありがとうございました。',
        };

        return $thanks.'アンケートへのご協力をお願いします。'
            // ポイント機能が無効なプロジェクトでは付与しないので、案内もしない
            .($survey->rewardPoints > 0 && $this->points->getUsePoints($survey->projectId) ? "回答すると{$survey->rewardPoints}ポイントをプレゼントします。" : '');
    }
}
