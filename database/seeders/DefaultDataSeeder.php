<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Project;
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

        $hasProducts = Product::withoutGlobalScopes()->where('project_id', $project->id)->exists();
        $shouldRefreshLegacyProducts = $this->shouldRefreshLegacyProducts($project->id);
        if ($hasProducts && $forceProducts === false && $shouldRefreshLegacyProducts === false) {
            return;
        }

        if ($forceProducts || $shouldRefreshLegacyProducts) {
            Product::withoutGlobalScopes()->where('project_id', $project->id)->delete();
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

    /** デフォルト商品のみ投入（既存は触らない。事前に削除する場合は呼び出し側で実施） */
    public function seedProductsOnly(Project $project): void
    {
        $products = $this->getDefaultProducts();
        foreach ($products as $product) {
            Product::withoutGlobalScopes()->create(array_merge($product, [
                'project_id' => $project->id,
            ]));
        }
    }

    /**
     * @return list<array{
     *     name: string,
     *     description: string,
     *     price: int,
     *     image_url: string,
     *     category: string,
     *     is_available: bool,
     *     stock: int
     * }>
     */
    private function getDefaultProducts(): array
    {
        return [
            [
                'name' => 'ローストチキンサラダボウル',
                'description' => 'ハーブチキンと彩り野菜の定番ボウル',
                'price' => 980,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Salad_platter.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'サーモンとアボカドのデリプレート',
                'description' => 'サーモンとアボカドを合わせた人気プレート',
                'price' => 1180,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Salmon_salad.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => '季節野菜のキッシュ',
                'description' => 'サラダ付きの軽食キッシュ',
                'price' => 760,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Quiche.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => '海老とトマトのクリームパスタ',
                'description' => '海老の旨みを活かした濃厚クリームパスタ',
                'price' => 1280,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Shrimp_pasta.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => '彩り野菜のトマトリゾット',
                'description' => '野菜をたっぷり使ったやさしい味わいのリゾット',
                'price' => 1120,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Risotto.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => '国産ポテトフライ',
                'description' => 'ハーブソルトで仕上げたサイドメニュー',
                'price' => 420,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/French_fries.jpg',
                'category' => 'side',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'グリーンサラダ',
                'description' => '季節野菜を使ったシンプルサラダ',
                'price' => 560,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Green_salad.jpg',
                'category' => 'side',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => '本日のスープ',
                'description' => '日替わりで楽しめるスープ',
                'price' => 480,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Tomato_soup.jpg',
                'category' => 'side',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => '自家製レモネード',
                'description' => 'すっきりした甘さのオリジナルレモネード',
                'price' => 420,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Lemonade.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'アイスティー',
                'description' => '香り豊かな定番アイスティー',
                'price' => 390,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Iced%20tea.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'カフェラテ',
                'description' => 'ミルクでまろやかに仕上げたラテ',
                'price' => 460,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Cafe_latte.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => '季節のフルーツソーダ',
                'description' => '季節の果実を使った炭酸ドリンク',
                'price' => 520,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Fruit_soda.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'ティラミス',
                'description' => 'エスプレッソ香る定番デザート',
                'price' => 620,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Tiramisu.jpg',
                'category' => 'dessert',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'パンナコッタ',
                'description' => '口どけなめらかなミルクデザート',
                'price' => 540,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Panna_cotta.jpg',
                'category' => 'dessert',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'ベリーパンケーキ',
                'description' => 'ベリーソースを添えたふんわりパンケーキ',
                'price' => 780,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Pancakes.jpg',
                'category' => 'dessert',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'チョコレートケーキ',
                'description' => 'しっとり食感の濃厚チョコレートケーキ',
                'price' => 620,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/ChocolateCake.jpg',
                'category' => 'dessert',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'バニラアイス',
                'description' => 'ミルク感たっぷりのバニラアイスクリーム',
                'price' => 420,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Vanilla%20ice%20cream.jpg',
                'category' => 'dessert',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'オレンジジュース',
                'description' => '果汁感のあるすっきりオレンジジュース',
                'price' => 350,
                'image_url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Fresh%20Orange%20Juice.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
        ];
    }

    private function shouldRefreshLegacyProducts(int $projectId): bool
    {
        return Product::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where(function ($query) {
                $query->where('name', 'like', 'フリーライト%')
                    ->orWhere('name', 'like', '%バーガー%');
            })
            ->exists();
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
