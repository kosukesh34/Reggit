<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Coupon;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\StampGrantCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStampsTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createAdmin(Project $project): AdminUser
    {
        return AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
    }

    public function test_stamps_スタンプ一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.stamps', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_有効なスタンプカードの付与qr画面を表示できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'name' => '来店スタンプ',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.stamps.grant-qr', [
                'project_key' => $project->project_key,
                'id' => $card->id,
            ]));

        $response->assertOk()
            ->assertSee('スタンプ付与QR')
            ->assertSee('来店スタンプ')
            ->assertSee('まだQRはありません')
            ->assertSee('QRの有効期限')
            ->assertSee('付与する個数')
            ->assertSee('利用上限')
            ->assertSee('1日1回（お客様ごと）')
            ->assertSee('1日1回までに制限する')
            ->assertSee('レジ横に貼っておく来店スタンプ')
            ->assertSee('お客様1人につき1回（再利用不可）')
            ->assertSee('チラシ・DM・初回限定のQR')
            ->assertSee('先着')
            ->assertSee('data-stamp-grant-quota', false)
            ->assertSee('オープニングキャンペーンの来店スタンプ')
            ->assertSee('期限内は何度でも')
            ->assertSee('動作確認や、連続で押す必要があるとき')
            ->assertSee('無期限')
            ->assertSee('data-stamp-grant-daily-toggle', false)
            ->assertDontSee('grant=', false);
    }

    public function test_付与qrの個数と上限を指定して発行できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'name' => '来店スタンプ',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.stamps.grant-qr.store', [
                'project_key' => $project->project_key,
                'id' => $card->id,
            ]), [
                'expires_minutes' => 60,
                'amount' => 3,
                'limit' => 'once',
                'quota' => 30,
            ])
            ->assertRedirect(route('admin.stamps.grant-qr', [
                'project_key' => $project->project_key,
                'id' => $card->id,
            ]));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.stamps.grant-qr', [
                'project_key' => $project->project_key,
                'id' => $card->id,
            ]))
            ->assertOk()
            ->assertSee('今回発行したQR')
            ->assertSee('data-stamp-grant-qr', false)
            ->assertSee('QRをダウンロード')
            ->assertSee('/g/', false)
            ->assertSee('1回 3 個')
            ->assertSee('先着30名');

        $this->assertSame('once', StampGrantCode::query()->value('usage_limit'));
        $this->assertSame(30, (int) StampGrantCode::query()->value('usage_quota'));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.stamps.grant-qr', [
                'project_key' => $project->project_key,
                'id' => $card->id,
            ]))
            ->assertOk()
            ->assertDontSee('今回発行したQR')
            ->assertDontSee('data-stamp-grant-qr', false)
            ->assertSee('発行履歴')
            ->assertSee('1回 3 個')
            ->assertSee('先着30名')
            ->assertSee('0 / 30名');
    }

    public function test_付与qrを複数発行でき無期限も選べること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        $storeUrl = route('admin.stamps.grant-qr.store', [
            'project_key' => $project->project_key,
            'id' => $card->id,
        ]);

        $this->actingAs($admin, 'admin')->post($storeUrl, [
            'expires_minutes' => 15,
            'amount' => 1,
            'limit' => 'member',
        ])->assertRedirect();
        $this->actingAs($admin, 'admin')->post($storeUrl, [
            'expires_minutes' => 0,
            'amount' => 2,
            'limit' => 'unlimited',
        ])->assertRedirect();

        $this->assertSame(2, StampGrantCode::query()->count());
        $this->assertNull(
            StampGrantCode::query()->where('amount', 2)->value('expires_at')
        );
    }

    public function test_付与qrの利用上限を省略すると1日1回で発行すること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.stamps.grant-qr.store', [
                'project_key' => $project->project_key,
                'id' => $card->id,
            ]), [
                'expires_minutes' => 15,
                'amount' => 1,
            ])
            ->assertRedirect();

        $this->assertSame('daily', StampGrantCode::query()->value('usage_limit'));
    }

    public function test_作成画面でクーポンとガチャを特典に選べること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => 'ドリンク無料券',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.stamps.create', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee('特典の種類')
            ->assertSee('クーポン')
            ->assertSee('ガチャ')
            ->assertSee('ドリンク無料券')
            ->assertSee('押す前')
            ->assertSee('押したあと')
            ->assertSee('達成後の動き')
            ->assertDontSee('value="text"', false);
    }

    public function test_押す前と押したあとのスタンプ画像を保存できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.stamps.store', ['project_key' => $project->project_key]), [
                'name' => '画像スタンプ',
                'required_stamps' => 5,
                'is_active' => '1',
                'stamp_images_data' => json_encode(['1' => 'https://example.com/filled.png']),
                'stamp_empty_images_data' => json_encode(['1' => 'https://example.com/empty.png']),
            ])
            ->assertRedirect(route('admin.stamps', ['project_key' => $project->project_key]));

        $card = StampCard::query()->where('name', '画像スタンプ')->first();
        $this->assertNotNull($card);
        $this->assertSame(['1' => 'https://example.com/filled.png'], $card->stamp_images);
        $this->assertSame(['1' => 'https://example.com/empty.png'], $card->stamp_empty_images);
    }

    public function test_クーポンとガチャの達成特典を保存できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => 'デザート割引',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.stamps.store', ['project_key' => $project->project_key]), [
                'name' => '来店スタンプ',
                'required_stamps' => 10,
                'is_active' => '1',
                'reward_milestones' => [
                    [
                        'stamp_count' => 5,
                        'reward_type' => 'coupon',
                        'coupon_id' => $coupon->id,
                    ],
                    [
                        'stamp_count' => 10,
                        'reward_type' => 'gacha',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.stamps', ['project_key' => $project->project_key]));

        $card = StampCard::query()->where('name', '来店スタンプ')->first();
        $this->assertNotNull($card);
        $this->assertEquals([
            [
                'stamp_count' => 5,
                'reward_type' => 'coupon',
                'coupon_id' => $coupon->id,
                'description' => 'デザート割引',
            ],
            [
                'stamp_count' => 10,
                'reward_type' => 'gacha',
                'description' => 'ガチャ1回',
            ],
        ], $card->reward_milestones);
    }

    public function test_テキスト特典は保存できないこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.stamps.create', ['project_key' => $project->project_key]))
            ->post(route('admin.stamps.store', ['project_key' => $project->project_key]), [
                'name' => '来店スタンプ',
                'required_stamps' => 10,
                'reward_milestones' => [
                    [
                        'stamp_count' => 3,
                        'reward_type' => 'text',
                        'description' => '店員にお声がけ',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.stamps.create', ['project_key' => $project->project_key]))
            ->assertSessionHasErrors('reward_milestones.0.reward_type');
        $this->assertSame(0, StampCard::query()->where('name', '来店スタンプ')->count());
    }

    public function test_他店舗のクーポンは特典に設定できないこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $otherCoupon = Coupon::factory()->create([
            'project_id' => Project::factory()->create()->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.stamps.create', ['project_key' => $project->project_key]))
            ->post(route('admin.stamps.store', ['project_key' => $project->project_key]), [
                'name' => '来店スタンプ',
                'required_stamps' => 10,
                'reward_milestones' => [
                    [
                        'stamp_count' => 5,
                        'reward_type' => 'coupon',
                        'coupon_id' => $otherCoupon->id,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.stamps.create', ['project_key' => $project->project_key]))
            ->assertSessionHasErrors('reward_milestones.0.coupon_id');
        $this->assertSame(0, StampCard::query()->where('name', '来店スタンプ')->count());
    }

    public function test_編集画面でクーポンとガチャを特典に選べること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'name' => '来店スタンプ',
            'required_stamps' => 10,
            'is_active' => true,
            'reward_milestones' => [
                [
                    'stamp_count' => 5,
                    'reward_type' => 'text',
                    'description' => '店員にお声がけ',
                ],
            ],
        ]);
        Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => 'ドリンク無料券',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.stamps.edit', [
                'project_key' => $project->project_key,
                'id' => $card->id,
            ]))
            ->assertOk()
            ->assertSee('特典の種類')
            ->assertSee('クーポン')
            ->assertSee('ガチャ')
            ->assertSee('ドリンク無料券')
            ->assertDontSee('value="text"', false)
            ->assertDontSee('店員にお声がけ');
    }

    public function test_編集でクーポンとガチャの達成特典を更新できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => 'デザート割引',
            'is_active' => true,
        ]);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'name' => '来店スタンプ',
            'required_stamps' => 10,
            'is_active' => true,
            'reward_milestones' => [
                [
                    'stamp_count' => 10,
                    'reward_type' => 'gacha',
                    'description' => 'ガチャ1回',
                ],
            ],
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.stamps.update', [
                'project_key' => $project->project_key,
                'id' => $card->id,
            ]), [
                'name' => '来店スタンプ',
                'required_stamps' => 10,
                'is_active' => '1',
                'reward_milestones' => [
                    [
                        'stamp_count' => 5,
                        'reward_type' => 'coupon',
                        'coupon_id' => $coupon->id,
                    ],
                    [
                        'stamp_count' => 10,
                        'reward_type' => 'gacha',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.stamps', ['project_key' => $project->project_key]));

        $card->refresh();
        $this->assertEquals([
            [
                'stamp_count' => 5,
                'reward_type' => 'coupon',
                'coupon_id' => $coupon->id,
                'description' => 'デザート割引',
            ],
            [
                'stamp_count' => 10,
                'reward_type' => 'gacha',
                'description' => 'ガチャ1回',
            ],
        ], $card->reward_milestones);
    }

    public function test_編集で他店舗のクーポンは特典に設定できないこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'name' => '来店スタンプ',
            'required_stamps' => 10,
            'is_active' => true,
        ]);
        $otherCoupon = Coupon::factory()->create([
            'project_id' => Project::factory()->create()->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.stamps.edit', [
                'project_key' => $project->project_key,
                'id' => $card->id,
            ]))
            ->put(route('admin.stamps.update', [
                'project_key' => $project->project_key,
                'id' => $card->id,
            ]), [
                'name' => '来店スタンプ',
                'required_stamps' => 10,
                'reward_milestones' => [
                    [
                        'stamp_count' => 5,
                        'reward_type' => 'coupon',
                        'coupon_id' => $otherCoupon->id,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.stamps.edit', [
                'project_key' => $project->project_key,
                'id' => $card->id,
            ]))
            ->assertSessionHasErrors('reward_milestones.0.coupon_id');

        $card->refresh();
        $this->assertNull($card->reward_milestones);
    }

    public function test_達成後の動きを保存できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $next = StampCard::factory()->create([
            'project_id' => $project->id,
            'name' => 'シルバーカード',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.stamps.store', ['project_key' => $project->project_key]), [
                'name' => 'ブロンズカード',
                'required_stamps' => 10,
                'is_active' => '1',
                'cycle_mode' => 'next_card',
                'next_stamp_card_id' => $next->id,
            ])
            ->assertRedirect(route('admin.stamps', ['project_key' => $project->project_key]));

        $card = StampCard::query()->where('name', 'ブロンズカード')->first();
        $this->assertNotNull($card);
        $this->assertSame('next_card', $card->cycle_mode);
        $this->assertSame((int) $next->id, (int) $card->next_stamp_card_id);
    }

    public function test_別カード切替では次のカードが必須であること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.stamps.create', ['project_key' => $project->project_key]))
            ->post(route('admin.stamps.store', ['project_key' => $project->project_key]), [
                'name' => 'ブロンズカード',
                'required_stamps' => 10,
                'cycle_mode' => 'next_card',
            ])
            ->assertRedirect(route('admin.stamps.create', ['project_key' => $project->project_key]))
            ->assertSessionHasErrors('next_stamp_card_id');
        $this->assertSame(0, StampCard::query()->where('name', 'ブロンズカード')->count());
    }
}
