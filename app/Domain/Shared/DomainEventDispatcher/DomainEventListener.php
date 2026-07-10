<?php

namespace App\Domain\Shared\DomainEventDispatcher;

interface DomainEventListener
{
    public function handle(object $event): void;
}
