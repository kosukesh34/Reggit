<?php

namespace Tests\Unit\Services\Smaregi;

use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerCustomFieldLabelSyncService;
use Mockery;
use Tests\TestCase;

class SmaregiCustomerCustomFieldLabelSyncServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sync_契約未設定なら空を返すこと(): void
    {
        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->once()->with(1)->andReturn(null);

        $service = new SmaregiCustomerCustomFieldLabelSyncService($smaregiApi);
        $this->assertSame([], $service->sync(1, [
            ['key' => 'nickname', 'label' => 'ニックネーム'],
        ]));
    }

    public function test_sync_項目が空なら_a_p_iを呼ばないこと(): void
    {
        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->never();
        $smaregiApi->shouldReceive('getCustomerCustomFieldLabels')->never();

        $service = new SmaregiCustomerCustomFieldLabelSyncService($smaregiApi);
        $this->assertSame([], $service->sync(1, []));
    }

    public function test_sync_新規ラベルを登録して番号を返すこと(): void
    {
        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->once()->with(1)->andReturn('contract_1');
        $smaregiApi->shouldReceive('getCustomerCustomFieldLabels')->once()->andReturn([]);
        $smaregiApi->shouldReceive('createCustomerCustomFieldLabel')
            ->once()
            ->with('contract_1', ['label' => 'ニックネーム'])
            ->andReturn(['no' => 3, 'label' => 'ニックネーム']);

        $service = new SmaregiCustomerCustomFieldLabelSyncService($smaregiApi);
        $this->assertSame(['nickname' => 3], $service->sync(1, [
            ['key' => 'nickname', 'label' => 'ニックネーム'],
        ]));
    }

    public function test_sync_既存番号のラベル変更は更新すること(): void
    {
        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->once()->with(1)->andReturn('contract_1');
        $smaregiApi->shouldReceive('getCustomerCustomFieldLabels')->once()->andReturn([
            ['no' => 2, 'label' => '旧名称'],
        ]);
        $smaregiApi->shouldReceive('updateCustomerCustomFieldLabel')
            ->once()
            ->with('contract_1', 2, ['label' => '新名称'])
            ->andReturn(['no' => 2, 'label' => '新名称']);
        $smaregiApi->shouldReceive('createCustomerCustomFieldLabel')->never();

        $service = new SmaregiCustomerCustomFieldLabelSyncService($smaregiApi);
        $this->assertSame(['company' => 2], $service->sync(1, [
            ['key' => 'company', 'label' => '新名称', 'smaregi_no' => 2],
        ]));
    }

    public function test_sync_同名の既存ラベルは番号を再利用すること(): void
    {
        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->once()->with(1)->andReturn('contract_1');
        $smaregiApi->shouldReceive('getCustomerCustomFieldLabels')->once()->andReturn([
            ['no' => 5, 'label' => '会社名'],
        ]);
        $smaregiApi->shouldReceive('createCustomerCustomFieldLabel')->never();
        $smaregiApi->shouldReceive('updateCustomerCustomFieldLabel')->never();

        $service = new SmaregiCustomerCustomFieldLabelSyncService($smaregiApi);
        $this->assertSame(['company' => 5], $service->sync(1, [
            ['key' => 'company', 'label' => '会社名'],
        ]));
    }
}
