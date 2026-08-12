<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminWalletSettingsUploadTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $project->plan = 'premium';
        $project->save();

        return $project;
    }

    private function createAdmin(Project $project): AdminUser
    {
        return AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
    }

    public function test_wallet設定画面にurl入力がなくドロップゾーンがあること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.wallet', ['project_key' => $project->project_key]))
            ->assertStatus(200)
            ->assertSee('name="wallet_google_program_logo_file"', false)
            ->assertSee('name="wallet_google_hero_file"', false)
            ->assertDontSee('name="wallet_google_program_logo_url"', false)
            ->assertDontSee('name="wallet_google_hero_image_url"', false)
            ->assertDontSee('プログラムロゴ URL', false)
            ->assertDontSee('ヒーロー画像 URL', false);
    }

    public function test_google_wallet画像をアップロードできること(): void
    {
        Storage::fake('public');
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $logo = UploadedFile::fake()->image('program.png', 120, 120);
        $hero = UploadedFile::fake()->image('hero.jpg', 320, 200);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.wallet.update', ['project_key' => $project->project_key]), [
                'wallet_google_program_logo_file' => $logo,
                'wallet_google_hero_file' => $hero,
            ])
            ->assertRedirect();

        $logoUrl = ShopSettingStore::getWalletGoogleProgramLogoUrl($project->id);
        $heroUrl = ShopSettingStore::getWalletGoogleHeroImageUrl($project->id);
        $this->assertNotNull($logoUrl);
        $this->assertNotNull($heroUrl);
        $this->assertStringContainsString('storage/wallet_google/'.$project->id, (string) $logoUrl);
        $this->assertStringContainsString('storage/wallet_google/'.$project->id, (string) $heroUrl);
    }

    public function test_google_wallet画像をクリアできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        ShopSettingStore::setWalletGoogleProgramLogoUrl('https://example.com/logo.png', $project->id);
        ShopSettingStore::setWalletGoogleHeroImageUrl('https://example.com/hero.png', $project->id);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.wallet.update', ['project_key' => $project->project_key]), [
                'wallet_google_program_logo_clear' => '1',
                'wallet_google_hero_clear' => '1',
            ])
            ->assertRedirect();

        $this->assertNull(ShopSettingStore::getWalletGoogleProgramLogoUrl($project->id));
        $this->assertNull(ShopSettingStore::getWalletGoogleHeroImageUrl($project->id));
    }
}
