<?php

namespace App\Services;

use App\Domain\Settings\Repositories\LineConnectionSettings;
use App\Enums\MemberNotificationPreference;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
use App\Services\Line\LineService;
use App\Support\LiffTabUrlBuilder;
use App\Support\MemberChannel;
use App\Support\NotificationImageUrl;
use App\Util\LogHelper;
use Illuminate\Support\Collection;

class AnnouncementNotificationService
{
    public function __construct(
        private PushNotificationService $pushNotificationService,
        private LineService $lineService,
        private LineConnectionSettings $shopSettingRepository,
    ) {}

    /**
     * ネイティブ（FCM）と LINE の両方へお知らせを配信する。
     *
     * @return array{
     *     target_tokens: int,
     *     sent: int,
     *     failed: int,
     *     skipped: int,
     *     notification_ids: list<string>,
     *     line_sent: int,
     *     line_failed: int,
     *     line_skipped: int
     * }
     */
    public function send(Announcement $announcement): array
    {
        $nativeResult = $this->pushNotificationService->sendAnnouncement($announcement);
        $lineResult = $this->sendLinePushes($announcement);

        return array_merge($nativeResult, $lineResult);
    }

    /**
     * @return array{line_sent: int, line_failed: int, line_skipped: int}
     */
    private function sendLinePushes(Announcement $announcement): array
    {
        if (! $this->shopSettingRepository->hasLineChannelAccessToken((int) $announcement->project_id)) {
            LogHelper::info('[Announcement] LINE push skipped (token not configured)', [
                'announcement_id' => $announcement->id,
                'project_id' => $announcement->project_id,
            ]);

            return ['line_sent' => 0, 'line_failed' => 0, 'line_skipped' => 0];
        }

        $members = $this->resolveTargetLineMembers($announcement);
        if ($members->isEmpty()) {
            return ['line_sent' => 0, 'line_failed' => 0, 'line_skipped' => 0];
        }

        $projectId = (int) $announcement->project_id;
        $channelAccessToken = $this->shopSettingRepository->getLineChannelAccessToken($projectId);
        $projectKey = Project::query()->where('id', $projectId)->value('project_key');
        $projectKey = is_string($projectKey) ? trim($projectKey) : '';
        if ($projectKey === '') {
            LogHelper::error('[Announcement] LINE push skipped (project_key missing)', [
                'announcement_id' => $announcement->id,
                'project_id' => $projectId,
            ]);

            return ['line_sent' => 0, 'line_failed' => 0, 'line_skipped' => $members->count()];
        }
        $announcementsUrl = LiffTabUrlBuilder::build(
            $projectId,
            $projectKey,
            'announcements'
        );
        $title = trim((string) $announcement->title);
        $body = trim((string) ($announcement->body ?? ''));
        $rawImageUrl = trim((string) ($announcement->image_url ?? ''));
        $imageUrl = NotificationImageUrl::forLine($rawImageUrl !== '' ? $rawImageUrl : null);
        if ($rawImageUrl !== '' && $imageUrl === null) {
            LogHelper::warning('[Announcement] LINE image URL skipped (not publicly accessible via HTTPS)', [
                'announcement_id' => $announcement->id,
                'image_url' => $rawImageUrl,
            ]);
        }

        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $includeImages = true;

        foreach ($members as $member) {
            $lineUserId = trim((string) ($member->user?->line_user_id ?? ''));
            if ($lineUserId === '' || ! MemberChannel::isValidLineUserId($lineUserId)) {
                $skipped++;

                continue;
            }

            $payload = $this->buildLinePayload(
                $title,
                $body,
                $announcementsUrl,
                $includeImages ? $imageUrl : null,
                $announcement,
                $projectId,
                $projectKey,
                $includeImages,
            );

            try {
                $this->lineService->pushMessage($lineUserId, $payload, $channelAccessToken);
                $sent++;
            } catch (\Throwable $e) {
                if ($includeImages && $this->linePayloadHasHero($payload)) {
                    LogHelper::warning('[Announcement] LINE push with image failed; retrying without image', [
                        'announcement_id' => $announcement->id,
                        'member_id' => $member->id,
                        'line_user_id' => $lineUserId,
                        'error' => $e->getMessage(),
                    ]);
                    try {
                        $retryPayload = $this->buildLinePayload(
                            $title,
                            $body,
                            $announcementsUrl,
                            null,
                            $announcement,
                            $projectId,
                            $projectKey,
                            false,
                        );
                        $this->lineService->pushMessage($lineUserId, $retryPayload, $channelAccessToken);
                        $sent++;
                        $includeImages = false;

                        continue;
                    } catch (\Throwable $retryError) {
                        $e = $retryError;
                    }
                }
                $failed++;
                LogHelper::error('[Announcement] LINE push failed', [
                    'announcement_id' => $announcement->id,
                    'project_id' => $announcement->project_id,
                    'member_id' => $member->id,
                    'line_user_id' => $lineUserId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['line_sent' => $sent, 'line_failed' => $failed, 'line_skipped' => $skipped];
    }

    /** @return Collection<int, Member> */
    private function resolveTargetLineMembers(Announcement $announcement): Collection
    {
        $query = Member::query()
            ->where('project_id', (int) $announcement->project_id)
            ->where(function ($query) use ($announcement): void {
                $query->whereNull('notification_preference')
                    ->orWhereIn(
                        'notification_preference',
                        MemberNotificationPreference::eligibleValues($announcement->delivery_coupon_id !== null),
                    );
            })
            ->whereHas('user', function ($q): void {
                $q->whereNotNull('line_user_id')
                    ->where('line_user_id', '!=', '')
                    ->whereRaw('line_user_id REGEXP ?', ['^U[0-9a-fA-F]{32}$']);
            })
            ->with('user');

        $hasTargetSnapshot = is_array($announcement->target_member_ids);
        $targetMemberIds = $hasTargetSnapshot
            ? array_values(array_filter(array_map('intval', $announcement->target_member_ids), fn ($id) => $id > 0))
            : [];
        if ($hasTargetSnapshot) {
            if ($targetMemberIds === []) {
                return collect();
            }
            $query->whereIn('id', $targetMemberIds);
        }

        return $query->get();
    }

    /**
     * @return array{type: string, altText: string, contents: array<string, mixed>}|list<array{type: string, altText: string, contents: array<string, mixed>}>
     */
    private function buildLinePayload(
        string $title,
        string $body,
        string $announcementsUrl,
        ?string $imageUrl,
        Announcement $announcement,
        int $projectId,
        string $projectKey,
        bool $includeCouponImage,
    ): array {
        $messages = [$this->buildFlexMessage($title, $body, $announcementsUrl, $imageUrl)];
        $couponMessage = $this->couponLineMessage($announcement, $projectId, $projectKey, $includeCouponImage);
        if ($couponMessage !== null) {
            $messages[] = $couponMessage;
        }

        return count($messages) === 1 ? $messages[0] : $messages;
    }

    /**
     * @return array{type: string, altText: string, contents: array<string, mixed>}|null
     */
    private function couponLineMessage(
        Announcement $announcement,
        int $projectId,
        string $projectKey,
        bool $includeImage,
    ): ?array {
        if ($announcement->delivery_coupon_id === null) {
            return null;
        }
        $coupon = Coupon::query()
            ->where('project_id', $projectId)
            ->find((int) $announcement->delivery_coupon_id);
        if ($coupon === null) {
            return null;
        }

        return resolve(CouponNotificationService::class)->buildIssuedLineFlex(
            $coupon,
            $projectId,
            $projectKey,
            $includeImage,
        );
    }

    /**
     * @param  array{type?: string, contents?: array<string, mixed>}|list<array{type?: string, contents?: array<string, mixed>}>  $payload
     */
    private function linePayloadHasHero(array $payload): bool
    {
        if (array_key_exists('type', $payload)) {
            return isset($payload['contents']['hero']);
        }
        foreach ($payload as $message) {
            if (isset($message['contents']['hero'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{type: string, altText: string, contents: array<string, mixed>}
     */
    private function buildFlexMessage(string $title, string $body, string $announcementsUrl, ?string $imageUrl): array
    {
        $displayTitle = $title !== '' ? $title : 'お知らせ';
        $bodyContents = [
            ['type' => 'text', 'text' => $displayTitle, 'weight' => 'bold', 'size' => 'lg', 'color' => '#06C755', 'wrap' => true],
        ];
        if ($body !== '') {
            $bodyContents[] = ['type' => 'text', 'text' => $body, 'wrap' => true, 'size' => 'sm'];
        }

        $bubble = [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'md',
                'contents' => $bodyContents,
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
                        'label' => 'お知らせを見る',
                        'uri' => $announcementsUrl,
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
            'altText' => $displayTitle,
            'contents' => $bubble,
        ];
    }
}
