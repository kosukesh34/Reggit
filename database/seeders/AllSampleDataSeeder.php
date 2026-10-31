<?php

namespace Database\Seeders;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Settings\Repositories\SettingValueWriter;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Enums\ScheduledDeliveryCategory;
use App\Enums\ScheduledDeliveryContentType;
use App\Enums\ScheduledDeliveryType;
use App\Models\AfterRegistrationDelivery;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberTag;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\Reservation;
use App\Models\StampCard;
use App\Models\User;
use App\Services\Member\MemberPointLedger;
use App\Services\Smaregi\SmaregiProductRegisterService;
use App\Services\Smaregi\SmaregiProductSyncService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * デモ・開発用の全サンプルデータを投入する。
 * プロジェクト・商品・クーポン・スタンプ・会員・注文・タグ・お知らせ・スケジュール配信・予約を含む。
 */
class AllSampleDataSeeder extends Seeder
{
    private const MEMBER_COUNT = 12;

    private const ORDER_COUNT_PER_MEMBER_MIN = 0;

    private const ORDER_COUNT_PER_MEMBER_MAX = 5;

    private Project $project;

    public function run(): void
    {
        Artisan::call('sample-users:delete');

        $this->project = Project::where('is_active', true)->orderBy('id')->first();
        if ($this->project === null) {
            $this->project = Project::create([
                'project_key' => 'default',
                'name' => 'サンプル店舗',
                'description' => '全サンプルデータ用',
                'is_active' => true,
            ]);
            $this->command->info('プロジェクトを作成しました: '.$this->project->name);
        }

        $this->applySmaregiContractFromEnv();

        $this->seedDefaultProductsAndCoupons();
        $this->seedExtraCoupons();
        $this->seedStampCards();
        $this->seedUsersAndMembers();
        $this->seedOrders();
        $this->seedMemberTagsAndAttach();
        $this->seedAnnouncements();
        $this->seedAfterRegistrationDeliveries();
        $this->seedReservations();
        $this->seedMemberCoupons();

        $this->syncProductsToSmaregi();

        $this->command->info('全サンプルデータの投入が完了しました。');
    }

    private function applySmaregiContractFromEnv(): void
    {
        $contractId = config('smaregi.contract_id');
        if (is_string($contractId) && trim($contractId) !== '') {
            app(SettingValueWriter::class)->setValue('smaregi_contract_id', trim($contractId), 'string', $this->project->id);
            $this->command->info('スマレジ契約IDを .env の値で設定しました: '.trim($contractId));
        }
    }

    private function syncProductsToSmaregi(): void
    {
        $registerService = app(SmaregiProductRegisterService::class);
        if ($registerService->hasSmaregiContract($this->project->id) === false) {
            return;
        }
        $syncService = app(SmaregiProductSyncService::class);
        try {
            $result = $syncService->fullSync($this->project->id);
            $this->command->info(sprintf(
                'スマレジへ商品を同期しました（新規: %d、更新: %d、スマレジ登録: %d、失敗: %d）',
                $result['created'],
                $result['updated'],
                $result['pushed_to_smaregi'],
                $result['failed']
            ));
            Log::info('[Seeder] スマレジへサンプル商品を同期しました', [
                'project_id' => $this->project->id,
                'created' => $result['created'],
                'updated' => $result['updated'],
                'pushed_to_smaregi' => $result['pushed_to_smaregi'],
                'failed' => $result['failed'],
            ]);
        } catch (\Throwable $e) {
            $this->command->warn('スマレジ同期に失敗しました（DBのサンプルデータは投入済み）: '.$e->getMessage());
            Log::warning('[Seeder] スマレジ同期に失敗しました', [
                'project_id' => $this->project->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function seedDefaultProductsAndCoupons(): void
    {
        $defaultSeeder = app(DefaultDataSeeder::class);
        $defaultSeeder->runForProject($this->project, false, false);
        $this->command->info('デフォルト商品・クーポンを投入しました');
    }

    private function seedExtraCoupons(): void
    {
        $now = now();
        $pid = $this->project->id;

        if (Coupon::where('project_id', $pid)->where('code', 'BIRTHDAY500')->exists()) {
            return;
        }

        Coupon::create([
            'project_id' => $pid,
            'code' => 'BIRTHDAY500',
            'name' => '誕生日 500円オフ',
            'type' => 'fixed',
            'value' => 500,
            'min_order_amount' => 1000,
            'usage_limit' => null,
            'used_count' => 0,
            'start_at' => $now,
            'end_at' => $now->copy()->addYear(),
            'is_active' => true,
            'issue_trigger' => 'birthday',
        ]);

        Coupon::create([
            'project_id' => $pid,
            'code' => 'WINBACK300',
            'name' => 'お久しぶり 300円オフ',
            'type' => 'fixed',
            'value' => 300,
            'min_order_amount' => 500,
            'usage_limit' => 1,
            'used_count' => 0,
            'start_at' => $now,
            'end_at' => $now->copy()->addMonths(3),
            'is_active' => true,
            'issue_trigger' => Coupon::ISSUE_TRIGGER_MANUAL,
        ]);

        $this->command->info('追加クーポン（誕生日・ウィンバック）を投入しました');
    }

    private function seedStampCards(): void
    {
        if (StampCard::where('project_id', $this->project->id)->exists()) {
            return;
        }

        StampCard::create([
            'project_id' => $this->project->id,
            'name' => 'スタンプカード（10個で1杯無料）',
            'required_stamps' => 10,
            'reward_description' => 'ドリンク1杯無料',
            'layout_style' => StampCard::LAYOUT_GRID,
            'is_active' => true,
        ]);
        $this->command->info('スタンプカードを投入しました');
    }

    private function seedUsersAndMembers(): void
    {
        $pid = $this->project->id;
        $names = [
            '山田太郎', '佐藤花子', '鈴木一郎', '高橋美咲', '伊藤健太',
            '渡辺さくら', '中村大輔', '小林あおい', '加藤翔', '吉田奈々',
            '山本翼', '松本ひかり',
        ];

        for ($i = 0; $i < self::MEMBER_COUNT; $i++) {
            $user = User::firstOrCreate(
                [
                    'project_id' => $pid,
                    'email' => 'sample'.($i + 1).'@example.com',
                ],
                [
                    'line_user_id' => 'sample-line-'.($i + 1),
                    'name' => $names[$i],
                    'phone' => $i < 5 ? '090-1234-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT) : null,
                ]
            );

            $member = Member::firstOrCreate(
                ['project_id' => $pid, 'user_id' => $user->id],
                [
                    'member_number' => 'M'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                    'points' => 0,
                    'status' => 'active',
                    'birthday' => $i < 4 ? Carbon::now()->subYears(30)->subMonths($i)->format('Y-m-d') : null,
                    'memo' => $i === 0 ? '常連。要フォローなし。' : ($i === 1 ? 'アレルギー確認済み' : null),
                ]
            );
            if ($i % 3 === 0 && app(MemberPointLedger::class)->total($member) === 0) {
                $points = rand(50, 500);
                $repo = app(MemberRepository::class);
                $aggregate = $repo->findById((int) $member->id, (int) $member->project_id);
                if ($aggregate !== null) {
                    $aggregate->earnPoints($points, 'サンプル初期ポイント', null, now()->addYear());
                    $repo->save($aggregate);
                }
            }
        }
        $this->command->info('会員・ユーザー '.self::MEMBER_COUNT.' 件を投入しました');
    }

    private function seedOrders(): void
    {
        $pid = $this->project->id;
        $products = Product::withoutGlobalScopes()->where('project_id', $pid)->get();
        if ($products->isEmpty()) {
            return;
        }

        $members = Member::where('project_id', $pid)->whereNotNull('user_id')->with('user')->get();
        $orderNum = (int) Order::where('project_id', $pid)->max('id') + 1000;

        foreach ($members as $idx => $member) {
            $count = self::ORDER_COUNT_PER_MEMBER_MIN + (int) (($idx / max(1, count($members) - 1)) * (self::ORDER_COUNT_PER_MEMBER_MAX - self::ORDER_COUNT_PER_MEMBER_MIN));
            $count = min($count, self::ORDER_COUNT_PER_MEMBER_MAX);
            $daysAgo = $idx * 15;
            for ($o = 0; $o < $count; $o++) {
                $orderDate = Carbon::now()->subDays($daysAgo + $o * 7);
                $total = 0;
                $items = [];
                $take = min(3, $products->count());
                foreach ($products->random($take) as $p) {
                    $qty = rand(1, 2);
                    $price = (int) $p->price;
                    $items[] = ['product' => $p, 'quantity' => $qty, 'price' => $price];
                    $total += $price * $qty;
                }
                if ($total === 0) {
                    continue;
                }
                $order = Order::create([
                    'project_id' => $pid,
                    'user_id' => $member->user_id,
                    'order_number' => 'ORD-'.($orderNum++),
                    'order_type' => 'take_out',
                    'total_amount' => $total,
                    'status' => OrderStatus::COMPLETED,
                    'payment_method' => PaymentMethod::CASH,
                    'created_at' => $orderDate,
                ]);
                foreach ($items as $item) {
                    OrderItem::create([
                        'project_id' => $pid,
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }
            }
        }
        $this->command->info('注文・注文明細を投入しました');
    }

    private function seedMemberTagsAndAttach(): void
    {
        $pid = $this->project->id;
        $tagDefs = [
            ['name' => 'VIP', 'color' => '#7c3aed'],
            ['name' => 'リピーター', 'color' => '#0891b2'],
            ['name' => '要フォロー', 'color' => '#dc2626'],
            ['name' => '新規', 'color' => '#16a34a'],
            ['name' => '休眠', 'color' => '#ca8a04'],
        ];

        $tags = [];
        foreach ($tagDefs as $def) {
            $tag = MemberTag::firstOrCreate(
                ['project_id' => $pid, 'name' => $def['name']],
                ['color' => $def['color']]
            );
            $tags[$def['name']] = $tag;
        }

        $members = Member::where('project_id', $pid)->get();
        foreach ($members as $i => $member) {
            if ($i === 0) {
                $member->tags()->syncWithoutDetaching([$tags['VIP']->id]);
            } elseif ($i === 1 || $i === 2) {
                $member->tags()->syncWithoutDetaching([$tags['リピーター']->id]);
            } elseif ($i >= 3 && $i <= 5) {
                $member->tags()->syncWithoutDetaching([$tags['新規']->id]);
            } elseif ($i === 6) {
                $member->tags()->syncWithoutDetaching([$tags['要フォロー']->id]);
            }
        }
        $this->command->info('会員タグと付与を投入しました');
    }

    private function seedAnnouncements(): void
    {
        $pid = $this->project->id;
        if (Announcement::where('project_id', $pid)->exists()) {
            return;
        }

        $items = [
            ['title' => '新メニュー登場', 'body' => '今月から新メニューが加わりました。ぜひお試しください。', 'published_at' => now()->subDays(3)],
            ['title' => '年末年始の営業について', 'body' => '12/30〜1/2は休業いたします。', 'published_at' => now()->subDays(10)],
            ['title' => 'ポイント2倍キャンペーン', 'body' => '今週末はポイント2倍！', 'published_at' => now()->subDay()],
        ];

        foreach ($items as $item) {
            Announcement::create(array_merge($item, [
                'project_id' => $pid,
                'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
                'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
                'is_pinned' => false,
                'sent_at' => $item['published_at'],
            ]));
        }
        $this->command->info('お知らせを投入しました');
    }

    private function seedAfterRegistrationDeliveries(): void
    {
        $pid = $this->project->id;
        if (AfterRegistrationDelivery::where('project_id', $pid)->exists()) {
            return;
        }

        $coupon = Coupon::where('project_id', $pid)->where('issue_trigger', Coupon::ISSUE_TRIGGER_FIRST_REGISTRATION)->first()
            ?? Coupon::where('project_id', $pid)->first();

        AfterRegistrationDelivery::create([
            'project_id' => $pid,
            'category' => ScheduledDeliveryCategory::CONDITION,
            'schedule_type' => ScheduledDeliveryType::NEW_USER_MINUTES,
            'content_type' => ScheduledDeliveryContentType::ANNOUNCEMENT,
            'delay_minutes' => 5,
            'title' => 'ご登録ありがとうございます',
            'body' => '会員登録が完了しました。このメッセージとともにお得なクーポンをお届けします。',
            'coupon_id' => $coupon !== null ? $coupon->id : null,
            'params' => ['delay_minutes' => 5],
            'is_active' => true,
        ]);
        $this->command->info('スケジュール配信を投入しました');
    }

    private function seedReservations(): void
    {
        $pid = $this->project->id;
        $users = User::where('project_id', $pid)->take(3)->get();
        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $i => $user) {
            if (Reservation::where('project_id', $pid)->where('user_id', $user->id)->exists()) {
                continue;
            }
            Reservation::create([
                'project_id' => $pid,
                'user_id' => $user->id,
                'reservation_number' => 'RES-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -6)),
                'reserved_at' => now()->addDays($i + 1)->setHour(12)->setMinute(0),
                'number_of_people' => 2,
                'status' => $i === 0 ? ReservationStatus::CONFIRMED : ReservationStatus::PENDING,
                'notes' => $i === 0 ? '窓際希望' : null,
            ]);
        }
        $this->command->info('予約を投入しました');
    }

    private function seedMemberCoupons(): void
    {
        $pid = $this->project->id;
        $manualCoupon = Coupon::where('project_id', $pid)->where('issue_trigger', Coupon::ISSUE_TRIGGER_MANUAL)->first();
        if ($manualCoupon === null) {
            return;
        }

        $members = Member::where('project_id', $pid)->take(3)->get();
        $now = now();
        foreach ($members as $member) {
            if (MemberCoupon::where('member_id', $member->id)->where('coupon_id', $manualCoupon->id)->exists()) {
                continue;
            }
            MemberCoupon::create([
                'project_id' => $pid,
                'member_id' => $member->id,
                'coupon_id' => $manualCoupon->id,
                'coupon_name_snapshot' => $manualCoupon->name,
                'status' => MemberCoupon::STATUS_UNUSED,
                'issued_at' => $now,
                'valid_from' => $now,
                'valid_until' => $now->copy()->addMonths(1),
            ]);
        }
        $this->command->info('会員クーポン（手動発行サンプル）を投入しました');
    }
}
