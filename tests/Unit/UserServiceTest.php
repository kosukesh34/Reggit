<?php

namespace Tests\Unit;

use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\UserService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
        $this->assertTrue($resolved->relationLoaded('member') || $resolved->member !== null);
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
}
