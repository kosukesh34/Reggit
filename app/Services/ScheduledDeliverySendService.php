<?php

namespace App\Services;

use App\Enums\MemberCouponSource;
use App\Enums\ScheduledDeliveryType;
use App\Jobs\SendAnnouncementNotificationJob;
use App\Models\AfterRegistrationDelivery;
use App\Models\Announcement;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\ScheduledDeliveryAttempt;
use App\Models\ScheduledDeliverySend;
use App\Services\Admin\MemberBenefitService;
use App\Util\LogHelper;
use App\Util\TenantContext;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ScheduledDeliverySendService
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private ScheduledDeliveryTargetService $targetService,
        private AnnouncementNotificationService $announcementNotificationService,
        private MemberBenefitService $memberBenefitService,
        private TenantContext $tenants,
    ) {}

    /** @return array{sent: int, failed: int} */
    public function processDue(Carbon $now): array
    {
        $result = ['sent' => 0, 'failed' => 0];
        $deliveries = AfterRegistrationDelivery::withoutGlobalScopes()
            ->where('is_active', true)
            ->get();

        foreach ($deliveries as $delivery) {
            if (! ScheduledDeliveryType::runsOnCron((string) $delivery->schedule_type)) {
                continue;
            }
            if (ScheduledDeliveryType::needsClock((string) $delivery->schedule_type)
                && ! $this->matchesClock($delivery, $now)) {
                continue;
            }
            try {
                ScheduledDeliveryAttempt::firstOrCreate([
                    'after_registration_delivery_id' => $delivery->id,
                    'occurrence_key' => $now->toDateString(),
                ], [
                    'scheduled_at' => $now,
                    'next_attempt_at' => $now,
                    'delivery_snapshot' => $delivery->getAttributes(),
                ]);
            } catch (\Throwable $exception) {
                report($exception);
                $result['failed']++;
            }
        }

        $attempts = ScheduledDeliveryAttempt::whereNull('failed_at')
            ->where('next_attempt_at', '<=', $now)->orderBy('id')->limit(100)->get();
        foreach ($attempts as $attempt) {
            try {
                $claimed = DB::transaction(function () use ($attempt, $now, &$result): ?ScheduledDeliveryAttempt {
                    $current = ScheduledDeliveryAttempt::whereKey($attempt->id)->lockForUpdate()->first();
                    if ($current === null || $current->failed_at !== null || $current->next_attempt_at->gt($now)) {
                        return null;
                    }
                    if ($current->attempts >= self::MAX_ATTEMPTS) {
                        $current->update(['failed_at' => $now]);
                        $result['failed']++;

                        return null;
                    }
                    $current->update(['attempts' => $current->attempts + 1, 'next_attempt_at' => $now->copy()->addMinutes(5)]);

                    return $current;
                });
            } catch (\Throwable $exception) {
                report($exception);
                $result['failed']++;

                continue;
            }
            if ($claimed === null) {
                continue;
            }
            $owned = fn () => ScheduledDeliveryAttempt::whereKey($claimed->id)->where('attempts', $claimed->attempts);
            try {
                $currentDelivery = AfterRegistrationDelivery::withoutGlobalScopes()->find($claimed->after_registration_delivery_id);
                if ($currentDelivery === null || ! $currentDelivery->is_active) {
                    $owned()->delete();

                    continue;
                }
                $delivery = new AfterRegistrationDelivery;
                $delivery->setRawAttributes($claimed->delivery_snapshot, true);
                $delivery->exists = true;
                $memberIds = $claimed->target_member_ids;
                if ($memberIds === null) {
                    $memberIds = $this->targetService->memberIds($delivery, $claimed->scheduled_at);
                    if ($owned()->update(['target_member_ids' => $memberIds]) === 0) {
                        continue;
                    }
                }
                $deliveryResult = $this->sendToMemberIds($delivery, $claimed->scheduled_at, $memberIds);
            } catch (\Throwable $exception) {
                report($exception);
                $deliveryResult = ['sent' => 0, 'failed' => 1];
            }
            $result['sent'] += $deliveryResult['sent'];
            $result['failed'] += $deliveryResult['failed'];
            try {
                if ($deliveryResult['failed'] === 0) {
                    $owned()->delete();
                } else {
                    $delay = [1, 5, 15, 60][$claimed->attempts - 1] ?? 60;
                    $owned()->update([
                        'next_attempt_at' => $now->copy()->addMinutes($delay),
                        'failed_at' => $claimed->attempts >= self::MAX_ATTEMPTS ? $now : null,
                    ]);
                }
            } catch (\Throwable $exception) {
                report($exception);
                $result['failed']++;
            }
        }

        return $result;
    }

    public function sendRankUpArrival(Member $member, string $newRank): int
    {
        if ($newRank === '') {
            return 0;
        }
        $deliveries = AfterRegistrationDelivery::withoutGlobalScopes()
            ->where('project_id', $member->project_id)
            ->where('is_active', true)
            ->where('schedule_type', ScheduledDeliveryType::RANK_UP_ARRIVAL)
            ->get();

        $count = 0;
        foreach ($deliveries as $delivery) {
            if ((string) $delivery->param('reach_rank', '') !== $newRank) {
                continue;
            }
            if ($this->sendToMember($delivery, $member)) {
                $count++;
            }
        }

        return $count;
    }

    /** @return array{sent: int, failed: int} */
    public function sendToMatchingMembers(AfterRegistrationDelivery $delivery, Carbon $now): array
    {
        return $this->sendToMemberIds($delivery, $now, $this->targetService->memberIds($delivery, $now));
    }

    /**
     * @param  list<int>  $memberIds
     * @return array{sent: int, failed: int}
     */
    private function sendToMemberIds(AfterRegistrationDelivery $delivery, Carbon $now, array $memberIds): array
    {
        $result = ['sent' => 0, 'failed' => 0];
        foreach ($memberIds as $memberId) {
            try {
                $member = Member::query()
                    ->where('project_id', $delivery->project_id)
                    ->find($memberId);
                if ($member !== null && $this->sendToMember($delivery, $member, $now)) {
                    $result['sent']++;
                }
            } catch (\Throwable $exception) {
                report($exception);
                $result['failed']++;
            }
        }

        return $result;
    }

    public function sendToMember(AfterRegistrationDelivery $delivery, Member $member, ?Carbon $now = null): bool
    {
        $now ??= now();
        if (! $delivery->is_active) {
            return false;
        }
        if ((int) $member->project_id !== (int) $delivery->project_id) {
            return false;
        }
        if ($delivery->isCouponOnly() && $delivery->coupon_id === null) {
            return false;
        }
        $sendKey = $delivery->sendKey($now);
        $announcement = null;
        try {
            $claimed = DB::transaction(function () use ($delivery, $member, $now, $sendKey, &$announcement): bool {
                if ($this->alreadySent($delivery, (int) $member->id, $sendKey)) {
                    return false;
                }
                ScheduledDeliverySend::query()->create([
                    'after_registration_delivery_id' => $delivery->id,
                    'member_id' => $member->id,
                    'send_key' => $sendKey,
                    'sent_at' => now(),
                ]);
                if ($delivery->isCouponOnly()) {
                    $this->issueCoupon($delivery, $member, $now, $delivery->shouldSendNotification());

                    return true;
                }
                if ($delivery->coupon_id !== null) {
                    $this->issueCoupon($delivery, $member, $now, false);
                }
                $announcement = $this->createAnnouncement($delivery, $member);

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            return false;
        }
        if ($claimed !== true) {
            return false;
        }
        if ($announcement !== null) {
            DB::afterCommit(function () use ($announcement): void {
                try {
                    (new SendAnnouncementNotificationJob((int) $announcement->id))->handle($this->announcementNotificationService, $this->tenants);
                } catch (\Throwable $exception) {
                    // The persisted pending notification is retried by the scheduler.
                    report($exception);
                }
            });
        }

        LogHelper::info('[ScheduledDelivery] sent', [
            'project_id' => (int) $delivery->project_id,
            'delivery_id' => (int) $delivery->id,
            'member_id' => (int) $member->id,
            'schedule_type' => (string) $delivery->schedule_type,
        ]);

        return true;
    }

    private function matchesClock(AfterRegistrationDelivery $delivery, Carbon $now): bool
    {
        return (int) $now->format('G') === (int) $delivery->push_hour
            && (int) $now->format('i') === (int) $delivery->push_minute;
    }

    private function alreadySent(AfterRegistrationDelivery $delivery, int $memberId, string $sendKey): bool
    {
        return ScheduledDeliverySend::query()
            ->where('after_registration_delivery_id', $delivery->id)
            ->where('member_id', $memberId)
            ->where('send_key', $sendKey)
            ->exists();
    }

    private function createAnnouncement(AfterRegistrationDelivery $delivery, Member $member): ?Announcement
    {
        $title = trim((string) $delivery->title);
        if ($title === '') {
            return null;
        }

        return Announcement::create([
            'project_id' => $member->project_id,
            'title' => $title,
            'body' => $delivery->body,
            'is_pinned' => false,
            'delivery_type' => Announcement::DELIVERY_TYPE_AFTER_REGISTRATION,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'published_at' => now(),
            'sent_at' => now(),
            'send_notification' => $delivery->shouldSendNotification(),
            'notification_pending' => $delivery->shouldSendNotification(),
            'notification_retry_at' => $delivery->shouldSendNotification() ? now() : null,
            'target_member_ids' => [$member->id],
            'delivery_coupon_id' => $delivery->coupon_id,
        ]);
    }

    private function issueCoupon(AfterRegistrationDelivery $delivery, Member $member, Carbon $now, bool $shouldNotify): void
    {
        $couponId = (int) $delivery->coupon_id;
        if ($couponId <= 0) {
            return;
        }
        $grantKey = implode(':', [
            MemberCouponSource::SCHEDULED_DELIVERY,
            (string) $delivery->id,
            $delivery->sendKey($now),
            (string) $member->id,
        ]);
        $issue = function () use ($member, $couponId, $delivery, $grantKey): void {
            $this->memberBenefitService->issueCouponToMember(
                $member,
                $couponId,
                null,
                null,
                MemberCouponSource::SCHEDULED_DELIVERY,
                (string) $delivery->id,
                $grantKey,
            );
        };
        if ($shouldNotify) {
            $issue();

            return;
        }
        MemberCoupon::withoutEvents($issue);
    }
}
