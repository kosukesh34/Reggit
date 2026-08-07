<?php

namespace Tests\Feature;

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

    public function test_project_id未解決時はコンテキストに付けないこと(): void
    {
        AdminContext::setCurrentProject(null);
        Log::spy();

        LogHelper::info('test-message', []);

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
            return $message === 'test-message' && ! array_key_exists('project_id', $context);
        })->once();
    }

    public function test_AdminContextがあるときproject_idがコンテキストに含まれること(): void
    {
        $project = new Project;
        $project->id = 7;
        AdminContext::setCurrentProject($project);
        Log::spy();

        LogHelper::info('test-message', []);

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
            return $message === 'test-message' && ($context['project_id'] ?? null) === 7;
        })->once();
    }
}
