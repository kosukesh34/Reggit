<?php

namespace App\UseCases\Announcement;

use App\Repositories\Contracts\ProjectRepository;
use App\Services\CancelledAnnouncementIdStore;

class AnnouncementCancelledIdsUseCase
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private CancelledAnnouncementIdStore $cancelledAnnouncementIdStore,
    ) {}

    /**
     * @return array{cancelled_ids: list<int>, items: list<array{id: int, title: string}>}
     */
    public function handleByProjectKey(string $projectKey): array
    {
        $project = $this->projectRepository->findActiveByProjectKey($projectKey);
        if ($project === null) {
            return ['cancelled_ids' => [], 'items' => []];
        }

        return $this->payload((int) $project->id);
    }

    /**
     * @return array{cancelled_ids: list<int>, items: list<array{id: int, title: string}>}
     */
    public function handleByProjectId(int $projectId): array
    {
        return $this->payload($projectId);
    }

    /**
     * @return array{cancelled_ids: list<int>, items: list<array{id: int, title: string}>}
     */
    private function payload(int $projectId): array
    {
        $items = $this->cancelledAnnouncementIdStore->listItems($projectId);

        return [
            'cancelled_ids' => array_values(array_map(fn (array $item) => $item['id'], $items)),
            'items' => $items,
        ];
    }
}
