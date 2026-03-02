<?php

namespace App\Services\Smaregi;

use App\Models\Product;
use App\Models\ShopSetting;
use Illuminate\Support\Facades\Log;

/**
 * スマレジの商品一覧を取得し、プロジェクトの products テーブルに同期する。
 * 同期キー: project_id + smaregi_product_id（スマレジの productId）。
 */
class SmaregiProductSyncService
{
    private const LIMIT = 100;

    public function __construct(
        private SmaregiApiService $smaregiApi,
    ) {}

    /**
     * 指定プロジェクトのスマレジ契約の商品をDBに同期する。
     * 契約IDが未設定またはAPI失敗時は例外を投げる。
     *
     * @return array{created: int, updated: int, failed: int, errors: list<string>}
     */
    public function sync(int $projectId): array
    {
        $contractId = ShopSetting::getValue('smaregi_contract_id', null, $projectId);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId === '') {
            throw new \RuntimeException('スマレジの契約IDが設定されていません。設定画面で契約を紐付けてください。');
        }

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $page = 1;

        do {
            $result = $this->smaregiApi->request($contractId, 'GET', 'pos/products', [
                'limit' => self::LIMIT,
                'page' => $page,
            ]);
            if ($result === null) {
                throw new \RuntimeException('スマレジAPI（商品一覧）の取得に失敗しました。ログを確認してください。');
            }

            $list = $this->extractListFromResponse($result, 'products');
            foreach ($list as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $productId = $row['productId'] ?? null;
                if ($productId === null || $productId === '') {
                    continue;
                }
                try {
                    $attrs = $this->mapSmaregiProductToAttributes($row);
                    $product = Product::withoutGlobalScopes()->updateOrCreate(
                        [
                            'project_id' => $projectId,
                            'smaregi_product_id' => (string) $productId,
                        ],
                        $attrs
                    );
                    if ($product->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = sprintf('productId %s: %s', $productId, $e->getMessage());
                    Log::warning('[Smaregi] product sync failed', [
                        'project_id' => $projectId,
                        'smaregi_product_id' => $productId,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $page++;
        } while (count($list) === self::LIMIT);

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * スマレジAPIの一覧レスポンスから商品配列を取り出す。
     *
     * @param  array<int|string, mixed>  $result
     * @return array<int, mixed>
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
     * スマレジ商品1件をアプリの Product 用 attributes に変換する。
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapSmaregiProductToAttributes(array $row): array
    {
        $name = trim((string) ($row['productName'] ?? ''));
        if ($name === '') {
            $name = '(商品名なし)';
        }
        $price = isset($row['price']) ? (int) $row['price'] : 0;
        $description = isset($row['description']) ? trim((string) $row['description']) : null;
        $displayFlag = (string) ($row['displayFlag'] ?? '1');
        $isAvailable = $displayFlag === '1';

        $category = $this->mapCategoryIdToAppCategory($row['categoryId'] ?? null);

        return [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'category' => $category,
            'is_available' => $isAvailable,
            'stock' => 0,
            // image_url はスマレジ商品APIでは返らないため更新しない（既存値維持は updateOrCreate で自動）
        ];
    }

    /**
     * スマレジの部門ID（categoryId）をアプリの category にマッピングする。
     * 未設定時は 'other'。
     */
    private function mapCategoryIdToAppCategory(mixed $categoryId): string
    {
        $allowed = ['food', 'drink', 'dessert', 'side', 'other'];

        // 必要に応じて config や DB で部門ID→カテゴリのマッピングを追加可能
        return in_array((string) $categoryId, $allowed, true) ? (string) $categoryId : 'other';
    }
}
