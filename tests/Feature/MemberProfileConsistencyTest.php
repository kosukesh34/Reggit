<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\MemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MemberProfileConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        config(['smaregi.contract_id' => '']);
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id, 'name' => 'Original']);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'city' => 'Original']);
        ShopSettingStore::setProfileFieldSettings([
            'smaregi_fields' => [],
            'custom_fields' => [
                ['key' => 'choice', 'label' => 'Choice', 'input_type' => 'selectbox', 'values' => ['Tokyo, Japan', 'Osaka']],
                ['key' => 'checks', 'label' => 'Checks', 'input_type' => 'checkbox', 'values' => ['A,B', 'C']],
            ],
        ], (int) $project->id);

        return $user;
    }

    public function test_profile_failure_rolls_back_user_and_member_changes(): void
    {
        $user = $this->user();
        Event::listen('eloquent.updating: '.Member::class, function (): void {
            throw new \RuntimeException('member write failed');
        });
        try {
            app(MemberService::class)->updateProfile($user, ['name' => 'Changed', 'city' => 'Changed']);
            $this->fail('Expected the member write to fail');
        } catch (\RuntimeException $exception) {
            $this->assertSame('member write failed', $exception->getMessage());
        }
        $this->assertSame('Original', $user->fresh()->name);
        $this->assertSame('Original', $user->member->fresh()->city);
    }

    public function test_stale_member_instances_preserve_other_custom_field_edits(): void
    {
        $user = $this->user();
        $staleUser = $user->fresh(['member']);
        app(MemberService::class)->updateProfile($user, ['choice' => 'Osaka']);
        app(MemberService::class)->updateProfile($staleUser, ['checks' => ['C']]);
        $this->assertEquals(['choice' => 'Osaka', 'checks' => ['C']], $user->member->fresh()->profile_extra);
    }

    public function test_choices_with_commas_are_validated_as_complete_values(): void
    {
        $user = $this->user();
        $rules = array_intersect_key(app(MemberService::class)->getProfileValidationRules((int) $user->project_id), array_flip(['choice', 'checks', 'checks.*']));
        $valid = Validator::make(['choice' => 'Tokyo, Japan', 'checks' => ['A,B', 'C']], $rules);
        $this->assertFalse($valid->fails(), json_encode($valid->errors()->all()));
        foreach (['Tokyo', ' Japan'] as $fragment) {
            $this->assertTrue(Validator::make(['choice' => $fragment], $rules)->fails());
        }
        $this->assertTrue(Validator::make(['checks' => ['A']], $rules)->fails());
    }
}
