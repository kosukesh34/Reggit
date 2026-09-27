<?php

namespace App\Services\Health;

use Illuminate\Database\ConnectionResolverInterface;

class DatabaseHealthCheck
{
    public function __construct(private ConnectionResolverInterface $connections) {}

    public function isHealthy(): bool
    {
        try {
            $this->connections->connection()->getPdo();
        } catch (\Throwable) {
            return false;
        }

        return true;
    }
}
