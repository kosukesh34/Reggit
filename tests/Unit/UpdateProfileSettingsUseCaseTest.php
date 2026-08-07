<?php

namespace Tests\Unit;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\ProfileCustomFieldInputType;
use App\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
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
            memberRegistrationMode: 'prompt',
        ));

        $settings = ShopSettingStore::getProfileFieldSettings((int) $project->id);
        $this->assertTrue($settings['smaregi_fields']['name']['enabled']);
        $this->assertTrue($settings['smaregi_fields']['name']['required']);
        $this->assertFalse($settings['smaregi_fields']['phone']['required']);
        $this->assertSame('nickname', $settings['custom_fields'][0]['key']);
        $this->assertSame('ニックネーム', $settings['custom_fields'][0]['label']);
        $this->assertTrue($settings['custom_fields'][0]['required']);
        $this->assertSame(ProfileCustomFieldInputType::TEXT, $settings['custom_fields'][0]['input_type']);
        $this->assertSame('prompt', ShopSettingStore::getMemberRegistrationMode((int) $project->id));
    }

    public function test_handle_カスタム項目の入力タイプと選択肢を保存できること(): void
    {
        $project = Project::factory()->create();

        $useCase = new UpdateProfileSettingsUseCase(app(ShopSettingRepository::class));
        $useCase->handle(new UpdateProfileSettingsInput(
            projectId: (int) $project->id,
            rawSmaregiFields: [
                'name' => ['enabled' => '1', 'required' => '1'],
            ],
            rawCustomFields: [
                [
                    'key' => 'favorite_color',
                    'label' => '好きな色',
                    'required' => '0',
                    'input_type' => ProfileCustomFieldInputType::RADIO,
                    'values' => "赤\n青\n緑",
                    'description' => '任意の説明',
                ],
            ],
            memberRegistrationMode: 'optional',
        ));

        $settings = ShopSettingStore::getProfileFieldSettings((int) $project->id);
        $this->assertSame(ProfileCustomFieldInputType::RADIO, $settings['custom_fields'][0]['input_type']);
        $this->assertSame(['赤', '青', '緑'], $settings['custom_fields'][0]['values']);
        $this->assertSame('任意の説明', $settings['custom_fields'][0]['description']);
    }

    public function test_handle_会員登録モードをforceで保存できること(): void
    {
        $project = Project::factory()->create();

        $useCase = new UpdateProfileSettingsUseCase(app(ShopSettingRepository::class));
        $useCase->handle(new UpdateProfileSettingsInput(
            projectId: (int) $project->id,
            rawSmaregiFields: [
                'name' => ['enabled' => '1', 'required' => '0'],
            ],
            rawCustomFields: null,
            memberRegistrationMode: 'force',
        ));

        $this->assertSame('force', ShopSettingStore::getMemberRegistrationMode((int) $project->id));
    }
}
