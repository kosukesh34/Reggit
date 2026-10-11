<?php

namespace Tests\Unit\Services;

use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Services\OrderNotificationOutbox;
use App\Services\PostCommitDomainEventDispatcher;
use App\Util\StoreContext;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class PostCommitDomainEventDispatcherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::shouldReceive('afterCommit')->andReturnUsing(fn ($callback) => $callback());
    }

    public function test_dispatch_delegates_the_event(): void
    {
        $event = new \stdClass;
        $dispatcher = $this->createMock(DomainEventDispatcher::class);
        $dispatcher->expects(self::once())->method('dispatch')->with($event);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        (new PostCommitDomainEventDispatcher($dispatcher, $logger, $this->createMock(OrderNotificationOutbox::class), app(StoreContext::class)))->dispatch($event);
    }

    public function test_dispatch_logs_listener_failures_without_rethrowing_them(): void
    {
        $event = new \stdClass;
        $dispatcher = $this->createMock(DomainEventDispatcher::class);
        $dispatcher->method('dispatch')->willThrowException(new \RuntimeException('listener unavailable'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                '[DomainEvent] post-commit dispatch failed',
                [
                    'event' => \stdClass::class,
                    'error' => 'listener unavailable',
                ],
            );

        (new PostCommitDomainEventDispatcher($dispatcher, $logger, $this->createMock(OrderNotificationOutbox::class), app(StoreContext::class)))->dispatch($event);

        self::assertTrue(true);
    }
}
