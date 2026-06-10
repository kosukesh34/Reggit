<?php

namespace App\Services\Queue;

use Illuminate\Contracts\Session\Session;

class QueueEntrySessionStore
{
    private const SESSION_QUEUE_ENTRY_KEY_PREFIX = 'mobile_order_queue_entry';

    public function __construct(
        private Session $session,
    ) {}

    public function hasSession(): bool
    {
        return $this->session->isStarted() || $this->session->getId() !== '';
    }

    public function getEntryId(int $projectId): ?int
    {
        $entryId = (int) $this->session->get($this->getKey($projectId), 0);

        return $entryId > 0 ? $entryId : null;
    }

    public function putEntryId(int $projectId, int $entryId): void
    {
        $this->session->put($this->getKey($projectId), $entryId);
    }

    public function forgetEntryId(int $projectId): void
    {
        $this->session->forget($this->getKey($projectId));
    }

    private function getKey(int $projectId): string
    {
        return self::SESSION_QUEUE_ENTRY_KEY_PREFIX.'_'.$projectId;
    }
}
