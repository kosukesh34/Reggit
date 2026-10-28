<?php

namespace App\UseCases\Survey;

use App\Domain\Member\Events\PointsEarned;
use App\Domain\Settings\Repositories\FeatureSettings;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Domain\Survey\Exceptions\InvalidSurveyAnswers;
use App\Domain\Survey\Exceptions\SurveyClosed;
use App\Domain\Survey\Policies\SurveyAnswerPolicy;
use App\Domain\Survey\Repositories\SurveyRepository;
use App\Domain\Survey\Repositories\SurveyResponseRepository;
use App\Models\User;
use App\Services\Member\CurrentMemberPrincipal;
use App\Services\Member\MemberAutoTagger;
use App\Services\Member\MemberPointGranter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/** 会員本人の回答を保存し、回答特典ポイントと、NPS 区分・選んだ選択肢のタグを付ける。 */
class SubmitSurveyAnswerUseCase
{
    public function __construct(
        private readonly SurveyRepository $surveys,
        private readonly SurveyResponseRepository $responses,
        private readonly SurveyAnswerPolicy $policy,
        private readonly CurrentMemberPrincipal $principals,
        private readonly MemberPointGranter $points,
        private readonly MemberAutoTagger $tagger,
        private readonly DomainEventDispatcher $events,
        private readonly FeatureSettings $features,
    ) {}

    /**
     * @param  array<array-key, mixed>  $answers
     * @return int 付与したポイント
     *
     * @throws InvalidSurveyAnswers|SurveyClosed|ModelNotFoundException
     */
    public function handle(User $user, int $surveyId, array $answers): int
    {
        $projectId = (int) $user->project_id;
        [$memberId, $granted] = DB::transaction(function () use ($user, $projectId, $surveyId, $answers): array {
            // 本人の行ロックで、同じ会員の二重送信を直列化する
            $owner = $this->principals->lock($user, $projectId);
            abort_if($owner === null, 403, 'ログインし直してください。');
            abort_if($owner->member === null, 403, 'アンケートの回答には会員登録が必要です。');
            $memberId = (int) $owner->member->id;

            $survey = $this->surveys->find($projectId, $surveyId) ?? throw (new ModelNotFoundException)->setModel('survey', [$surveyId]);
            if (! $survey->isOpen(CarbonImmutable::now()) || ! $this->features->isFeatureEnabled('feature_surveys_enabled', $projectId)) {
                throw new SurveyClosed;
            }
            if ($this->responses->isAnswered($survey->id, $memberId)) {
                throw new InvalidSurveyAnswers(['survey' => 'このアンケートは回答済みです。']);
            }
            $clean = $this->policy->normalize($survey->questions, $answers);
            $npsScore = $this->policy->npsScore($survey->questions, $clean);
            $this->responses->saveAnswer($projectId, $survey->id, $memberId, $clean, $npsScore);
            if ($npsScore !== null) {
                $this->tagger->assign($projectId, $memberId, $this->policy->npsTag($npsScore), SurveyAnswerPolicy::NPS_TAGS);
            }
            foreach ($this->policy->optionTags($survey->questions, $clean) as $tag) {
                $this->tagger->assign($projectId, $memberId, $tag);
            }

            return [$memberId, $this->points->grant($projectId, $memberId, $survey->rewardPoints, 'アンケート回答')];
        });
        if ($granted > 0) {
            $this->events->dispatch(new PointsEarned($memberId, $projectId, $granted, null, isBestEffortSync: true));
        }

        return $granted;
    }
}
