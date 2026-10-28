<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\Store;
use App\Models\User;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** ご利用店舗（customer_store_id）はアプリの注文・お知らせの店舗と同じ。プロフィール編集で選べる */
class MemberStoreProfileTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create(['project_key' => 'brand', 'name' => 'Brand', 'is_active' => true]);
        AdminContext::setCurrentProject($this->project);
        $this->withHeader('X-Project-Key', 'brand');
    }

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    /** 項目設定でご利用店舗を無効にし、名前だけを必須にする */
    private function disableStoreField(): void
    {
        $settings = ShopSettingStore::defaultProfileFieldSettings();
        foreach ($settings['smaregi_fields'] as $key => $field) {
            $settings['smaregi_fields'][$key]['enabled'] = $key === 'name';
            $settings['smaregi_fields'][$key]['required'] = $key === 'name';
        }
        ShopSettingStore::setProfileFieldSettings($settings, (int) $this->project->id);
    }

    private function member(): Member
    {
        $user = User::factory()->create(['project_id' => $this->project->id, 'name' => '山田 花子']);

        return Member::factory()->create(['user_id' => $user->id, 'project_id' => $this->project->id]);
    }

    public function test_店舗が複数あればご利用店舗を選んで保存でき店舗のお知らせ対象になる(): void
    {
        $this->disableStoreField();
        $branch = Store::create(['project_id' => $this->project->id, 'store_key' => 'branch', 'name' => '支店B', 'is_active' => true]);
        $member = $this->member();
        $this->actingAs($member->user);

        $fields = collect($this->getJson('/api/member-profile')->assertOk()->json('profile_field_settings'));
        $store = $fields->firstWhere('key', 'customer_store_id');
        $this->assertSame('ご利用店舗', $store['label']);
        $this->assertSame((string) $branch->id, collect($store['options'])->last()['value']);

        $this->putJson('/api/member-profile', ['name' => '山田 花子', 'customer_store_id' => $branch->id])->assertOk();
        $this->assertSame($branch->id, (int) $member->fresh()->customer_store_id);
        $this->assertDatabaseHas('member_stores', ['member_id' => $member->id, 'store_id' => $branch->id]);

        $this->putJson('/api/member-profile', ['name' => '山田 花子', 'customer_store_id' => 999999])->assertUnprocessable();
    }

    public function test_項目設定で無効かつ店舗が1つなら項目を出さない(): void
    {
        $this->disableStoreField();
        $this->actingAs($this->member()->user);

        $fields = collect($this->getJson('/api/member-profile')->assertOk()->json('profile_field_settings'));
        $this->assertNull($fields->firstWhere('key', 'customer_store_id'));
    }
}
