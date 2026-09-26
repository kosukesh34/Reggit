<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Member\AdminMemberAttachTagUseCase;
use App\UseCases\Admin\Member\AdminMemberDetachTagUseCase;
use App\UseCases\Admin\Member\AdminMemberRecordVisitUseCase;
use App\UseCases\Admin\Member\AdminMemberUpdateMemoUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberActivityController extends AdminBaseController
{
    public function __construct(
        private AdminMemberRecordVisitUseCase $recordVisitUseCase,
        private AdminMemberAttachTagUseCase $attachTagUseCase,
        private AdminMemberDetachTagUseCase $detachTagUseCase,
        private AdminMemberUpdateMemoUseCase $updateMemoUseCase,
    ) {}

    public function recordVisit(Request $request, string $project_key, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'visited_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $this->recordVisitUseCase->handle(
            $this->resolveAdminProjectId(),
            $id,
            $validated['visited_at'],
            $validated['note'] ?? null,
        );

        return $this->redirectToEdit($id)->with('success', '来店を登録しました');
    }

    public function attachTag(Request $request, string $project_key, int $id): RedirectResponse
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'tag_id' => ['required', 'integer', Rule::exists('member_tags', 'id')->where('project_id', $projectId)],
        ]);
        $this->attachTagUseCase->handle($projectId, $id, (int) $validated['tag_id']);

        return $this->redirectToDetail($id)->with('success', 'タグを付与しました');
    }

    public function detachTag(string $project_key, int $id, int $tagId): RedirectResponse
    {
        $this->detachTagUseCase->handle($this->resolveAdminProjectId(), $id, $tagId);

        return $this->redirectToDetail($id)->with('success', 'タグを解除しました');
    }

    public function updateMemo(Request $request, string $project_key, int $id): RedirectResponse
    {
        $validated = $request->validate(['memo' => ['nullable', 'string', 'max:2000']]);
        $this->updateMemoUseCase->handle($this->resolveAdminProjectId(), $id, $validated['memo'] ?? null);

        return $this->redirectToDetail($id)->with('success', 'メモを更新しました');
    }

    private function redirectToEdit(int $memberId): RedirectResponse
    {
        return redirect()->route('admin.members.edit', array_merge(
            ['id' => $memberId],
            $this->buildAdminRouteParams(),
        ));
    }

    private function redirectToDetail(int $memberId): RedirectResponse
    {
        return redirect()->route('admin.members.detail', array_merge(
            ['id' => $memberId],
            $this->buildAdminRouteParams(),
        ));
    }
}
