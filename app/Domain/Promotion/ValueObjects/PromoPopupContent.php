<?php

namespace App\Domain\Promotion\ValueObjects;

/** アプリに表示する宣伝ポップアップの内容と、表示する相手・店舗。表示回数の判定は端末側で行う。 */
final class PromoPopupContent
{
    /**
     * @param  list<int>  $audienceTagIds
     * @param  list<string>  $audienceRanks
     * @param  list<int>|null  $storeIds  NULLは全店舗
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ?string $imageUrl,
        public readonly string $displayType,
        public readonly ?string $buttonLabel,
        public readonly ?string $linkUrl,
        public readonly ?string $secondaryButtonLabel,
        public readonly ?string $secondaryLinkUrl,
        public readonly string $frequency,
        /** 編集した日時。端末の表示済み記録を版ごとに分け、編集後にもう一度表示する */
        public readonly int $version,
        public readonly string $audience,
        public readonly array $audienceTagIds,
        public readonly array $audienceRanks,
        public readonly ?array $storeIds,
    ) {}
}
