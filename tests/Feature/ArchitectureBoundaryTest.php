<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\UserService;
use App\Support\QueueRequestContext;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ArchitectureBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_headers_do_not_create_new_api_rate_limit_buckets(): void
    {
        $limiter = RateLimiter::limiter('api');
        $first = Request::create('/api/queue/me', 'GET', server: ['REMOTE_ADDR' => '192.0.2.10']);
        $first->headers->set('X-Device-Identifier', 'device-one');
        $first->headers->set('X-Line-User-Id', 'U'.str_repeat('a', 32));
        $first->headers->set('X-Project-Key', 'store-one');
        $second = Request::create('/api/queue/me', 'GET', server: ['REMOTE_ADDR' => '192.0.2.10']);
        $second->headers->set('X-Device-Identifier', 'device-two');
        $second->headers->set('X-Line-User-Id', 'U'.str_repeat('b', 32));
        $second->headers->set('X-Project-Key', 'store-two');

        $this->assertSame($limiter($first)->key, $limiter($second)->key);
        $this->assertSame('ip:192.0.2.10', $limiter($first)->key);

        $otherIp = Request::create('/api/queue/me', 'GET', server: ['REMOTE_ADDR' => '192.0.2.11']);
        $this->assertNotSame($limiter($first)->key, $limiter($otherIp)->key);
    }

    public function test_tenant_context_does_not_survive_a_new_scoped_lifecycle(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $this->assertSame($project->id, AdminContext::getCurrentProjectId());

        app()->forgetScopedInstances();

        $this->assertNull(AdminContext::getCurrentProjectId());
    }

    public function test_http_request_clears_previous_tenant_even_when_project_binding_fails(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);

        $this->getJson('/api/queue/me?project_key=missing-project')->assertNotFound();

        $this->assertNull(AdminContext::getCurrentProjectId());
    }

    public function test_production_ignores_the_unverified_line_identity_header(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('c', 32),
        ]);
        AdminContext::setCurrentProject($project);
        config(['line.verify_liff_id_token' => false]);
        $previousEnvironment = app()->environment();
        app()->instance('env', 'production');

        try {
            $request = Request::create('/api/member-profile', 'GET');
            $request->headers->set('X-Line-User-Id', $user->line_user_id);
            $this->assertNull(app(UserService::class)->findUser($request));
            $this->assertSame('', QueueRequestContext::resolveLineUserId($request, null));
        } finally {
            app()->instance('env', $previousEnvironment);
        }
    }
}
