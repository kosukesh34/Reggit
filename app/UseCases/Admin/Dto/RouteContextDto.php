<?php

namespace App\UseCases\Admin\Dto;

class RouteContextDto
{
    /** @param array<string, mixed> $params */
    public function __construct(
        public string $routePrefix,
        public array $params
    ) {
    }
}
