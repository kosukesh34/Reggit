<?php

namespace App\UseCases\Admin\SystemNotice;

use App\Models\SystemNotice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/** システム管理者が全店舗向けに出すお知らせの一覧・作成・更新・削除。 */
class SystemNoticeManageUseCase
{
    public function paginate(): LengthAwarePaginator
    {
        return SystemNotice::latest()->paginate(20);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): void
    {
        SystemNotice::create($data);
    }

    /**
     * 内容を変えたら、既読を消して全員にもう一度読んでもらう。
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): void
    {
        SystemNotice::findOrFail($id)->update($data);
        DB::table('system_notice_reads')->where('system_notice_id', $id)->delete();
    }

    public function delete(int $id): void
    {
        SystemNotice::findOrFail($id)->delete();
    }
}
