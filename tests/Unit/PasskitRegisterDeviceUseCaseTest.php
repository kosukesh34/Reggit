<?php

namespace Tests\Unit;

use App\Models\Member;
use App\Services\AppleWalletPasskitService;
use App\UseCases\Member\WalletPass\Data\PasskitRegisterDeviceInput;
use App\UseCases\Member\WalletPass\PasskitRegisterDeviceUseCase;
use Mockery;
use Tests\TestCase;

class PasskitRegisterDeviceUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_認証ヘッダー不正で401を返すこと(): void
    {
        $passkitService = Mockery::mock(AppleWalletPasskitService::class);
        $passkitService->shouldReceive('parseAuthToken')->once()->with('Bearer invalid')->andReturn(null);

        $useCase = new PasskitRegisterDeviceUseCase($passkitService);
        $output = $useCase->handle(new PasskitRegisterDeviceInput(
            authorizationHeader: 'Bearer invalid',
            deviceLibraryIdentifier: 'device-1',
            passTypeIdentifier: 'pass.com.example',
            serialNumber: 'member-1',
            pushToken: 'token',
        ));

        $this->assertSame(401, $output->status);
    }

    public function test_handle_push_token空で400を返すこと(): void
    {
        $member = Mockery::mock(Member::class);

        $passkitService = Mockery::mock(AppleWalletPasskitService::class);
        $passkitService->shouldReceive('parseAuthToken')->once()->andReturn('valid-token');
        $passkitService->shouldReceive('resolveMember')->once()->andReturn($member);
        $passkitService->shouldReceive('isAuthorizedForMember')->once()->with($member, 'valid-token')->andReturn(true);

        $useCase = new PasskitRegisterDeviceUseCase($passkitService);
        $output = $useCase->handle(new PasskitRegisterDeviceInput(
            authorizationHeader: 'ApplePass valid-token',
            deviceLibraryIdentifier: 'device-1',
            passTypeIdentifier: 'pass.com.example',
            serialNumber: 'member-1',
            pushToken: '   ',
        ));

        $this->assertSame(400, $output->status);
    }
}
