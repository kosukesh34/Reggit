<?php

namespace Tests\Unit\Services\Smaregi;

use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerRankSyncService;
use Mockery;
use Tests\TestCase;

class SmaregiCustomerRankSyncServiceTest extends TestCase
{
    public function test_failed_rank_fetch_does_not_create_replacement_ranks(): void
    {
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->once()->andReturn('contract');
        $api->shouldReceive('getCustomerRanks')->once()->andReturnNull();
        $api->shouldReceive('createCustomerRank')->never();
        $this->expectException(\RuntimeException::class);

        (new SmaregiCustomerRankSyncService($api))->sync(1, [['key' => 'bronze', 'label' => 'Bronze', 'min_points' => 0, 'is_active' => true]]);
    }

    public function test_failed_rank_delete_does_not_complete_empty_settings_sync(): void
    {
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->once()->andReturn('contract');
        $api->shouldReceive('getCustomerRanks')->once()->andReturn(['customer_ranks' => [['rank' => 'old', 'name' => 'Old']]]);
        $api->shouldReceive('deleteCustomerRank')->once()->with('contract', 'old')->andReturnNull();
        $this->expectException(\RuntimeException::class);

        (new SmaregiCustomerRankSyncService($api))->sync(1, []);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sync_新規ランクはnameとポイント付与率で登録しrankは送らないこと(): void
    {
        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->once()->with(1)->andReturn('contract_1');
        $smaregiApi->shouldReceive('getCustomerRanks')->once()->andReturn([]);
        $smaregiApi->shouldReceive('createCustomerRank')
            ->once()
            ->with('contract_1', Mockery::on(function (array $body): bool {
                return ($body['name'] ?? null) === 'ブロンズ'
                    && ($body['displaySequence'] ?? null) === '1'
                    && ($body['displayFlag'] ?? null) === '1'
                    && ($body['pointGivingUnitPrice'] ?? null) === '100'
                    && ($body['pointGivingUnit'] ?? null) === '1'
                    && ! array_key_exists('rank', $body)
                    && ! array_key_exists('rankName', $body);
            }))
            ->andReturn(['rank' => '10', 'name' => 'ブロンズ']);

        $service = new SmaregiCustomerRankSyncService($smaregiApi);
        $ids = $service->sync(1, [
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'is_active' => true, 'order' => 1, 'point_earn_rate' => 100],
        ]);

        $this->assertSame(['bronze' => '10'], $ids);
    }

    public function test_sync_ランク別付与レートをスマレジへ送ること(): void
    {
        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->once()->with(1)->andReturn('contract_1');
        $smaregiApi->shouldReceive('getCustomerRanks')->once()->andReturn([
            ['rank' => '7', 'name' => 'ゴールド', 'displayFlag' => '1'],
        ]);
        $smaregiApi->shouldReceive('updateCustomerRank')
            ->once()
            ->with('contract_1', '7', Mockery::on(function (array $body): bool {
                return ($body['name'] ?? null) === 'ゴールド'
                    && ($body['pointGivingUnitPrice'] ?? null) === '50'
                    && ($body['pointGivingUnit'] ?? null) === '1';
            }))
            ->andReturn(['rank' => '7', 'name' => 'ゴールド']);
        $smaregiApi->shouldReceive('createCustomerRank')->never();
        $smaregiApi->shouldReceive('deleteCustomerRank')->never();

        $service = new SmaregiCustomerRankSyncService($smaregiApi);
        $ids = $service->sync(1, [
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 5000, 'is_active' => true, 'order' => 2, 'point_earn_rate' => 50],
        ]);

        $this->assertSame(['gold' => '7'], $ids);
    }

    public function test_sync_同名の既存ランクはスマレジrankで更新すること(): void
    {
        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->once()->with(1)->andReturn('contract_1');
        $smaregiApi->shouldReceive('getCustomerRanks')->once()->andReturn([
            ['rank' => '7', 'name' => 'ブロンズ', 'displayFlag' => '1'],
        ]);
        $smaregiApi->shouldReceive('updateCustomerRank')
            ->once()
            ->with('contract_1', '7', Mockery::on(function (array $body): bool {
                return ($body['name'] ?? null) === 'ブロンズ'
                    && ($body['displayFlag'] ?? null) === '0'
                    && ($body['pointGivingUnitPrice'] ?? null) === '100'
                    && ! array_key_exists('rankName', $body);
            }))
            ->andReturn(['rank' => '7', 'name' => 'ブロンズ']);
        $smaregiApi->shouldReceive('createCustomerRank')->never();
        $smaregiApi->shouldReceive('deleteCustomerRank')->never();

        $service = new SmaregiCustomerRankSyncService($smaregiApi);
        $service->sync(1, [
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'is_active' => false, 'order' => 1, 'point_earn_rate' => 100],
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_sync_アプリ側にないスマレジランクは削除すること(): void
    {
        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->once()->with(1)->andReturn('contract_1');
        $smaregiApi->shouldReceive('getCustomerRanks')->once()->andReturn([
            ['rank' => '7', 'name' => 'ブロンズ'],
            ['rank' => '8', 'name' => '旧ランク'],
        ]);
        $smaregiApi->shouldReceive('updateCustomerRank')
            ->once()
            ->with('contract_1', '7', Mockery::type('array'))
            ->andReturn(['rank' => '7', 'name' => 'ブロンズ']);
        $smaregiApi->shouldReceive('deleteCustomerRank')
            ->once()
            ->with('contract_1', '8')
            ->andReturn([]);

        $service = new SmaregiCustomerRankSyncService($smaregiApi);
        $service->sync(1, [
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'is_active' => true, 'order' => 1, 'point_earn_rate' => 100],
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_sync_smaregi_rank_idがある場合は表示名が変わっても同一rankを更新すること(): void
    {
        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->once()->with(1)->andReturn('contract_1');
        $smaregiApi->shouldReceive('getCustomerRanks')->once()->andReturn([
            ['rank' => '7', 'name' => 'ブロンズ', 'displayFlag' => '1'],
            ['rank' => '8', 'name' => '他店舗ランク', 'displayFlag' => '1'],
        ]);
        $smaregiApi->shouldReceive('updateCustomerRank')
            ->once()
            ->with('contract_1', '7', Mockery::on(function (array $body): bool {
                return ($body['name'] ?? null) === 'ブロンズ会員'
                    && ($body['pointGivingUnitPrice'] ?? null) === '100';
            }))
            ->andReturn(['rank' => '7', 'name' => 'ブロンズ会員']);
        $smaregiApi->shouldReceive('createCustomerRank')->never();
        $smaregiApi->shouldReceive('deleteCustomerRank')
            ->once()
            ->with('contract_1', '8')
            ->andReturn([]);

        $service = new SmaregiCustomerRankSyncService($smaregiApi);
        $ids = $service->sync(1, [
            [
                'key' => 'bronze',
                'label' => 'ブロンズ会員',
                'min_points' => 0,
                'is_active' => true,
                'order' => 1,
                'point_earn_rate' => 100,
                'smaregi_rank_id' => '7',
            ],
        ]);

        $this->assertSame(['bronze' => '7'], $ids);
    }

    public function test_sync_登録失敗時は例外を投げること(): void
    {
        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->once()->andReturn('contract_1');
        $smaregiApi->shouldReceive('getCustomerRanks')->once()->andReturn([]);
        $smaregiApi->shouldReceive('createCustomerRank')->once()->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('スマレジの会員ランク登録に失敗しました（bronze）');

        $service = new SmaregiCustomerRankSyncService($smaregiApi);
        $service->sync(1, [
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'is_active' => true, 'order' => 1, 'point_earn_rate' => 100],
        ]);
    }
}
