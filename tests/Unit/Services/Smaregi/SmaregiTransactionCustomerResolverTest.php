<?php

namespace Tests\Unit\Services\Smaregi;

use App\Services\Smaregi\SmaregiApiClient;
use App\Services\Smaregi\SmaregiTransactionCustomerResolver;
use Mockery;
use Tests\TestCase;

class SmaregiTransactionCustomerResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_customer_idとtotal_pointを返すこと(): void
    {
        $client = Mockery::mock(SmaregiApiClient::class);
        $client->shouldReceive('request')
            ->once()
            ->with('contract-1', 'GET', 'pos/transactions/10')
            ->andReturn([
                'customerId' => '247',
                'totalPoint' => '395',
            ]);

        $resolved = (new SmaregiTransactionCustomerResolver($client))
            ->resolveCustomersFromTransactionHeads('contract-1', ['10']);

        $this->assertSame(['247'], $resolved['customer_ids']);
        $this->assertSame(395, $resolved['points_by_customer_id']['247'] ?? null);
    }

    public function test_customer_idが空ならリトライ後に空結果になること(): void
    {
        $client = Mockery::mock(SmaregiApiClient::class);
        $client->shouldReceive('request')
            ->times(3)
            ->with('contract-1', 'GET', 'pos/transactions/10')
            ->andReturn([
                'transactionHeadId' => '10',
                'customerId' => null,
                'totalPoint' => '100',
            ]);

        $resolved = (new SmaregiTransactionCustomerResolver($client))
            ->resolveCustomersFromTransactionHeads('contract-1', ['10']);

        $this->assertSame([], $resolved['customer_ids']);
        $this->assertSame([], $resolved['points_by_customer_id']);
    }

    public function test_途中でcustomer_idが取れたらその時点で返すこと(): void
    {
        $client = Mockery::mock(SmaregiApiClient::class);
        $client->shouldReceive('request')
            ->twice()
            ->with('contract-1', 'GET', 'pos/transactions/34')
            ->andReturn(
                [
                    'transactionHeadId' => '34',
                    'customerId' => null,
                    'totalPoint' => '395',
                ],
                [
                    'transactionHeadId' => '34',
                    'customerId' => '247',
                    'totalPoint' => '395',
                ],
            );

        $resolved = (new SmaregiTransactionCustomerResolver($client))
            ->resolveCustomersFromTransactionHeads('contract-1', ['34']);

        $this->assertSame(['247'], $resolved['customer_ids']);
        $this->assertSame(395, $resolved['points_by_customer_id']['247'] ?? null);
    }

    public function test_nested_customerからcustomer_idを取ること(): void
    {
        $client = Mockery::mock(SmaregiApiClient::class);
        $client->shouldReceive('request')
            ->once()
            ->andReturn([
                'customer' => ['customer_id' => '99'],
                'totalPoint' => 10,
            ]);

        $resolved = (new SmaregiTransactionCustomerResolver($client))
            ->resolveCustomersFromTransactionHeads('contract-1', ['1']);

        $this->assertSame(['99'], $resolved['customer_ids']);
        $this->assertSame(10, $resolved['points_by_customer_id']['99'] ?? null);
    }
}
