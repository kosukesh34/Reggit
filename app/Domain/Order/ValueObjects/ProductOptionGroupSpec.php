<?php

namespace App\Domain\Order\ValueObjects;

final class ProductOptionGroupSpec
{
    public const SELECTION_SINGLE = 'single';

    public const SELECTION_MULTIPLE = 'multiple';

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $selectionType,
        public readonly bool $isRequired,
        public readonly int $minSelect,
        public readonly ?int $maxSelect,
        /** @var list<ProductOptionChoice> */
        public readonly array $options,
    ) {}
}
