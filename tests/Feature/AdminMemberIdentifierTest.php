<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\Admin\MemberAdminQueryService;
use App\Support\AdminMemberIdentifier;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMemberIdentifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_identifier_label_can_render_without_a_selected_project(): void
    {
        AdminContext::setCurrentProject(null);
        $this->assertSame('会員番号', app(AdminMemberIdentifier::class)->label());
    }

    public function test_identifier_and_search_follow_each_tenants_numbering_setting(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('member_code_type', 'sequential', 'string', $project->id);
        app(ShopSettingStore::class)->setValue('member_code_type', 'random', 'string', $other->id);
        $member = Member::factory()->create(['project_id' => $project->id, 'member_number' => 'M00001234', 'user_id' => User::factory()->create(['project_id' => $project->id])->id]);
        $random = Member::factory()->create(['project_id' => $other->id, 'member_number' => 'XYZ987654', 'user_id' => User::factory()->create(['project_id' => $other->id])->id]);
        $this->assertSame((string) $member->id, app(AdminMemberIdentifier::class)->value($member));
        $this->assertSame('XYZ987654', app(AdminMemberIdentifier::class)->value($random));
        $this->assertSame('会員ID', app(AdminMemberIdentifier::class)->label($project->id));
        $this->assertSame('会員番号', app(AdminMemberIdentifier::class)->label($other->id));
        $service = app(MemberAdminQueryService::class);
        $this->assertSame((string) $member->id, $service->searchByKeyword($project->id, (string) $member->id)->first()['display_identifier']);
        $this->assertSame($member->id, $service->findByMemberNumbers($project->id, [(string) $member->id])->first()['id']);
        $this->assertSame($random->id, $service->findByMemberNumbers($other->id, ['XYZ987654'])->first()['id']);
        $this->assertCount(0, $service->findByMemberNumbers($project->id, [(string) $random->id]));
        foreach ([$project, $other] as $tenant) {
            $admin = AdminUser::factory()->create(['project_id' => $tenant->id, 'role' => AdminUser::ROLE_ADMIN]);
            $target = $tenant->id === $project->id ? $member : $random;
            foreach (['admin.rfm', 'admin.members', 'admin.members.detail', 'admin.members.edit'] as $routeName) {
                $params = ['project_key' => $tenant->project_key];
                if (in_array($routeName, ['admin.members.detail', 'admin.members.edit'], true)) {
                    $params['id'] = $target->id;
                }
                $this->actingAs($admin, 'admin')->get(route($routeName, $params))
                    ->assertOk()->assertSee(app(AdminMemberIdentifier::class)->label($tenant->id))
                    ->assertSee(app(AdminMemberIdentifier::class)->value($target));
            }
        }
    }
}
