<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\GalleryItem;
use App\Models\Project;
use App\Services\Admin\PublicImageStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGalleryItemsUploadTest extends TestCase
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

    public function test_ギャラリー画面にドロップゾーンがあること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.gallery.items', ['project_key' => $project->project_key]))
            ->assertStatus(200)
            ->assertSee('name="image"', false)
            ->assertSee('ドラッグ＆ドロップ')
            ->assertDontSee('name="image_url"', false);
    }

    public function test_ギャラリー画像をアップロードして追加できること(): void
    {
        Storage::fake('public');
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $file = UploadedFile::fake()->image('shop.png', 120, 80);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.gallery.items.store', ['project_key' => $project->project_key]), [
                'title' => '店内',
                'image' => $file,
                'size' => 'medium',
                'sort_order' => 0,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.gallery.items', ['project_key' => $project->project_key]));

        $item = GalleryItem::query()->where('project_id', $project->id)->where('title', '店内')->first();
        $this->assertNotNull($item);
        $this->assertStringContainsString('storage/'.PublicImageStorageService::DIR_GALLERY.'/'.$project->id, (string) $item->image_url);
    }

    public function test_ギャラリー画像なしでは追加できないこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.gallery.items', ['project_key' => $project->project_key]))
            ->post(route('admin.gallery.items.store', ['project_key' => $project->project_key]), [
                'title' => '画像なし',
                'size' => 'medium',
            ])
            ->assertRedirect(route('admin.gallery.items', ['project_key' => $project->project_key]))
            ->assertSessionHasErrors('image');
    }
}
