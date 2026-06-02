<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Services\Line\LineService;
use App\Support\CouponLiffUrlBuilder;
use App\Util\LogHelper;

class CouponNotificationService
{
    public function __construct(
        private PushNotificationService $pushNotificationService,
        private LineService $lineService
    ) {}

    public function notifyIssued(MemberCoupon $memberCoupon): void
    {
        $memberCoupon->loadMissing(['member.user', 'coupon']);
        $member = $memberCoupon->member;
        $coupon = $memberCoupon->coupon;
        if ($member === null || $coupon === null) {
            return;
        }

        $couponName = trim((string) ($memberCoupon->coupon_name_snapshot ?: $coupon->name));
        if ($couponName === '') {
            $couponName = 'クーポン';
        }

        $title = 'クーポンが届きました';
        $body = $couponName.'を受け取りました。クーポンタブからご確認ください。';

        $projectId = (int) $memberCoupon->project_id;
        if (! ShopSetting::isFeatureEnabled('feature_coupons_enabled', $projectId)) {
            LogHelper::info('[Coupon] notification skipped (coupons feature disabled)', [
                'member_coupon_id' => $memberCoupon->id,
                'project_id' => $projectId,
            ]);

            return;
        }

        $projectKey = Project::query()->where('id', $projectId)->value('project_key') ?? 'default';

        $this->sendNativePush($member, $projectId, $title, $body, $memberCoupon);
        $this->sendLinePush($member, $projectId, $projectKey, $title, $body, $couponName);
    }

    private function sendNativePush(Member $member, int $projectId, string $title, string $body, MemberCoupon $memberCoupon): void
    {
        $userId = $member->user_id;
        if ($userId === null) {
            return;
        }

        $result = $this->pushNotificationService->sendToUser(
            $projectId,
            (int) $userId,
            $title,
            $body,
            [
                'type' => 'coupon_issued',
                'tab' => 'coupons',
                'member_coupon_id' => (string) $memberCoupon->id,
                'coupon_id' => (string) $memberCoupon->coupon_id,
                'project_id' => (string) $projectId,
            ]
        );

        if (($result['sent'] ?? 0) === 0 && ($result['target_tokens'] ?? 0) === 0) {
            LogHelper::info('[Coupon] native push skipped (no device tokens)', [
                'member_id' => $member->id,
                'member_coupon_id' => $memberCoupon->id,
            ]);
        }
    }

    private function sendLinePush(
        Member $member,
        int $projectId,
        string $projectKey,
        string $title,
        string $body,
        string $couponName
    ): void {
        $lineUserId = trim((string) ($member->user?->line_user_id ?? ''));
        if ($lineUserId === '' || ! $this->isValidLineUserId($lineUserId)) {
            return;
        }

        if (trim((string) env('LINE_CHANNEL_ACCESS_TOKEN', '')) === '') {
            LogHelper::info('[Coupon] LINE push skipped (token not configured)', [
                'member_id' => $member->id,
            ]);

            return;
        }

        try {
            $couponsUrl = CouponLiffUrlBuilder::buildCouponsTabUrl($projectId, $projectKey);
            $flex = [
                'type' => 'flex',
                'altText' => $title,
                'contents' => [
                    'type' => 'bubble',
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'spacing' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => $title, 'weight' => 'bold', 'size' => 'lg', 'color' => '#06C755'],
                            ['type' => 'text', 'text' => $body, 'wrap' => true, 'size' => 'sm'],
                            ['type' => 'text', 'text' => $couponName, 'wrap' => true, 'size' => 'md', 'weight' => 'bold'],
                        ],
                    ],
                    'footer' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [[
                            'type' => 'button',
                            'style' => 'primary',
                            'height' => 'sm',
                            'action' => [
                                'type' => 'uri',
                                'label' => 'クーポンを見る',
                                'uri' => $couponsUrl,
                            ],
                            'color' => '#06C755',
                        ]],
                    ],
                ],
            ];
            $this->lineService->pushMessage($lineUserId, $flex);
        } catch (\Throwable $e) {
            LogHelper::error('[Coupon] LINE push failed', [
                'member_id' => $member->id,
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isValidLineUserId(string $lineUserId): bool
    {
        return preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
    }
}
