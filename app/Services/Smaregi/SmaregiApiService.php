<?php

namespace App\Services\Smaregi;

use App\Enums\SmaregiContractStatus;
use App\Models\Settings\ProjectProfileSmaregiSetting;
use App\Models\ShopSetting;
use App\Models\SmaregiContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * スマレジ・プラットフォームAPI 呼び出し用サービス。
 * アプリアクセストークン（Client Credentials）で契約単位のAPIを実行する。
 */
class SmaregiApiService
{
    private ?string $lastErrorDetail = null;

    private ?int $lastHttpStatus = null;

    public function getLastErrorDetail(): ?string
    {
        return $this->lastErrorDetail;
    }

    public function getLastHttpStatus(): ?int
    {
        return $this->lastHttpStatus;
    }

    public function clearLastErrorDetail(): void
    {
        $this->lastErrorDetail = null;
    }

    private const CACHE_KEY_PREFIX = 'smaregi_access_token:';

    private const CACHE_BUFFER_SECONDS = 60;

    public function getAppAccessToken(string $contractId): ?string
    {
        $credentials = $this->resolveCredentialsForContract($contractId);
        $clientId = $credentials['client_id'];
        $clientSecret = $credentials['client_secret'];
        $cacheKey = self::CACHE_KEY_PREFIX.$contractId.':'.$clientId;
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $idBaseUrl = rtrim(config('smaregi.id_base_url'), '/');
        $scope = config('smaregi.scope');

        if ($clientId === '' || $clientSecret === '') {
            Log::warning('[Smaregi] token skip client not configured', ['smaregi_contract_id' => $contractId]);

            return null;
        }

        $url = "{$idBaseUrl}/app/{$contractId}/token";
        try {
            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(30)
                ->connectTimeout(15)
                ->post($url, [
                    'grant_type' => 'client_credentials',
                    'scope' => $scope,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('[Smaregi] token request connection failed (timeout or unreachable)', [
                'smaregi_contract_id' => $contractId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::error('[Smaregi] token request failed', [
                'smaregi_contract_id' => $contractId,
                'status' => $response->status(),
            ]);

            return null;
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        if (! is_string($token) || $token === '') {
            return null;
        }

        Cache::put($cacheKey, $token, $expiresIn - self::CACHE_BUFFER_SECONDS);

        return $token;
    }

    /**
     * 契約IDに紐づくプロジェクトの credentials を優先し、なければ .env にフォールバックする。
     *
     * @return array{client_id: string, client_secret: string}
     */
    public function resolveCredentialsForContract(string $contractId): array
    {
        $projectId = $this->resolveProjectIdForContract($contractId);
        if ($projectId !== null) {
            $clientId = ShopSetting::getValue('smaregi_client_id', null, $projectId);
            $clientSecret = ShopSetting::getValue('smaregi_client_secret', null, $projectId);
            $clientId = is_string($clientId) ? trim($clientId) : '';
            $clientSecret = is_string($clientSecret) ? trim($clientSecret) : '';
            if ($clientId !== '' && $clientSecret !== '') {
                return ['client_id' => $clientId, 'client_secret' => $clientSecret];
            }
        }

        return [
            'client_id' => trim((string) config('smaregi.client_id', '')),
            'client_secret' => trim((string) config('smaregi.client_secret', '')),
        ];
    }

    private function resolveProjectIdForContract(string $contractId): ?int
    {
        $linked = SmaregiContract::where('contract_id', $contractId)
            ->whereNotNull('project_id')
            ->value('project_id');
        if (is_numeric($linked)) {
            return (int) $linked;
        }

        $setting = ProjectProfileSmaregiSetting::query()
            ->where('smaregi_contract_id', $contractId)
            ->value('project_id');

        return is_numeric($setting) ? (int) $setting : null;
    }

    /**
     * プラットフォームAPI（POS 等）を呼び出す。任意のエンドポイントで利用可能。
     * 例: request('GET', 'pos/stores') => GET .../pos/stores
     * 例: request('POST', 'pos/customers', $body) => POST .../pos/customers
     *
     * @param  string  $path  pos/ から始まるパス
     * @param  array<string, string|int|float|bool|array|null>  $body  GET時はクエリ、POST/PATCH/PUT時はJSONボディ
     * @return array<int|string, string|int|float|bool|array|null>|null
     */
    public function request(string $contractId, string $method, string $path, array $body = [], bool $bodyAsRequestJsonForm = false): ?array
    {
        $this->lastHttpStatus = null;
        $token = $this->getAppAccessToken($contractId);
        if ($token === null) {
            return null;
        }

        $apiBaseUrl = rtrim(config('smaregi.api_base_url'), '/');
        $path = ltrim($path, '/');
        $url = "{$apiBaseUrl}/{$contractId}/{$path}";

        $request = Http::withToken($token)
            ->acceptJson()
            ->timeout(30);

        $methodLower = strtolower($method);
        if ($methodLower === 'get') {
            $response = $request->get($url, $body);
        } elseif ($body !== [] && in_array($methodLower, ['post', 'put', 'patch'], true)) {
            if ($bodyAsRequestJsonForm) {
                $response = $request->asForm()
                    ->{$methodLower}($url, ['request_json' => json_encode($body)]);
            } else {
                $response = $request->withBody(json_encode($body), 'application/json')
                    ->{$methodLower}($url);
            }
        } else {
            $response = $request->{$methodLower}($url, $body);
        }

        $this->lastHttpStatus = $response->status();

        if (! $response->successful()) {
            $responseBody = $response->body();
            $this->lastErrorDetail = $this->parseErrorDetail($responseBody);
            $context = [
                'smaregi_contract_id' => $contractId,
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'response_body' => $responseBody !== '' ? $responseBody : null,
            ];
            if ($body !== [] && in_array(strtolower($method), ['post', 'patch'], true)) {
                if ($path === 'pos/coupons' || str_starts_with($path, 'pos/products')) {
                    $context['request_body_sent'] = $body;
                }
            }
            Log::warning('[Smaregi] API request failed', $context);

            return null;
        }

        $this->lastErrorDetail = null;

        // 204 No Content 等で body が空の場合は [] を返し、呼び出し側で null=失敗 と区別できるようにする
        return $response->json() ?? [];
    }

    private function parseErrorDetail(string $responseBody): ?string
    {
        if ($responseBody === '') {
            return null;
        }

        $decoded = json_decode($responseBody, true);
        if (! is_array($decoded)) {
            return null;
        }

        $detail = $decoded['detail'] ?? $decoded['message'] ?? $decoded['title'] ?? null;

        return is_string($detail) && trim($detail) !== '' ? trim($detail) : null;
    }

    /** 店舗一覧を取得（pos.stores:read スコープ必要） */
    public function getStores(string $contractId): ?array
    {
        return $this->request($contractId, 'GET', 'pos/stores');
    }

    /**
     * 在庫更新などで使用する先頭店舗IDを取得する。店舗が無い場合は null。
     */
    public function getFirstStoreId(string $contractId): ?string
    {
        $result = $this->getStores($contractId);
        if ($result === null) {
            return null;
        }
        $list = is_array($result) && ! isset($result[0]) && isset($result['stores'])
            ? $result['stores']
            : $result;
        $first = is_array($list) ? ($list[0] ?? null) : null;
        if (! is_array($first)) {
            return null;
        }
        $id = $first['storeId'] ?? $first['store_id'] ?? $first['id'] ?? null;

        return $id !== null ? (string) $id : null;
    }

    /** 商品一覧を取得（pos.products:read スコープ必要） */
    public function getProducts(string $contractId): ?array
    {
        return $this->request($contractId, 'GET', 'pos/products');
    }

    /**
     * 会員一覧を取得（pos.customers:read スコープ必要）
     *
     * @param  array<string, int|string>  $query  クエリ（customer_code, customer_id-from, limit, page など）
     */
    public function getCustomers(string $contractId, array $query = []): ?array
    {
        return $this->request($contractId, 'GET', 'pos/customers', $query);
    }

    /**
     * 会員コードから会員1件を取得（pos.customers:read）
     *
     * @return array<string, string|int|float|bool|array|null>|null
     */
    public function getCustomerByCode(string $contractId, string $customerCode): ?array
    {
        $customerCode = trim($customerCode);
        if ($customerCode === '') {
            return null;
        }

        $result = $this->getCustomers($contractId, ['customer_code' => $customerCode, 'limit' => 1]);
        if ($result === null) {
            return null;
        }

        $list = $this->extractList($result, ['customers']);
        foreach ($list as $customer) {
            if (is_array($customer) && (string) ($customer['customerCode'] ?? '') === $customerCode) {
                return $customer;
            }
        }

        return null;
    }

    /**
     * 会員ポイントを加算・減算してスマレジに送信（pos.customers:write スコープ必要）
     * 負の値を指定すると減算。
     *
     * @param  string  $contractId  契約ID
     * @param  string  $customerId  スマレジ会員ID
     * @param  int  $point  加算するポイント（負で減算）
     * @param  int|null  $mile  加算するマイル（任意）
     * @param  string|null  $pointExpireDate  ポイント失効予定日 Y-m-d（任意）
     * @return array{customerId?: string, point?: string, mile?: string, transactionHeadIds?: array}|null
     */
    public function addCustomerPoints(
        string $contractId,
        string $customerId,
        int $point,
        ?int $mile = null,
        ?string $pointExpireDate = null
    ): ?array {
        $path = 'pos/customers/'.$customerId.'/point/add';
        $body = ['point' => (string) $point];
        if ($mile !== null) {
            $body['mile'] = (string) $mile;
        }
        if ($pointExpireDate !== null && $pointExpireDate !== '') {
            $body['pointExpireDate'] = $pointExpireDate;
        }

        return $this->request($contractId, 'POST', $path, $body);
    }

    /** 会員1件取得（pos.customers:read） */
    public function getCustomer(string $contractId, string $customerId): ?array
    {
        return $this->request($contractId, 'GET', 'pos/customers/'.$customerId);
    }

    /**
     * 会員ポイント一覧を取得（pos.customers:read）
     *
     * @param  array<string, int|string>  $query  クエリ（customer_id, limit, page など）
     */
    public function getCustomerPoints(string $contractId, array $query = []): ?array
    {
        return $this->request($contractId, 'GET', 'pos/customers/point', $query);
    }

    /**
     * 会員1件のポイント情報を取得（pos.customers:read）
     *
     * 公式は GET pos/customers/point?customer_id=（一覧の絞り込み）。
     * サンドボックスでは GET pos/customers/{id}/points が 404 になるため使わない。
     *
     * @return array<string, string|int|float|bool|array|null>|null
     */
    public function getCustomerPoint(string $contractId, string $customerId): ?array
    {
        $customerId = trim($customerId);
        if ($customerId === '') {
            return null;
        }

        $list = $this->getCustomerPoints($contractId, [
            'customer_id' => $customerId,
            'limit' => 1,
        ]);
        if ($list === null) {
            return null;
        }

        return $this->normalizeCustomerPointRecord($list);
    }

    /**
     * @param  array<int|string, string|int|float|bool|array|null>  $result
     * @return array<string, string|int|float|bool|array|null>|null
     */
    private function normalizeCustomerPointRecord(array $result): ?array
    {
        foreach (['point', 'points', 'Point', 'Points'] as $key) {
            if (array_key_exists($key, $result)) {
                return $result;
            }
        }

        $list = $this->extractList($result, ['customer_points', 'customerPoints', 'points']);
        $first = $list[0] ?? null;

        return is_array($first) ? $first : null;
    }

    /** 会員登録（pos.customers:write） */
    public function createCustomer(string $contractId, array $body): ?array
    {
        return $this->request($contractId, 'POST', 'pos/customers', $body);
    }

    /** 会員更新（pos.customers:write） */
    public function updateCustomer(string $contractId, string $customerId, array $body): ?array
    {
        return $this->request($contractId, 'PATCH', 'pos/customers/'.$customerId, $body);
    }

    /** 会員削除（pos.customers:write）。404 は既に削除済みとみなし成功扱い。 */
    public function deleteCustomer(string $contractId, string $customerId): ?array
    {
        $result = $this->request($contractId, 'DELETE', 'pos/customers/'.$customerId);
        if ($result !== null) {
            return $result;
        }
        if ($this->lastHttpStatus === 404) {
            Log::info('[Smaregi] deleteCustomer treated as success (already gone)', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $customerId,
            ]);
            $this->lastErrorDetail = null;

            return [];
        }

        return null;
    }

    /** 取引一覧取得（pos.transactions:read） */
    public function getTransactions(string $contractId, array $query = []): ?array
    {
        return $this->request($contractId, 'GET', 'pos/transactions', $query);
    }

    /** 取引1件取得（pos.transactions:read） */
    public function getTransaction(string $contractId, string $transactionId, bool $withDetails = false): ?array
    {
        $query = $withDetails ? ['with_details' => 'all'] : [];

        return $this->request($contractId, 'GET', 'pos/transactions/'.$transactionId, $query);
    }

    private const TRANSACTION_CUSTOMER_RESOLVE_ATTEMPTS = 3;

    private const TRANSACTION_CUSTOMER_RESOLVE_DELAY_MICROSECONDS = 400000;

    private function transactionCustomerResolveDelayMicroseconds(): int
    {
        if (app()->environment('testing')) {
            return 0;
        }

        return self::TRANSACTION_CUSTOMER_RESOLVE_DELAY_MICROSECONDS;
    }

    /**
     * Webhook の transactionHeadIds から会員IDと取引後ポイントを解決（pos:customer-points / pos:transactions 用）。
     *
     * ポイント更新直後は customerId が未反映のことがあるため、短時間リトライする。
     * totalPoint が取れた場合はポイント同期のヒントとして返す（会員ポイントAPI失敗時のフォールバック）。
     *
     * @param  list<string|int>  $transactionHeadIds
     * @return array{customer_ids: list<string>, points_by_customer_id: array<string, int>}
     */
    public function resolveCustomersFromTransactionHeads(string $contractId, array $transactionHeadIds): array
    {
        $customerIds = [];
        $pointsByCustomerId = [];
        foreach ($transactionHeadIds as $rawHeadId) {
            $headId = trim((string) $rawHeadId);
            if ($headId === '') {
                continue;
            }
            $resolved = $this->resolveOneTransactionCustomer($contractId, $headId);
            if ($resolved === null) {
                continue;
            }
            $customerIds[] = $resolved['customer_id'];
            if ($resolved['total_point'] !== null) {
                $pointsByCustomerId[$resolved['customer_id']] = $resolved['total_point'];
            }
        }

        return [
            'customer_ids' => array_values(array_unique($customerIds)),
            'points_by_customer_id' => $pointsByCustomerId,
        ];
    }

    /**
     * @return array{customer_id: string, total_point: int|null}|null
     */
    private function resolveOneTransactionCustomer(string $contractId, string $headId): ?array
    {
        $transaction = null;
        $customerId = '';
        for ($attempt = 1; $attempt <= self::TRANSACTION_CUSTOMER_RESOLVE_ATTEMPTS; $attempt++) {
            $transaction = $this->getTransaction($contractId, $headId);
            if ($transaction === null) {
                Log::warning('[Smaregi] resolveCustomersFromTransactionHeads: getTransaction failed', [
                    'smaregi_contract_id' => $contractId,
                    'transaction_head_id' => $headId,
                    'attempt' => $attempt,
                ]);
                if ($attempt < self::TRANSACTION_CUSTOMER_RESOLVE_ATTEMPTS) {
                    usleep($this->transactionCustomerResolveDelayMicroseconds());
                }

                continue;
            }

            $customerId = $this->extractCustomerIdFromTransaction($transaction);
            if ($customerId !== '') {
                break;
            }
            if ($attempt < self::TRANSACTION_CUSTOMER_RESOLVE_ATTEMPTS) {
                usleep($this->transactionCustomerResolveDelayMicroseconds());
            }
        }

        if ($transaction === null) {
            return null;
        }
        if ($customerId === '') {
            Log::info('[Smaregi] resolveCustomersFromTransactionHeads: transaction has no customerId', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
                'transaction_head_division' => $transaction['transactionHeadDivision'] ?? null,
                'total' => $transaction['total'] ?? null,
                'new_point' => $transaction['newPoint'] ?? null,
                'total_point' => $transaction['totalPoint'] ?? null,
            ]);

            return null;
        }

        return [
            'customer_id' => $customerId,
            'total_point' => $this->parseOptionalInt($transaction['totalPoint'] ?? null),
        ];
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function extractCustomerIdFromTransaction(array $transaction): string
    {
        $customerId = $transaction['customerId'] ?? $transaction['customer_id'] ?? null;
        if ($customerId === null || trim((string) $customerId) === '') {
            $nested = $transaction['customer'] ?? null;
            if (is_array($nested)) {
                $customerId = $nested['customerId'] ?? $nested['customer_id'] ?? null;
            }
        }
        if ($customerId === null) {
            return '';
        }

        return trim((string) $customerId);
    }

    /**
     * @param  string|int|float|bool|array|null  $value
     */
    private function parseOptionalInt($value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) round($value);
        }
        if (is_string($value) && $value !== '' && is_numeric($value)) {
            return (int) round((float) $value);
        }

        return null;
    }

    /** 取引登録（pos.transactions:write） */
    public function createTransaction(string $contractId, array $body): ?array
    {
        return $this->request($contractId, 'POST', 'pos/transactions', $body);
    }

    /** 取引更新（pos.transactions:write） */
    public function updateTransaction(string $contractId, string $transactionId, array $body): ?array
    {
        return $this->request($contractId, 'PATCH', 'pos/transactions/'.$transactionId, $body);
    }

    /** 部門一覧取得（pos.products:read）。公式APIは pos/categories */
    public function getDepartments(string $contractId, array $query = []): ?array
    {
        return $this->request($contractId, 'GET', 'pos/categories', $query);
    }

    /** 部門1件取得（pos.products:read） */
    public function getDepartment(string $contractId, string $departmentId): ?array
    {
        return $this->request($contractId, 'GET', 'pos/categories/'.$departmentId);
    }

    /** 部門登録（pos.products:write） */
    public function createDepartment(string $contractId, array $body): ?array
    {
        return $this->request($contractId, 'POST', 'pos/categories', $body);
    }

    /** 部門更新（pos.products:write） */
    public function updateDepartment(string $contractId, string $departmentId, array $body): ?array
    {
        return $this->request($contractId, 'PATCH', 'pos/categories/'.$departmentId, $body);
    }

    /** 部門削除（pos.products:write） */
    public function deleteDepartment(string $contractId, string $departmentId): ?array
    {
        return $this->request($contractId, 'DELETE', 'pos/categories/'.$departmentId);
    }

    /** 部門グループ一覧取得（pos.products:read）。公式APIは pos/category_groups */
    public function getCategoryGroups(string $contractId, array $query = []): ?array
    {
        return $this->request($contractId, 'GET', 'pos/category_groups', $query);
    }

    /** 商品1件取得（pos.products:read） */
    public function getProduct(string $contractId, string $productId): ?array
    {
        return $this->request($contractId, 'GET', 'pos/products/'.$productId);
    }

    /** 商品登録（pos.products:write） */
    public function createProduct(string $contractId, array $body): ?array
    {
        return $this->request($contractId, 'POST', 'pos/products', $body);
    }

    /** 商品更新（pos.products:write） */
    public function updateProduct(string $contractId, string $productId, array $body): ?array
    {
        return $this->request($contractId, 'PATCH', 'pos/products/'.$productId, $body);
    }

    /** 商品削除（pos.products:write） */
    public function deleteProduct(string $contractId, string $productId): ?array
    {
        return $this->request($contractId, 'DELETE', 'pos/products/'.$productId);
    }

    /** 在庫一覧取得（pos.stock:read） */
    public function getStocks(string $contractId, array $query = []): ?array
    {
        return $this->request($contractId, 'GET', 'pos/stock', $query);
    }

    /** 在庫更新（pos.stock:write） */
    public function updateStock(string $contractId, string $productId, array $body): ?array
    {
        return $this->request($contractId, 'PATCH', 'pos/stock/'.$productId, $body);
    }

    /** 会員ランク一覧取得（pos.customers:read） */
    public function getCustomerRanks(string $contractId, array $query = []): ?array
    {
        return $this->request($contractId, 'GET', 'pos/customer_ranks', $query);
    }

    /** 会員ランク登録（pos.customers:write） */
    public function createCustomerRank(string $contractId, array $body): ?array
    {
        return $this->request($contractId, 'POST', 'pos/customer_ranks', $body);
    }

    /** 会員ランク更新（pos.customers:write） */
    public function updateCustomerRank(string $contractId, string $rank, array $body): ?array
    {
        return $this->request($contractId, 'PATCH', 'pos/customer_ranks/'.$rank, $body);
    }

    /** 会員ランク削除（pos.customers:write） */
    public function deleteCustomerRank(string $contractId, string $rank): ?array
    {
        return $this->request($contractId, 'DELETE', 'pos/customer_ranks/'.$rank);
    }

    /** 店舗登録（pos.stores:write） */
    public function createStore(string $contractId, array $body): ?array
    {
        return $this->request($contractId, 'POST', 'pos/stores', $body);
    }

    /** 店舗更新（pos.stores:write） */
    public function updateStore(string $contractId, string $storeId, array $body): ?array
    {
        return $this->request($contractId, 'PATCH', 'pos/stores/'.$storeId, $body);
    }

    /** 店舗削除（pos.stores:write） */
    public function deleteStore(string $contractId, string $storeId): ?array
    {
        return $this->request($contractId, 'DELETE', 'pos/stores/'.$storeId);
    }

    /** 支払方法一覧取得（pos.transactions:read） */
    public function getPaymentMethods(string $contractId, array $query = []): ?array
    {
        return $this->request($contractId, 'GET', 'pos/payment-methods', $query);
    }

    /** クーポン一覧取得（pos.transactions:read） */
    public function getCoupons(string $contractId, array $query = []): ?array
    {
        return $this->request($contractId, 'GET', 'pos/coupons', $query);
    }

    /** クーポン1件取得（pos.transactions:read） */
    public function getCoupon(string $contractId, string $couponId): ?array
    {
        return $this->request($contractId, 'GET', 'pos/coupons/'.$couponId);
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $body  クーポン登録ボディ
     * @return array<int|string, string|int|float|bool|array|null>|null
     */
    public function createCoupon(string $contractId, array $body): ?array
    {
        return $this->request($contractId, 'POST', 'pos/coupons', $body);
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $body  更新する項目のみ
     * @return array<int|string, string|int|float|bool|array|null>|null
     */
    public function updateCoupon(string $contractId, string $couponId, array $body): ?array
    {
        return $this->request($contractId, 'PATCH', 'pos/coupons/'.$couponId, $body);
    }

    /** クーポン削除（pos.transactions:write） */
    public function deleteCoupon(string $contractId, string $couponId): ?array
    {
        return $this->request($contractId, 'DELETE', 'pos/coupons/'.$couponId);
    }

    /** スタッフ一覧取得（pos.staffs:read） */
    public function getStaffs(string $contractId, array $query = []): ?array
    {
        return $this->request($contractId, 'GET', 'pos/staffs', $query);
    }

    /** 店舗1件取得（pos.stores:read） */
    public function getStore(string $contractId, string $storeId): ?array
    {
        return $this->request($contractId, 'GET', 'pos/stores/'.$storeId);
    }

    /**
     * 上記以外のエンドポイントは request() で直接指定可能。
     * 例: $service->request($contractId, 'POST', 'pos/transactions/1/cancel', []);
     * 全パス一覧は docs/SMAREGI_POS_API_REFERENCE.md を参照。
     */

    /**
     * @param  array<int|string, string|int|float|bool|array|null>  $result
     * @param  list<string>  $possibleKeys
     * @return array<int, string|int|float|bool|array|null>
     */
    private function extractList(array $result, array $possibleKeys): array
    {
        if ($result === [] || array_keys($result) === range(0, count($result) - 1)) {
            return $result;
        }

        foreach ($possibleKeys as $key) {
            $list = $result[$key] ?? null;
            if (is_array($list)) {
                return $list;
            }
        }

        return [];
    }

    /**
     * 指定プロジェクトに紐づく契約の会員ポイントを加算・減算する（プロジェクトIDから契約解決を行う）。
     *
     * @return array{customerId?: string, point?: string, mile?: string, transactionHeadIds?: array}|null 成功時はレスポンス、失敗時は null
     */
    public function syncCustomerPointsForProject(
        int $projectId,
        string $smaregiCustomerId,
        int $pointDelta,
        ?int $mileDelta = null,
        ?string $pointExpireDate = null
    ): ?array {
        $contractId = $this->getContractIdForProject($projectId);
        if ($contractId === null || $contractId === '') {
            return null;
        }

        return $this->addCustomerPoints(
            $contractId,
            $smaregiCustomerId,
            $pointDelta,
            $mileDelta,
            $pointExpireDate
        );
    }

    /**
     * プロジェクトに紐づく契約IDを取得。
     * 優先順: (1) ShopSetting (2) SmaregiContract (3) config のデフォルト契約ID（sb_skx928r2）
     */
    public function getContractIdForProject(int $projectId): ?string
    {
        $contractId = ShopSetting::getValue('smaregi_contract_id', null, $projectId);
        if (is_string($contractId) && trim($contractId) !== '') {
            return trim($contractId);
        }
        $contract = SmaregiContract::where('project_id', $projectId)
            ->where('status', SmaregiContractStatus::ACTIVE)
            ->first();

        if ($contract !== null && $contract->contract_id !== '') {
            return $contract->contract_id;
        }

        $default = config('smaregi.contract_id');
        $default = is_string($default) ? trim($default) : '';

        return $default !== '' ? $default : null;
    }
}
