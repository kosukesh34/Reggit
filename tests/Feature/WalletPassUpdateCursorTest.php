<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\WalletPassRegistration;
use App\Models\WalletPassUpdate;
use App\Services\AppleWalletPasskitService;
use App\Services\WalletPassAuthTokenService;
use App\Services\WalletPassSyncService;
use App\UseCases\Member\WalletPass\Data\PasskitListUpdatedSerialsInput;
use App\UseCases\Member\WalletPass\PasskitListUpdatedSerialsUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WalletPassUpdateCursorTest extends TestCase
{
    use RefreshDatabase;

    private const TYPE = 'pass.example.member';

    protected function setUp(): void
    {
        parent::setUp();
        config(['wallet_pass.type_id' => self::TYPE]);
        $this->freezeTime();
    }

    private function member(): Member
    {
        $project = Project::factory()->create();
        $member = Member::factory()->create(['project_id' => $project->id]);
        WalletPassRegistration::create([
            'project_id' => $project->id, 'member_id' => $member->id,
            'pass_type_identifier' => self::TYPE, 'serial_number' => 'member-'.$member->id,
            'device_library_identifier' => 'device', 'push_token' => 'push',
        ]);

        return $member;
    }

    private function updates(?string $cursor = null): array
    {
        return app(AppleWalletPasskitService::class)->listUpdatedSerials('device', self::TYPE, $cursor);
    }

    public function test_repeated_same_second_updates_are_returned_once(): void
    {
        $member = $this->member();
        $sync = app(WalletPassSyncService::class);
        $sync->markMemberUpdated($member);
        $first = $this->updates();
        $sync->markMemberUpdated($member);
        $second = $this->updates($first['lastUpdated']);
        $this->assertSame(['member-'.$member->id], $second['serialNumbers']);
        $this->assertNotSame($first['lastUpdated'], $second['lastUpdated']);
        $this->assertSame([], $this->updates($second['lastUpdated'])['serialNumbers']);
    }

    public function test_other_member_and_registration_added_in_same_second_are_not_skipped(): void
    {
        $one = $this->member();
        $sync = app(WalletPassSyncService::class);
        $sync->markMemberUpdated($one);
        $first = $this->updates();
        $two = $this->member();
        $sync->markMemberUpdated($two);
        $second = $this->updates($first['lastUpdated']);
        $this->assertSame(['member-'.$two->id], $second['serialNumbers']);
        $sync->markMemberUpdated($one);
        $this->assertSame(['member-'.$one->id], $this->updates($second['lastUpdated'])['serialNumbers']);
    }

    public function test_empty_response_preserves_cursor_and_later_same_second_update_is_visible(): void
    {
        $member = $this->member();
        $empty = $this->updates();
        $this->assertSame([], $empty['serialNumbers']);
        app(WalletPassSyncService::class)->markMemberUpdated($member);
        $updated = $this->updates($empty['lastUpdated']);
        $this->assertSame(['member-'.$member->id], $updated['serialNumbers']);
        $this->assertSame($updated['lastUpdated'], $this->updates($updated['lastUpdated'])['lastUpdated']);
    }

    #[TestWith(['2026-10-12 04:00:00'])]
    #[TestWith(['invalid'])]
    #[TestWith(['v2:99999999999999999999999999999'])]
    public function test_legacy_or_invalid_cursor_resyncs_existing_zero_revision_markers(string $cursor): void
    {
        $member = $this->member();
        WalletPassUpdate::create([
            'project_id' => $member->project_id, 'member_id' => $member->id,
            'pass_type_identifier' => self::TYPE, 'serial_number' => 'member-'.$member->id,
            'updated_at' => now(),
        ]);
        $result = $this->updates($cursor);
        $this->assertSame(['member-'.$member->id], $result['serialNumbers']);
        $this->assertSame('v2:0', $result['lastUpdated']);
        $this->assertSame([], $this->updates($result['lastUpdated'])['serialNumbers']);
    }

    public function test_unchanged_passes_return_no_content_and_later_updates_return_serials(): void
    {
        $member = $this->member();
        $sync = app(WalletPassSyncService::class);
        $sync->markMemberUpdated($member);
        $token = app(WalletPassAuthTokenService::class)->createForMember($member);
        $useCase = app(PasskitListUpdatedSerialsUseCase::class);
        $request = fn (?string $cursor) => new PasskitListUpdatedSerialsInput('ApplePass '.$token, 'device', self::TYPE, $cursor);
        $first = $useCase->handle($request(null));
        $this->assertSame(200, $first->status);
        $cursor = $first->json['lastUpdated'];
        $unchanged = $useCase->handle($request($cursor));
        $this->assertSame(204, $unchanged->status);
        $this->assertNull($unchanged->json);
        $sync->markMemberUpdated($member);
        $updated = $useCase->handle($request($cursor));
        $this->assertSame(200, $updated->status);
        $this->assertSame(['member-'.$member->id], $updated->json['serialNumbers']);
    }

    public function test_rollback_preserves_marker_and_counter(): void
    {
        $member = $this->member();
        $sync = app(WalletPassSyncService::class);
        $sync->markMemberUpdated($member);
        $first = $this->updates();
        DB::beginTransaction();
        try {
            $sync->markMemberUpdated($member);
        } finally {
            DB::rollBack();
        }
        $this->assertSame($first['lastUpdated'], $this->updates()['lastUpdated']);
        $this->assertSame(1, (int) DB::table('wallet_pass_update_counters')->value('revision'));
        $sync->markMemberUpdated($member);
        $this->assertSame(['member-'.$member->id], $this->updates($first['lastUpdated'])['serialNumbers']);
    }
}
