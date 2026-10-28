<?php

namespace App\Infrastructure\Persistence\Eloquent\Promotion;

use App\Domain\Promotion\Repositories\PromoPopupRepository;
use App\Domain\Promotion\ValueObjects\PromoPopupContent;
use App\Models\PromoPopup;
use Illuminate\Database\Eloquent\Builder;

final class EloquentPromoPopupRepository implements PromoPopupRepository
{
    public function findShowing(int $projectId, \DateTimeInterface $now, int $limit): array
    {
        return PromoPopup::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->orderByDesc('sort_order')->latest('id')->limit($limit)->get()
            ->map(fn (PromoPopup $popup) => new PromoPopupContent(
                id: (int) $popup->id,
                title: (string) $popup->title,
                body: $popup->body,
                imageUrl: $popup->image_url,
                displayType: (string) $popup->display_type,
                buttonLabel: $popup->button_label,
                linkUrl: $popup->link_url,
                secondaryButtonLabel: $popup->secondary_button_label,
                secondaryLinkUrl: $popup->secondary_link_url,
                frequency: (string) $popup->frequency,
                version: (int) $popup->updated_at?->timestamp,
                audience: (string) $popup->audience,
                audienceTagIds: array_map('intval', $popup->audience_tag_ids ?? []),
                audienceRanks: array_map('strval', $popup->audience_ranks ?? []),
                storeIds: $popup->store_ids === null ? null : array_map('intval', $popup->store_ids),
            ))
            ->all();
    }
}
