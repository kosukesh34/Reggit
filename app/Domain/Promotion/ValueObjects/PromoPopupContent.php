<?php

namespace App\Domain\Promotion\ValueObjects;

use App\Domain\Survey\ValueObjects\SurveyDefinition;

/** アプリに表示する宣伝ポップアップの内容と、表示する相手・店舗。表示回数は会員ならサーバー、未登録なら端末で判定する。 */
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
        /** リンク先のアンケートに回答した会員には出さない */
        public readonly bool $hideWhenAnswered,
        /** 編集した日時。端末の表示済み記録を版ごとに分け、編集後にもう一度表示する */
        public readonly int $version,
        public readonly string $audience,
        public readonly array $audienceTagIds,
        public readonly array $audienceRanks,
        public readonly ?array $storeIds,
    ) {}

    /** 最後に表示した日時から、今回の起動で出すか。編集後（版より前に表示）はもう一度出す */
    public function isDue(?\DateTimeImmutable $lastSeenAt, \DateTimeInterface $now): bool
    {
        if ($this->frequency === 'every_open' || $lastSeenAt === null || $lastSeenAt->getTimestamp() < $this->version) {
            return true;
        }

        return $this->frequency === 'daily' && $lastSeenAt->format('Y-m-d') !== $now->format('Y-m-d');
    }

    /** @return list<int> ボタンのリンク先にあるアンケートのID */
    public function surveyIds(): array
    {
        return array_values(array_unique(array_filter([
            SurveyDefinition::idFromAppPath($this->linkUrl),
            SurveyDefinition::idFromAppPath($this->secondaryLinkUrl),
        ], fn (?int $id): bool => $id !== null)));
    }
}
