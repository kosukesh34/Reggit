<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\GalleryCategory;
use App\Models\GalleryItem;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class GalleryCategoryTenantTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['create'])]
    #[TestWith(['update'])]
    public function test_other_project_category_is_rejected_before_image_storage(string $action): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $category = GalleryCategory::withoutGlobalScopes()->create(['project_id' => $other->id, 'name' => 'Other category']);
        $item = $action === 'update' ? GalleryItem::withoutGlobalScopes()->create(['project_id' => $project->id, 'title' => 'Original', 'image_url' => 'https://example.com/original.png', 'size' => 'medium']) : null;
        $payload = ['category_id' => $category->id, 'title' => 'Changed', 'image' => UploadedFile::fake()->image('new.png'), 'size' => 'medium'];
        $this->actingAs($admin, 'admin');
        $response = $action === 'create'
            ? $this->post(route('admin.gallery.items.store', ['project_key' => $project->project_key]), $payload)
            : $this->put(route('admin.gallery.items.update', ['project_key' => $project->project_key, 'id' => $item->id]), $payload);
        $this->assertFalse(GalleryItem::withoutGlobalScopes()->where('project_id', $project->id)->where('category_id', $category->id)->exists(), 'Cross-project category was persisted');
        $response->assertSessionHasErrors('category_id');
        $this->assertCount(0, Storage::disk('public')->allFiles());
        $this->assertFalse(GalleryItem::withoutGlobalScopes()->where('project_id', $project->id)->where('category_id', $category->id)->exists());
        if ($item !== null) {
            $this->assertSame('Original', $item->fresh()->title);
        }
    }

    #[TestWith(['create', true])]
    #[TestWith(['create', false])]
    #[TestWith(['update', true])]
    #[TestWith(['update', false])]
    public function test_current_project_or_empty_category_is_accepted(string $action, bool $withCategory): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $category = GalleryCategory::withoutGlobalScopes()->create(['project_id' => $project->id, 'name' => 'Current category']);
        $item = $action === 'update' ? GalleryItem::withoutGlobalScopes()->create(['project_id' => $project->id, 'category_id' => $category->id, 'title' => 'Original', 'image_url' => 'https://example.com/original.png']) : null;
        $this->actingAs($admin, 'admin');
        $payload = ['category_id' => $withCategory ? $category->id : null, 'title' => 'Changed', 'image' => UploadedFile::fake()->image('new.png'), 'size' => 'medium'];
        $response = $action === 'create'
            ? $this->post(route('admin.gallery.items.store', ['project_key' => $project->project_key]), $payload)
            : $this->put(route('admin.gallery.items.update', ['project_key' => $project->project_key, 'id' => $item->id]), $payload);
        $response->assertRedirect(route('admin.gallery.items', ['project_key' => $project->project_key]))->assertSessionHasNoErrors();
        $saved = $item !== null ? $item->fresh() : GalleryItem::withoutGlobalScopes()->where('project_id', $project->id)->sole();
        $this->assertSame('Changed', $saved->title);
        $this->assertSame($withCategory ? $category->id : null, $saved->category_id);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    #[TestWith(['item-delete'])]
    #[TestWith(['category-update'])]
    #[TestWith(['category-delete'])]
    public function test_project_key_does_not_replace_target_id(string $action): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $category = GalleryCategory::withoutGlobalScopes()->create(['project_id' => $project->id, 'name' => 'Original']);
        $item = GalleryItem::withoutGlobalScopes()->create(['project_id' => $project->id, 'category_id' => $category->id, 'image_url' => 'https://example.com/original.png']);
        $this->actingAs($admin, 'admin');
        if ($action === 'item-delete') {
            $this->delete(route('admin.gallery.items.delete', ['project_key' => $project->project_key, 'id' => $item->id]))
                ->assertRedirect(route('admin.gallery.items', ['project_key' => $project->project_key]));
            $this->assertNull(GalleryItem::withoutGlobalScopes()->find($item->id));
        } elseif ($action === 'category-update') {
            $this->put(route('admin.gallery.categories.update', ['project_key' => $project->project_key, 'id' => $category->id]), ['name' => 'Changed'])
                ->assertRedirect(route('admin.gallery.categories', ['project_key' => $project->project_key]));
            $this->assertSame('Changed', $category->fresh()->name);
        } else {
            $this->delete(route('admin.gallery.categories.delete', ['project_key' => $project->project_key, 'id' => $category->id]))
                ->assertRedirect(route('admin.gallery.categories', ['project_key' => $project->project_key]));
            $this->assertNull(GalleryCategory::withoutGlobalScopes()->find($category->id));
        }
    }
}
