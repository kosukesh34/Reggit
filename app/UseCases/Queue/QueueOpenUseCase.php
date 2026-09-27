<?php

namespace App\UseCases\Queue;

use App\Domain\Settings\Repositories\QueueSettings;
use App\Models\QueueEntry;
use App\Repositories\Contracts\ProjectRepository;
use App\Support\QueueLaunchUrlBuilder;
use App\Support\QueueLiffUrlBuilder;
use App\UseCases\Queue\Data\QueueOpenView;
use App\Util\StrHelper;

class QueueOpenUseCase
{
    public function __construct(
        private QueueSettings $shopSettingRepository,
        private ProjectRepository $projectRepository,
    ) {}

    public function handle(string $projectKey, string $token): QueueOpenView
    {
        $projectKey = trim($projectKey);
        $token = trim($token);

        if ($projectKey === '') {
            return new QueueOpenView('error', $projectKey, $token, null, null, '店舗が見つかりませんでした。');
        }

        $project = $this->projectRepository->findActiveByProjectKey($projectKey);

        if ($project === null) {
            return new QueueOpenView('error', $projectKey, $token, null, null, '店舗が見つかりませんでした。');
        }

        $projectId = (int) $project->id;
        $entry = QueueEntry::query()
            ->where('project_id', $projectId)
            ->where('qr_token', $token)
            ->first();

        if ($entry === null && ctype_digit($token)) {
            $entry = QueueEntry::query()
                ->where('project_id', $projectId)
                ->whereKey((int) $token)
                ->first();
        }

        if ($entry === null) {
            return new QueueOpenView('error', $projectKey, $token, null, null, '順番待ちが見つかりませんでした。');
        }

        $effectiveToken = ! StrHelper::isEmptyStringOrNull($entry->qr_token)
            ? (string) $entry->qr_token
            : (string) $entry->id;

        $channelLine = $this->shopSettingRepository->getQueueChannelLine($projectId);
        $channelNative = $this->shopSettingRepository->getQueueChannelNative($projectId);

        $lineUrl = QueueLiffUrlBuilder::build(
            $projectId,
            $projectKey,
            (string) ($entry->qr_token ?? ''),
            (int) $entry->id
        );
        $nativeSchemeUrl = QueueLaunchUrlBuilder::buildNativeSchemeUrl($projectKey, $effectiveToken);

        if ($channelLine && $channelNative) {
            return new QueueOpenView('chooser', $projectKey, $effectiveToken, $lineUrl, $nativeSchemeUrl, null);
        }
        if ($channelNative) {
            return new QueueOpenView('native', $projectKey, $effectiveToken, $lineUrl, $nativeSchemeUrl, null);
        }
        if ($channelLine) {
            return new QueueOpenView('line', $projectKey, $effectiveToken, $lineUrl, $nativeSchemeUrl, null);
        }

        return new QueueOpenView('error', $projectKey, $effectiveToken, null, null, '起動アプリが設定されていません。');
    }
}
