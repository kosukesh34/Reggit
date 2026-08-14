<?php

namespace App\Services;

use App\Domain\Coupon\Repositories\CouponDeliveryTargetRepository;
use App\Enums\ScheduledDeliveryType;
use App\Models\AfterRegistrationDelivery;
use App\Models\Member;
use App\Models\MemberVisit;
use Carbon\Carbon;

class ScheduledDeliveryTargetService
{
    public function __construct(
        private CouponDeliveryTargetRepository $targetRepository,
    ) {}

    /**
     * @return list<int>
     */
    public function memberIds(AfterRegistrationDelivery $delivery, Carbon $now): array
    {
        $projectId = (int) $delivery->project_id;
        $type = (string) $delivery->schedule_type;

        return match ($type) {
            ScheduledDeliveryType::NEW_USER_MINUTES => $this->newUserMinutes($projectId, $delivery, $now),
            ScheduledDeliveryType::NEW_USER_DAYS => $this->createdOn($projectId, $now->copy()->subDays($delivery->intParam('delay_days', 1))),
            ScheduledDeliveryType::LAST_VISIT => $this->targetRepository->findIdsByLastVisited(
                $projectId,
                'days_after',
                $delivery->intParam('delay_days', 7),
            ),
            ScheduledDeliveryType::VISIT_MILESTONE => $this->visitMilestone($projectId, $delivery, $now),
            ScheduledDeliveryType::RANK_UP_ARRIVAL => $this->targetRepository->findIdsByRank(
                $projectId,
                (string) $delivery->param('reach_rank', ''),
            ),
            ScheduledDeliveryType::TOTAL_POINTS => $this->targetRepository->findIdsByLifetimeGrantedPoints(
                $projectId,
                $delivery->intParam('total_points', 0),
                null,
            ),
            ScheduledDeliveryType::TOTAL_STAMPS => $this->targetRepository->findIdsByStampProgress(
                $projectId,
                null,
                $delivery->intParam('total_stamps', 0),
                false,
            ),
            ScheduledDeliveryType::BIRTHDAY_DAYS_BEFORE => $this->birthdayDaysBefore($projectId, $delivery->intParam('before_days', 7), $now),
            ScheduledDeliveryType::BIRTHDAY_MONTH_FIRST => $now->day === 1
                ? $this->targetRepository->findIdsByBirthdayMonth($projectId, (int) $now->month)
                : [],
            ScheduledDeliveryType::BIRTHDAY_NEXT_MONTH => $this->onDayOfMonth($delivery, $now)
                ? $this->targetRepository->findIdsByBirthdayMonth($projectId, (int) $now->copy()->addMonthNoOverflow()->month)
                : [],
            ScheduledDeliveryType::WEEKLY => (int) $now->dayOfWeek === $delivery->intParam('day_of_week', 1)
                ? $this->targetRepository->findAllIds($projectId)
                : [],
            ScheduledDeliveryType::MONTHLY => $this->onDayOfMonth($delivery, $now)
                ? $this->targetRepository->findAllIds($projectId)
                : [],
            ScheduledDeliveryType::LAST_MONTH_VISITS => $this->onDayOfMonth($delivery, $now)
                ? $this->lastMonthVisits($projectId, $delivery->intParam('last_month_visit_min', 1), $now)
                : [],
            default => [],
        };
    }

    /**
     * @return list<int>
     */
    private function newUserMinutes(int $projectId, AfterRegistrationDelivery $delivery, Carbon $now): array
    {
        $delay = $delivery->resolvedDelayMinutes();
        $from = $now->copy()->subMinutes($delay + 1);
        $to = $now->copy()->subMinutes($delay);

        return Member::query()
            ->where('project_id', $projectId)
            ->where('created_at', '>', $from)
            ->where('created_at', '<=', $to)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function createdOn(int $projectId, Carbon $date): array
    {
        return Member::query()
            ->where('project_id', $projectId)
            ->whereDate('created_at', $date->toDateString())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function birthdayDaysBefore(int $projectId, int $beforeDays, Carbon $now): array
    {
        $target = $now->copy()->addDays($beforeDays);

        return Member::query()
            ->where('project_id', $projectId)
            ->whereNotNull('birthday')
            ->whereMonth('birthday', (int) $target->month)
            ->whereDay('birthday', (int) $target->day)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function visitMilestone(int $projectId, AfterRegistrationDelivery $delivery, Carbon $now): array
    {
        $nth = max(1, $delivery->intParam('visit_times', 1));
        $afterDays = $delivery->intParam('visit_after_days', 0);
        $targetDate = $now->copy()->subDays($afterDays)->toDateString();
        // ponytail: 会員ごとの来店をメモリで数える。来店が増えたら N 回目来店日をカラム化する
        $grouped = MemberVisit::query()
            ->where('project_id', $projectId)
            ->orderBy('visited_at')
            ->get(['member_id', 'visited_at'])
            ->groupBy('member_id');

        $ids = [];
        foreach ($grouped as $memberId => $visits) {
            $nthVisit = $visits->values()->get($nth - 1);
            if ($nthVisit === null || $nthVisit->visited_at === null) {
                continue;
            }
            if ($nthVisit->visited_at->toDateString() === $targetDate) {
                $ids[] = (int) $memberId;
            }
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    private function lastMonthVisits(int $projectId, int $minVisits, Carbon $now): array
    {
        $start = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $end = $now->copy()->subMonthNoOverflow()->endOfMonth();

        return MemberVisit::query()
            ->where('project_id', $projectId)
            ->whereBetween('visited_at', [$start, $end])
            ->select('member_id')
            ->groupBy('member_id')
            ->havingRaw('COUNT(*) >= ?', [$minVisits])
            ->pluck('member_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function onDayOfMonth(AfterRegistrationDelivery $delivery, Carbon $now): bool
    {
        $day = $delivery->param('day_of_month', 1);
        if ($day === 'last_day') {
            return $now->isLastOfMonth();
        }

        return $now->day === (int) $day;
    }
}
