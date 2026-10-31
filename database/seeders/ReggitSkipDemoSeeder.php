<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\GalleryCategory;
use App\Models\GalleryItem;
use App\Models\Project;
use App\Models\Settings\ProjectFeatureFlags;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * reggit-skip の実機確認に使う sb_skp835t6 専用の冪等デモデータ。
 */
class ReggitSkipDemoSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::where('project_key', 'sb_skp835t6')->first();
        if ($project === null) {
            $this->command?->warn('sb_skp835t6 が見つからないため、デモデータを作成しませんでした。');

            return;
        }

        $projectId = (int) $project->id;

        DB::transaction(function () use ($projectId): void {
            ProjectFeatureFlags::query()->updateOrCreate(
                ['project_id' => $projectId],
                ['feature_gallery_enabled' => true, 'feature_queue_enabled' => true, 'feature_members_enabled' => true]
            );

            $menuItems = app(ShopSettingStore::class)->getValue('frontend_menu_items', [], $projectId);
            $menuItems = is_array($menuItems) ? array_values($menuItems) : [];
            if (! collect($menuItems)->contains(fn ($item) => is_array($item) && ($item['tab'] ?? null) === 'gallery')) {
                $menuItems[] = [
                    'tab' => 'gallery',
                    'icon' => 'fa-images',
                    'label' => 'ギャラリー',
                    'order' => count($menuItems),
                    'enabled' => true,
                ];
                app(ShopSettingStore::class)->setValue('frontend_menu_items', $menuItems, 'json', $projectId);
            }

            $catalog = [
                'おすすめ' => [
                    ['季節のベリーチーズケーキ', '甘酸っぱいベリーと軽やかなチーズクリーム。午後のコーヒーにおすすめです。', '/images/liff/menu-cheesecake.jpg', 'large'],
                ],
                'ドリンク' => [
                    ['アイスカフェラテ', '香り豊かなエスプレッソと、まろやかなミルクを合わせました。', '/images/liff/menu-iced-latte.jpg', 'medium'],
                    ['抹茶ラテ', '宇治抹茶のほろ苦さとミルクのやさしい甘さ。', '/images/liff/menu-matcha.jpg', 'medium'],
                    ['カプチーノ', 'きめ細かなフォームミルクを楽しむ定番の一杯。', '/images/liff/menu-cappuccino.jpg', 'small'],
                ],
                'フード' => [
                    ['焼きたてクロワッサン', '発酵バターが香る、朝の人気メニューです。', '/images/liff/menu-croissant.jpg', 'medium'],
                    ['アボカドトースト', 'サワードウとアボカドを合わせた軽やかなブランチ。', '/images/liff/menu-avocado-toast.jpg', 'medium'],
                    ['チョコレートクッキー', '店内で焼き上げる、コーヒーによく合うクッキー。', '/images/liff/menu-cookie.jpg', 'small'],
                ],
                '店内' => [
                    ['朝のカフェカウンター', '一日の始まりをゆっくり過ごせる店内です。', '/images/liff/home-hero.jpg', 'large'],
                    ['季節のお知らせ', '旬のメニューやイベント情報を店頭からお届けします。', '/images/liff/announce-hero.jpg', 'medium'],
                ],
            ];

            $categoryOrder = 0;
            $itemOrder = 0;
            foreach ($catalog as $categoryName => $items) {
                $category = GalleryCategory::query()->updateOrCreate(
                    ['project_id' => $projectId, 'name' => $categoryName],
                    ['sort_order' => $categoryOrder++, 'is_active' => true]
                );

                foreach ($items as [$title, $description, $imageURL, $size]) {
                    GalleryItem::query()->updateOrCreate(
                        ['project_id' => $projectId, 'title' => $title],
                        [
                            'category_id' => $category->id,
                            'description' => $description,
                            'image_url' => $imageURL,
                            'size' => $size,
                            'sort_order' => $itemOrder++,
                            'is_active' => true,
                        ]
                    );
                }
            }

            GalleryCategory::query()
                ->where('project_id', $projectId)
                ->where('name', 'あ')
                ->whereDoesntHave('items')
                ->delete();
        });

        $this->command?->info('sb_skp835t6 にreggit-skip用ギャラリーデータを作成しました。');
    }
}
