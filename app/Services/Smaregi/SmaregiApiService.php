<?php

namespace App\Services\Smaregi;

use App\Enums\SmaregiContractStatus;
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

    public function getLastErrorDetail(): ?string
    {
        return $this->lastErrorDetail;
    }

    public function clearLastErrorDetail(): void
    {
        $this->lastErrorDetail = null;
    }

    private const CACHE_KEY_PREFIX = 'smaregi_access_token:';

    private const CACHE_BUFFER_SECONDS = 60;

    public function getAppAccessToken(string $contractId): ?string
    {
        $cacheKey = self::CACHE_KEY_PREFIX.$contractId;
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $clientId = config('smaregi.client_id');
        $clientSecret = config('smaregi.client_secret');
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
     * @return array<string, string|int|float|bool|array|null>|null
     */
    public function getCustomerPoint(string $contractId, string $customerId): ?array
    {
        $customerId = trim($customerId);
        if ($customerId === '') {
            return null;
        }

        $direct = $this->request($contractId, 'GET', 'pos/customers/'.$customerId.'/points');
        if ($direct !== null) {
            $normalized = $this->normalizeCustomerPointRecord($direct);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        $legacy = $this->getCustomerPoints($contractId, [
            'customer_id' => $customerId,
            'limit' => 1,
        ]);
        if ($legacy === null) {
            return null;
        }

        return $this->normalizeCustomerPointRecord($legacy);
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

    /** 会員削除（pos.customers:write） */
    public function deleteCustomer(string $contractId, string $customerId): ?array
    {
        return $this->request($contractId, 'DELETE', 'pos/customers/'.$customerId);
    }

    /** 取引一覧取得（pos.transactions:read） */
    public function getTransactions(string $contractId, array $query = []): ?array
    {
        return $this->request($contractId, 'GET', 'pos/transactions', $query);
    }

    /** 取引1件取得（pos.transactions:read） */
    public function getTransaction(string $contractId, string $transactionId): ?array
    {
        return $this->request($contractId, 'GET', 'pos/transactions/'.$transactionId);
    }

    /**
     * Webhook の transactionHeadIds から会員IDを解決（pos:customer-points / pos:transactions 用）。
     *
     * @param  list<string|int>  $transactionHeadIds
     * @return list<string>
     */
    public function resolveCustomerIdsFromTransactionHeads(string $contractId, array $transactionHeadIds): array
    {
        $customerIds = [];
        foreach ($transactionHeadIds as $rawHeadId) {
            $headId = trim((string) $rawHeadId);
            if ($headId === '') {
                continue;
            }
            $transaction = $this->getTransaction($contractId, $headId);
            if ($transaction === null) {
                Log::warning('[Smaregi] resolveCustomerIdsFromTransactionHeads: getTransaction failed', [
                    'smaregi_contract_id' => $contractId,
                    'transaction_head_id' => $headId,
                ]);

                continue;
            }
            $customerId = $transaction['customerId'] ?? $transaction['customer_id'] ?? null;
            if ($customerId === null || trim((string) $customerId) === '') {
                Log::info('[Smaregi] resolveCustomerIdsFromTransactionHeads: transaction has no customerId', [
                    'smaregi_contract_id' => $contractId,
                    'transaction_head_id' => $headId,
                ]);

                continue;
            }
            $customerIds[] = (string) $customerId;
        }

        return array_values(array_unique($customerIds));
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
