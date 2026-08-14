<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Product;
use App\Models\Project;
use App\Services\Admin\PublicImageStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductsTest extends TestCase
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

    public function test_products_商品一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        Product::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.products', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_products_商品作成ページにアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.products.create', ['project_key' => $project->project_key]));

        $response->assertStatus(200)
            ->assertSee('name="image"', false)
            ->assertSee('ドラッグ＆ドロップ')
            ->assertDontSee('name="image_url"', false);
    }

    public function test_products_画像をアップロードして作成できること(): void
    {
        Storage::fake('public');
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $file = UploadedFile::fake()->image('latte.png', 80, 80);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store', ['project_key' => $project->project_key]), [
                'name' => 'カフェラテ',
                'price' => 560,
                'category' => 'drink',
                'stock' => 10,
                'is_available' => '1',
                'image' => $file,
            ])
            ->assertRedirect(route('admin.products', ['project_key' => $project->project_key]));

        $product = Product::query()->where('project_id', $project->id)->where('name', 'カフェラテ')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->image_url);
        $this->assertStringContainsString('storage/'.PublicImageStorageService::DIR_PRODUCT.'/'.$project->id, (string) $product->image_url);
    }

    public function test_products_画像をクリアできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'name' => 'クリア対象',
            'price' => 300,
            'image_url' => 'https://example.com/keep.png',
            'stock' => 5,
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.products.update', ['project_key' => $project->project_key, 'id' => $product->id]), [
                'name' => 'クリア対象',
                'price' => 300,
                'category' => 'drink',
                'stock' => 5,
                'is_available' => '1',
                'image_clear' => '1',
            ])
            ->assertRedirect();

        $product->refresh();
        $this->assertSame('', (string) $product->image_url);
    }
}
