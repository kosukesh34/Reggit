<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ShopSetting;
use App\Services\Smaregi\SmaregiApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * スマレジAPI の送受信ができることを検証するテスト。
 * 実際のスマレジには接続せず、Http::fake() でレスポンスをモックします。
 */
class SmaregiApiTest extends TestCase
{
    use RefreshDatabase;

    private const CONTRACT_ID = 'test_contract_sb';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
        ]);
    }

    /** プロジェクトに契約を紐づけ、トークン・API をフェイクして受信（GET）を検証 */
    public function test_get_stores_スマレジから店舗一覧を取得できること(): void
    {
        $this->createProjectWithContract();
        $this->fakeSmaregiHttp([
            'get_pos_stores' => ['stores' => [
                ['storeId' => '1', 'name' => 'テスト店舗', 'storeCode' => 'STORE01'],
            ]],
        ]);

        $service = app(SmaregiApiService::class);
        $result = $service->getStores(self::CONTRACT_ID);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('stores', $result);
        $this->assertCount(1, $result['stores']);
        $this->assertSame('1', $result['stores'][0]['storeId']);
        $this->assertSame('テスト店舗', $result['stores'][0]['name']);
    }

    /** 会員一覧の受信（GET）を検証 */
    public function test_get_customers_スマレジから会員一覧を取得できること(): void
    {
        $this->createProjectWithContract();
        $this->fakeSmaregiHttp([
            'get_pos_customers' => [
                'customers' => [
                    ['customerId' => '1', 'customerCode' => 'C001', 'lastName' => '山田', 'firstName' => '太郎'],
                ],
                'total' => 1,
            ],
        ]);

        $service = app(SmaregiApiService::class);
        $result = $service->getCustomers(self::CONTRACT_ID, ['limit' => 10]);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('customers', $result);
        $this->assertCount(1, $result['customers']);
        $this->assertSame('1', $result['customers'][0]['customerId']);
        $this->assertSame('C001', $result['customers'][0]['customerCode']);
    }

    /** 会員登録の送信（POST）を検証 */
    public function test_create_customer_スマレジに会員を登録できること(): void
    {
        $this->createProjectWithContract();
        $this->fakeSmaregiHttp([
            'post_pos_customers' => [
                'customerId' => '2',
                'customerCode' => 'TEST_NEW',
                'lastName' => '送信',
                'firstName' => '検証',
            ],
        ]);

        $service = app(SmaregiApiService::class);
        $body = [
            'customerCode' => 'TEST_NEW',
            'lastName' => '送信',
            'firstName' => '検証',
            'lastKana' => 'ソウシン',
            'firstKana' => 'ケンショウ',
        ];
        $result = $service->createCustomer(self::CONTRACT_ID, $body);

        $this->assertNotNull($result);
        $this->assertSame('2', $result['customerId']);
        $this->assertSame('TEST_NEW', $result['customerCode']);
    }

    /** 会員ポイント加算の送信（POST）を検証 */
    public function test_add_customer_points_スマレジに会員ポイントを加算できること(): void
    {
        $this->createProjectWithContract();
        $this->fakeSmaregiHttp([
            'post_pos_customers_point_add' => [
                'customerId' => '1',
                'point' => '10',
                'transactionHeadIds' => ['100'],
            ],
        ]);

        $service = app(SmaregiApiService::class);
        $result = $service->addCustomerPoints(self::CONTRACT_ID, '1', 10);

        $this->assertNotNull($result);
        $this->assertSame('1', $result['customerId']);
        $this->assertSame('10', $result['point']);
    }

    /** プロジェクトに紐づく契約IDを取得し、その契約でAPI送受信できることを検証 */
    public function test_get_contract_id_for_project_契約_i_dを取得して会員の受信と登録ができること(): void
    {
        $project = $this->createProjectWithContract();
        $this->fakeSmaregiHttp([
            'get_pos_customers' => ['customers' => [['customerId' => '1', 'customerCode' => 'PJ_MEM']], 'total' => 1],
            'post_pos_customers' => ['customerId' => '99', 'customerCode' => 'SENT', 'lastName' => '姓', 'firstName' => '名'],
        ]);

        $service = app(SmaregiApiService::class);
        $contractId = $service->getContractIdForProject((int) $project->id);
        $this->assertSame(self::CONTRACT_ID, $contractId);

        $received = $service->getCustomers($contractId, []);
        $this->assertNotNull($received);
        $this->assertSame('PJ_MEM', $received['customers'][0]['customerCode']);

        $sent = $service->createCustomer($contractId, [
            'customerCode' => 'SENT',
            'lastName' => '姓',
            'firstName' => '名',
            'lastKana' => 'セイ',
            'firstKana' => 'メイ',
        ]);
        $this->assertNotNull($sent);
        $this->assertSame('99', $sent['customerId']);
    }

    private function createProjectWithContract(): Project
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        return $project;
    }

    /**
     * トークン取得 + 指定したPOS API のレスポンスをフェイクする。
     *
     * @param  array<string, array<int|string, bool|int|float|string|array|null>>  $responses  キーは get_pos_stores, get_pos_customers, post_pos_customers, post_pos_customers_point_add など
     */
    private function fakeSmaregiHttp(array $responses): void
    {
        $idBase = 'https://id.smaregi.dev';
        $apiBase = 'https://api.smaregi.dev';

        Http::fake(function ($request) use ($idBase, $apiBase, $responses) {
            $url = $request->url();
            $method = strtoupper($request->method());

            // トークン取得
            if (str_contains($url, $idBase) && str_contains($url, '/token')) {
                return Http::response([
                    'access_token' => 'fake_access_token_'.self::CONTRACT_ID,
                    'expires_in' => 3600,
                ], 200);
            }

            // POS API
            if (! str_contains($url, $apiBase) || ! str_contains($url, self::CONTRACT_ID)) {
                return Http::response([], 404);
            }

            if ($method === 'GET' && str_contains($url, 'pos/stores')) {
                return Http::response($responses['get_pos_stores'] ?? ['stores' => []], 200);
            }
            if ($method === 'GET' && str_contains($url, 'pos/customers') && ! preg_match('#/pos/customers/\d+($|\?)#', $url)) {
                return Http::response($responses['get_pos_customers'] ?? ['customers' => [], 'total' => 0], 200);
            }
            if ($method === 'POST' && str_contains($url, 'pos/customers') && ! str_contains($url, '/point/add')) {
                return Http::response($responses['post_pos_customers'] ?? ['customerId' => '1'], 200);
            }
            if ($method === 'POST' && str_contains($url, '/point/add')) {
                return Http::response($responses['post_pos_customers_point_add'] ?? ['customerId' => '1', 'point' => '0'], 200);
            }

            return Http::response([], 404);
        });
    }
}
