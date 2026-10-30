<?php

namespace App\UseCases\Admin\Member;

use App\Models\Member;
use App\Services\Admin\MemberAdminListQueryService;
use App\Support\AdminMemberIdentifier;
use App\Support\MemberChannel;
use Illuminate\Support\Collection;

class AdminMemberExportUseCase
{
    public function __construct(
        private MemberAdminListQueryService $memberList,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string>>
     */
    public function handle(int $projectId, array $filters): Collection
    {
        return $this->memberList->getFilteredMembers($projectId, $filters)
            ->map(function (Member $m): array {
                $user = $m->user;

                return [
                    AdminMemberIdentifier::label($m->project_id) => AdminMemberIdentifier::value($m),
                    'ユーザー名' => (string) ($user !== null ? $user->name : ''),
                    '登録チャネル' => MemberChannel::label($user),
                    'メール' => (string) ($user !== null ? $user->email : ''),
                    '電話番号' => (string) ($user !== null ? $user->phone : ''),
                    'ポイント' => (string) $m->getDisplayPoints(),
                    'ランク' => (string) ($m->rank ?? ''),
                    '最終来店' => $m->last_visited_at !== null ? $m->last_visited_at->format('Y/m/d H:i') : '',
                    '登録日' => $m->created_at->format('Y/m/d H:i'),
                ];
            });
    }
}
