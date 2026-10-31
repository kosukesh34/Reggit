<?php

namespace App\UseCases\Admin\Chat;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\StampCard;
use App\Services\Admin\MemberStampAdjuster;
use App\Services\Chat\ChatReplyNotifier;
use App\Services\Chat\ChatStaffInbox;
use App\Services\MemberBenefitService;

/** チャット画面から会員に対して行う操作。会員は StoreAccessScope で見られる範囲から探す。 */
class AdminChatMemberUseCase
{
    public function __construct(
        private ChatReplyNotifier $notifier,
        private ChatStaffInbox $inbox,
        private MemberBenefitService $benefits,
        private MemberStampAdjuster $stamps,
    ) {}

    /**
     * 会話一覧の会員の名前とプロフィール画像。
     *
     * @param  list<int>  $memberIds
     * @return array<int, array{name: string, avatar_url: ?string}>
     */
    public function summaries(array $memberIds): array
    {
        return Member::query()->with('user:id,name,profile_image_path')->whereIn('id', $memberIds)->get(['id', 'user_id'])
            ->mapWithKeys(fn (Member $member): array => [$member->id => [
                'name' => admin_person_name($member->user?->name),
                'avatar_url' => $member->user?->profile_image_path ? asset('storage/'.ltrim($member->user->profile_image_path, '/')) : null,
            ]])->all();
    }

    /** 返信を会員に通知し、店舗側の未読も消す */
    public function notifyReply(int $memberId, string $projectKey): void
    {
        $target = Member::query()->with('user')->findOrFail($memberId);
        $this->notifier->notify($target, $projectKey);
        $this->inbox->markRead((int) $target->project_id, (int) $target->id);
    }

    /** 店舗が会話を開いて読んだ。メニューの未読数から外す */
    public function markRead(int $memberId): void
    {
        $target = Member::query()->findOrFail($memberId);
        $this->inbox->markRead((int) $target->project_id, (int) $target->id);
    }

    /**
     * 会員詳細の手動配布と同じ処理でクーポンを発行する。
     *
     * @return array{name: string, valid_until: ?string}
     *
     * @throws \InvalidArgumentException 発行できない場合
     */
    public function issueCoupon(int $memberId, int $couponId): array
    {
        $target = Member::query()->findOrFail($memberId);
        $issued = $this->benefits->issueCouponToMember($target, Coupon::query()->where('is_active', true)->findOrFail($couponId)->id);

        return [
            'name' => (string) $issued->coupon_name_snapshot,
            'valid_until' => $issued->valid_until?->toIso8601String(),
        ];
    }

    /**
     * 会員詳細の「スタンプ調整」と同じ処理でスタンプを加算する。
     *
     * @return array{name: string, count: int, current: int, required: int}
     *
     * @throws \InvalidArgumentException 付与できない場合
     */
    public function grantStamps(int $projectId, int $memberId, int $stampCardId, int $count): array
    {
        $target = Member::query()->findOrFail($memberId);
        $this->stamps->adjust($projectId, (int) $target->id, $stampCardId, $count, 0, 'チャットで付与');
        $card = StampCard::query()->findOrFail($stampCardId);
        $current = (int) MemberStamp::query()->where('member_id', $target->id)->where('stamp_card_id', $card->id)->value('current_stamps');

        return ['name' => $card->name, 'count' => $count, 'current' => $current, 'required' => (int) $card->required_stamps];
    }
}
