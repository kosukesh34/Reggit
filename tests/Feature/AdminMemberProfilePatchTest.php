<?php

namespace Tests\Feature;

use App\Enums\MemberNotificationPreference;
use App\Enums\ProfileCustomFieldInputType;
use App\Http\Requests\Admin\MemberUpdateRequest;
use App\Infrastructure\Persistence\Eloquent\Settings\ProfileSettingStore;
use App\Models\AdminUser;
use App\Models\Member;
use App\Models\Project;
use App\Models\SmaregiMemberProfileSync;
use App\Models\User;
use App\Services\Admin\MemberAdminService;
use App\Services\Smaregi\SmaregiApiService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Tests\TestCase;

class AdminMemberProfilePatchTest extends TestCase
{
    use RefreshDatabase;

    private function member(): Member
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id, 'name' => 'Original', 'email' => 'old@example.com']);
        $this->mock(SmaregiApiService::class)->shouldReceive('getContractIdForProject')->andReturnNull();
        ProfileSettingStore::setProfileFieldSettings(['smaregi_fields' => [], 'custom_fields' => [
            ['key' => 'note', 'label' => 'Note', 'input_type' => ProfileCustomFieldInputType::TEXT, 'smaregi_no' => 1],
            ['key' => 'other', 'label' => 'Other', 'input_type' => ProfileCustomFieldInputType::TEXT, 'smaregi_no' => 2],
            ['key' => 'choices', 'label' => 'Choices', 'input_type' => ProfileCustomFieldInputType::CHECKBOX, 'values' => ['Tea', 'Coffee'], 'smaregi_no' => 3],
        ]], (int) $project->id);

        return Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id,
            'birthday' => '2000-01-02', 'address' => 'Old address', 'postal_code' => '1234567', 'sex' => '2',
            'smaregi_customer_id' => null, 'profile_extra' => ['note' => 'Old', 'other' => 'Keep', 'choices' => ['Tea']]])->load('user');
    }

    private function updateThroughHttp(Member $member, array $fields): void
    {
        $admin = AdminUser::factory()->create(['project_id' => $member->project_id, 'role' => AdminUser::ROLE_ADMIN]);
        $response = $this->actingAs($admin, 'admin')->put(route('admin.members.update', [
            'project_key' => $member->project->project_key, 'id' => $member->id,
        ]), array_merge([
            'member_number' => $member->member_number, 'status' => 'active',
            'notification_preference' => MemberNotificationPreference::ALL,
        ], $fields));
        $response->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_omitted_optional_fields_preserve_existing_user_and_member_values(): void
    {
        $member = $this->member();
        $this->updateThroughHttp($member, []);

        $this->assertSame('Original', $member->user->fresh()->name);
        $this->assertSame('old@example.com', $member->user->fresh()->email);
        $this->assertSame('2000-01-02', $member->fresh()->birthday->format('Y-m-d'));
        $this->assertSame('1234567', $member->fresh()->postal_code);
        $this->assertSame('2', $member->fresh()->sex);
        $this->assertSame(['note' => 'Old', 'other' => 'Keep', 'choices' => ['Tea']], $member->fresh()->profile_extra);
    }

    public function test_explicit_empty_optional_fields_clear_local_values(): void
    {
        $member = $this->member();
        $this->updateThroughHttp($member, ['birthday' => '', 'address' => '', 'postal_code' => '', 'sex' => '']);

        $this->assertNull($member->fresh()->birthday);
        $this->assertNull($member->fresh()->address);
        $this->assertNull($member->fresh()->postal_code);
        $this->assertNull($member->fresh()->sex);
    }

    public function test_unknown_sex_zero_is_saved_without_becoming_null(): void
    {
        $member = $this->member();
        $this->updateThroughHttp($member, ['sex' => '0']);

        $this->assertSame('0', $member->fresh()->sex);
    }

    public function test_custom_partial_update_preserves_unsubmitted_fields(): void
    {
        $member = $this->member();
        $this->updateThroughHttp($member, ['custom_profile' => ['note' => 'Changed']]);

        $this->assertSame(['note' => 'Changed', 'other' => 'Keep', 'choices' => ['Tea']], $member->fresh()->profile_extra);
    }

    public function test_full_form_still_clears_unchecked_checkbox(): void
    {
        $member = $this->member();
        $admin = AdminUser::factory()->create(['project_id' => $member->project_id, 'role' => AdminUser::ROLE_ADMIN]);
        $this->actingAs($admin, 'admin')->get(route('admin.members.edit', [
            'project_key' => $member->project->project_key, 'id' => $member->id,
        ]))->assertOk()->assertSee('name="custom_profile_form" value="1"', false);
        $this->updateThroughHttp($member, ['custom_profile_form' => '1', 'custom_profile' => ['note' => 'Old', 'other' => 'Keep']]);

        $this->assertSame([], $member->fresh()->profile_extra['choices']);
    }

    public function test_raw_json_without_definitions_keeps_replacement_and_empty_clear_behavior(): void
    {
        $member = $this->member();
        ProfileSettingStore::setProfileFieldSettings(['smaregi_fields' => [], 'custom_fields' => []], (int) $member->project_id);
        $this->updateThroughHttp($member, ['profile_extra' => '{"note":"Changed"}']);
        $this->assertSame(['note' => 'Changed'], $member->fresh()->profile_extra);

        $this->updateThroughHttp($member, ['profile_extra' => '{}']);
        $this->assertSame([], $member->fresh()->profile_extra);
    }

    public function test_custom_patch_merges_current_locked_fields_and_records_only_submitted_keys(): void
    {
        $member = $this->member();
        $request = MemberUpdateRequest::create('/admin/member/'.$member->id, 'PUT', ['custom_profile' => ['note' => 'Changed']]);
        $route = new Route('PUT', 'admin/member/{id}', fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $patch = $request->getProfileExtraArray();
        $member->fresh()->update(['smaregi_customer_id' => '42', 'profile_extra' => ['note' => 'Old', 'other' => 'Concurrent', 'choices' => ['Coffee']]]);
        $this->mock(SmaregiApiService::class)->shouldReceive('getContractIdForProject')->andReturn('contract');

        app(MemberAdminService::class)->updateMemberProfile($member, [], ['profile_extra' => $patch], profileExtraIsPatch: true);

        $this->assertSame(['note' => 'Changed', 'other' => 'Concurrent', 'choices' => ['Coffee']], $member->fresh()->profile_extra);
        $requestRow = SmaregiMemberProfileSync::findOrFail($member->id);
        $this->assertSame(['note'], $requestRow->field_keys);
        $this->assertSame(['customFields' => [['no' => 1, 'value' => 'Changed']]], $requestRow->body);
    }
}
