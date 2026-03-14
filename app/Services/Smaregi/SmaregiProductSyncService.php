<?php

namespace App\Services\Smaregi;

use App\Models\Product;
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
     * DB とスマレジの商品を完全同期する（双方向）。
     * - スマレジにあるが DB にない → DB に登録
     * - DB にあるがスマレジにない（または smaregi_product_id 未設定）→ スマレジに登録し DB の smaregi_product_id を更新
     * データ漏れがないように両者を揃える。
     *
     * @return array{created: int, updated: int, pushed_to_smaregi: int, failed: int, errors: list<string>}
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
            foreach ($list as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $productId = $row['productId'] ?? null;
                if ($productId === null || $productId === '') {
                    continue;
                }
                $smaregiProductIds[(string) $productId] = true;
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

        // 2. DB → スマレジ: DB にありスマレジにない商品をスマレジに登録
        $dbProducts = Product::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->get();

        foreach ($dbProducts as $product) {
            $smaregiId = $product->smaregi_product_id ? trim((string) $product->smaregi_product_id) : '';
            $needPush = $smaregiId === '' || ! isset($smaregiProductIds[$smaregiId]);
            if (! $needPush) {
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

        return [
            'created' => $created,
            'updated' => $updated,
            'pushed_to_smaregi' => $pushedToSmaregi,
            'failed' => $failed,
            'errors' => $errors,
        ];
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
            'stock' => 0,
            // image_url はスマレジ商品APIでは返らないため更新しない（既存値維持は updateOrCreate で自動）
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
