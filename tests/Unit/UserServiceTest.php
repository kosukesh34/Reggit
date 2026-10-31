<?php

namespace Tests\Unit;

use App\Infrastructure\Persistence\Eloquent\Settings\LineSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\UserService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000002';

    private const OTHER_LINE_USER_ID = 'U00000000000000000000000000000003';

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);

        parent::tearDown();
    }

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_get_or_create_user_line_idで既存会員ユーザーを優先すること(): void
    {
        $project = $this->createProject();
        AdminContext::setCurrentProject($project);

        $lineUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => self::LINE_USER_ID,
        ]);
        Member::factory()->create([
            'user_id' => $lineUser->id,
            'project_id' => $project->id,
        ]);

        $guest = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-'.uniqid(),
        ]);
        Session::put('mobile_order_user_id_'.$project->id, $guest->id);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Line-User-Id', self::LINE_USER_ID);

        $resolved = app(UserService::class)->getOrCreateUser($request);

        $this->assertSame($lineUser->id, $resolved->id);
        $this->assertSame($lineUser->id, (int) Session::get('mobile_order_user_id_'.$project->id));
    }

    public function test_get_or_create_user_ゲストセッションにline_idを紐づけること(): void
    {
        $project = $this->createProject();
        AdminContext::setCurrentProject($project);

        $guest = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-'.uniqid(),
        ]);
        Session::put('mobile_order_user_id_'.$project->id, $guest->id);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Line-User-Id', self::LINE_USER_ID);

        $resolved = app(UserService::class)->getOrCreateUser($request);

        $this->assertSame($guest->id, $resolved->id);
        $this->assertSame(self::LINE_USER_ID, $resolved->line_user_id);
    }

    public function test_get_or_create_user_退会済みユーザーは復元せずゲストにline_idを紐づけること(): void
    {
        $project = $this->createProject();
        AdminContext::setCurrentProject($project);

        $withdrawn = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => self::LINE_USER_ID,
        ]);
        $userService = app(UserService::class);
        $userService->releaseLineUserIdForWithdraw($withdrawn);
        $withdrawn->delete();

        $guest = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-'.uniqid(),
        ]);
        Session::put('mobile_order_user_id_'.$project->id, $guest->id);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Line-User-Id', self::LINE_USER_ID);

        $resolved = $userService->getOrCreateUser($request);

        $this->assertNotSame($withdrawn->id, $resolved->id);
        $this->assertTrue($withdrawn->fresh()->trashed());
        $this->assertSame('withdrawn-'.$withdrawn->id, $withdrawn->fresh()->line_user_id);
        $this->assertSame($guest->id, $resolved->id);
        $this->assertSame(self::LINE_USER_ID, $resolved->line_user_id);
    }

    public function test_get_or_create_user_退会済みがline_idを保持していても復元しないこと(): void
    {
        $project = $this->createProject();
        AdminContext::setCurrentProject($project);

        $withdrawn = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => self::LINE_USER_ID,
        ]);
        $withdrawn->delete();

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Line-User-Id', self::LINE_USER_ID);

        $resolved = app(UserService::class)->getOrCreateUser($request);

        $this->assertNotSame($withdrawn->id, $resolved->id);
        $this->assertTrue($withdrawn->fresh()->trashed());
        $this->assertSame(self::LINE_USER_ID, $resolved->line_user_id);
    }

    public function test_get_or_create_user_line_idが異なるセッションユーザーを使わないこと(): void
    {
        $project = $this->createProject();
        AdminContext::setCurrentProject($project);

        $sessionUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => self::OTHER_LINE_USER_ID,
        ]);
        Member::factory()->create([
            'user_id' => $sessionUser->id,
            'project_id' => $project->id,
        ]);
        Session::put('mobile_order_user_id_'.$project->id, $sessionUser->id);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Line-User-Id', self::LINE_USER_ID);

        $resolved = app(UserService::class)->getOrCreateUser($request);

        $this->assertNotSame($sessionUser->id, $resolved->id);
        $this->assertSame(self::LINE_USER_ID, $resolved->line_user_id);
        $this->assertSame($resolved->id, (int) Session::get('mobile_order_user_id_'.$project->id));
    }

    public function test_idトークン検証時はヘッダのline_idを信じないこと(): void
    {
        config(['line.verify_liff_id_token' => true]);
        $project = $this->createProject();
        AdminContext::setCurrentProject($project);
        LineSettingStore::setLineLiffId('1234567890-AbCdEfGh', (int) $project->id);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Line-User-Id', self::LINE_USER_ID);

        $resolved = app(UserService::class)->getOrCreateUser($request);

        $this->assertNotSame(self::LINE_USER_ID, $resolved->line_user_id);
    }

    public function test_idトークン検証時はsubを利用者にすること(): void
    {
        config(['line.verify_liff_id_token' => true]);
        $project = $this->createProject();
        AdminContext::setCurrentProject($project);
        LineSettingStore::setLineLiffId('1234567890-AbCdEfGh', (int) $project->id);
        Http::fake([
            'https://api.line.me/oauth2/v2.1/verify' => Http::response([
                'sub' => self::LINE_USER_ID,
            ], 200),
        ]);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Line-User-Id', self::OTHER_LINE_USER_ID);
        $request->headers->set('X-Line-Id-Token', 'jwt-token');

        $resolved = app(UserService::class)->getOrCreateUser($request);

        $this->assertSame(self::LINE_USER_ID, $resolved->line_user_id);
    }

    public function test_idトークン検証時も端末識別子でユーザーを解決できること(): void
    {
        config(['line.verify_liff_id_token' => true]);
        $project = $this->createProject();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-native-1',
        ]);
        $device = UserDevice::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'platform' => 'ios',
            'device_identifier' => 'device-abc',
            'last_used_at' => now(),
        ]);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Line-User-Id', self::LINE_USER_ID);
        $request->headers->set('X-Device-Identifier', 'device-abc');
        $device->forceFill(['auth_token' => 'test-device-token'])->save();
        $request->headers->set('X-Device-Token', 'test-device-token');

        $resolved = app(UserService::class)->findUser($request);

        $this->assertSame($user->id, $resolved?->id);
        $this->assertSame('guest-native-1', $resolved->line_user_id);
    }
}
