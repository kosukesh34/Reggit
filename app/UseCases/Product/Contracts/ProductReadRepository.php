<?php

namespace App\UseCases\Product\Contracts;

interface ProductReadRepository
{
    /** @return list<array<string, mixed>> */
    public function list(?string $category, ?bool $availableOnly): array;

    /** @return array<string, mixed> */
    public function findOrFail(int $id): array;

    /** @return array<string, int> */
    public function categoryCounts(): array;
}
