<?php

namespace App\Services\Smaregi;

use App\Enums\SmaregiContractStatus;
use App\Models\ShopSetting;
use App\Models\SmaregiContract;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * スマレジ・プラットフォームAPI 呼び出し用サービス。
 * アプリアクセストークン（Client Credentials）で契約単位のAPIを実行する。
 */
class SmaregiApiService
{
    public function __construct(
        private SmaregiApiClient $client,
        private SmaregiTransactionCustomerResolver $transactionCustomerResolver,
    ) {}

    public function getLastErrorDetail(): ?string
    {
        return $this->client->getLastErrorDetail();
    }

    public function getLastHttpStatus(): ?int
    {
        return $this->client->getLastHttpStatus();
    }

    public function clearLastErrorDetail(): void
    {
        $this->client->clearLastErrorDetail();
    }

    public function getAppAccessToken(string $contractId): ?string
    {
        return $this->client->getAppAccessToken($contractId);
    }

    /**
     * 契約IDに紐づくプロジェクトの credentials を優先し、なければ .env にフォールバックする。
     *
     * @return array{client_id: string, client_secret: string}
     */
    public function resolveCredentialsForContract(string $contractId): array
    {
        return $this->client->resolveCredentialsForContract($contractId);
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
        return $this->client->request($contractId, $method, $path, $body, $bodyAsRequestJsonForm);
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

    /**
     * 取引登録などで使用する先頭レジ端末IDを取得する。端末が無い場合は null。
     */
    public function getFirstTerminalId(string $contractId, ?string $storeId = null): ?string
    {
        $query = [];
        if ($storeId !== null && trim($storeId) !== '') {
            $query['store_id'] = trim($storeId);
        }
        $result = $this->getTerminals($contractId, $query);
        if ($result === null) {
            return null;
        }
        $list = is_array($result) && ! isset($result[0]) && isset($result['terminals'])
            ? $result['terminals']
            : $result;
        $first = is_array($list) ? ($list[0] ?? null) : null;
        if (! is_array($first)) {
            return null;
        }
        $id = $first['terminalId'] ?? $first['terminal_id'] ?? $first['id'] ?? null;

        return $id !== null ? (string) $id : null;
    }

    /**
     * @param  array<string, int|string>  $query
     */
    public function getTerminals(string $contractId, array $query = []): ?array
    {
        return $this->request($contractId, 'GET', 'pos/terminals', $query);
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

        $list = $this->client->extractList($result, ['customers']);
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

        $list = $this->client->extractList($result, ['customer_points', 'customerPoints', 'points']);
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

    /** 会員削除（pos.customers:write）。Not Found は既に削除済みとみなし成功扱い。 */
    public function deleteCustomer(string $contractId, string $customerId): ?array
    {
        $result = $this->request($contractId, 'DELETE', 'pos/customers/'.$customerId);
        if ($result !== null) {
            return $result;
        }
        if ($this->client->getLastHttpStatus() === Response::HTTP_NOT_FOUND) {
            Log::info('[Smaregi] deleteCustomer treated as success (already gone)', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $customerId,
            ]);
            $this->client->clearLastErrorDetail();

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

    /**
     * Webhook の transactionHeadIds から会員IDと取引後ポイントを解決（pos:customer-points / pos:transactions 用）。
     *
     * @param  list<string|int>  $transactionHeadIds
     * @return array{customer_ids: list<string>, points_by_customer_id: array<string, int>}
     */
    public function resolveCustomersFromTransactionHeads(string $contractId, array $transactionHeadIds): array
    {
        return $this->transactionCustomerResolver->resolveCustomersFromTransactionHeads(
            $contractId,
            $transactionHeadIds,
        );
    }

    /** 取引登録（pos.transactions:write） */
    public function createTransaction(string $contractId, array $body): ?array
    {
        return $this->request($contractId, 'POST', 'pos/transactions', $body);
    }

    /** 取置き登録（pos.transactions:write） */
    public function createLayaway(string $contractId, array $body): ?array
    {
        return $this->request($contractId, 'POST', 'pos/transactions/layaways', $body);
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

    /**
     * 商品画像登録（pos.products:write）。非同期受付。
     *
     * @param  array{imageUrl: string, callbackUrl?: string, state?: string}  $body
     */
    public function putProductImage(string $contractId, string $productId, array $body): ?array
    {
        return $this->request($contractId, 'PUT', 'pos/products/'.$productId.'/image', $body);
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
