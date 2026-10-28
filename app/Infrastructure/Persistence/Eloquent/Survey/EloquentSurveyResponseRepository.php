<?php

namespace App\Infrastructure\Persistence\Eloquent\Survey;

use App\Domain\Survey\Repositories\SurveyResponseRepository;
use App\Domain\Survey\SurveyInviteTrigger;
use App\Enums\MemberStatus;
use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use App\Models\SurveyResponse;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

final class EloquentSurveyResponseRepository implements SurveyResponseRepository
{
    public function findAnsweredSurveyIds(int $memberId, array $surveyIds): array
    {
        if ($surveyIds === []) {
            return [];
        }

        return SurveyResponse::withoutGlobalScopes()->where('member_id', $memberId)->whereIn('survey_id', $surveyIds)
            ->whereNotNull('answered_at')->pluck('survey_id')->map(fn ($id): int => (int) $id)->all();
    }

    public function isAnswered(int $surveyId, int $memberId): bool
    {
        return SurveyResponse::withoutGlobalScopes()->where('survey_id', $surveyId)->where('member_id', $memberId)
            ->whereNotNull('answered_at')->exists();
    }

    public function saveAnswer(int $projectId, int $surveyId, int $memberId, array $answers, ?int $npsScore): void
    {
        // 回答依頼だけ送った行があれば、その行に回答を書き込む
        SurveyResponse::withoutGlobalScopes()->updateOrCreate(
            ['survey_id' => $surveyId, 'member_id' => $memberId],
            ['project_id' => $projectId, 'answers' => $answers, 'nps_score' => $npsScore, 'answered_at' => now()],
        );
    }

    public function claimInvitation(int $projectId, int $surveyId, int $memberId): bool
    {
        return SurveyResponse::withoutGlobalScopes()->insertOrIgnore([
            'project_id' => $projectId, 'survey_id' => $surveyId, 'member_id' => $memberId,
            'invited_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]) === 1;
    }

    public function releaseInvitation(int $surveyId, int $memberId): void
    {
        SurveyResponse::withoutGlobalScopes()->where('survey_id', $surveyId)->where('member_id', $memberId)->whereNull('answered_at')->delete();
    }

    public function findTriggeredMemberIdsWithoutResponse(int $projectId, int $surveyId, string $trigger, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $query = match ($trigger) {
            SurveyInviteTrigger::VISIT => DB::table('member_visits')->select('member_visits.member_id as member_id')
                ->where('member_visits.project_id', $projectId)->whereBetween('member_visits.visited_at', [$from, $to]),
            SurveyInviteTrigger::ORDER => DB::table('orders')->join('members', fn (JoinClause $join) => $join->on('members.user_id', '=', 'orders.user_id')->on('members.project_id', '=', 'orders.project_id'))
                ->select('members.id as member_id')->where('orders.project_id', $projectId)->where('orders.status', OrderStatus::COMPLETED)->whereBetween('orders.created_at', [$from, $to]),
            SurveyInviteTrigger::RESERVATION => DB::table('reservations')->join('members', fn (JoinClause $join) => $join->on('members.user_id', '=', 'reservations.user_id')->on('members.project_id', '=', 'reservations.project_id'))
                ->select('members.id as member_id')->where('reservations.project_id', $projectId)->where('reservations.status', ReservationStatus::COMPLETED)->whereBetween('reservations.reserved_at', [$from, $to]),
            SurveyInviteTrigger::REGISTRATION => DB::table('members')->select('members.id as member_id')
                ->where('members.project_id', $projectId)->whereBetween('members.created_at', [$from, $to]),
            default => throw new \InvalidArgumentException('Unknown survey invite trigger: '.$trigger),
        };

        return DB::query()->fromSub($query, 'triggered')
            ->join('members as target', 'target.id', '=', 'triggered.member_id')
            ->where('target.status', MemberStatus::ACTIVE)
            ->whereNull('target.deleted_at')
            ->whereNotExists(fn (Builder $exists) => $exists->selectRaw('1')->from('survey_responses')
                ->whereColumn('survey_responses.member_id', 'triggered.member_id')
                ->where('survey_responses.survey_id', $surveyId))
            ->distinct()
            ->pluck('triggered.member_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
