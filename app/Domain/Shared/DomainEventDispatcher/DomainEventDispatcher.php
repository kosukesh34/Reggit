<?php

namespace App\Domain\Shared\DomainEventDispatcher;

/**
 * ドメインイベントの発行口。MulticastDomainEventDispatcher 経由でリスナーへ配信する。
 */
interface DomainEventDispatcher
{
    public function dispatch(object $event): void;
}
