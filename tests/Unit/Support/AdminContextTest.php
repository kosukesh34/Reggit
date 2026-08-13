<?php

namespace Tests\Unit\Support;

use App\Exceptions\ProjectContextMissingException;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Project;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_require_current_project_id_未設定なら例外になること(): void
    {
        $this->expectException(ProjectContextMissingException::class);
        AdminContext::requireCurrentProjectId();
    }

    public function test_resolve_project_id_明示_i_dを優先すること(): void
    {
        $this->assertSame(9, AdminContext::resolveProjectId(9));
    }

    public function test_require_current_project_id_設定済みなら_i_dを返すこと(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);

        $this->assertSame((int) $project->id, AdminContext::requireCurrentProjectId());
    }

    public function test_resolve_project_id_未指定かつ未設定なら例外になること(): void
    {
        $this->expectException(ProjectContextMissingException::class);
        AdminContext::resolveProjectId(null);
    }

    public function test_get_business_hours_店舗未解決なら例外になること(): void
    {
        $this->expectException(ProjectContextMissingException::class);
        ShopSettingStore::getBusinessHours();
    }
}
