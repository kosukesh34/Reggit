<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\ShopSetting;
use App\Models\SmaregiContract;
use App\Services\Smaregi\SmaregiApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmaregiApiServiceCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_プロジェクト別credentialsを優先すること(): void
    {
        config([
            'smaregi.client_id' => 'env_client_id',
            'smaregi.client_secret' => 'env_client_secret',
        ]);

        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', 'contract_proj', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_client_id', 'proj_client_id', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_client_secret', 'proj_client_secret', 'string', (int) $project->id);
        SmaregiContract::create([
            'contract_id' => 'contract_proj',
            'project_id' => $project->id,
            'status' => 'active',
        ]);

        $credentials = app(SmaregiApiService::class)->resolveCredentialsForContract('contract_proj');

        $this->assertSame('proj_client_id', $credentials['client_id']);
        $this->assertSame('proj_client_secret', $credentials['client_secret']);
    }

    public function test_プロジェクトcredentialsがなければenvにフォールバックすること(): void
    {
        config([
            'smaregi.client_id' => 'env_client_id',
            'smaregi.client_secret' => 'env_client_secret',
        ]);

        $credentials = app(SmaregiApiService::class)->resolveCredentialsForContract('unknown_contract');

        $this->assertSame('env_client_id', $credentials['client_id']);
        $this->assertSame('env_client_secret', $credentials['client_secret']);
    }
}
