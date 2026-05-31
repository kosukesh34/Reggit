<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Project;
use App\Services\Admin\ProductDeduplicationService;
use Illuminate\Database\Seeder;

/**
 * デフォルトプロジェクト用の商品・クーポンを投入する。
 * スマレジ未連携時や「デフォルトデータで上書き」したいときに使用。
 */
class DefaultDataSeeder extends Seeder
{
    public function runForProject(Project $project, bool $forceProducts = false, bool $forceCoupons = false): void
    {
        $this->seedCouponsOnly($project, $forceCoupons);
        app(ProductDeduplicationService::class)->deduplicateForProject((int) $project->id);

        if ($forceProducts) {
            Product::withoutGlobalScopes()->where('project_id', $project->id)->delete();
            $this->seedProductsOnly($project);

            return;
        }

        if ($this->hasAllDefaultProducts($project)) {
            return;
        }

        $this->seedProductsOnly($project);
    }

    /** デフォルトのサンプルクーポンのみ投入（既存がある場合はスキップ。$force で上書き） */
    public function seedCouponsOnly(Project $project, bool $force = false): void
    {
        if (Coupon::where('project_id', $project->id)->exists() && ! $force) {
            return;
        }

        if ($force) {
            Coupon::where('project_id', $project->id)->delete();
        }

        $this->createSampleCoupons($project);
    }

    /** デフォルト商品のみ投入（同名は updateOrCreate で上書きし、重複を作らない） */
    public function seedProductsOnly(Project $project): void
    {
        foreach ($this->getDefaultProducts() as $product) {
            Product::withoutGlobalScopes()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'name' => $product['name'],
                ],
                $product
            );
        }

        app(ProductDeduplicationService::class)->deduplicateForProject((int) $project->id);
    }

    private function hasAllDefaultProducts(Project $project): bool
    {
        $names = array_column($this->getDefaultProducts(), 'name');

        return Product::withoutGlobalScopes()
            ->where('project_id', $project->id)
            ->whereIn('name', $names)
            ->count() === count($names);
    }

    /** @return list<array<string, mixed>> */
    private function getDefaultProducts(): array
    {
        return [
            [
                'name' => 'フリーライト ハンバーガー',
                'description' => 'シンプルで食べやすい定番ハンバーガー',
                'price' => 680,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Hamburger%20-%201.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト チーズバーガー',
                'description' => 'チーズのコクを楽しめる王道バーガー',
                'price' => 720,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Cheeseburger.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト エッグサンド',
                'description' => 'たまごの風味をしっかり味わえるサンド',
                'price' => 590,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Egg-Sandwich.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト フライドチキン',
                'description' => '香ばしい衣で仕上げたジューシーなフライドチキン',
                'price' => 760,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Fried%20Chicken.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト チキンナゲット 8P',
                'description' => '外はカリッと中はジューシーなチキンナゲット',
                'price' => 480,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Chicken%20Nuggets.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト フレンチフライ',
                'description' => 'サクッと揚げた定番ポテト',
                'price' => 360,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/French%20Fries%20%288556689169%29.jpg',
                'category' => 'side',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト レモネード',
                'description' => 'すっきりした甘さの自家製レモネード',
                'price' => 320,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Lemonade.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト アイスティー',
                'description' => 'レモンスライスを添えたアイスティー',
                'price' => 320,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Iced%20tea.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト マルゲリータピザ',
                'description' => 'トマトとモッツァレラのシンプルな王道ピザ',
                'price' => 980,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Margherita%20pizza.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト カルボナーラ',
                'description' => '卵とチーズのコクが楽しめる濃厚パスタ',
                'price' => 920,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Spaghetti%20carbonara.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト シーザーサラダ',
                'description' => 'クルトンとチーズを合わせた定番サラダ',
                'price' => 640,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/A%20Caesar%20salad.jpg',
                'category' => 'side',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト パンケーキ',
                'description' => 'やさしい甘さのふんわりパンケーキ',
                'price' => 580,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Pancakes.jpg',
                'category' => 'dessert',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト チョコレートケーキ',
                'description' => 'しっとり食感の濃厚チョコレートケーキ',
                'price' => 620,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/ChocolateCake.jpg',
                'category' => 'dessert',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト バニラアイス',
                'description' => 'ミルク感たっぷりのバニラアイスクリーム',
                'price' => 420,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Vanilla%20ice%20cream.jpg',
                'category' => 'dessert',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト オレンジジュース',
                'description' => '果汁感のあるすっきりオレンジジュース',
                'price' => 350,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Fresh%20Orange%20Juice.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'フリーライト カフェラテ',
                'description' => 'ミルクでまろやかに仕上げたカフェラテ',
                'price' => 420,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Cafe%20Latte.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
        ];
    }

    private function createSampleCoupons(Project $project): void
    {
        $now = now();
        $coupons = [
            [
                'code' => 'SAMPLE100',
                'name' => 'サンプル 100円オフ',
                'type' => 'fixed',
                'value' => 100,
                'min_order_amount' => 500,
                'usage_limit' => null,
                'used_count' => 0,
                'start_at' => $now,
                'end_at' => $now->copy()->addMonths(3),
                'is_active' => true,
                'issue_trigger' => Coupon::ISSUE_TRIGGER_MANUAL,
            ],
            [
                'code' => 'SAMPLE10PCT',
                'name' => 'サンプル 10%オフ',
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 300,
                'usage_limit' => 100,
                'used_count' => 0,
                'start_at' => $now,
                'end_at' => $now->copy()->addMonths(1),
                'is_active' => true,
                'issue_trigger' => Coupon::ISSUE_TRIGGER_MANUAL,
            ],
        ];
        foreach ($coupons as $c) {
            Coupon::create(array_merge($c, ['project_id' => $project->id]));
        }
    }
}
