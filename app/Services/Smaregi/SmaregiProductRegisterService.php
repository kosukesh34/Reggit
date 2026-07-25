<?php

namespace App\Services\Smaregi;

use App\Util\StrHelper;
use Illuminate\Support\Facades\Log;

class SmaregiProductRegisterService
{
    private const PRODUCT_NAME_MAX_LENGTH = 85;

    private const DESCRIPTION_MAX_LENGTH = 2000;

    private const STOCK_AMOUNT_MIN = -999999999;

    private const STOCK_AMOUNT_MAX = 999999999;

    private SmaregiApiService $smaregiApi;

    public function __construct(SmaregiApiService $smaregiApi)
    {
        $this->smaregiApi = $smaregiApi;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $data  フォーム（name, price, description, category, is_available 等）
     * @return array{smaregi_product_id: string}|null
     */
    public function registerToSmaregi(int $projectId, array $data): ?array
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return null;
        }

        $body = $this->buildBodyFromArray($projectId, $data, null);
        if ($body === null) {
            Log::warning('[Smaregi] product register failed: could not build body (no category or department). See above for "product register skipped no category" or "create default department failed".', ['project_id' => $projectId]);

            return null;
        }

        $result = $this->smaregiApi->createProduct($contractId, $body);
        if ($result === null) {
            Log::warning('[Smaregi] product register failed: createProduct API returned null. Check above for "API request failed" with path pos/products.', ['project_id' => $projectId]);

            return null;
        }

        $smaregiProductId = $this->extractProductIdFromResponse($result);
        if ($smaregiProductId === null) {
            Log::warning('[Smaregi] product register failed: could not extract productId from API response.', ['project_id' => $projectId, 'response_keys' => array_keys($result)]);

            return null;
        }

        return ['smaregi_product_id' => $smaregiProductId];
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $data  フォームデータ
     */
    public function updateOnSmaregi(int $projectId, string $smaregiProductId, array $data): bool
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return true;
        }

        $body = $this->buildBodyFromArray($projectId, $data, $smaregiProductId);
        if ($body === null) {
            return false;
        }

        $result = $this->smaregiApi->updateProduct($contractId, $smaregiProductId, $body);

        return $result !== null;
    }

    /**
     * スマレジに契約がある場合、スマレジ上の商品を削除する。成功時 true。
     */
    public function deleteOnSmaregi(int $projectId, string $smaregiProductId): bool
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return true;
        }

        $result = $this->smaregiApi->deleteProduct($contractId, $smaregiProductId);

        return $result !== null;
    }

    /**
     * プロジェクトにスマレジ契約が紐づいているか
     */
    public function hasSmaregiContract(int $projectId): bool
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);

        return $contractId !== null && $contractId !== '';
    }

    /**
     * 商品登録に必要な部門IDを取得する。取得できない場合（部門0件で自動作成も失敗）は null。
     * fullSync で DB→スマレジ を試す前に呼び、null なら登録処理をスキップして失敗0で返すために使う。
     */
    public function resolveDefaultCategoryId(int $projectId): ?string
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return null;
        }

        return $this->getDefaultCategoryId($contractId);
    }

    /**
     * 商品画像をスマレジへ登録する（非同期）。imageUrl はスマレジから取得可能な公開URLであること。
     */
    public function registerProductImage(int $projectId, string $smaregiProductId, string $imageUrl, ?string $callbackUrl = null): bool
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return true;
        }

        $trimmedUrl = trim($imageUrl);
        if ($trimmedUrl === '') {
            return false;
        }

        $body = ['imageUrl' => mb_substr($trimmedUrl, 0, 511)];
        if ($callbackUrl !== null && trim($callbackUrl) !== '') {
            $body['callbackUrl'] = mb_substr(trim($callbackUrl), 0, 511);
        }

        $result = $this->smaregiApi->putProductImage($contractId, $smaregiProductId, $body);

        return $result !== null;
    }

    /**
     * 商品の在庫数をスマレジに反映する。先頭店舗の在庫として更新する。店舗が無い場合は何もしない。
     */
    public function syncStockToSmaregi(int $projectId, string $smaregiProductId, int $stockAmount): bool
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return true;
        }

        $storeId = $this->smaregiApi->getFirstStoreId($contractId);
        if ($storeId === null) {
            Log::warning('[Smaregi] stock sync skipped no store', ['project_id' => $projectId]);

            return false;
        }

        $amount = max(self::STOCK_AMOUNT_MIN, min(self::STOCK_AMOUNT_MAX, $stockAmount));
        $body = ['storeId' => $storeId, 'stockAmount' => (string) $amount];
        $result = $this->smaregiApi->updateStock($contractId, $smaregiProductId, $body);

        return $result !== null;
    }

    /**
     * 契約の部門一覧から先頭の部門IDを取得する（商品登録で categoryId 必須のため）。
     * 部門が1件もない場合はデフォルト部門を自動作成してそのIDを返す。
     */
    private function getDefaultCategoryId(string $contractId): ?string
    {
        $result = $this->smaregiApi->getDepartments($contractId, ['limit' => 1]);
        if ($result === null) {
            Log::warning('[Smaregi] getDefaultCategoryId: getDepartments (pos/categories) returned null. Check token and scope pos.products:read.', ['contract_id' => $contractId]);

            return null;
        }

        $list = $this->extractListFromResponse($result, 'categories');
        if ($list === []) {
            $list = $this->extractListFromResponse($result, 'data');
        }
        $first = $list[0] ?? null;
        if (is_array($first)) {
            $id = $first['categoryId'] ?? $first['category_id'] ?? $first['id'] ?? null;
            if ($id !== null) {
                return (string) $id;
            }
        }

        // 部門が0件の場合はデフォルト部門を自動作成
        return $this->createDefaultDepartment($contractId);
    }

    /**
     * スマレジにデフォルト部門を1件登録し、その部門IDを返す。失敗時は null。
     */
    private function createDefaultDepartment(string $contractId): ?string
    {
        $body = [
            'categoryCode' => 'APPDEF'.uniqid('', false),
            'categoryName' => 'デフォルト部門',
            'categoryAbbr' => 'デフォルト',
            'displaySequence' => '0',
            'displayFlag' => '1',
            'taxDivision' => '0',
            'pointNotApplicable' => '0',
            'taxFreeDivision' => '0',
        ];
        $result = $this->smaregiApi->createDepartment($contractId, $body);
        if ($result === null || ! is_array($result)) {
            Log::warning('[Smaregi] create default department failed', ['contract_id' => $contractId]);

            return null;
        }

        $id = $result['categoryId'] ?? $result['category_id'] ?? $result['id'] ?? null;
        if ($id !== null) {
            Log::info('[Smaregi] default department created', ['contract_id' => $contractId, 'category_id' => $id]);

            return (string) $id;
        }

        return null;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $data
     * @return array<string, string|int|float|bool|null>|null
     */
    private function buildBodyFromArray(int $projectId, array $data, ?string $smaregiProductId): ?array
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return null;
        }

        $categoryId = $this->getDefaultCategoryId($contractId);
        if ($categoryId === null) {
            Log::warning('[Smaregi] product register skipped no category (部門が0件でデフォルト部門の自動作成にも失敗した可能性があります。直上の [Smaregi] ログを確認してください)', ['project_id' => $projectId]);

            return null;
        }

        $productName = trim((string) ($data['name'] ?? ''));
        if ($productName === '') {
            return null;
        }

        $price = isset($data['price']) ? (int) round((float) $data['price']) : 0;
        if ($price < 0) {
            $price = 0;
        }

        $isAvailable = isset($data['is_available'])
            && ($data['is_available'] === true || $data['is_available'] === 1 || $data['is_available'] === '1');
        $body = [
            'categoryId' => $categoryId,
            'productName' => mb_substr($productName, 0, self::PRODUCT_NAME_MAX_LENGTH),
            'price' => (string) $price,
            'displayFlag' => $isAvailable ? '1' : '0',
        ];

        if ($smaregiProductId !== null && $smaregiProductId !== '') {
            // 更新時は productCode を送らなくてよい（PATCH は変更項目のみ）
        } else {
            $body['productCode'] = substr(uniqid('A'), 0, 20);
        }

        $description = trim((string) ($data['description'] ?? ''));
        if ($description !== '') {
            $body['description'] = mb_substr($description, 0, self::DESCRIPTION_MAX_LENGTH);
        }

        return $body;
    }

    /**
     * @param  array<int|string, string|int|float|bool|array|null>  $result  API一覧応答
     * @return array<int|string, string|int|float|bool|array|null>
     */
    private function extractListFromResponse(array $result, string $listKey): array
    {
        if (array_keys($result) === range(0, count($result) - 1) || $result === []) {
            return $result;
        }
        $list = $result[$listKey] ?? [];

        return is_array($list) ? $list : [];
    }

    /**
     * @param  array<int|string, string|int|float|bool|array|null>  $response  スマレジAPI応答
     */
    private function extractProductIdFromResponse(array $response): ?string
    {
        $id = $response['productId'] ?? $response['product_id'] ?? null;
        if ($id !== null) {
            return (string) $id;
        }
        if (isset($response['id'])) {
            return (string) $response['id'];
        }

        return null;
    }
}
