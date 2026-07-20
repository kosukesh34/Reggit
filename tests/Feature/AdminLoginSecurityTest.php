<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_loginはプロジェクト一覧を表示しないこと(): void
    {
        Project::factory()->create([
            'project_key' => 'secret-store',
            'name' => '秘密の店舗',
            'is_active' => true,
        ]);

        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertDontSee('秘密の店舗');
        $response->assertDontSee('secret-store');
        $response->assertSee('IDとパスワードでログインしてください');
    }
}
