<?php

namespace Tests\Unit;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\Project;
use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\UpdateProfileSettingsInput;
use App\UseCases\Admin\Settings\UpdateProfileSettingsUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProfileSettingsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_プロフィール項目設定を保存できること(): void
    {
        $project = Project::factory()->create();

        $useCase = new UpdateProfileSettingsUseCase(app(ShopSettingRepository::class));
        $useCase->handle(new UpdateProfileSettingsInput(
            projectId: (int) $project->id,
            rawSmaregiFields: [
                'name' => ['enabled' => '1', 'required' => '1'],
                'phone' => ['enabled' => true, 'required' => false],
            ],
            rawCustomFields: [
                ['key' => 'nickname', 'label' => 'ニックネーム', 'required' => '1'],
            ],
        ));

        $settings = ShopSetting::getProfileFieldSettings((int) $project->id);
        $this->assertTrue($settings['smaregi_fields']['name']['enabled']);
        $this->assertTrue($settings['smaregi_fields']['name']['required']);
        $this->assertFalse($settings['smaregi_fields']['phone']['required']);
        $this->assertSame('nickname', $settings['custom_fields'][0]['key']);
        $this->assertSame('ニックネーム', $settings['custom_fields'][0]['label']);
        $this->assertTrue($settings['custom_fields'][0]['required']);
    }
}
