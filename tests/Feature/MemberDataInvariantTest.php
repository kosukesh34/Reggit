<?php

namespace Tests\Feature;

use App\Enums\ProfileCustomFieldInputType;
use App\Http\Requests\Admin\MemberUpdateRequest;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Services\MemberProfileRules;
use App\Services\MemberVisitRecorder;
use App\Util\AdminContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MemberDataInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_member_cannot_move_last_visit_backwards(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $member = Member::factory()->create(['project_id' => $project->id, 'last_visited_at' => null]);
        $stale = $member->fresh();
        $recorder = app(MemberVisitRecorder::class);

        $recorder->record($member, Carbon::parse('2026-10-09 12:00:00'), 'manual');
        $recorder->record($stale, Carbon::parse('2026-10-08 12:00:00'), 'manual');

        $this->assertSame('2026-10-09 12:00:00', $member->fresh()->last_visited_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-09 12:00:00', $stale->last_visited_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('member_visits', 2);
        $recorder->record($stale, Carbon::parse('2026-10-10 12:00:00'), 'manual');
        $this->assertSame('2026-10-10 12:00:00', $member->fresh()->last_visited_at->format('Y-m-d H:i:s'));
    }

    public function test_customer_and_admin_rules_preserve_complete_custom_options(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $member = Member::factory()->create(['project_id' => $project->id]);
        ShopSettingStore::setProfileFieldSettings([
            'smaregi_fields' => [],
            'custom_fields' => [
                ['key' => 'city', 'label' => 'City', 'input_type' => ProfileCustomFieldInputType::SELECTBOX, 'values' => ['Tokyo, Japan', 'Kyoto'], 'required' => false],
                ['key' => 'choices', 'label' => 'Choices', 'input_type' => ProfileCustomFieldInputType::CHECKBOX, 'values' => ['Tea, cake', 'Coffee'], 'required' => false],
                ['key' => 'radio', 'label' => 'Radio', 'input_type' => ProfileCustomFieldInputType::RADIO, 'values' => ['Yes, please', 'No'], 'required' => false],
            ],
        ], (int) $project->id);
        $customerRules = array_filter(
            app(MemberProfileRules::class)->getProfileValidationRules((int) $project->id),
            fn ($key) => in_array($key, ['city', 'choices', 'choices.*', 'radio'], true),
            ARRAY_FILTER_USE_KEY,
        );
        $request = MemberUpdateRequest::create('/admin/member/'.$member->id, 'PUT');
        $route = new Route('PUT', 'admin/member/{id}', fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $adminRules = array_filter($request->rules(), fn ($key) => str_starts_with($key, 'custom_profile.'), ARRAY_FILTER_USE_KEY);

        $valid = ['name' => 'Test Member', 'city' => 'Tokyo, Japan', 'choices' => ['Tea, cake'], 'radio' => 'Yes, please'];
        $invalid = ['city' => 'Tokyo', 'choices' => ['Tea'], 'radio' => 'Yes'];
        $this->assertTrue(Validator::make($valid, $customerRules)->passes());
        $this->assertTrue(Validator::make(['custom_profile' => $valid], $adminRules)->passes());
        foreach ([$invalid, ['city' => 'Unknown', 'choices' => ['Unknown'], 'radio' => 'Unknown']] as $input) {
            $this->assertEqualsCanonicalizing(['city', 'choices.0', 'radio'], array_keys(Validator::make($input, $customerRules)->errors()->toArray()));
            $this->assertEqualsCanonicalizing(['custom_profile.city', 'custom_profile.choices.0', 'custom_profile.radio'], array_keys(Validator::make(['custom_profile' => $input], $adminRules)->errors()->toArray()));
        }
    }
}
