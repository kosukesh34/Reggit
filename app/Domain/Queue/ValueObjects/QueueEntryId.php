<?php

namespace App\Domain\Queue\ValueObjects;

final class QueueEntryId
{
    public function __construct(private int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('QueueEntryId must be positive.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
