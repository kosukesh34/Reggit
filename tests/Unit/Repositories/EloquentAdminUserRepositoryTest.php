<?php

namespace Tests\Unit\Repositories;

use App\Models\AdminUser;
use App\Repositories\Eloquent\EloquentAdminUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentAdminUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_findByLoginIdOrEmail_login_idで検索できること(): void
    {
        $user = AdminUser::factory()->create([
            'login_id' => 'admin_user',
            'email' => 'admin@example.com',
        ]);

        $repository = new EloquentAdminUserRepository;
        $found = $repository->findByLoginIdOrEmail('admin_user');

        $this->assertNotNull($found);
        $this->assertSame((int) $user->id, (int) $found->id);
    }

    public function test_findByLoginIdOrEmail_emailで検索できること(): void
    {
        $user = AdminUser::factory()->create([
            'login_id' => 'admin_user',
            'email' => 'admin@example.com',
        ]);

        $repository = new EloquentAdminUserRepository;
        $found = $repository->findByLoginIdOrEmail('admin@example.com');

        $this->assertNotNull($found);
        $this->assertSame((int) $user->id, (int) $found->id);
    }
}
