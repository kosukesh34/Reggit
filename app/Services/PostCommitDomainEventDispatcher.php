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
    ) {}

    public function dispatch(object $event): void
    {
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
