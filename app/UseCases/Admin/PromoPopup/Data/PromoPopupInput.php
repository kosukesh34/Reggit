<?php

namespace App\UseCases\Admin\PromoPopup\Data;

use Illuminate\Http\UploadedFile;

/** 管理画面で保存する宣伝ポップアップ。 */
final class PromoPopupInput
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $body,
        public readonly string $displayType,
        public readonly ?string $buttonLabel,
        public readonly ?string $linkUrl,
        public readonly ?string $secondaryButtonLabel,
        public readonly ?string $secondaryLinkUrl,
        public readonly string $frequency,
        public readonly bool $hideWhenAnswered,
        public readonly string $audience,
        /** @var list<int> */
        public readonly array $audienceTagIds,
        /** @var list<string> */
        public readonly array $audienceRanks,
        /** @var list<int>|null NULLは全店舗 */
        public readonly ?array $storeIds,
        public readonly bool $isActive,
        public readonly int $sortOrder,
        public readonly ?string $startsAt,
        public readonly ?string $endsAt,
        public readonly ?UploadedFile $image,
        public readonly bool $removeImage,
    ) {}

    /** @return array<string, mixed> 画像以外の項目 */
    public function attributes(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'display_type' => $this->displayType,
            'button_label' => $this->buttonLabel,
            'link_url' => $this->linkUrl,
            'secondary_button_label' => $this->secondaryButtonLabel,
            'secondary_link_url' => $this->secondaryLinkUrl,
            'frequency' => $this->frequency,
            'hide_when_answered' => $this->hideWhenAnswered,
            'audience' => $this->audience,
            'audience_tag_ids' => $this->audienceTagIds,
            'audience_ranks' => $this->audienceRanks,
            'store_ids' => $this->storeIds,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
        ];
    }
}
