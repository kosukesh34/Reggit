<?php

namespace App\Services;

use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Support\Facades\DB;
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
        private StoreContext $storeContext,
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
        } catch (\Throwable $exception) {
            $this->logger->error('[DomainEvent] outbox delivery unavailable', [
                'event' => $event::class,
                'error' => $exception->getMessage(),
            ]);
        }
        $project = AdminContext::getCurrentProject();
        $storeContext = clone $this->storeContext;
        DB::afterCommit(function () use ($event, $project, $storeContext): void {
            $previousProject = AdminContext::getCurrentProject();
            AdminContext::setCurrentProject($project);
            try {
                $this->storeContext->runWithContext($storeContext, fn () => $this->deliver($event));
            } finally {
                AdminContext::setCurrentProject($previousProject);
            }
        });
    }

    private function deliver(object $event): void
    {
        try {
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
