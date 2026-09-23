<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_利用規約とプライバシーポリシーを公開表示できること(): void
    {
        Project::factory()->create([
            'project_key' => 'legal-shop',
            'name' => 'リーガル店舗',
            'is_active' => true,
        ]);

        $this->get('/legal/legal-shop/terms')
            ->assertOk()
            ->assertSee('利用規約')
            ->assertSee('リーガル店舗');

        $this->get('/legal/legal-shop/privacy')
            ->assertOk()
            ->assertSee('プライバシーポリシー')
            ->assertSee('リーガル店舗');
    }

    public function test_無効な店舗の規約は404を返すこと(): void
    {
        Project::factory()->create([
            'project_key' => 'inactive-shop',
            'is_active' => false,
        ]);

        $this->get('/legal/inactive-shop/terms')->assertNotFound();
    }
}
