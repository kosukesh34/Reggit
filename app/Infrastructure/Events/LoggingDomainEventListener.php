<?php

namespace App\Infrastructure\Events;

use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;
use Illuminate\Support\Facades\Log;

class LoggingDomainEventListener implements DomainEventListener
{
    public function handle(object $event): void
    {
        $context = [
            'event' => method_exists($event, 'jsonSerialize') ? $event : get_object_vars($event),
        ];
        if (property_exists($event, 'projectId')) {
            $context['project_id'] = $event->projectId;
        }

        Log::debug('[DomainEvent] '.$event::class, $context);
    }
}
