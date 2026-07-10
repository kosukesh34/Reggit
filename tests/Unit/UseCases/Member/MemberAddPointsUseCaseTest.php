<?php

namespace Tests\Unit\UseCases\Member;

use App\Domain\Member\Aggregates\Member as MemberAggregate;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Member\ValueObjects\MemberId;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Models\User;
use App\UseCases\Member\Data\MemberAddPointsInput;
use App\UseCases\Member\MemberAddPointsUseCase;
use Mockery;
use Tests\TestCase;

class MemberAddPointsUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createUser(int $id = 1, int $projectId = 1): User
    {
        $user = new User;
        $user->forceFill(['id' => $id, 'project_id' => $projectId]);
        $user->exists = true;

        return $user;
    }

    public function test_handle_会員なしで失敗出力を返すこと(): void
    {
        $user = $this->createUser();

        $memberRepository = Mockery::mock(MemberRepository::class);
        $memberRepository->shouldReceive('findByUserId')->once()->with(1, 1)->andReturn(null);

        $useCase = new MemberAddPointsUseCase(
            $memberRepository,
            Mockery::mock(ShopSettingRepository::class),
            Mockery::mock(DomainEventDispatcher::class),
        );

        $output = $useCase->handle(new MemberAddPointsInput($user, 10, 'test'));

        $this->assertFalse($output->hasMember);
        $this->assertSame(0, $output->points);
    }

    public function test_handle_ポイント加算して残高を返すこと(): void
    {
        $user = $this->createUser();
        $member = new MemberAggregate(new MemberId(5), 1, 100, '');

        $memberRepository = Mockery::mock(MemberRepository::class);
        $memberRepository->shouldReceive('findByUserId')->once()->with(1, 1)->andReturn($member);
        $memberRepository->shouldReceive('syncPointsLedger')->once()->with(5, 1);
        $memberRepository->shouldReceive('findById')->once()->with(5, 1)->andReturn($member);
        $memberRepository->shouldReceive('save')->once()->with(Mockery::on(function (MemberAggregate $saved) {
            return $saved->displayPoints() === 110;
        }));

        $shopSettingRepository = Mockery::mock(ShopSettingRepository::class);
        $shopSettingRepository->shouldReceive('getPointsValidDays')->once()->with(1)->andReturn(365);

        $domainEvents = Mockery::mock(DomainEventDispatcher::class);
        $domainEvents->shouldReceive('dispatch')->once();

        $useCase = new MemberAddPointsUseCase(
            $memberRepository,
            $shopSettingRepository,
            $domainEvents,
        );
        $output = $useCase->handle(new MemberAddPointsInput($user, 10, 'test'));

        $this->assertTrue($output->hasMember);
        $this->assertSame(110, $output->points);
    }
}
