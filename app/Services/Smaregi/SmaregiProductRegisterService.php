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
     * @param array<string, string|int|float|bool|array|null> $data フォーム（name, price, description, category, is_available 等）
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
            return null;
        }

        $result = $this->smaregiApi->createProduct($contractId, $body);
        if ($result === null) {
            return null;
        }

        $smaregiProductId = $this->extractProductIdFromResponse($result);
        if ($smaregiProductId === null) {
            return null;
        }

        return ['smaregi_product_id' => $smaregiProductId];
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $data フォームデータ
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
     */
    private function getDefaultCategoryId(string $contractId): ?string
    {
        $result = $this->smaregiApi->getDepartments($contractId, ['limit' => 1]);
        if ($result === null) {
            return null;
        }

        $list = $this->extractListFromResponse($result, 'categories');
        $first = $list[0] ?? null;
        if (! is_array($first)) {
            return null;
        }

        $id = $first['categoryId'] ?? $first['category_id'] ?? $first['id'] ?? null;
        if ($id !== null) {
            return (string) $id;
        }

        return null;
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $data
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
            Log::warning('[Smaregi] product register skipped no category', ['project_id' => $projectId]);

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
            $body['productCode'] = 'APP-'.uniqid('', true);
        }

        $description = trim((string) ($data['description'] ?? ''));
        if ($description !== '') {
            $body['description'] = mb_substr($description, 0, self::DESCRIPTION_MAX_LENGTH);
        }

        return $body;
    }

    /**
     * @param array<int|string, string|int|float|bool|array|null> $result API一覧応答
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
     * @param array<int|string, string|int|float|bool|array|null> $response スマレジAPI応答
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
