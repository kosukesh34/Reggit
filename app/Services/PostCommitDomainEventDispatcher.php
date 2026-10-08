<?php

namespace App\Services;

use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use Psr\Log\LoggerInterface;

/**
 * Dispatches follow-up work after a database commit without changing the
 * already-committed application result when a listener is unavailable.
 */
class PostCommitDomainEventDispatcher
{
    public function __construct(
        private DomainEventDispatcher $dispatcher,
        private LoggerInterface $logger,
        private OrderNotificationOutbox $outbox,
    ) {}

    public function record(object $event): void
    {
        $this->outbox->record($event);
    }

    public function dispatch(object $event): void
    {
        // State-changing callers already record atomically. This also supports
        // legacy callers while preserving the durable row on delivery failure.
        try {
            $this->record($event);
            $this->outbox->deliverEvent($event);
        } catch (\Throwable $exception) {
            $this->logger->error('[DomainEvent] outbox delivery unavailable', [
                'event' => $event::class,
                'error' => $exception->getMessage(),
            ]);
        }
        try {
            $this->dispatcher->dispatch($event);
        } catch (\Throwable $exception) {
            $this->logger->error('[DomainEvent] post-commit dispatch failed', [
                'event' => $event::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
