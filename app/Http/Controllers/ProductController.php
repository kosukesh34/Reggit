<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * カテゴリラベル・アイコンの定義
     */
    private const CATEGORY_META = [
        'food'    => ['label' => 'フード',       'icon' => 'fa-utensils',   'sort' => 1],
        'drink'   => ['label' => 'ドリンク',     'icon' => 'fa-coffee',     'sort' => 2],
        'dessert' => ['label' => 'デザート',     'icon' => 'fa-ice-cream',  'sort' => 3],
        'side'    => ['label' => 'サイドメニュー', 'icon' => 'fa-french-fries', 'sort' => 4],
        'other'   => ['label' => 'その他',       'icon' => 'fa-ellipsis-h', 'sort' => 99],
    ];

    public function index(Request $request)
    {
        $query = Product::query()->where('is_available', true);

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $products = $query->orderBy('category')->orderBy('name')->get();

        // カテゴリ情報を集計して返す
        $categoryCounts = Product::query()
            ->where('is_available', true)
            ->select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category');

        $categories = [];
        $categories[] = [
            'key'   => 'all',
            'label' => 'すべて',
            'icon'  => 'fa-th-large',
            'count' => $categoryCounts->sum(),
            'sort'  => 0,
        ];

        foreach ($categoryCounts as $catKey => $count) {
            $meta = self::CATEGORY_META[$catKey] ?? self::CATEGORY_META['other'];
            $categories[] = [
                'key'   => $catKey,
                'label' => $meta['label'],
                'icon'  => $meta['icon'],
                'count' => $count,
                'sort'  => $meta['sort'],
            ];
        }

        usort($categories, fn($a, $b) => $a['sort'] <=> $b['sort']);

        return response()->json([
            'products'   => $products,
            'categories' => $categories,
        ]);
    }

    public function show($id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }
}


