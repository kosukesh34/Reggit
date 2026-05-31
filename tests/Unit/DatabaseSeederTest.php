<?php

namespace Tests\Unit;

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\Product;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('DEMO_SEED=false');
        $_ENV['DEMO_SEED'] = 'false';
        $_SERVER['DEMO_SEED'] = 'false';
    }

    public function test_再実行しても商品件数が増えないこと(): void
    {
        $this->seed(DatabaseSeeder::class);
        $firstCount = Product::withoutGlobalScopes()->count();

        $this->seed(DatabaseSeeder::class);
        $secondCount = Product::withoutGlobalScopes()->count();

        $this->assertGreaterThan(0, $firstCount);
        $this->assertSame($firstCount, $secondCount);
    }

    public function test_再seed後もデモシステム管理者のパスワードが有効なこと(): void
    {
        putenv('DEMO_SEED=true');
        $_ENV['DEMO_SEED'] = 'true';
        $_SERVER['DEMO_SEED'] = 'true';

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $user = AdminUser::where('login_id', 'demo_sys')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check(AdminUserSeeder::demoSystemPassword(), $user->password));
    }

    public function test_demo_seed有効時も注文が重複しないこと(): void
    {
        putenv('DEMO_SEED=true');
        $_ENV['DEMO_SEED'] = 'true';
        $_SERVER['DEMO_SEED'] = 'true';

        $this->seed(DatabaseSeeder::class);
        $firstCount = Order::count();

        $this->seed(DatabaseSeeder::class);
        $secondCount = Order::count();

        $this->assertGreaterThan(0, $firstCount);
        $this->assertSame($firstCount, $secondCount);
    }
}
