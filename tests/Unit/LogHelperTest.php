<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    public function test_project_idがないときデフォルトの1を付けないこと(): void
    {
        AdminContext::setCurrentProject(null);
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'test message'
                    && ! array_key_exists('project_id', $context);
            });

        LogHelper::info('test message', ['foo' => 'bar']);
    }

    public function test_admin_contextのproject_idを付与すること(): void
    {
        $project = new Project;
        $project->id = 42;
        AdminContext::setCurrentProject($project);
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'with project'
                    && ($context['project_id'] ?? null) === 42;
            });

        LogHelper::info('with project');
    }
}
