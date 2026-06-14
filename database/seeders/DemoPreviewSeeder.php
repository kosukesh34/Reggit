<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\QueueStatus;
use App\Enums\ReservationStatus;
use App\Models\Coupon;
use App\Models\GalleryCategory;
use App\Models\GalleryItem;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberStamp;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\Reservation;
use App\Models\StampCard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * ブラウザでの「実UIプレビュー」用デモ会員＆コンテンツ。
 *
 * LINEログイン無しのブラウザ（?demo=1）でも、会員証・ポイント・クーポン・
 * スタンプに加えて、注文履歴・予約・順番待ち・ギャラリーなど “実際のアプリ画面”
 * がすべて埋まった状態で確認できるよう、有効な LINE 形式 ID（U+32hex）を持つ
 * リッチなデモ会員と関連データを冪等に用意する。
 */
class DemoPreviewSeeder extends Seeder
{
    /** 固定のデモ用 LINE ユーザーID（U + 32桁hex の正規形式） */
    public static function demoLineUserId(): string
    {
        return 'U'.md5('reggit-demo');
    }

    public function run(): void
    {
        $project = Project::where('project_key', 'default')->first() ?? Project::orderBy('id')->first();
        if ($project === null) {
            return;
        }
        $pid = (int) $project->id;
        $lineId = self::demoLineUserId();

        $user = User::firstOrCreate(
            ['project_id' => $pid, 'line_user_id' => $lineId],
            [
                'name' => 'デモ 太郎',
                'email' => 'demo-preview@example.com',
                'phone' => '090-0000-0000',
            ]
        );

        $member = Member::firstOrCreate(
            ['project_id' => $pid, 'user_id' => $user->id],
            [
                'member_number' => 'M999001',
                'points' => 0,
                'status' => 'active',
                'birthday' => Carbon::now()->subYears(29)->subMonths(3)->format('Y-m-d'),
                'last_visited_at' => Carbon::now()->subDays(2),
            ]
        );

        if ($member->getTotalPointsFromTransactions() === 0) {
            $member->addPoints(820, 'デモ：ご来店ポイント');
            $member->addPoints(460, 'デモ：モバイル注文');
        }

        $this->seedProductOptions($pid);
        $this->seedCoupons($pid, $member);
        $this->seedStamps($pid, $member);
        $this->seedOrders($pid, $user);
        $this->seedReservations($pid, $user);
        $this->seedQueue($pid, $member, $user);
        $this->seedGallery($pid);

        $this->command?->info('デモプレビュー会員＆コンテンツを用意しました（?demo=1 で表示）。LINE ID: '.$lineId);
    }

    /**
     * サンプル商品にサイズ・トッピング・セットメニューのオプションを付与する。
     * 「カートに追加」前に詳細モーダルで選択できるようにするためのデモデータ。
     */
    private function seedProductOptions(int $pid): void
    {
        $foodGroups = [
            [
                'name' => 'サイズ',
                'selection_type' => ProductOptionGroup::TYPE_SINGLE,
                'is_required' => true,
                'min_select' => 1,
                'max_select' => 1,
                'options' => [
                    ['name' => '普通', 'price_delta' => 0, 'is_default' => true],
                    ['name' => '大盛り', 'price_delta' => 150],
                ],
            ],
            [
                'name' => 'トッピング',
                'selection_type' => ProductOptionGroup::TYPE_MULTIPLE,
                'is_required' => false,
                'min_select' => 0,
                'max_select' => 3,
                'options' => [
                    ['name' => 'チーズ', 'price_delta' => 100],
                    ['name' => 'ベーコン', 'price_delta' => 150],
                    ['name' => '目玉焼き', 'price_delta' => 120],
                    ['name' => 'アボカド', 'price_delta' => 180],
                ],
            ],
            [
                'name' => 'セットにする',
                'selection_type' => ProductOptionGroup::TYPE_SINGLE,
                'is_required' => true,
                'min_select' => 1,
                'max_select' => 1,
                'options' => [
                    ['name' => '単品', 'price_delta' => 0, 'is_default' => true],
                    ['name' => 'ドリンクセット', 'price_delta' => 300],
                    ['name' => 'ポテト＆ドリンクセット', 'price_delta' => 450],
                ],
            ],
        ];

        $drinkGroups = [
            [
                'name' => 'サイズ',
                'selection_type' => ProductOptionGroup::TYPE_SINGLE,
                'is_required' => true,
                'min_select' => 1,
                'max_select' => 1,
                'options' => [
                    ['name' => 'M', 'price_delta' => 0, 'is_default' => true],
                    ['name' => 'L', 'price_delta' => 80],
                ],
            ],
            [
                'name' => 'カスタマイズ',
                'selection_type' => ProductOptionGroup::TYPE_MULTIPLE,
                'is_required' => false,
                'min_select' => 0,
                'max_select' => 2,
                'options' => [
                    ['name' => 'ガムシロップ追加', 'price_delta' => 0],
                    ['name' => 'ミルク多め', 'price_delta' => 0],
                    ['name' => 'エスプレッソ追加', 'price_delta' => 100],
                ],
            ],
        ];

        // 既存サンプルはカテゴリが 'other' に寄っているため、カテゴリに依存せず
        // 先頭の商品にフード系オプション、続く商品にドリンク系オプションを付与する。
        $products = Product::withoutGlobalScopes()
            ->where('project_id', $pid)
            ->orderBy('id')
            ->take(5)
            ->get();

        foreach ($products as $index => $product) {
            $groups = $index < 3 ? $foodGroups : $drinkGroups;
            $this->attachOptionGroups($pid, $product, $groups);
        }
    }

    /** @param array<int, array<string, mixed>> $groups */
    private function attachOptionGroups(int $pid, Product $product, array $groups): void
    {
        if (ProductOptionGroup::where('product_id', $product->id)->exists()) {
            return;
        }

        $groupOrder = 0;
        foreach ($groups as $g) {
            $group = ProductOptionGroup::create([
                'project_id' => $pid,
                'product_id' => $product->id,
                'name' => $g['name'],
                'selection_type' => $g['selection_type'],
                'is_required' => $g['is_required'],
                'min_select' => $g['min_select'],
                'max_select' => $g['max_select'],
                'sort_order' => $groupOrder++,
            ]);
            $optionOrder = 0;
            foreach ($g['options'] as $o) {
                ProductOption::create([
                    'project_id' => $pid,
                    'group_id' => $group->id,
                    'name' => $o['name'],
                    'price_delta' => $o['price_delta'] ?? 0,
                    'is_default' => $o['is_default'] ?? false,
                    'sort_order' => $optionOrder++,
                ]);
            }
        }
    }

    private function seedCoupons(int $pid, Member $member): void
    {
        $coupons = Coupon::where('project_id', $pid)->orderBy('id')->take(3)->get();
        $now = now();
        foreach ($coupons as $coupon) {
            $exists = MemberCoupon::where('member_id', $member->id)
                ->where('coupon_id', $coupon->id)
                ->exists();
            if ($exists) {
                continue;
            }
            MemberCoupon::create([
                'project_id' => $pid,
                'member_id' => $member->id,
                'coupon_id' => $coupon->id,
                'coupon_name_snapshot' => $coupon->name,
                'status' => MemberCoupon::STATUS_UNUSED,
                'issued_at' => $now,
                'valid_from' => $now,
                'valid_until' => $now->copy()->addMonths(1),
            ]);
        }
    }

    private function seedStamps(int $pid, Member $member): void
    {
        $card = StampCard::where('project_id', $pid)->where('is_active', true)->first();
        if ($card === null) {
            return;
        }
        MemberStamp::firstOrCreate(
            ['project_id' => $pid, 'member_id' => $member->id, 'stamp_card_id' => $card->id],
            ['current_stamps' => 7]
        );
    }

    /** デモ会員の注文履歴（進行中＋完了）を用意する。 */
    private function seedOrders(int $pid, User $user): void
    {
        if (Order::where('project_id', $pid)->where('user_id', $user->id)->exists()) {
            return;
        }

        $products = Product::withoutGlobalScopes()
            ->where('project_id', $pid)
            ->where('is_available', true)
            ->orderBy('id')
            ->take(4)
            ->get();
        if ($products->isEmpty()) {
            return;
        }

        $orderNum = (int) Order::where('project_id', $pid)->max('id') + 9000;

        $plans = [
            ['status' => OrderStatus::READY,     'created' => now()->subMinutes(8),  'lines' => [[0, 1], [1, 1]]],
            ['status' => OrderStatus::PREPARING, 'created' => now()->subDays(3),     'lines' => [[2, 2]]],
            ['status' => OrderStatus::COMPLETED, 'created' => now()->subDays(12),    'lines' => [[0, 1], [3, 1]]],
        ];

        foreach ($plans as $plan) {
            $total = 0;
            $items = [];
            foreach ($plan['lines'] as [$idx, $qty]) {
                $product = $products[$idx % $products->count()];
                $price = (int) $product->price;
                $total += $price * $qty;
                $items[] = ['product_id' => $product->id, 'quantity' => $qty, 'price' => $price];
            }
            if ($total === 0) {
                continue;
            }
            $order = Order::create([
                'project_id' => $pid,
                'user_id' => $user->id,
                'order_number' => 'ORD-'.($orderNum++),
                'order_type' => 'take_out',
                'total_amount' => $total,
                'status' => $plan['status'],
                'payment_method' => PaymentMethod::CASH,
                'created_at' => $plan['created'],
                'updated_at' => $plan['created'],
            ]);
            foreach ($items as $item) {
                OrderItem::create(array_merge($item, [
                    'project_id' => $pid,
                    'order_id' => $order->id,
                ]));
            }
        }
    }

    /** デモ会員の予約（確定＋予約待ち）を用意する。 */
    private function seedReservations(int $pid, User $user): void
    {
        if (Reservation::where('project_id', $pid)->where('user_id', $user->id)->exists()) {
            return;
        }

        $plans = [
            ['at' => now()->addDays(2)->setTime(18, 30), 'people' => 2, 'status' => ReservationStatus::CONFIRMED, 'notes' => '窓際希望'],
            ['at' => now()->addDays(9)->setTime(12, 0),  'people' => 4, 'status' => ReservationStatus::PENDING,   'notes' => 'テラス席希望'],
        ];

        foreach ($plans as $plan) {
            Reservation::create([
                'project_id' => $pid,
                'user_id' => $user->id,
                'reservation_number' => 'RES-'.date('Ymd').'-'.strtoupper(Str::random(6)),
                'reserved_at' => $plan['at'],
                'number_of_people' => $plan['people'],
                'status' => $plan['status'],
                'notes' => $plan['notes'],
            ]);
        }
    }

    /** デモ会員の順番待ちエントリ（待ち状態）を用意する。 */
    private function seedQueue(int $pid, Member $member, User $user): void
    {
        if (QueueEntry::where('project_id', $pid)->where('user_id', $user->id)->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])->exists()) {
            return;
        }

        $nextNumber = (int) QueueEntry::where('project_id', $pid)->max('queue_number') + 1;
        if ($nextNumber < 12) {
            $nextNumber = 12;
        }

        QueueEntry::create([
            'project_id' => $pid,
            'member_id' => $member->id,
            'user_id' => $user->id,
            'guest_name' => 'デモ 太郎',
            'party_size' => 2,
            'status' => QueueStatus::WAITING,
            'queue_number' => $nextNumber,
            'qr_token' => Str::random(24),
            'entered_at' => null,
        ]);
    }

    /** ギャラリー（カテゴリ＋画像）をプロジェクトに用意する。 */
    private function seedGallery(int $pid): void
    {
        if (GalleryItem::where('project_id', $pid)->exists()) {
            return;
        }

        $categories = [
            '料理' => [
                ['title' => 'シグネチャーバーガー', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=800'],
                ['title' => '季節のサラダ', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'],
                ['title' => '本日のパスタ', 'image_url' => 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=800'],
                ['title' => 'クラフトドリンク', 'image_url' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800'],
            ],
            '店内' => [
                ['title' => '落ち着いた店内', 'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800'],
                ['title' => 'テラス席', 'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800'],
                ['title' => 'カウンター', 'image_url' => 'https://images.unsplash.com/photo-1559329007-40df8a9345d8?w=800'],
            ],
        ];

        $catOrder = 0;
        foreach ($categories as $catName => $items) {
            $category = GalleryCategory::create([
                'project_id' => $pid,
                'name' => $catName,
                'sort_order' => $catOrder++,
                'is_active' => true,
            ]);
            $itemOrder = 0;
            foreach ($items as $item) {
                GalleryItem::create([
                    'project_id' => $pid,
                    'category_id' => $category->id,
                    'title' => $item['title'],
                    'image_url' => $item['image_url'],
                    'description' => null,
                    'size' => 'medium',
                    'sort_order' => $itemOrder++,
                    'is_active' => true,
                ]);
            }
        }
    }
}
