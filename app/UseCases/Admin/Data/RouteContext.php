<?php

namespace App\UseCases\Admin\Data;

/** 管理画面のルートプレフィックスとパラメータ。不変。 @param array<string, string|int|float|bool|array|null> $params ルートパラメータ（例: project_key） */
class RouteContext
{
    public function __construct(
        public string $routePrefix,
        public array $params
    ) {}
}
