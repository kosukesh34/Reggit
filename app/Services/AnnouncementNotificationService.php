<?php

namespace App\Services;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\Announcement;
use App\Models\Member;
use App\Models\Project;
use App\Services\Line\LineService;
use App\Support\LiffTabUrlBuilder;
use App\Support\MemberChannel;
use App\Util\LogHelper;
use Illuminate\Support\Collection;

class AnnouncementNotificationService
{
    public function __construct(
        private PushNotificationService $pushNotificationService,
        private LineService $lineService,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    /**
     * ネイティブ（OneSignal/FCM）と LINE の両方へお知らせを配信する。
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

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($members as $member) {
            $lineUserId = trim((string) ($member->user?->line_user_id ?? ''));
            if ($lineUserId === '' || ! MemberChannel::isValidLineUserId($lineUserId)) {
                $skipped++;

                continue;
            }

            try {
                $this->lineService->pushMessage(
                    $lineUserId,
                    $this->buildFlexMessage($title, $body, $announcementsUrl),
                    $channelAccessToken
                );
                $sent++;
            } catch (\Throwable $e) {
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
            ->whereHas('user', function ($q): void {
                $q->whereNotNull('line_user_id')
                    ->where('line_user_id', '!=', '')
                    ->whereRaw('line_user_id REGEXP ?', ['^U[0-9a-fA-F]{32}$']);
            })
            ->with('user');

        $targetMemberIds = is_array($announcement->target_member_ids)
            ? array_values(array_filter(array_map('intval', $announcement->target_member_ids), fn ($id) => $id > 0))
            : [];
        $isTargeted = in_array($announcement->delivery_type, [
            Announcement::DELIVERY_TYPE_PERSONAL,
            Announcement::DELIVERY_TYPE_SEGMENT,
            Announcement::DELIVERY_TYPE_AFTER_REGISTRATION,
        ], true);
        if ($isTargeted && $targetMemberIds !== []) {
            $query->whereIn('id', $targetMemberIds);
        }

        return $query->get();
    }

    /**
     * @return array{type: string, altText: string, contents: array<string, mixed>}
     */
    private function buildFlexMessage(string $title, string $body, string $announcementsUrl): array
    {
        $displayTitle = $title !== '' ? $title : 'お知らせ';
        $bodyContents = [
            ['type' => 'text', 'text' => $displayTitle, 'weight' => 'bold', 'size' => 'lg', 'color' => '#06C755', 'wrap' => true],
        ];
        if ($body !== '') {
            $bodyContents[] = ['type' => 'text', 'text' => $body, 'wrap' => true, 'size' => 'sm'];
        }

        return [
            'type' => 'flex',
            'altText' => $displayTitle,
            'contents' => [
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
            ],
        ];
    }
}
