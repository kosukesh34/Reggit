<?php

namespace Tests\Feature;

use App\Enums\ProfileCustomFieldInputType;
use App\Http\Requests\Admin\MemberUpdateRequest;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AdminCustomProfileValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_rules_preserve_complete_custom_options(): void
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
        $request = MemberUpdateRequest::create('/admin/member/'.$member->id, 'PUT');
        $route = new Route('PUT', 'admin/member/{id}', fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $adminRules = array_filter($request->rules(), fn ($key) => str_starts_with($key, 'custom_profile.'), ARRAY_FILTER_USE_KEY);

        $valid = ['city' => 'Tokyo, Japan', 'choices' => ['Tea, cake'], 'radio' => 'Yes, please'];
        $invalid = ['city' => 'Tokyo', 'choices' => ['Tea'], 'radio' => 'Yes'];
        $this->assertTrue(Validator::make(['custom_profile' => $valid], $adminRules)->passes());
        foreach ([$invalid, ['city' => 'Unknown', 'choices' => ['Unknown'], 'radio' => 'Unknown']] as $input) {
            $this->assertEqualsCanonicalizing(['custom_profile.city', 'custom_profile.choices.0', 'custom_profile.radio'], array_keys(Validator::make(['custom_profile' => $input], $adminRules)->errors()->toArray()));
        }
    }
}
