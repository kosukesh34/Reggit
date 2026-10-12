<?php

namespace App\Services;

use App\Models\Member;
use App\Models\WalletPassUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletPassSyncService
{
    public function markMemberUpdated(Member $member): void
    {
        $passTypeId = (string) config('wallet_pass.type_id', '');
        if ($passTypeId === '') {
            return;
        }

        $serial = 'member-'.(string) $member->id;

        DB::transaction(function () use ($member, $passTypeId, $serial): void {
            DB::table('wallet_pass_update_counters')->insertOrIgnore([
                'pass_type_identifier' => $passTypeId, 'revision' => 0,
            ]);
            // Hold this lock until the marker commits so revisions follow commit order.
            $counter = DB::table('wallet_pass_update_counters')
                ->where('pass_type_identifier', $passTypeId)->lockForUpdate()->first();
            if ($counter === null) {
                throw new \LogicException('Wallet update counter is missing.');
            }
            $revision = (int) $counter->revision + 1;
            DB::table('wallet_pass_update_counters')->where('pass_type_identifier', $passTypeId)
                ->update(['revision' => $revision]);
            WalletPassUpdate::query()->updateOrCreate(
            [
                'member_id' => (int) $member->id,
                'pass_type_identifier' => $passTypeId,
            ],
            [
                'project_id' => (int) $member->project_id,
                'serial_number' => $serial,
                'updated_at' => now(),
                'revision' => $revision,
            ]
            );
        }, 3);

        Log::info('[WalletPass] pass update marker saved', [
            'member_id' => (int) $member->id,
            'member_points' => (int) $member->points,
            'pass_type_identifier' => $passTypeId,
            'serial_number' => $serial,
        ]);
    }
}
