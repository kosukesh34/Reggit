<?php

namespace App\Services\Admin;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * 同一プロジェクト内で商品名が重複しているレコードを統合する。
 * 注文明細は残す商品へ付け替えてから重複行を削除する。
 */
class ProductDeduplicationService
{
    public function deduplicateForProject(int $projectId): int
    {
        $deleted = 0;

        $duplicateNames = Product::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($duplicateNames as $name) {
            $products = Product::withoutGlobalScopes()
                ->where('project_id', $projectId)
                ->where('name', $name)
                ->orderByRaw("CASE WHEN smaregi_product_id IS NULL OR smaregi_product_id = '' THEN 1 ELSE 0 END")
                ->orderBy('id')
                ->get();

            if ($products->count() <= 1) {
                continue;
            }

            $keep = $products->first();
            foreach ($products->skip(1) as $duplicate) {
                DB::transaction(function () use ($keep, $duplicate, &$deleted): void {
                    if (($keep->smaregi_product_id ?? '') === '' && ($duplicate->smaregi_product_id ?? '') !== '') {
                        $keep->update(['smaregi_product_id' => $duplicate->smaregi_product_id]);
                    }

                    OrderItem::where('product_id', $duplicate->id)->update(['product_id' => $keep->id]);
                    $duplicate->delete();
                    $deleted++;
                });
            }
        }

        return $deleted;
    }
}
