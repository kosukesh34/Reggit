<?php

namespace Tests\Unit\Services;

use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Services\PostCommitDomainEventDispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PostCommitDomainEventDispatcherTest extends TestCase
{
    public function test_dispatch_delegates_the_event(): void
    {
        $event = new \stdClass;
        $dispatcher = $this->createMock(DomainEventDispatcher::class);
        $dispatcher->expects(self::once())->method('dispatch')->with($event);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        (new PostCommitDomainEventDispatcher($dispatcher, $logger))->dispatch($event);
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

        (new PostCommitDomainEventDispatcher($dispatcher, $logger))->dispatch($event);

        self::assertTrue(true);
    }
}
