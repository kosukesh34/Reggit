<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\Scopes\ProjectScope;
use App\Services\Smaregi\SmaregiProductRegisterService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductAdminService
{
    public function __construct(
        private SmaregiProductRegisterService $smaregiProductRegister
    ) {}

    public function getPaginatedList(int $perPage = 20): LengthAwarePaginator
    {
        return Product::orderByDesc('id')->paginate($perPage);
    }

    /**
     * 商品作成。スマレジ契約ありの場合はPOSへ登録を試み、成功時はsmaregi_product_idを保存する。
     * スマレジ登録に失敗してもDBには保存する（ベストエフォート）。
     */
    public function create(int $projectId, array $data): Product
    {
        $optionGroups = $data['option_groups'] ?? [];
        unset($data['option_groups']);
        $data['project_id'] = $projectId;

        if ($this->smaregiProductRegister->hasSmaregiContract($projectId)) {
            $result = $this->smaregiProductRegister->registerToSmaregi($projectId, $data);
            if ($result !== null) {
                $data['smaregi_product_id'] = $result['smaregi_product_id'];
                $this->syncExtrasToSmaregi($projectId, $result['smaregi_product_id'], $data);
            } else {
                Log::warning('[ProductAdmin] スマレジへの商品登録はスキップしましたが、DBには保存します。', ['project_id' => $projectId]);
            }
        }

        return DB::transaction(function () use ($data, $optionGroups, $projectId) {
            $product = Product::create($data);
            $this->syncOptionGroups($product, $optionGroups, $projectId);

            return $product->load('optionGroups.options');
        });
    }

    public function findOrFail(int $id): Product
    {
        return Product::findOrFail($id);
    }

    /** 指定プロジェクトに属する商品を取得。存在しない場合は ModelNotFoundException（404） */
    public function findForProjectOrFail(int $projectId, int $id): Product
    {
        return Product::withoutGlobalScope(ProjectScope::class)
            ->where('project_id', $projectId)
            ->with('optionGroups.options')
            ->findOrFail($id);
    }

    /**
     * 商品更新。スマレジ契約ありの場合はPOSへ更新/登録を試み、成功時は反映する。
     * スマレジ側の失敗時もDBは更新する。
     */
    public function update(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $projectId = (int) $product->project_id;
        $optionGroups = $data['option_groups'] ?? [];
        unset($data['option_groups']);

        if ($this->smaregiProductRegister->hasSmaregiContract($projectId)) {
            $smaregiId = $product->smaregi_product_id ?? '';
            if ($smaregiId !== null && $smaregiId !== '') {
                $ok = $this->smaregiProductRegister->updateOnSmaregi($projectId, $smaregiId, $data);
                if (! $ok) {
                    Log::warning('[ProductAdmin] スマレジの商品更新はスキップしましたが、DBは更新します。', ['project_id' => $projectId, 'product_id' => $id]);
                } else {
                    $this->syncExtrasToSmaregi($projectId, $smaregiId, $data);
                }
            } else {
                $result = $this->smaregiProductRegister->registerToSmaregi($projectId, array_merge($data, ['project_id' => $projectId]));
                if ($result !== null) {
                    $data['smaregi_product_id'] = $result['smaregi_product_id'];
                    $this->syncExtrasToSmaregi($projectId, $result['smaregi_product_id'], $data);
                } else {
                    Log::warning('[ProductAdmin] スマレジへの商品登録（更新時）はスキップしましたが、DBは更新します。', ['project_id' => $projectId, 'product_id' => $id]);
                }
            }
        }

        DB::transaction(function () use ($product, $data, $optionGroups, $projectId) {
            $product->update($data);
            $this->syncOptionGroups($product, $optionGroups, $projectId);
        });

        return $product->load('optionGroups.options');
    }

    /**
     * 商品削除。スマレジ連携済みの場合はPOSからの削除を試みる。スマレジ側の失敗時もDBからは削除する。
     */
    public function delete(int $id): void
    {
        $product = Product::findOrFail($id);
        $projectId = (int) $product->project_id;
        $smaregiId = $product->smaregi_product_id ?? '';

        if ($smaregiId !== null && $smaregiId !== '' && $this->smaregiProductRegister->hasSmaregiContract($projectId)) {
            $ok = $this->smaregiProductRegister->deleteOnSmaregi($projectId, $smaregiId);
            if (! $ok) {
                Log::warning('[ProductAdmin] スマレジの商品削除はスキップしましたが、DBからは削除します。', ['project_id' => $projectId, 'product_id' => $id]);
            }
        }

        $product->delete();
    }

    /**
     * @param  array<string, string|int|float|bool|null>  $data
     */
    private function syncExtrasToSmaregi(int $projectId, string $smaregiProductId, array $data): void
    {
        if (array_key_exists('stock', $data) && $data['stock'] !== null) {
            $this->smaregiProductRegister->syncStockToSmaregi($projectId, $smaregiProductId, (int) $data['stock']);
        }
        $this->syncImageToSmaregi($projectId, $smaregiProductId, $data);
    }

    /**
     * @param  array<string, string|int|float|bool|null>  $data
     */
    private function syncImageToSmaregi(int $projectId, string $smaregiProductId, array $data): void
    {
        if (! array_key_exists('image_url', $data)) {
            return;
        }

        $imageUrl = trim((string) ($data['image_url'] ?? ''));
        if ($imageUrl === '') {
            $this->smaregiProductRegister->deleteProductImage($projectId, $smaregiProductId);

            return;
        }

        $this->smaregiProductRegister->registerProductImage($projectId, $smaregiProductId, $imageUrl);
    }

    /** @param array<int, array<string, mixed>> $groups */
    private function syncOptionGroups(Product $product, array $groups, int $projectId): void
    {
        $product->optionGroups()->whereNull('smaregi_option_group_id')->delete();
        foreach (array_values($groups) as $groupIndex => $groupData) {
            $selectionType = (string) ($groupData['selection_type'] ?? 'single');
            $required = (bool) ($groupData['is_required'] ?? false);
            $group = $product->optionGroups()->create([
                'project_id' => $projectId,
                'name' => (string) $groupData['name'],
                'selection_type' => $selectionType,
                'is_required' => $required,
                'min_select' => $required ? 1 : 0,
                'max_select' => $selectionType === 'single' ? 1 : null,
                'sort_order' => $groupIndex,
            ]);
            foreach (array_values($groupData['options'] ?? []) as $optionIndex => $optionData) {
                $group->options()->create([
                    'project_id' => $projectId,
                    'name' => (string) $optionData['name'],
                    'price_delta' => (float) ($optionData['price_delta'] ?? 0),
                    'is_default' => false,
                    'sort_order' => $optionIndex,
                ]);
            }
        }
    }
}
