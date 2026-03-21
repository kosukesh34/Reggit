<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\AfterRegistrationDelivery;
use App\Models\Announcement;
use App\Models\ApiToken;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Reservation;
use App\Models\StampCard;
use App\Models\User;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 全ての画面が開けることを確認するテスト。
 * 404・500 にならずに表示できるかを検証し、開けない画面を洗い出す目的で使用する。
 */
class AllScreensAccessibleTest extends TestCase
{
    use RefreshDatabase;

    private const STORE_ADMIN_SKIP_ROUTES = [];

    private Project $project;

    private AdminUser $storeAdmin;

    private int $orderId;

    private int $productId;

    private int $memberId;

    private int $reservationId;

    private int $stampCardId;

    private int $couponId;

    private int $announcementId;

    private int $afterRegistrationDeliveryId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost']);
        AdminContext::setCurrentProject(null);
        $this->project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $this->storeAdmin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $this->project->id,
        ]);
        $this->storeAdmin->projects()->sync([$this->project->id]);
        $user = User::factory()->create();
        $order = Order::create([
            'project_id' => $this->project->id,
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-001',
            'order_type' => 'take_out',
            'total_amount' => 1000,
            'status' => 'pending',
            'payment_method' => 'cash',
        ]);
        $this->orderId = $order->id;
        $this->productId = Product::factory()->create(['project_id' => $this->project->id])->id;
        $this->memberId = Member::factory()->create(['project_id' => $this->project->id])->id;
        $this->reservationId = Reservation::factory()->create(['project_id' => $this->project->id])->id;
        $this->stampCardId = StampCard::factory()->create(['project_id' => $this->project->id])->id;
        $this->couponId = Coupon::factory()->create(['project_id' => $this->project->id])->id;
        $this->announcementId = Announcement::factory()->create(['project_id' => $this->project->id])->id;
        $this->afterRegistrationDeliveryId = AfterRegistrationDelivery::create([
            'project_id' => $this->project->id,
            'delay_minutes' => 30,
            'title' => 'Test',
            'body' => null,
            'coupon_id' => null,
            'is_active' => true,
        ])->id;
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function storeAdminScreenProvider(): array
    {
        $key = 'default';

        return [
            'dashboard' => ['admin.dashboard', ['project_key' => $key]],
            'orders' => ['admin.orders', ['project_key' => $key]],
            'orders.detail' => ['admin.orders.detail', ['project_key' => $key, 'id' => 1]],
            'products' => ['admin.products', ['project_key' => $key]],
            'products.create' => ['admin.products.create', ['project_key' => $key]],
            'products.edit' => ['admin.products.edit', ['project_key' => $key, 'id' => 1]],
            'members' => ['admin.members', ['project_key' => $key]],
            'members.detail' => ['admin.members.detail', ['project_key' => $key, 'id' => 1]],
            'members.edit' => ['admin.members.edit', ['project_key' => $key, 'id' => 1]],
            'reservations' => ['admin.reservations', ['project_key' => $key]],
            'reservations.detail' => ['admin.reservations.detail', ['project_key' => $key, 'id' => 1]],
            'stamps' => ['admin.stamps', ['project_key' => $key]],
            'stamps.create' => ['admin.stamps.create', ['project_key' => $key]],
            'stamps.edit' => ['admin.stamps.edit', ['project_key' => $key, 'id' => 1]],
            'coupons' => ['admin.coupons', ['project_key' => $key]],
            'coupons.create' => ['admin.coupons.create', ['project_key' => $key]],
            'coupons.bulk-issue' => ['admin.coupons.bulk-issue', ['project_key' => $key]],
            'coupons.edit' => ['admin.coupons.edit', ['project_key' => $key, 'id' => 1]],
            'announcements' => ['admin.announcements', ['project_key' => $key]],
            'announcements.create' => ['admin.announcements.create', ['project_key' => $key]],
            'announcements.delivery' => ['admin.announcements.delivery.create', ['project_key' => $key]],
            'announcements.edit' => ['admin.announcements.edit', ['project_key' => $key, 'id' => 1]],
            'after-registration-deliveries' => ['admin.after-registration-deliveries.index', ['project_key' => $key]],
            'after-registration-deliveries.create' => ['admin.after-registration-deliveries.create', ['project_key' => $key]],
            'after-registration-deliveries.edit' => ['admin.after-registration-deliveries.edit', ['project_key' => $key, 'id' => 1]],
            'queue' => ['admin.queue', ['project_key' => $key]],
            'settings.basic' => ['admin.settings.basic', ['project_key' => $key]],
            'settings.advanced' => ['admin.settings.advanced', ['project_key' => $key]],
            // settings.menu はホーム設定へリダイレクトするため、画面テストからは除外（admin.settings.home で検証）
            'settings.home' => ['admin.settings.home', ['project_key' => $key]],
            'settings.features' => ['admin.settings.features', ['project_key' => $key]],
            'settings.ranks' => ['admin.settings.ranks', ['project_key' => $key]],
            'settings.smaregi' => ['admin.settings.smaregi', ['project_key' => $key]],
            'settings.smaregi.pos-data' => ['admin.settings.smaregi.pos-data', ['project_key' => $key]],
            'settings.liff-designer' => ['admin.settings.liff-designer', ['project_key' => $key]],
            'settings.reservations' => ['admin.settings.reservations', ['project_key' => $key]],
            'settings.queue' => ['admin.settings.queue', ['project_key' => $key]],
            'settings.coupons' => ['admin.settings.coupons', ['project_key' => $key]],
            'settings.announcements' => ['admin.settings.announcements', ['project_key' => $key]],
            'settings.gallery' => ['admin.settings.gallery', ['project_key' => $key]],
            'settings.profile' => ['admin.settings.profile', ['project_key' => $key]],
            'settings.wallet' => ['admin.settings.wallet', ['project_key' => $key]],
            'analytics' => ['admin.analytics', ['project_key' => $key]],
            'audit-logs.index' => ['admin.audit-logs.index', ['project_key' => $key]],
            'audit-logs.show' => ['admin.audit-logs.show', ['project_key' => $key, 'id' => 1]],
            'gallery.categories' => ['admin.gallery.categories', ['project_key' => $key]],
            'gallery.items' => ['admin.gallery.items', ['project_key' => $key]],
        ];
    }

    #[DataProvider('storeAdminScreenProvider')]
    public function test_store_admin_screen_opens(string $routeName, array $params): void
    {
        if (in_array($routeName, self::STORE_ADMIN_SKIP_ROUTES, true)) {
            $this->markTestSkipped('要調査: 詳細/編集画面のID・プロジェクトコンテキスト（404/500）');
        }
        $params = $this->resolveStoreAdminParams($params, $routeName);
        $url = route($routeName, $params);
        $path = parse_url($url, PHP_URL_PATH).(parse_url($url, PHP_URL_QUERY) ? '?'.parse_url($url, PHP_URL_QUERY) : '');

        $response = $this->actingAs($this->storeAdmin, 'admin')
            ->withSession(['admin_project_id' => $this->project->id])
            ->get($path);

        $response->assertStatus(200, "画面が開けません: {$routeName} ({$path})");
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveStoreAdminParams(array $params, ?string $routeName = null): array
    {
        $routeName = $routeName ?? request()->route()?->getName();
        $map = [
            'id' => [
                'admin.orders.detail' => $this->orderId,
                'admin.products.edit' => $this->productId,
                'admin.members.detail' => $this->memberId,
                'admin.members.edit' => $this->memberId,
                'admin.reservations.detail' => $this->reservationId,
                'admin.stamps.edit' => $this->stampCardId,
                'admin.coupons.edit' => $this->couponId,
                'admin.announcements.edit' => $this->announcementId,
                'admin.after-registration-deliveries.edit' => $this->afterRegistrationDeliveryId,
                'admin.audit-logs.show' => $this->createStoreAdminAuditLogId(),
            ],
        ];
        $resolved = $params;
        foreach ($resolved as $key => $value) {
            if ($key === 'id' && $value === 1 && $routeName !== null) {
                if (isset($map['id'][$routeName])) {
                    $resolved['id'] = $map['id'][$routeName];
                }
                break;
            }
        }

        return $resolved;
    }

    private function createStoreAdminAuditLogId(): int
    {
        $log = AdminAuditLog::create([
            'admin_user_id' => $this->storeAdmin->id,
            'project_id' => $this->project->id,
            'route_name' => 'admin.dashboard',
            'method' => 'GET',
            'url' => '/admin/default/dashboard',
            'ip' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => ['event' => 'test'],
        ]);

        return $log->id;
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function systemAdminScreenProvider(): array
    {
        return [
            'dashboard' => ['system-admin.dashboard', []],
            'projects.index' => ['system-admin.projects.index', []],
            'projects.input-home' => ['system-admin.projects.input-home', []],
            'projects.create' => ['system-admin.projects.create', []],
            'projects.edit' => ['system-admin.projects.edit', ['id' => 1]],
            'accounts.index' => ['system-admin.accounts.index', []],
            'accounts.create' => ['system-admin.accounts.create', []],
            'accounts.edit' => ['system-admin.accounts.edit', ['id' => 1]],
            'api-tokens.index' => ['system-admin.api-tokens.index', []],
            'api-tokens.create' => ['system-admin.api-tokens.create', []],
            'api-tokens.edit' => ['system-admin.api-tokens.edit', ['id' => 1]],
            'audit-logs.index' => ['system-admin.audit-logs.index', []],
            'audit-logs.show' => ['system-admin.audit-logs.show', ['id' => 1]],
        ];
    }

    #[DataProvider('systemAdminScreenProvider')]
    public function test_system_admin_screen_opens(string $routeName, array $params): void
    {
        $systemAdmin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $params = $this->resolveSystemAdminParams($params, $routeName, $systemAdmin);
        $url = route($routeName, $params);
        $path = parse_url($url, PHP_URL_PATH).(parse_url($url, PHP_URL_QUERY) ? '?'.parse_url($url, PHP_URL_QUERY) : '');

        $response = $this->actingAs($systemAdmin, 'admin')->get($path);

        $response->assertStatus(200, "画面が開けません: {$routeName} ({$path})");
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveSystemAdminParams(array $params, string $routeName, AdminUser $systemAdmin): array
    {
        $resolved = $params;
        if (! isset($resolved['id']) || $resolved['id'] !== 1) {
            return $resolved;
        }

        $projectId = $this->project->id;
        $accountId = $this->storeAdmin->id;
        $token = ApiToken::issue($this->project, 'Test');
        $tokenId = $token['token']->id;
        $auditLog = AdminAuditLog::create([
            'admin_user_id' => $systemAdmin->id,
            'project_id' => null,
            'route_name' => 'system-admin.dashboard',
            'method' => 'GET',
            'url' => '/system-admin/dashboard',
            'ip' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => ['event' => 'test'],
        ]);

        $resolved['id'] = match ($routeName) {
            'system-admin.projects.edit' => $projectId,
            'system-admin.accounts.edit' => $accountId,
            'system-admin.api-tokens.edit' => $tokenId,
            'system-admin.audit-logs.show' => $auditLog->id,
            default => $resolved['id'],
        };

        return $resolved;
    }
}
