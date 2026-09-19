<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ProjectDataUpdated implements ShouldBroadcastNow
{
    public function __construct(
        public string $projectKey,
        public string $topic,
    ) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new Channel('project.'.$this->projectKey.'.data')];
    }

    public function broadcastAs(): string
    {
        return 'data.updated';
    }

    /** @return array{topic: string, updated_at: string} */
    public function broadcastWith(): array
    {
        return [
            'topic' => $this->topic,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
