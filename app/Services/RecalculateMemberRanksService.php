<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use Illuminate\Support\Facades\Log;

class RecalculateMemberRanksService
{
    public function __construct(
        private MemberRankSyncService $memberRankSyncService,
    ) {}

    /**
     * @return array{projects: int, members: int, changed: int}
     */
    public function handle(?int $projectId = null): array
    {
        $projects = $projectId !== null
            ? Project::query()->whereKey($projectId)->where('is_active', true)->get()
            : Project::query()->where('is_active', true)->get();

        $memberCount = 0;
        $changed = 0;
        foreach ($projects as $project) {
            $result = $this->processProject((int) $project->id);
            $memberCount += $result['members'];
            $changed += $result['changed'];
        }

        Log::info('[RecalculateMemberRanks] 処理完了', [
            'project_id' => $projectId,
            'projects' => $projects->count(),
            'members' => $memberCount,
            'changed' => $changed,
        ]);

        return [
            'projects' => $projects->count(),
            'members' => $memberCount,
            'changed' => $changed,
        ];
    }

    /**
     * @return array{members: int, changed: int}
     */
    private function processProject(int $projectId): array
    {
        $members = 0;
        $changed = 0;
        Member::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('project_id', $projectId)
            ->where('status', MemberStatus::ACTIVE)
            ->orderBy('id')
            ->chunkById(200, function ($chunk) use (&$members, &$changed): void {
                foreach ($chunk as $member) {
                    $members++;
                    if ($this->memberRankSyncService->sync($member)) {
                        $changed++;
                    }
                }
            });

        return ['members' => $members, 'changed' => $changed];
    }
}
