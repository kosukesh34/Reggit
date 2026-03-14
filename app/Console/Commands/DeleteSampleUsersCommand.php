<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * シーダーで作成したテストユーザー（sample*@example.com）のみ削除する。
 * 既存のユーザーは削除しない。
 */
class DeleteSampleUsersCommand extends Command
{
    protected $signature = 'sample-users:delete
                            {--dry-run : 削除せずに対象件数のみ表示}';

    protected $description = 'テスト用サンプルユーザー（sample*@example.com）と関連データを削除する（既存ユーザーは触らない）';

    private const EMAIL_PATTERN = 'sample%@example.com';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $users = User::withTrashed()->where('email', 'like', self::EMAIL_PATTERN)->get();
        if ($users->isEmpty()) {
            $this->info('削除対象のサンプルユーザーはいません。');

            return self::SUCCESS;
        }

        $userIds = $users->pluck('id')->all();
        $memberIds = Member::whereIn('user_id', $userIds)->pluck('id')->all();
        $orderIds = Order::whereIn('user_id', $userIds)->pluck('id')->all();

        $this->info(sprintf(
            '対象: ユーザー %d 件、会員 %d 件、注文 %d 件',
            count($userIds),
            count($memberIds),
            count($orderIds)
        ));

        if ($isDryRun) {
            $this->warn('--dry-run のため削除しません。');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($userIds, $memberIds, $orderIds): void {
            $this->deleteRelatedToMembers($memberIds);
            $this->deleteRelatedToOrders($orderIds);
            $this->deleteRelatedToUsers($userIds);
            Member::whereIn('user_id', $userIds)->delete();
            User::withTrashed()->whereIn('id', $userIds)->forceDelete();
        });

        $this->info('サンプルユーザーと関連データを削除しました。');

        return self::SUCCESS;
    }

    /** @param array<int, int> $memberIds */
    private function deleteRelatedToMembers(array $memberIds): void
    {
        if ($memberIds === []) {
            return;
        }
        DB::table('member_tag_members')->whereIn('member_id', $memberIds)->delete();
        DB::table('member_coupons')->whereIn('member_id', $memberIds)->delete();
        DB::table('point_transactions')->whereIn('member_id', $memberIds)->delete();
        DB::table('member_stamps')->whereIn('member_id', $memberIds)->delete();
        DB::table('coupon_redemptions')->whereIn('member_id', $memberIds)->delete();
        DB::table('queue_entries')->whereIn('member_id', $memberIds)->delete();
    }

    /** @param array<int, int> $orderIds */
    private function deleteRelatedToOrders(array $orderIds): void
    {
        if ($orderIds === []) {
            return;
        }
        DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
        DB::table('point_transactions')->whereIn('order_id', $orderIds)->delete();
        DB::table('coupon_redemptions')->whereIn('order_id', $orderIds)->delete();
        Order::whereIn('id', $orderIds)->delete();
    }

    /** @param array<int, int> $userIds */
    private function deleteRelatedToUsers(array $userIds): void
    {
        if ($userIds === []) {
            return;
        }
        DB::table('reservations')->whereIn('user_id', $userIds)->delete();
        DB::table('announcement_reads')->whereIn('user_id', $userIds)->delete();
        DB::table('user_devices')->whereIn('user_id', $userIds)->delete();
        DB::table('queue_entries')->whereIn('user_id', $userIds)->delete();
    }
}
