<?php

namespace Tests\Unit;

use App\Domain\Settings\Repositories\ProfileSettings;
use App\Enums\ProfileCustomFieldInputType;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Project;
use App\Services\Smaregi\SmaregiCustomerCustomFieldLabelSyncService;
use App\UseCases\Admin\Settings\Data\UpdateProfileSettingsInput;
use App\UseCases\Admin\Settings\UpdateProfileSettingsUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UpdateProfileSettingsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function useCase(?SmaregiCustomerCustomFieldLabelSyncService $sync = null): UpdateProfileSettingsUseCase
    {
        if ($sync === null) {
            $sync = Mockery::mock(SmaregiCustomerCustomFieldLabelSyncService::class);
            $sync->shouldReceive('sync')->andReturn([]);
        }

        return new UpdateProfileSettingsUseCase(app(ProfileSettings::class), $sync);
    }

    public function test_handle_プロフィール項目設定を保存できること(): void
    {
        $project = Project::factory()->create();

        $this->useCase()->handle(new UpdateProfileSettingsInput(
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
        $this->assertArrayNotHasKey('prefecture', $settings['smaregi_fields']);
        $this->assertArrayNotHasKey('city', $settings['smaregi_fields']);
        $this->assertArrayNotHasKey('street', $settings['smaregi_fields']);
        $this->assertArrayHasKey('address', $settings['smaregi_fields']);
        $this->assertSame('prompt', ShopSettingStore::getMemberRegistrationMode((int) $project->id));
    }

    public function test_handle_カスタム項目の入力タイプと選択肢を保存できること(): void
    {
        $project = Project::factory()->create();

        $this->useCase()->handle(new UpdateProfileSettingsInput(
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

    public function test_handle_スマレジ自由項目番号を保存できること(): void
    {
        $project = Project::factory()->create();
        $sync = Mockery::mock(SmaregiCustomerCustomFieldLabelSyncService::class);
        $sync->shouldReceive('sync')->once()->andReturn(['nickname' => 4]);

        $this->useCase($sync)->handle(new UpdateProfileSettingsInput(
            projectId: (int) $project->id,
            rawSmaregiFields: [
                'name' => ['enabled' => '1', 'required' => '1'],
            ],
            rawCustomFields: [
                ['key' => 'nickname', 'label' => 'ニックネーム', 'required' => '0'],
            ],
            memberRegistrationMode: 'optional',
        ));

        $settings = ShopSettingStore::getProfileFieldSettings((int) $project->id);
        $this->assertSame(4, $settings['custom_fields'][0]['smaregi_no']);
        $this->assertFalse($settings['custom_fields'][0]['required']);
    }

    public function test_handle_追加項目は99件まで保存し100件目は捨てること(): void
    {
        $project = Project::factory()->create();
        $fields = [];
        for ($i = 1; $i <= 100; $i++) {
            $fields[] = ['key' => 'f'.$i, 'label' => '項目'.$i, 'required' => '0'];
        }

        $this->useCase()->handle(new UpdateProfileSettingsInput(
            projectId: (int) $project->id,
            rawSmaregiFields: [
                'name' => ['enabled' => '1', 'required' => '1'],
            ],
            rawCustomFields: $fields,
            memberRegistrationMode: 'optional',
        ));

        $settings = ShopSettingStore::getProfileFieldSettings((int) $project->id);
        $this->assertCount(99, $settings['custom_fields']);
        $this->assertSame('f1', $settings['custom_fields'][0]['key']);
        $this->assertSame('f99', $settings['custom_fields'][98]['key']);
        $this->assertFalse($settings['custom_fields'][0]['required']);
    }

    public function test_handle_会員登録モードをforceで保存できること(): void
    {
        $project = Project::factory()->create();

        $this->useCase()->handle(new UpdateProfileSettingsInput(
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
