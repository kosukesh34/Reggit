<?php

namespace Tests\Feature;

use App\Domain\Settings\Repositories\OperationalSettings;
use App\Models\AdminUser;
use App\Models\Announcement;
use App\Models\Member;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\Store;
use App\Models\User;
use App\Services\Admin\MemberTagAdminService;
use App\Services\AnnouncementStoreAudience;
use App\Services\CustomerStoreProfile;
use App\Services\MemberService;
use App\Services\Smaregi\SmaregiCustomerUpdatePayloadBuilder;
use App\UseCases\Announcement\AnnouncementIndexUseCase;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MultiStoreTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private Store $a;

    private Store $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create(['project_key' => 'brand', 'name' => 'Brand', 'is_active' => true, 'plan' => 'distribution']);
        $this->a = $this->project->stores()->firstOrFail();
        $this->b = Store::create(['project_id' => $this->project->id, 'store_key' => 'branch', 'name' => '支店B', 'is_active' => true]);
        AdminContext::setCurrentProject($this->project);
    }

    private function admin(string $role = AdminUser::ROLE_ADMIN): AdminUser
    {
        $admin = AdminUser::factory()->create(['role' => $role, 'project_id' => $this->project->id]);
        $admin->projects()->sync([$this->project->id]);
        $admin->stores()->sync([$this->a->id => ['project_id' => $this->project->id]]);

        return $admin;
    }

    private function member(Store $store): Member
    {
        $member = Member::factory()->create(['project_id' => $this->project->id, 'user_id' => User::factory()->create(['project_id' => $this->project->id])->id]);
        $member->stores()->sync([$store->id => ['project_id' => $this->project->id]]);

        return $member;
    }

    public function test_headquarters_store_page_only_shows_store_selection(): void
    {
        $this->assertSame('main', $this->a->store_key);
        $this->b->update(['smaregi_store_id' => '20', 'settings' => ['smaregi_available' => true]]);
        $this->actingAs($this->admin(AdminUser::ROLE_PROJECT_ADMIN), 'admin')
            ->get('/admin/brand/stores')->assertOk()
            ->assertSee('name="store_ids[]"', false)
            ->assertSee('支店B')
            ->assertSee('利用店舗を保存')
            ->assertDontSee('店舗を追加')
            ->assertDontSee('担当管理者');
    }

    public function test_store_selection_requires_at_least_one_store(): void
    {
        $this->actingAs($this->admin(AdminUser::ROLE_PROJECT_ADMIN), 'admin')
            ->from('/admin/brand/stores')->put('/admin/brand/stores/active', [])
            ->assertRedirect('/admin/brand/stores')
            ->assertSessionHasErrors(['store_ids' => '利用する店舗を1件以上選択してください。']);
        $this->get('/admin/brand/stores')->assertOk()->assertSee('利用する店舗を1件以上選択してください。');
        $this->assertTrue($this->a->fresh()->is_active);
    }

    public function test_store_selection_cannot_exceed_project_limit(): void
    {
        $this->project->update(['max_active_stores' => 1]);
        foreach ([$this->a, $this->b] as $store) {
            $store->update(['smaregi_store_id' => (string) $store->id, 'settings' => ['smaregi_available' => true]]);
        }
        $activeBefore = [$this->a->fresh()->is_active, $this->b->fresh()->is_active];
        $this->actingAs($this->admin(AdminUser::ROLE_PROJECT_ADMIN), 'admin')
            ->from('/admin/brand/stores')->put('/admin/brand/stores/active', ['store_ids' => [$this->a->id, $this->b->id]])
            ->assertRedirect('/admin/brand/stores')
            ->assertSessionHasErrors(['store_ids' => '利用できる店舗は最大1件です。選択する店舗数を減らしてください。']);
        $this->assertSame($activeBefore, [$this->a->fresh()->is_active, $this->b->fresh()->is_active]);
        $this->get('/admin/brand/stores')->assertOk()->assertSee('利用できる店舗は最大1件です。選択する店舗数を減らしてください。');
    }

    public function test_branch_cannot_select_unassigned_store(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/brand/stores/select', ['store_id' => $this->b->id])->assertForbidden();
        $this->post('/admin/brand/stores/select', ['store_id' => 'all'])->assertForbidden();
    }

    public function test_branch_member_scope_excludes_other_store_but_keeps_shared_member(): void
    {
        $a = $this->member($this->a);
        $b = $this->member($this->b);
        $shared = $this->member($this->a);
        $shared->stores()->attach($this->b->id, ['project_id' => $this->project->id]);
        app(StoreContext::class)->bind(Request::create('/', 'GET', ['store_id' => $this->a->id]), $this->project->id, $this->admin());
        $this->assertSame([$a->id, $shared->id], Member::orderBy('id')->pluck('id')->all());
        $this->assertNull(Member::find($b->id));
        $this->assertNull(User::find($b->user_id));
    }

    public function test_branch_can_read_own_guest_users_but_not_other_store_guests(): void
    {
        $own = User::factory()->create(['project_id' => $this->project->id]);
        $other = User::factory()->create(['project_id' => $this->project->id]);
        QueueEntry::factory()->create(['project_id' => $this->project->id, 'store_id' => $this->a->id, 'user_id' => $own->id, 'member_id' => null]);
        QueueEntry::factory()->create(['project_id' => $this->project->id, 'store_id' => $this->b->id, 'user_id' => $other->id, 'member_id' => null]);
        app(StoreContext::class)->bind(Request::create('/', 'GET', ['store_id' => $this->a->id]), $this->project->id, $this->admin());
        $this->assertNotNull(User::find($own->id));
        $this->assertNull(User::find($other->id));
    }

    public function test_customer_profile_selects_named_project_store_and_preserves_visit_memberships(): void
    {
        $member = $this->member($this->a);
        $service = app(MemberService::class);
        $service->updateProfile($member->user, ['customer_store_id' => (string) $this->b->id]);
        $member->refresh();
        $this->assertSame($this->b->id, (int) $member->customer_store_id);
        $this->assertCount(2, $member->stores);
        $data = $service->getMemberData($member->user);
        $field = collect($data['profile_field_settings'])->firstWhere('key', 'customer_store_id');
        $this->assertSame('対象店舗', $field['label']);
        $this->assertContains(['value' => (string) $this->b->id, 'label' => '支店B'], $field['options']);
        $this->assertSame((string) $this->b->id, $data['profile']['customer_store_id']);
        $service->updateProfile($member->user, ['customer_store_id' => null]);
        $this->assertNull($member->fresh()->customer_store_id);
        $this->assertCount(2, $member->fresh()->stores);
    }

    public function test_customer_store_rejects_other_project_and_maps_only_configured_smaregi_ids(): void
    {
        $other = Project::create(['project_key' => 'outside', 'name' => 'Outside', 'is_active' => true]);
        $rules = app(MemberService::class)->getProfileValidationRules($this->project->id);
        $validator = Validator::make(['customer_store_id' => $other->stores()->first()->id], ['customer_store_id' => $rules['customer_store_id']]);
        $this->assertTrue($validator->fails());
        $mapping = app(CustomerStoreProfile::class);
        $builder = app(SmaregiCustomerUpdatePayloadBuilder::class);
        $this->assertArrayNotHasKey('storeId', $builder->buildFromProfileData($mapping->forSmaregi(['customer_store_id' => $this->b->id], $this->project->id)));
        $this->b->update(['smaregi_store_id' => 'external-20']);
        $this->assertSame('external-20', $builder->buildFromProfileData($mapping->forSmaregi(['customer_store_id' => $this->b->id], $this->project->id))['storeId']);
    }

    public function test_store_business_settings_link_persists_selection_and_branch_can_save_hours(): void
    {
        $this->actingAs($this->admin(), 'admin')->get('/admin/brand/settings/basic?store_id='.$this->a->id)->assertOk();
        $this->assertSame($this->a->id, session('admin_store.'.$this->project->id));
        $this->post('/admin/brand/settings/basic', ['business_hours_start' => '09:00', 'business_hours_end' => '18:00', 'order_mode' => 'both'])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('09:00', $this->a->fresh()->settings['business_hours_start']);
        $this->assertArrayNotHasKey('business_hours_start', $this->b->fresh()->settings ?? []);
    }

    public function test_unassigned_branch_admin_sees_no_members(): void
    {
        $this->member($this->a);
        $admin = $this->admin();
        $admin->stores()->detach();
        app(StoreContext::class)->bind(Request::create('/'), $this->project->id, $admin);
        $this->assertSame(0, Member::count());
    }

    public function test_public_store_list_is_project_scoped(): void
    {
        Project::create(['project_key' => 'other', 'name' => 'Other', 'is_active' => true]);
        $this->getJson('/api/v1/stores?project_key=brand')->assertOk()->assertJsonCount(2, 'stores')->assertJsonMissing(['name' => 'Other']);
    }

    public function test_branch_bulk_delivery_snapshots_only_its_members(): void
    {
        $a = $this->member($this->a);
        $b = $this->member($this->b);
        $this->actingAs($this->admin(), 'admin')->post('/admin/brand/announcements/delivery', [
            'title' => '店舗A配信', 'body' => '限定メッセージ', 'audience_type' => 'immediate_bulk', 'delivery_timing' => 'immediate', 'send_notification' => '0',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $announcement = Announcement::withoutGlobalScopes()->latest('id')->firstOrFail();
        $this->assertSame([(int) $this->a->id], $announcement->store_ids);
        $this->assertSame('recipients', $announcement->visibility);
        $this->assertContains($a->id, $announcement->target_member_ids);
        $this->assertNotContains($b->id, $announcement->target_member_ids);
    }

    public function test_private_announcement_not_visible_to_other_store_member_or_guest(): void
    {
        $a = $this->member($this->a);
        $b = $this->member($this->b);
        Announcement::create(['project_id' => $this->project->id, 'title' => '限定', 'body' => 'private', 'visibility' => 'recipients', 'store_ids' => [$this->a->id], 'target_member_ids' => [$a->id], 'published_at' => now()]);
        $useCase = app(AnnouncementIndexUseCase::class);
        $this->assertCount(1, $useCase->handle($a->user)->announcements);
        $this->assertCount(0, $useCase->handle($b->user)->announcements);
        $this->assertCount(0, $useCase->handle(null)->announcements);
    }

    public function test_background_audience_filter_keeps_store_boundary_and_deduplicates(): void
    {
        $a = $this->member($this->a);
        $b = $this->member($this->b);
        $delivery = new Announcement(['project_id' => $this->project->id, 'store_ids' => [$this->a->id]]);
        $result = app(AnnouncementStoreAudience::class)->filter($delivery, collect([$a->id, $a->id, $b->id]));
        $this->assertSame([$a->id], $result->all());
    }

    public function test_scheduled_delivery_authorization_changes_when_assignment_is_revoked(): void
    {
        $admin = $this->admin();
        $delivery = new Announcement(['project_id' => $this->project->id, 'created_by_admin_id' => $admin->id, 'store_ids' => [$this->a->id]]);
        $policy = app(AnnouncementStoreAudience::class);
        $this->assertTrue($policy->authorizedForSend($delivery));
        $admin->stores()->detach();
        $this->assertFalse($policy->authorizedForSend($delivery));
    }

    public function test_store_transactions_require_selection_and_hide_other_store_history(): void
    {
        $member = $this->member($this->a);
        $member->stores()->attach($this->b->id, ['project_id' => $this->project->id]);
        $aOrder = Order::create(['project_id' => $this->project->id, 'store_id' => $this->a->id, 'user_id' => $member->user_id, 'order_number' => 'A-1', 'total_amount' => 100]);
        $bOrder = Order::create(['project_id' => $this->project->id, 'store_id' => $this->b->id, 'user_id' => $member->user_id, 'order_number' => 'B-1', 'total_amount' => 900]);
        app(StoreContext::class)->bind(Request::create('/', 'GET', ['store_id' => $this->a->id]), $this->project->id, $this->admin());
        $this->assertSame([$aOrder->id], Order::pluck('id')->all());
        $this->assertSame(100.0, (float) Order::sum('total_amount'));
        $this->assertNull(Order::find($bOrder->id));
    }

    public function test_common_points_balance_is_not_recalculated_from_selected_store_only(): void
    {
        $member = $this->member($this->a);
        foreach ([$this->a, $this->b] as $store) {
            PointTransaction::create(['project_id' => $this->project->id, 'store_id' => $store->id, 'member_id' => $member->id, 'type' => 'earned', 'points' => 100]);
        }
        app(StoreContext::class)->bind(Request::create('/', 'GET', ['store_id' => $this->a->id]), $this->project->id, $this->admin());
        $this->assertSame(200, $member->getTotalPointsFromTransactions());
        $this->assertSame(100, (int) $member->pointTransactions()->sum('points'));
    }

    public function test_store_memo_does_not_overwrite_other_store_or_headquarters_memo(): void
    {
        $member = $this->member($this->a);
        $member->update(['memo' => '本部メモ']);
        $member->stores()->attach($this->b->id, ['project_id' => $this->project->id]);
        app(StoreContext::class)->bind(Request::create('/', 'GET', ['store_id' => $this->a->id]), $this->project->id, $this->admin());
        app(MemberTagAdminService::class)->updateMemo($member, '店舗Aメモ');
        $this->assertSame('店舗Aメモ', $member->memo);
        $this->assertDatabaseHas('members', ['id' => $member->id, 'memo' => '本部メモ']);
        $this->assertDatabaseHas('member_stores', ['member_id' => $member->id, 'store_id' => $this->b->id, 'memo' => null]);
    }

    public function test_guest_cannot_fetch_recipient_only_image_even_with_known_url(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('private/test.jpg', 'image');
        $member = $this->member($this->a);
        $announcement = Announcement::create(['project_id' => $this->project->id, 'title' => '限定画像', 'visibility' => 'recipients', 'store_ids' => [$this->a->id], 'target_member_ids' => [$member->id], 'private_image_path' => 'private/test.jpg', 'published_at' => now()]);
        $this->get('/api/v1/announcements/'.$announcement->id.'/image?project_key=brand')->assertNotFound();
    }

    public function test_store_business_settings_do_not_change_other_store_defaults(): void
    {
        $admin = $this->admin();
        app(StoreContext::class)->bind(Request::create('/', 'GET', ['store_id' => $this->a->id]), $this->project->id, $admin);
        $settings = app(OperationalSettings::class);
        $settings->setValue('reservation_capacity_per_slot', 7, 'integer', $this->project->id);
        $this->assertSame(7, (int) $settings->getValue('reservation_capacity_per_slot', 20, $this->project->id));
        app(StoreContext::class)->bind(Request::create('/', 'GET', ['store_id' => $this->b->id]), $this->project->id);
        $this->assertNotSame(7, (int) $settings->getValue('reservation_capacity_per_slot', 20, $this->project->id));
    }
}
