<?php

namespace App\Support;

use App\UseCases\Queue\Data\QueueMeOutput;
use App\UseCases\Queue\Data\QueueQrStatusOutput;
use Illuminate\Http\Request;

/**
 * 順番待ちエントリ ID のセッション永続化（HTTP 層の責務）。
 */
class QueueSessionStore
{
    private const SESSION_QUEUE_ENTRY_KEY_PREFIX = 'mobile_order_queue_entry';

    public function getEntryId(Request $request, int $projectId): ?int
    {
        if (! $request->hasSession()) {
            return null;
        }

        $sessionValue = $request->session()->get($this->getSessionEntryKey($projectId));
        $entryId = (int) $sessionValue;

        return $entryId > 0 ? $entryId : null;
    }

    public function remember(Request $request, int $projectId, int $entryId): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->put($this->getSessionEntryKey($projectId), $entryId);
    }

    public function forget(Request $request, int $projectId): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->forget($this->getSessionEntryKey($projectId));
    }

    public function applyMeOutput(Request $request, QueueMeOutput $result): void
    {
        if ($result->sessionProjectId === null) {
            return;
        }

        if ($result->shouldForgetSession) {
            $this->forget($request, $result->sessionProjectId);

            return;
        }

        if ($result->sessionEntryIdToRemember !== null) {
            $this->remember($request, $result->sessionProjectId, $result->sessionEntryIdToRemember);
        }
    }

    public function applyQrOutput(Request $request, QueueQrStatusOutput $result): void
    {
        $this->remember($request, $result->sessionProjectId, $result->sessionEntryId);
    }

    private function getSessionEntryKey(int $projectId): string
    {
        return self::SESSION_QUEUE_ENTRY_KEY_PREFIX.'_'.$projectId;
    }
}
