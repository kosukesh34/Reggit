<?php

namespace App\Services;

use App\Enums\MemberCouponSource;
use App\Enums\ScheduledDeliveryType;
use App\Jobs\SendAnnouncementNotificationJob;
use App\Models\AfterRegistrationDelivery;
use App\Models\Announcement;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\ScheduledDeliverySend;
use App\Services\Admin\MemberBenefitService;
use App\Util\LogHelper;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ScheduledDeliverySendService
{
    public function __construct(
        private ScheduledDeliveryTargetService $targetService,
        private AnnouncementNotificationService $announcementNotificationService,
        private MemberBenefitService $memberBenefitService,
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
                $deliveryResult = $this->sendToMatchingMembers($delivery, $now);
                $result['sent'] += $deliveryResult['sent'];
                $result['failed'] += $deliveryResult['failed'];
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
        $memberIds = $this->targetService->memberIds($delivery, $now);
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
                    'sent_at' => $now,
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
                    (new SendAnnouncementNotificationJob((int) $announcement->id))->handle($this->announcementNotificationService);
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
