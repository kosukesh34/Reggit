<?php

namespace App\UseCases\AppConfig\Data;

/**
 * ネイティブアプリ向け app-config API の出力。
 *
 * @param  array<string, mixed>  $data
 */
class AppConfigView
{
    public function __construct(
        public readonly array $data,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
