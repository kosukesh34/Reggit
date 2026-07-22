<?php

namespace Tests\Unit\Rules;

use App\Enums\ProfileCustomFieldInputType;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Rules\ProfileValidationRules;
use App\Services\MemberService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProfileValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    public function test_getProfileValidationRules_スマレジ制約の形式ルールを含むこと(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSetting::setProfileFieldSettings([
            'smaregi_fields' => [
                'name' => ['enabled' => true, 'required' => true],
                'kana' => ['enabled' => true, 'required' => false],
                'phone' => ['enabled' => true, 'required' => false],
                'postal_code' => ['enabled' => true, 'required' => false],
                'address' => ['enabled' => true, 'required' => false],
            ],
            'custom_fields' => [
                [
                    'key' => 'hobby',
                    'label' => '趣味',
                    'required' => false,
                    'input_type' => ProfileCustomFieldInputType::SELECTBOX,
                    'values' => ['読書', '旅行'],
                ],
            ],
        ], $project->id);

        $rules = app(MemberService::class)->getProfileValidationRules((int) $project->id);

        $this->assertContains('max:'.ProfileValidationRules::POSTAL_CODE_MAX, $rules['postal_code']);
        $this->assertContains('max:'.ProfileValidationRules::ADDRESS_MAX, $rules['address']);
        $this->assertContains('regex:'.ProfileValidationRules::PHONE_REGEX, $rules['phone']);
        $this->assertContains('regex:'.ProfileValidationRules::KANA_REGEX, $rules['kana']);
        $this->assertContains('in:読書,旅行', $rules['hobby']);

        $invalid = Validator::make(
            ['phone' => '不正な電話', 'kana' => 'ひらがな', 'postal_code' => '123456789'],
            [
                'phone' => $rules['phone'],
                'kana' => $rules['kana'],
                'postal_code' => $rules['postal_code'],
            ]
        );
        $this->assertTrue($invalid->fails());
        $this->assertTrue($invalid->errors()->has('phone'));
        $this->assertTrue($invalid->errors()->has('kana'));
        $this->assertTrue($invalid->errors()->has('postal_code'));
    }
}
