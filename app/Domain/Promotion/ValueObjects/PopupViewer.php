<?php

namespace App\Domain\Promotion\ValueObjects;

/** ポップアップを見る人。会員登録していない人は isMember が false。 */
final class PopupViewer
{
    /** @param list<int> $tagIds */
    public function __construct(
        public readonly bool $isMember,
        public readonly ?string $rank,
        public readonly array $tagIds,
        /** 利用中の店舗（アプリで選んだ店舗、なければ会員の対象店舗） */
        public readonly ?int $storeId,
    ) {}

    public static function guest(?int $storeId): self
    {
        return new self(false, null, [], $storeId);
    }
}
