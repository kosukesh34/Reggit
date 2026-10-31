<?php

namespace App\UseCases\Admin\Dashboard;

use App\Models\SystemNotice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** 管理画面に出すシステムからのお知らせと、管理者ごとの既読。 */
class AdminSystemNoticeReadUseCase
{
    /** 開いたお知らせを既読にして返す */
    public function open(int $noticeId, int $adminId): SystemNotice
    {
        $notice = SystemNotice::visible()->findOrFail($noticeId);
        DB::table('system_notice_reads')->updateOrInsert(
            ['system_notice_id' => $notice->id, 'admin_user_id' => $adminId],
            ['read_at' => now()]
        );

        return $notice;
    }

    /** @return array{notices: Collection<int, SystemNotice>, readIds: list<int>} */
    public function visible(int $adminId): array
    {
        $notices = SystemNotice::visible()->get();

        return [
            'notices' => $notices,
            'readIds' => DB::table('system_notice_reads')
                ->where('admin_user_id', $adminId)
                ->whereIn('system_notice_id', $notices->pluck('id'))
                ->pluck('system_notice_id')
                ->all(),
        ];
    }
}
