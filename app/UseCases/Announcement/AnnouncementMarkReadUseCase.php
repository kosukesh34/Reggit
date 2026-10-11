<?php

namespace App\UseCases\Announcement;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Services\AnnouncementVisibility;
use App\Services\Member\CurrentMemberPrincipal;
use App\UseCases\Announcement\Data\AnnouncementMarkReadInput;
use App\Util\AdminContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnnouncementMarkReadUseCase
{
    public function __construct(private CurrentMemberPrincipal $principals) {}

    /** @return array{announcement_id: int, read_at: string} */
    public function handle(AnnouncementMarkReadInput $input): array
    {
        return DB::transaction(fn () => $this->markRead($input));
    }

    /** @return array{announcement_id: int, read_at: string} */
    private function markRead(AnnouncementMarkReadInput $input): array
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $user = $this->principals->lock($input->user, $projectId);
        if ($user === null) {
            throw ValidationException::withMessages(['user' => ['ログインし直してください']]);
        }
        $announcement = app(AnnouncementVisibility::class)->apply(Announcement::where('project_id', $projectId), $user)
            ->where('id', $input->announcementId)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->first();

        if ($announcement === null) {
            throw ValidationException::withMessages([
                'announcement_id' => ['対象のお知らせが見つかりません'],
            ]);
        }

        $read = AnnouncementRead::updateOrCreate(
            [
                'project_id' => $projectId,
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
            ],
            ['read_at' => now()]
        );

        return [
            'announcement_id' => $announcement->id,
            'read_at' => $read->read_at->toIso8601String(),
        ];
    }
}
