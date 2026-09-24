<?php

namespace App\Services\Smaregi;

use App\Models\Product;
use App\Services\Admin\ProductDeduplicationService;
use Illuminate\Support\Facades\Log;

/**
 * スマレジの商品一覧を取得し、プロジェクトの products テーブルに同期する。
 * 同期キー: project_id + smaregi_product_id（スマレジの productId）。
 * fullSync: スマレジ→DB と DB→スマレジ の双方向で完全同期し、どちらか片方にしかないデータを両方に登録する。
 */
class SmaregiProductSyncService
{
    private const LIMIT = 100;

    public function __construct(
        private SmaregiApiService $smaregiApi,
        private SmaregiProductRegisterService $smaregiProductRegister,
        private ProductDeduplicationService $productDeduplication,
        private SmaregiProductOptionSyncService $productOptionSync,
    ) {}

    /**
     * 指定プロジェクトのスマレジ契約の商品をDBに同期する。
     * 契約IDが未設定またはAPI失敗時は例外を投げる。
     *
     * @return array{created: int, updated: int, failed: int, errors: list<string>}
     */
    public function sync(int $projectId): array
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId === '') {
            throw new \RuntimeException('スマレジの契約IDが設定されていません。設定画面で契約を紐付けてください。');
        }

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $page = 1;
        $allRows = [];

        do {
            $result = $this->smaregiApi->request($contractId, 'GET', 'pos/products', [
                'limit' => self::LIMIT,
                'page' => $page,
            ]);
            if ($result === null) {
                throw new \RuntimeException('スマレジAPI（商品一覧）の取得に失敗しました。ログを確認してください。');
            }

            $list = $this->extractListFromResponse($result, 'products');
            $allRows = array_merge($allRows, $list);
            foreach ($list as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ((string) ($row['division'] ?? '0') === '2') {
                    continue;
                }
                $productId = $row['productId'] ?? null;
                if ($productId === null || $productId === '') {
                    continue;
                }
                try {
                    $attrs = $this->mapSmaregiProductToAttributes($row);
                    $product = $this->upsertProductFromSmaregi($projectId, (string) $productId, $attrs);
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

        $optionResult = $this->productOptionSync->sync($projectId, $contractId, $allRows);
        $failed += $optionResult['failed'];
        $errors = array_merge($errors, $optionResult['errors']);

        $this->productDeduplication->deduplicateForProject($projectId);

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * DB とスマレジの商品を完全同期する（双方向）。
     * - スマレジにあるが DB にない → DB に登録
     * - DB にあるがスマレジにない（または smaregi_product_id 未設定）→ スマレジに登録し DB の smaregi_product_id を更新
     * データ漏れがないように両者を揃える。
     *
     * @return array{created: int, updated: int, pushed_to_smaregi: int, deleted_from_smaregi: int, stock_synced: int, failed: int, errors: list<string>}
     */
    public function fullSync(int $projectId): array
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId === '') {
            throw new \RuntimeException('スマレジの契約IDが設定されていません。設定画面で契約を紐付けてください。');
        }

        if (! $this->smaregiProductRegister->hasSmaregiContract($projectId)) {
            throw new \RuntimeException('スマレジ契約が紐づいていません。完全同期には契約の紐付けが必要です。');
        }

        $created = 0;
        $updated = 0;
        $pushedToSmaregi = 0;
        $failed = 0;
        $errors = [];
        $smaregiProductIds = [];
        $smaregiProductNames = [];
        $allRows = [];

        // 1. スマレジ → DB: スマレジの全商品を取得して DB に反映し、スマレジ側の productId 一覧を保持
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
            $allRows = array_merge($allRows, $list);
            foreach ($list as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ((string) ($row['division'] ?? '0') === '2') {
                    continue;
                }
                $productId = $row['productId'] ?? null;
                if ($productId === null || $productId === '') {
                    continue;
                }
                $smaregiProductIds[(string) $productId] = true;
                $productName = trim((string) ($row['productName'] ?? ''));
                if ($productName !== '' && ! isset($smaregiProductNames[$productName])) {
                    $smaregiProductNames[$productName] = (string) $productId;
                }
                try {
                    $attrs = $this->mapSmaregiProductToAttributes($row);
                    $product = $this->upsertProductFromSmaregi($projectId, (string) $productId, $attrs);
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

        $optionResult = $this->productOptionSync->sync($projectId, $contractId, $allRows);
        $failed += $optionResult['failed'];
        $errors = array_merge($errors, $optionResult['errors']);

        // 2. DB → スマレジ: DB にありスマレジにない商品をスマレジに登録
        // 部門が取得できない場合は登録を試みずスキップする（失敗0で完了させる）
        $defaultCategoryId = $this->smaregiProductRegister->resolveDefaultCategoryId($projectId);
        if ($defaultCategoryId === null) {
            Log::info('[Smaregi] fullSync: DB→スマレジの登録をスキップしました（部門の取得または自動作成に失敗）。', ['project_id' => $projectId]);

            return [
                'created' => $created,
                'updated' => $updated,
                'pushed_to_smaregi' => 0,
                'deleted_from_smaregi' => 0,
                'stock_synced' => 0,
                'failed' => $failed,
                'errors' => $errors,
            ];
        }

        $dbProducts = Product::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->get();

        foreach ($dbProducts as $product) {
            $smaregiId = $product->smaregi_product_id ? trim((string) $product->smaregi_product_id) : '';
            $needPush = $smaregiId === '' || ! isset($smaregiProductIds[$smaregiId]);
            if (! $needPush) {
                continue;
            }

            $productName = trim((string) $product->name);
            if ($productName !== '' && isset($smaregiProductNames[$productName])) {
                $linkedSmaregiId = $smaregiProductNames[$productName];
                $product->update(['smaregi_product_id' => $linkedSmaregiId]);
                $smaregiProductIds[$linkedSmaregiId] = true;

                continue;
            }

            try {
                $data = [
                    'name' => $product->name,
                    'price' => (int) $product->price,
                    'description' => $product->description ?? '',
                    'category' => $product->category ?? 'other',
                    'is_available' => $product->is_available,
                ];
                if ($product->stock !== null) {
                    $data['stock'] = $product->stock;
                }
                $result = $this->smaregiProductRegister->registerToSmaregi($projectId, $data);
                if ($result === null) {
                    $failed++;
                    $errors[] = sprintf('DB商品 id=%d "%s": スマレジ登録に失敗しました', $product->id, $product->name);
                    Log::warning('[Smaregi] fullSync: register to Smaregi failed', [
                        'project_id' => $projectId,
                        'product_id' => $product->id,
                        'name' => $product->name,
                    ]);

                    continue;
                }
                $newSmaregiId = $result['smaregi_product_id'];
                $product->update(['smaregi_product_id' => $newSmaregiId]);
                $smaregiProductIds[$newSmaregiId] = true;
                $pushedToSmaregi++;

                if (isset($data['stock']) && (int) $data['stock'] !== 0) {
                    $this->smaregiProductRegister->syncStockToSmaregi($projectId, $newSmaregiId, (int) $data['stock']);
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = sprintf('DB商品 id=%d "%s": %s', $product->id, $product->name, $e->getMessage());
                Log::warning('[Smaregi] fullSync: push to Smaregi failed', [
                    'project_id' => $projectId,
                    'product_id' => $product->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $smaregiDedupe = $this->deduplicateSmaregiProducts($projectId, $contractId);
        $failed += $smaregiDedupe['failed'];
        $errors = array_merge($errors, $smaregiDedupe['errors']);

        $stockSync = $this->syncAllStockToSmaregi($projectId);
        $failed += $stockSync['failed'];
        $errors = array_merge($errors, $stockSync['errors']);

        $this->productDeduplication->deduplicateForProject($projectId);

        return [
            'created' => $created,
            'updated' => $updated,
            'pushed_to_smaregi' => $pushedToSmaregi,
            'deleted_from_smaregi' => $smaregiDedupe['deleted'],
            'stock_synced' => $stockSync['synced'],
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * スマレジ上の同名商品を整理する。DB に紐づく productId を残し、それ以外の重複を削除する。
     *
     * @return array{deleted: int, failed: int, errors: list<string>}
     */
    public function deduplicateSmaregiProducts(int $projectId, ?string $contractId = null): array
    {
        $contractId = $contractId ?? $this->smaregiApi->getContractIdForProject($projectId);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId === '' || ! $this->smaregiProductRegister->hasSmaregiContract($projectId)) {
            return ['deleted' => 0, 'failed' => 0, 'errors' => []];
        }

        $keepIdsByName = Product::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->whereNotNull('smaregi_product_id')
            ->where('smaregi_product_id', '!=', '')
            ->get(['name', 'smaregi_product_id'])
            ->mapWithKeys(fn (Product $product) => [trim($product->name) => trim((string) $product->smaregi_product_id)])
            ->all();

        $byName = [];
        $page = 1;
        do {
            $result = $this->smaregiApi->request($contractId, 'GET', 'pos/products', [
                'limit' => self::LIMIT,
                'page' => $page,
            ]);
            if ($result === null) {
                return [
                    'deleted' => 0,
                    'failed' => 1,
                    'errors' => ['スマレジAPI（商品一覧）の取得に失敗しました。'],
                ];
            }

            $list = $this->extractListFromResponse($result, 'products');
            foreach ($list as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $productId = trim((string) ($row['productId'] ?? ''));
                $productName = trim((string) ($row['productName'] ?? ''));
                if ($productId === '' || $productName === '') {
                    continue;
                }
                $byName[$productName][] = $productId;
            }
            $page++;
        } while (count($list) === self::LIMIT);

        $deleted = 0;
        $failed = 0;
        $errors = [];

        foreach ($byName as $name => $ids) {
            if (count($ids) <= 1) {
                continue;
            }

            $keepId = $keepIdsByName[$name] ?? $ids[0];
            if (! in_array($keepId, $ids, true)) {
                $keepId = $ids[0];
            }

            $dbProduct = Product::withoutGlobalScopes()
                ->where('project_id', $projectId)
                ->where('name', $name)
                ->first();
            if ($dbProduct !== null && trim((string) ($dbProduct->smaregi_product_id ?? '')) !== $keepId) {
                $dbProduct->update(['smaregi_product_id' => $keepId]);
            }

            foreach ($ids as $id) {
                if ($id === $keepId) {
                    continue;
                }
                $ok = $this->smaregiProductRegister->deleteOnSmaregi($projectId, $id);
                if ($ok) {
                    $deleted++;
                } else {
                    $failed++;
                    $errors[] = sprintf('スマレジ商品削除失敗 productId=%s name=%s', $id, $name);
                }
            }
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * DB 上の在庫数をスマレジへ反映する。
     *
     * @return array{synced: int, failed: int, errors: list<string>}
     */
    public function syncAllStockToSmaregi(int $projectId): array
    {
        if (! $this->smaregiProductRegister->hasSmaregiContract($projectId)) {
            return ['synced' => 0, 'failed' => 0, 'errors' => []];
        }

        $synced = 0;
        $failed = 0;
        $errors = [];

        $products = Product::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->whereNotNull('smaregi_product_id')
            ->where('smaregi_product_id', '!=', '')
            ->whereNotNull('stock')
            ->get(['id', 'project_id', 'name', 'stock', 'smaregi_product_id']);

        foreach ($products as $product) {
            $smaregiId = trim((string) $product->smaregi_product_id);
            $ok = $this->smaregiProductRegister->syncStockToSmaregi($projectId, $smaregiId, (int) $product->stock);
            if ($ok) {
                $synced++;
            } else {
                $failed++;
                $errors[] = sprintf('在庫同期失敗 product_id=%d smaregi=%s name=%s', $product->id, $smaregiId, $product->name);
            }
        }

        return [
            'synced' => $synced,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * DB 上の商品画像をスマレジへ反映する。
     *
     * @return array{synced: int, failed: int, errors: list<string>}
     */
    public function syncAllImagesToSmaregi(int $projectId): array
    {
        if (! $this->smaregiProductRegister->hasSmaregiContract($projectId)) {
            return ['synced' => 0, 'failed' => 0, 'errors' => []];
        }

        $synced = 0;
        $failed = 0;
        $errors = [];

        $products = Product::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->whereNotNull('smaregi_product_id')
            ->where('smaregi_product_id', '!=', '')
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->get(['id', 'project_id', 'name', 'image_url', 'smaregi_product_id']);

        foreach ($products as $product) {
            $smaregiId = trim((string) $product->smaregi_product_id);
            $ok = $this->smaregiProductRegister->registerProductImage(
                $projectId,
                $smaregiId,
                (string) $product->image_url
            );
            if ($ok) {
                $synced++;
            } else {
                $failed++;
                $errors[] = sprintf('画像同期失敗 product_id=%d smaregi=%s name=%s', $product->id, $smaregiId, $product->name);
            }
        }

        return [
            'synced' => $synced,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * スマレジ商品を DB に取り込む。smaregi_product_id または商品名で既存行に紐付け、同名の重複行を作らない。
     *
     * @param  array<string, string|int|float|bool|null>  $attrs
     */
    private function upsertProductFromSmaregi(int $projectId, string $smaregiProductId, array $attrs): Product
    {
        $bySmaregi = Product::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where('smaregi_product_id', $smaregiProductId)
            ->first();

        if ($bySmaregi !== null) {
            $bySmaregi->update($attrs);

            return $bySmaregi;
        }

        $name = trim((string) ($attrs['name'] ?? ''));
        if ($name !== '') {
            $byName = Product::withoutGlobalScopes()
                ->where('project_id', $projectId)
                ->where('name', $name)
                ->orderByRaw("CASE WHEN smaregi_product_id IS NULL OR smaregi_product_id = '' THEN 1 ELSE 0 END")
                ->orderBy('id')
                ->first();

            if ($byName !== null) {
                $byName->update(array_merge($attrs, ['smaregi_product_id' => $smaregiProductId]));

                return $byName;
            }
        }

        return Product::withoutGlobalScopes()->create(array_merge($attrs, [
            'project_id' => $projectId,
            'smaregi_product_id' => $smaregiProductId,
        ]));
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
     * @param  array<string, string|int|float|bool|array|null>  $row  スマレジ商品1件
     * @return array<string, string|int|float|bool|null> Product用attributes
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
            // stock / image_url はスマレジ商品APIでは正しく返らないため更新しない（既存値を維持）
        ];
    }

    /**
     * @param  string|int|null  $categoryId  スマレジ部門ID
     */
    private function mapCategoryIdToAppCategory(string|int|null $categoryId): string
    {
        $allowed = ['food', 'drink', 'dessert', 'side', 'other'];

        // 必要に応じて config や DB で部門ID→カテゴリのマッピングを追加可能
        return in_array((string) $categoryId, $allowed, true) ? (string) $categoryId : 'other';
    }
}
