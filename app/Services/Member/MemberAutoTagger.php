<?php

namespace App\Services\Member;

use App\Models\MemberTag;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/** システムが自動で付けるタグ（NPS区分・流入経路など）。無ければ作成する。 */
class MemberAutoTagger
{
    private const COLOR = '#64748b';

    /** @param list<string> $replaces 同じ区分の他のタグ。付け替えるときに外す */
    public function assign(int $projectId, int $memberId, string $name, array $replaces = []): void
    {
        $name = mb_substr($name, 0, 50);
        $others = array_values(array_diff($replaces, [$name]));
        if ($others !== []) {
            $otherIds = MemberTag::withoutGlobalScopes()->where('project_id', $projectId)->whereIn('name', $others)->pluck('id');
            DB::table('member_tag_members')->where('member_id', $memberId)->whereIn('member_tag_id', $otherIds)->delete();
        }
        DB::table('member_tag_members')->insertOrIgnore(['member_id' => $memberId, 'member_tag_id' => $this->tagId($projectId, $name)]);
    }

    private function tagId(int $projectId, string $name): int
    {
        $find = fn (bool $latest) => MemberTag::withoutGlobalScopes()->where('project_id', $projectId)->where('name', $name)
            ->when($latest, fn ($query) => $query->lockForUpdate())->value('id');
        $id = $find(false);
        if ($id !== null) {
            return (int) $id;
        }

        // member_tags に一意キーが無いため、作成だけはプロジェクト単位で直列化して同名タグの重複を防ぐ
        return (int) DB::transaction(function () use ($projectId, $name, $find): int {
            Project::query()->whereKey($projectId)->lockForUpdate()->value('id');

            // ロック付きの読み取りで、外側のトランザクションの開始後に作られたタグも見る
            return (int) ($find(true) ?? MemberTag::withoutGlobalScopes()->create(['project_id' => $projectId, 'name' => $name, 'color' => self::COLOR])->id);
        });
    }
}
