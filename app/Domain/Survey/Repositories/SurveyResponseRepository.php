<?php

namespace App\Domain\Survey\Repositories;

/** 会員ごとの回答と回答依頼の記録。1会員1アンケートにつき1件。 */
interface SurveyResponseRepository
{
    /**
     * @param  list<int>  $surveyIds
     * @return list<int>
     */
    public function findAnsweredSurveyIds(int $memberId, array $surveyIds): array;

    public function isAnswered(int $surveyId, int $memberId): bool;

    /** @param list<mixed> $answers 質問の並びの回答（SurveyAnswerPolicy で正規化したもの） */
    public function saveAnswer(int $projectId, int $surveyId, int $memberId, array $answers, ?int $npsScore): void;

    /** 回答依頼を送る権利を取る。依頼済み・回答済みなら false（重複実行でも1回だけ送る） */
    public function claimInvitation(int $projectId, int $surveyId, int $memberId): bool;

    /** 送信に失敗したとき、次の実行で送り直せるよう依頼の記録を消す（回答済みは消さない） */
    public function releaseInvitation(int $surveyId, int $memberId): void;

    /**
     * 期間内にきっかけ（来店・注文の完了・予約の来店・会員登録）があり、依頼も回答もまだない有効な会員。
     *
     * @param  string  $trigger  SurveyInviteTrigger
     * @return list<int>
     */
    public function findTriggeredMemberIdsWithoutResponse(int $projectId, int $surveyId, string $trigger, \DateTimeInterface $from, \DateTimeInterface $to): array;
}
