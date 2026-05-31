<?php

namespace App\Services;

use App\Models\Member;
use App\Models\WalletPassUpdate;
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

        WalletPassUpdate::query()->updateOrCreate(
            [
                'member_id' => (int) $member->id,
                'pass_type_identifier' => $passTypeId,
            ],
            [
                'project_id' => (int) $member->project_id,
                'serial_number' => $serial,
                'updated_at' => now(),
            ]
        );

        Log::info('[WalletPass] pass update marker saved', [
            'member_id' => (int) $member->id,
            'member_points' => (int) $member->points,
            'pass_type_identifier' => $passTypeId,
            'serial_number' => $serial,
        ]);
    }
}
