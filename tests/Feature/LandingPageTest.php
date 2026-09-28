<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_logged_in_admin_can_still_view_landing_page(): void
    {
        $this->actingAs(AdminUser::factory()->make(), 'admin')
            ->get('/')
            ->assertOk()
            ->assertViewIs('landing')
            ->assertSee(route('demo.liff'), false);
    }

    public function test_demo_has_its_own_entry_without_live_liff_scripts(): void
    {
        $this->get('/demo/liff')->assertOk()
            ->assertSee('data-app-mode="demo"', false)
            ->assertSee('id="demo-root"', false)
            ->assertDontSee('static.line-scdn.net', false)
            ->assertDontSee('js.stripe.com', false);
    }
}
