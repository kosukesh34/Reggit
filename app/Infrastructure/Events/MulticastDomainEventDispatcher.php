<?php

namespace App\Infrastructure\Events;

use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;

/**
 * 登録されたリスナーへドメインイベントを配信する。
 */
class MulticastDomainEventDispatcher implements DomainEventDispatcher
{
    /** @param list<DomainEventListener> $listeners */
    public function __construct(
        private array $listeners,
    ) {}

    public function dispatch(object $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener->handle($event);
        }
    }
}
