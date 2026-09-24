<?php

namespace App\Services\Smaregi;

use App\Models\Product;
use App\Models\ProductOptionGroup;
use Illuminate\Support\Facades\DB;

class SmaregiProductOptionSyncService
{
    public function __construct(private SmaregiApiService $api) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{updated: int, failed: int, errors: list<string>}
     */
    public function sync(int $projectId, string $contractId, array $rows): array
    {
        $productsById = [];
        $groupIds = [];
        foreach ($rows as $row) {
            $id = trim((string) ($row['productId'] ?? ''));
            if ($id === '') {
                continue;
            }
            $productsById[$id] = $row;
            if ((string) ($row['division'] ?? '0') === '0') {
                $groupId = trim((string) ($row['productOptionGroupId'] ?? ''));
                if ($groupId !== '') {
                    $groupIds[$groupId] = true;
                }
            }
        }

        $groupsById = [];
        $errors = [];
        foreach (array_keys($groupIds) as $groupId) {
            $response = $this->api->request($contractId, 'GET', 'pos/product_option_groups/'.rawurlencode($groupId), ['with_products' => 'all']);
            $group = isset($response[0]) ? $response[0] : $response;
            if (! is_array($group) || ! isset($group['productOptionGroupId'])) {
                $errors[] = "オプショングループ {$groupId} の取得に失敗しました";

                continue;
            }
            $groupsById[$groupId] = $group;
        }

        $updated = 0;
        foreach ($rows as $row) {
            if ((string) ($row['division'] ?? '0') !== '0') {
                continue;
            }
            $remoteId = trim((string) ($row['productId'] ?? ''));
            $groupId = trim((string) ($row['productOptionGroupId'] ?? ''));
            if ($remoteId === '' || ($groupId !== '' && ! isset($groupsById[$groupId]))) {
                continue;
            }
            $product = Product::withoutGlobalScopes()->where('project_id', $projectId)
                ->where('smaregi_product_id', $remoteId)->first();
            if ($product === null) {
                continue;
            }

            DB::transaction(function () use ($projectId, $product, $groupId, $groupsById, $productsById) {
                $product->optionGroups()->whereNotNull('smaregi_option_group_id')
                    ->when($groupId !== '', fn ($query) => $query->where('smaregi_option_group_id', '!=', $groupId))
                    ->delete();
                if ($groupId === '') {
                    return;
                }

                $remoteGroup = $groupsById[$groupId];
                $min = max(0, (int) ($remoteGroup['min'] ?? 0));
                $max = max(0, (int) ($remoteGroup['max'] ?? 0));
                $group = ProductOptionGroup::updateOrCreate(
                    ['project_id' => $projectId, 'product_id' => $product->id, 'smaregi_option_group_id' => $groupId],
                    [
                        'name' => (string) ($remoteGroup['productOptionGroupName'] ?? 'オプション'),
                        'selection_type' => $max === 1 ? ProductOptionGroup::TYPE_SINGLE : ProductOptionGroup::TYPE_MULTIPLE,
                        'is_required' => $min > 0,
                        'min_select' => $min,
                        'max_select' => $max > 0 ? $max : null,
                        'sort_order' => 0,
                    ],
                );

                $remoteOptions = $remoteGroup['products'] ?? [];
                if (isset($remoteOptions['productId'])) {
                    $remoteOptions = [$remoteOptions];
                }
                $validIds = [];
                foreach ($remoteOptions as $index => $remoteOption) {
                    $optionId = trim((string) ($remoteOption['productId'] ?? ''));
                    $optionProduct = $productsById[$optionId] ?? null;
                    if ($optionId === '' || ! is_array($optionProduct)) {
                        continue;
                    }
                    $validIds[] = $optionId;
                    $group->options()->updateOrCreate(
                        ['smaregi_product_id' => $optionId],
                        [
                            'project_id' => $projectId,
                            'name' => (string) ($optionProduct['productName'] ?? ''),
                            'price_delta' => max(0, (int) ($optionProduct['price'] ?? 0)),
                            'is_default' => false,
                            'sort_order' => $index,
                        ],
                    );
                }
                $group->options()->whereNotIn('smaregi_product_id', $validIds)->delete();
            });
            $updated++;
        }

        return ['updated' => $updated, 'failed' => count($errors), 'errors' => $errors];
    }
}
