<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * DB（会員）とスマレジ側の会員データが一致することを検証するテスト。
 * 会員コード（member_number / customerCode）をキーに突き合わせ可能であることを確認する。
 */
class SmaregiDbConsistencyTest extends TestCase
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

    /** DBの会員とスマレジ会員一覧の会員コードが一致することを検証（モック） */
    public function test_get_customers_d_bの会員番号とスマレジ会員コードが一致すること(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'SYNC001',
        ]);

        $this->fakeSmaregiHttp([
            'get_pos_customers' => [
                'customers' => [
                    [
                        'customerId' => '1',
                        'customerCode' => 'SYNC001',
                        'lastName' => '山田',
                        'firstName' => '太郎',
                    ],
                ],
                'total' => 1,
            ],
        ]);

        $service = app(SmaregiApiService::class);
        $result = $service->getCustomers(self::CONTRACT_ID, ['customer_code' => $member->member_number]);

        $this->assertNotNull($result);
        $list = $result['customers'] ?? $result;
        $this->assertIsArray($list);
        $this->assertGreaterThanOrEqual(1, count($list), 'スマレジに同じ会員コードの会員が存在すること');

        $found = null;
        foreach ($list as $c) {
            if (($c['customerCode'] ?? '') === $member->member_number) {
                $found = $c;
                break;
            }
        }
        $this->assertNotNull($found, 'DBの会員コードと一致するスマレジ会員が取得できること');
        $this->assertSame($member->member_number, $found['customerCode']);
    }

    /** スマレジに登録した会員コードとDBで保持する会員番号が一致することを検証（モック） */
    public function test_create_customer_登録した会員コードと_d_b会員番号が一致すること(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $codeToUse = 'MATCH002';
        $this->fakeSmaregiHttp([
            'post_pos_customers' => [
                'customerId' => '2',
                'customerCode' => $codeToUse,
                'lastName' => '一致',
                'firstName' => '検証',
            ],
            'get_pos_customers' => [
                'customers' => [
                    [
                        'customerId' => '2',
                        'customerCode' => $codeToUse,
                        'lastName' => '一致',
                        'firstName' => '検証',
                    ],
                ],
                'total' => 1,
            ],
        ]);

        $service = app(SmaregiApiService::class);
        $createBody = [
            'customerCode' => $codeToUse,
            'lastName' => '一致',
            'firstName' => '検証',
            'lastKana' => 'イッチ',
            'firstKana' => 'ケンショウ',
        ];
        $created = $service->createCustomer(self::CONTRACT_ID, $createBody);
        $this->assertNotNull($created);
        $this->assertSame($codeToUse, $created['customerCode'] ?? null);

        // DB側で同じ会員番号を持つ会員を想定（連携時に同じコードで登録する前提）
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => $created['customerCode'],
        ]);

        $fetched = $service->getCustomers(self::CONTRACT_ID, ['customer_code' => $member->member_number]);
        $this->assertNotNull($fetched);
        $list = $fetched['customers'] ?? $fetched;
        $this->assertIsArray($list);
        $customerFromSmaregi = null;
        foreach (is_array($list) ? $list : [] as $c) {
            if (($c['customerCode'] ?? '') === $member->member_number) {
                $customerFromSmaregi = $c;
                break;
            }
        }
        $this->assertNotNull($customerFromSmaregi, 'スマレジから取得した会員の会員コードがDB会員番号と一致すること');
        $this->assertSame($member->member_number, $customerFromSmaregi['customerCode']);
    }

    /** 複数会員がいる場合にDB会員とスマレジ会員を会員コードで突き合わせられることを検証 */
    public function test_get_customers_複数会員を会員コードで突き合わせられること(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user1 = User::factory()->create(['project_id' => $project->id]);
        $user2 = User::factory()->create(['project_id' => $project->id]);
        $member1 = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user1->id,
            'member_number' => 'DB_A001',
        ]);
        $member2 = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user2->id,
            'member_number' => 'DB_B002',
        ]);

        $this->fakeSmaregiHttp([
            'get_pos_customers' => [
                'customers' => [
                    ['customerId' => '1', 'customerCode' => 'DB_A001', 'lastName' => 'A', 'firstName' => '一'],
                    ['customerId' => '2', 'customerCode' => 'DB_B002', 'lastName' => 'B', 'firstName' => '二'],
                ],
                'total' => 2,
            ],
        ]);

        $service = app(SmaregiApiService::class);
        $result = $service->getCustomers(self::CONTRACT_ID, []);

        $this->assertNotNull($result);
        $list = $result['customers'] ?? $result;
        $this->assertCount(2, $list);

        $dbMembersByNumber = [$member1->member_number => $member1, $member2->member_number => $member2];
        foreach ($list as $customer) {
            $code = $customer['customerCode'] ?? null;
            $this->assertArrayHasKey($code, $dbMembersByNumber, 'スマレジの会員コードがDBのいずれかの会員番号と一致すること');
            $this->assertSame($dbMembersByNumber[$code]->member_number, $code);
        }
    }

    private function fakeSmaregiHttp(array $responses): void
    {
        $idBase = 'https://id.smaregi.dev';
        $apiBase = 'https://api.smaregi.dev';

        Http::fake(function ($request) use ($idBase, $apiBase, $responses) {
            $url = $request->url();
            $method = strtoupper($request->method());

            if (str_contains($url, $idBase) && str_contains($url, '/token')) {
                return Http::response([
                    'access_token' => 'fake_access_token_'.self::CONTRACT_ID,
                    'expires_in' => 3600,
                ], 200);
            }

            if (! str_contains($url, $apiBase) || ! str_contains($url, self::CONTRACT_ID)) {
                return Http::response([], 404);
            }

            if ($method === 'GET' && str_contains($url, 'pos/customers') && ! preg_match('#/pos/customers/\d+($|\?)#', $url)) {
                return Http::response($responses['get_pos_customers'] ?? ['customers' => [], 'total' => 0], 200);
            }
            if ($method === 'POST' && str_contains($url, 'pos/customers') && ! str_contains($url, '/point/add')) {
                return Http::response($responses['post_pos_customers'] ?? ['customerId' => '1', 'customerCode' => ''], 200);
            }

            return Http::response([], 404);
        });
    }
}
