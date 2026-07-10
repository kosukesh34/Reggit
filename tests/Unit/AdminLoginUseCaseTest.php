<?php

namespace Tests\Unit;

use App\Services\Admin\AdminAuthService;
use App\UseCases\Admin\Auth\AdminLoginUseCase;
use App\UseCases\Admin\Auth\Data\AdminAuthAuditContext;
use App\UseCases\Admin\Auth\Data\AdminLoginInput;
use Illuminate\Http\RedirectResponse;
use Mockery;
use Tests\TestCase;

class AdminLoginUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_認証サービスに委譲すること(): void
    {
        $input = new AdminLoginInput(
            loginId: 'admin',
            password: 'secret',
            remember: false,
            projectKey: 'default',
            requestPath: 'admin/default/login',
            auditContext: new AdminAuthAuditContext(
                routeName: 'admin.project.login.submit',
                method: 'POST',
                url: 'https://example.test/admin/default/login',
                ip: '127.0.0.1',
                userAgent: 'test',
            ),
        );

        $redirect = Mockery::mock(RedirectResponse::class);
        $authService = Mockery::mock(AdminAuthService::class);
        $authService->shouldReceive('loginAdmin')->once()->with($input)->andReturn($redirect);

        $useCase = new AdminLoginUseCase($authService);
        $output = $useCase->handle($input);

        $this->assertSame($redirect, $output);
    }
}
