<?php

namespace App\Services;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Services\Line\LineService;
use App\Support\CouponLiffUrlBuilder;
use App\Support\NotificationImageUrl;
use App\Util\LogHelper;

class CouponNotificationService
{
    public function __construct(
        private PushNotificationService $pushNotificationService,
        private LineService $lineService,
        private ShopSettingRepository $shopSettingRepository,
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
        if ($this->shopSettingRepository->isFeatureEnabled('feature_coupons_enabled', $projectId) === false) {
            LogHelper::info('[Coupon] notification skipped (coupons feature disabled)', [
                'member_coupon_id' => $memberCoupon->id,
                'project_id' => $projectId,
            ]);

            return;
        }

        $projectKey = Project::query()->where('id', $projectId)->value('project_key');
        $projectKey = is_string($projectKey) ? trim($projectKey) : '';

        $this->sendNativePush($member, $projectId, $title, $body, $memberCoupon);
        if ($projectKey === '') {
            LogHelper::error('[Coupon] LINE push skipped (project_key missing)', [
                'member_id' => $member->id,
                'project_id' => $projectId,
            ]);

            return;
        }
        $this->sendLinePush(
            $member,
            $projectId,
            $projectKey,
            $title,
            $body,
            $couponName,
            $this->resolveLineImageUrl($coupon, $projectId)
        );
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
        string $couponName,
        ?string $imageUrl
    ): void {
        $lineUserId = trim((string) ($member->user?->line_user_id ?? ''));
        if ($lineUserId === '' || ! $this->isValidLineUserId($lineUserId)) {
            return;
        }

        if (! $this->shopSettingRepository->hasLineChannelAccessToken($projectId)) {
            LogHelper::info('[Coupon] LINE push skipped (token not configured)', [
                'member_id' => $member->id,
                'project_id' => $projectId,
            ]);

            return;
        }

        $couponsUrl = CouponLiffUrlBuilder::buildCouponsTabUrl($projectId, $projectKey);
        $channelAccessToken = $this->shopSettingRepository->getLineChannelAccessToken($projectId);

        try {
            $this->lineService->pushMessage(
                $lineUserId,
                $this->buildLineFlexMessage($title, $body, $couponName, $couponsUrl, $imageUrl),
                $channelAccessToken
            );
        } catch (\Throwable $e) {
            if ($imageUrl !== null) {
                LogHelper::warning('[Coupon] LINE push with image failed; retrying without image', [
                    'member_id' => $member->id,
                    'project_id' => $projectId,
                    'line_user_id' => $lineUserId,
                    'image_url' => $imageUrl,
                    'error' => $e->getMessage(),
                ]);
                try {
                    $this->lineService->pushMessage(
                        $lineUserId,
                        $this->buildLineFlexMessage($title, $body, $couponName, $couponsUrl, null),
                        $channelAccessToken
                    );

                    return;
                } catch (\Throwable $retryError) {
                    $e = $retryError;
                }
            }
            LogHelper::error('[Coupon] LINE push failed', [
                'member_id' => $member->id,
                'project_id' => $projectId,
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** クーポン画像 → 店舗デフォルトの順で、LINE が取得できる URL を返す。 */
    private function resolveLineImageUrl(Coupon $coupon, int $projectId): ?string
    {
        foreach ([
            trim((string) ($coupon->image_url ?? '')),
            trim((string) ($this->shopSettingRepository->getCouponDefaultImageUrl($projectId) ?? '')),
        ] as $candidate) {
            if ($candidate === '') {
                continue;
            }
            $resolved = NotificationImageUrl::forLine($candidate);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @return array{type: string, altText: string, contents: array<string, mixed>}
     */
    private function buildLineFlexMessage(
        string $title,
        string $body,
        string $couponName,
        string $couponsUrl,
        ?string $imageUrl
    ): array {
        $bubble = [
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
        ];
        if ($imageUrl !== null) {
            $bubble['hero'] = [
                'type' => 'image',
                'url' => $imageUrl,
                'size' => 'full',
                'aspectRatio' => '20:13',
                'aspectMode' => 'cover',
            ];
        }

        return [
            'type' => 'flex',
            'altText' => $title,
            'contents' => $bubble,
        ];
    }

    private function isValidLineUserId(string $lineUserId): bool
    {
        return preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
    }
}
