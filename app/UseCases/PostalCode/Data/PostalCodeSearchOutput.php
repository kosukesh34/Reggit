<?php

namespace App\UseCases\PostalCode\Data;

/**
 * 郵便番号検索 UseCase の出力。
 *
 * @param array<int, array{pref_name: string, city_name: string, town_name: string, block_name: string|null, full_address: string}> $addresses
 */
final class PostalCodeSearchOutput
{
    public function __construct(
        public array $addresses,
        public ?string $error = null
    ) {
    }
}
