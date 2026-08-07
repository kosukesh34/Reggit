<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\Support\CleansUpSmaregiCreatedResources;
use Tests\TestCase;

/**
 * スマレジAPI への実際の接続を検証する統合テスト。
 *
 * 現状の .env（SMAREGI_CLIENT_ID / SMAREGI_CLIENT_SECRET）と、
 * 契約ID（.env の SMAREGI_CONTRACT_ID または SMAREGI_TEST_CONTRACT_ID、未設定時はプロジェクト1の紐付け）を使って実接続します。
 * 資格情報・契約が無い環境ではスキップされます。
 * 作成した会員・クーポンは tearDown で削除します。
 *
 * 実行例: php artisan test tests/Feature/SmaregiApiIntegrationTest.php
 * または: php artisan test --group=smaregi-integration
 *
 * @group smaregi-integration
 */
class SmaregiApiIntegrationTest extends TestCase
{
    use CleansUpSmaregiCreatedResources;
    use RefreshDatabase;

    private ?string $contractId = null;

    /** config / env / getenv の順で参照（Docker や .env.testing 利用時も値が拾えるようにする） */
    private static function readEnv(string $key): string
    {
        $configKey = match ($key) {
            'SMAREGI_CLIENT_ID' => 'smaregi.client_id',
            'SMAREGI_CLIENT_SECRET' => 'smaregi.client_secret',
            default => null,
        };
        $v = $configKey ? (config($configKey) ?: null) : null;
        $v = $v !== null && $v !== '' ? $v : env($key, '');
        $v = $v !== null && $v !== '' ? $v : (getenv($key) ?: '');

        return trim((string) $v);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // テスト実行時に env を config へ反映（config キャッシュや読み込み順の影響を避ける）
        $clientIdEnv = env('SMAREGI_CLIENT_ID') ?: getenv('SMAREGI_CLIENT_ID');
        $clientSecretEnv = env('SMAREGI_CLIENT_SECRET') ?: getenv('SMAREGI_CLIENT_SECRET');
        if ($clientIdEnv !== null && $clientIdEnv !== '') {
            Config::set('smaregi.client_id', $clientIdEnv);
        }
        if ($clientSecretEnv !== null && $clientSecretEnv !== '') {
            Config::set('smaregi.client_secret', $clientSecretEnv);
        }

        $clientId = self::readEnv('SMAREGI_CLIENT_ID');
        $clientSecret = self::readEnv('SMAREGI_CLIENT_SECRET');
        if ($clientId === '' || $clientSecret === '') {
            $this->markTestSkipped(
                '.env に SMAREGI_CLIENT_ID と SMAREGI_CLIENT_SECRET を設定してください。'
                .' Docker の場合はコンテナに .env がマウントされているか、または docker-compose の environment で渡しているか確認してください。'
            );
        }

        $this->contractId = trim((string) (env('SMAREGI_TEST_CONTRACT_ID') ?: env('SMAREGI_CONTRACT_ID') ?: config('smaregi.contract_id') ?: ''));
        if ($this->contractId === '') {
            $service = app(SmaregiApiService::class);
            $this->contractId = $service->getContractIdForProject(1);
        }
        if ($this->contractId === null || $this->contractId === '') {
            $this->markTestSkipped('契約IDがありません。.env に SMAREGI_CONTRACT_ID（または SMAREGI_TEST_CONTRACT_ID）を設定するか、テスト用DBのプロジェクト1にスマレジ契約を紐付けてください。');
        }
    }

    /** 実際に店舗一覧を取得できる（受信） */
    public function test_get_stores_実接続で店舗一覧を取得できること(): void
    {
        $service = app(SmaregiApiService::class);
        $result = $service->getStores($this->contractId);

        $this->assertNotNull($result, '店舗一覧の取得に失敗しました。スコープ pos.stores:read と契約を確認してください。');
        $this->assertIsArray($result);
        // スマレジAPIは配列で返す場合と { "stores": [...] } で返す場合がある
        $list = $result['stores'] ?? $result;
        $this->assertIsArray($list);
        if (count($list) > 0) {
            $this->assertArrayHasKey('storeId', $list[0], '店舗オブジェクトに storeId が含まれること');
        }
    }

    /** 実際に会員一覧を取得できる（受信） */
    public function test_get_customers_実接続で会員一覧を取得できること(): void
    {
        $service = app(SmaregiApiService::class);
        $result = $service->getCustomers($this->contractId, ['limit' => 5]);

        $this->assertNotNull($result, '会員一覧の取得に失敗しました。スコープ pos.customers:read と契約を確認してください。');
        $this->assertIsArray($result);
        // スマレジAPIは配列で返す場合と { "customers": [...] } で返す場合がある
        $list = $result['customers'] ?? $result;
        $this->assertIsArray($list);
        if (count($list) > 0) {
            $this->assertArrayHasKey('customerId', $list[0], '会員オブジェクトに customerId が含まれること');
        }
    }

    /** 実際に会員を1件登録できる（送信） */
    public function test_create_customer_実接続で会員を1件登録できること(): void
    {
        $service = app(SmaregiApiService::class);
        // 会員コードは20文字以内の制限あり
        $code = 'TI'.date('YmdHis');
        $body = [
            'customerCode' => $code,
            'lastName' => '統合',
            'firstName' => 'テスト',
            'lastKana' => 'トウゴウ',
            'firstKana' => 'テスト',
        ];

        $result = $service->createCustomer($this->contractId, $body);

        $this->assertNotNull(
            $result,
            '会員登録に失敗しました。スコープ pos.customers:write を有効にし、スマレジの会員必須項目を確認してください。'
            .' 詳細は storage/logs/laravel.log の [Smaregi] API request failed を参照してください。'
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('customerId', $result);
        $this->assertArrayHasKey('customerCode', $result);
        $this->assertSame($code, $result['customerCode']);
        $this->trackSmaregiCustomer($this->contractId, (string) $result['customerId']);
    }

    /** DBの会員とスマレジに登録した会員の会員コードが一致することを実APIで検証 */
    public function test_integration_d_bの会員番号とスマレジの会員コードが一致すること(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', $this->contractId, 'string', (int) $project->id);

        $code = 'DC'.date('YmdHis');
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => $code,
        ]);

        $service = app(SmaregiApiService::class);
        $createBody = [
            'customerCode' => $code,
            'lastName' => 'DB一致',
            'firstName' => '検証',
            'lastKana' => 'ディービーイッチ',
            'firstKana' => 'ケンショウ',
        ];
        $created = $service->createCustomer($this->contractId, $createBody);
        if ($created === null) {
            $this->markTestSkipped('スマレジ会員登録に失敗したため、DB・スマレジ一致テストをスキップしました。');
        }
        $this->trackSmaregiCustomer($this->contractId, (string) ($created['customerId'] ?? ''));

        $fetched = $service->getCustomers($this->contractId, ['customer_code' => $code]);
        $this->assertNotNull($fetched, '会員一覧の取得に失敗しました。');
        $list = $fetched['customers'] ?? $fetched;
        $this->assertIsArray($list);

        $found = null;
        foreach ($list as $c) {
            if (($c['customerCode'] ?? '') === $member->member_number) {
                $found = $c;
                break;
            }
        }
        $this->assertNotNull($found, 'DBの会員番号とスマレジの会員コードが一致する会員が取得できること');
        $this->assertSame($member->member_number, $found['customerCode'], 'DBとスマレジでデータが一致すること');
    }
}
