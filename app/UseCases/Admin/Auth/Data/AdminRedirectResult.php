<?php

namespace App\UseCases\Admin\Auth\Data;

/**
 * 認証後のリダイレクト先（Controller が RedirectResponse に変換する）。
 */
class AdminRedirectResult
{
    /**
     * @param  array<string, string>  $parameters
     */
    public function __construct(
        public readonly string $route,
        public readonly array $parameters = [],
    ) {}
}
