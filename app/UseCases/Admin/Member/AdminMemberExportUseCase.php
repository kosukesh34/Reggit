<?php

namespace App\UseCases\Admin\Member;

use App\Models\Member;
use App\Support\MemberChannel;
use Illuminate\Support\Collection;

class AdminMemberExportUseCase
{
    /**
     * @param  array<string, string>  $filters
     * @return Collection<int, array<string, string>>
     */
    public function handle(int $projectId, array $filters): Collection
    {
        $query = Member::with(['user.devices'])
            ->where('project_id', $projectId);

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('member_number', 'like', '%'.$keyword.'%')
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', '%'.$keyword.'%'));
            });
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status !== '') {
            $query->where('status', $status);
        }

        $channel = (string) ($filters['channel'] ?? '');
        if ($channel === MemberChannel::LINE) {
            $query->whereHas('user', function ($uq) {
                $uq->whereRaw('line_user_id REGEXP ?', ['^U[0-9a-fA-F]{32}$']);
            });
        } elseif ($channel === MemberChannel::APP) {
            $query->whereHas('user.devices');
        } elseif ($channel === MemberChannel::BOTH) {
            $query->whereHas('user', function ($uq) {
                $uq->whereRaw('line_user_id REGEXP ?', ['^U[0-9a-fA-F]{32}$']);
            })->whereHas('user.devices');
        }

        return $query->orderByDesc('created_at')
            ->get()
            ->map(function (Member $m): array {
                $user = $m->user;

                return [
                    'ID' => (string) $m->id,
                    '会員番号' => (string) ($m->member_number ?? ''),
                    'ユーザー名' => (string) ($user !== null ? $user->name : ''),
                    '登録チャネル' => MemberChannel::label($user),
                    'メール' => (string) ($user !== null ? $user->email : ''),
                    '電話番号' => (string) ($user !== null ? $user->phone : ''),
                    'ポイント' => (string) $m->getDisplayPoints(),
                    'ランク' => (string) ($m->rank ?? ''),
                    'ステータス' => $m->status === 'active' ? '有効' : '無効',
                    '登録日' => $m->created_at->format('Y/m/d H:i'),
                ];
            });
    }
}
