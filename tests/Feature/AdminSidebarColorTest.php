<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\AppearanceSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_color_is_saved_rendered_with_readable_text_and_reset(): void
    {
        $project = Project::firstOrCreate(['project_key' => 'default'], ['name' => 'Default', 'is_active' => true]);
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $this->actingAs($admin, 'admin');
        $params = ['project_key' => $project->project_key];

        foreach (['#ffffff' => '#111827', '#00344d' => '#ffffff'] as $background => $text) {
            $this->post(route('admin.settings.admin-design.update', $params), ['admin_sidebar_color' => $background])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($background, app(AppearanceSettingStore::class)->getAdminSidebarColor($project->id));
            $this->get(route('admin.settings.admin-design', $params))->assertOk()
                ->assertSee('--sidebar-custom-bg: '.$background, false)
                ->assertSee('--sidebar-custom-text: '.$text, false);
        }

        $this->post(route('admin.settings.admin-design.update', $params), ['admin_sidebar_color' => 'red; background: url(example)'])
            ->assertSessionHasErrors('admin_sidebar_color');
        $this->assertSame('#00344d', app(AppearanceSettingStore::class)->getAdminSidebarColor($project->id));

        $this->post(route('admin.settings.admin-design.update', $params), ['admin_sidebar_color' => ''])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->get(route('admin.settings.admin-design', $params))->assertOk()->assertDontSee('--sidebar-custom-bg:', false);
    }

    public function test_saving_without_accent_color_keeps_standard_accent(): void
    {
        $project = Project::firstOrCreate(['project_key' => 'default'], ['name' => 'Default', 'is_active' => true]);
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $this->actingAs($admin, 'admin');
        $params = ['project_key' => $project->project_key];

        // カラーピッカーは常に値を送るため、送信対象はテキスト欄だけにする
        $this->get(route('admin.settings.admin-design', $params))->assertOk()
            ->assertDontSee('type="color" name="admin_primary_color"', false)
            ->assertSee('name="admin_primary_color" id="admin_primary_color"', false);

        $this->post(route('admin.settings.admin-design.update', $params), ['admin_sidebar_color' => '#00344d', 'admin_primary_color' => ''])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEmpty(app(AppearanceSettingStore::class)->getAdminPrimaryColor($project->id));
    }
}
