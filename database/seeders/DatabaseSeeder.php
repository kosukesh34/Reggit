<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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
                'name' => 'チキンマックナゲット（15ピース）',
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
                'name' => 'ホットコーヒー',
                'description' => '香り高いホットコーヒー',
                'price' => 150,
                'image_url' => 'https://www.mcdonalds.co.jp/product_images/2839/2010.m.webp?20251117045957',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}

