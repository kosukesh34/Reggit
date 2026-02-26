<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
        $this->call(DemoAdminUsersSeeder::class);

        // デフォルトプロジェクトを取得（なければ作成）
        $project = Project::where('is_active', true)->orderBy('id')->first();
        if ($project === null) {
            $project = Project::create([
                'project_key' => 'default',
                'name' => 'デフォルト店舗',
                'description' => 'デモ用プロジェクト',
                'is_active' => true,
            ]);
        }

        $this->seedSampleCoupons($project);

        // 既に商品がある場合はスキップ
        if (Product::withoutGlobalScopes()->where('project_id', $project->id)->count() > 0) {
            return;
        }

        $products = [
            [
                'name' => 'ハンバーガー',
                'description' => 'シンプルで美味しいハンバーガー',
                'price' => 150,
                'image_url' => 'https://www.mcdonalds.co.jp/product_images/1/1010-Hamburger.m.webp?20250916135952',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => '照り焼きマックバーガー',
                'description' => '甘辛い照り焼きソースが特徴のバーガー',
                'price' => 320,
                'image_url' => 'https://www.mcdonalds.co.jp/product_images/4/1070-Teriyaki-McBurger.m.webp?20260114100033',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'ビッグマック',
                'description' => '100%ビーフパティ2枚と特製ビッグマックソース',
                'price' => 450,
                'image_url' => 'https://www.mcdonalds.co.jp/product_images/165/4550-Bai-Big-Mac.m.webp?20250806100038',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'ソーセージマフィン',
                'description' => 'ジューシーなソーセージとマフィンの組み合わせ',
                'price' => 350,
                'image_url' => 'https://www.mcdonalds.co.jp/product_images/133/4030-Sausage-Muffin.m.webp?20250326100000',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'チキンマックナゲット',
                'description' => 'サクサクのチキンマックナゲット15ピース',
                'price' => 550,
                'image_url' => 'https://www.mcdonalds.co.jp/product_images/270/9030-chickenmcnuggets15p.m.webp?20260114100135',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'コカ・コーラ',
                'description' => '冷たいコカ・コーラ',
                'price' => 150,
                'image_url' => 'https://www.mcdonalds.co.jp/product_images/2056/3110-cocacola.m.webp?20250502140309',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'コカ・コーラゼロ',
                'description' => 'カロリーゼロのコカ・コーラゼロ',
                'price' => 150,
                'image_url' => 'https://www.mcdonalds.co.jp/product_images/2055/3270-cocacolazero.m.webp?20250409100146',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'ポテト',
                'description' => 'サクサクのポテト',
                'price' => 200,
                'image_url' => 'https://www.mcdonalds.co.jp/product_images/2839/2010.m.webp?20251117045957',
                'category' => 'side',
                'is_available' => true,
                'stock' => 100,
            ],
        ];

        foreach ($products as $product) {
            Product::withoutGlobalScopes()->create(array_merge($product, [
                'project_id' => $project->id,
            ]));
        }
    }

    /** サンプル・デモ用クーポンを1件以上作る（同一プロジェクトに未作成の場合のみ） */
    private function seedSampleCoupons(Project $project): void
    {
        if (Coupon::where('project_id', $project->id)->exists()) {
            return;
        }
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
