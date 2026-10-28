<?php

namespace App\Infrastructure\Persistence\Eloquent\Promotion;

use App\Domain\Promotion\PromoPopupEvent;
use App\Domain\Promotion\Repositories\PromoPopupStatsRepository;
use App\Models\PromoPopup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class EloquentPromoPopupStatsRepository implements PromoPopupStatsRepository
{
    private const COLUMNS = [
        PromoPopupEvent::VIEW => 'views',
        PromoPopupEvent::CLICK => 'clicks',
        PromoPopupEvent::SECONDARY_CLICK => 'secondary_clicks',
        PromoPopupEvent::CLOSE => 'closes',
    ];

    public function record(int $projectId, int $popupId, string $event, \DateTimeInterface $date): bool
    {
        $column = self::COLUMNS[$event] ?? throw new \InvalidArgumentException('Unknown popup event: '.$event);
        // 期間外・停止中のものは表示されないので、送られてきても数えない
        $showing = PromoPopup::withoutGlobalScopes()->where('project_id', $projectId)->whereKey($popupId)->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $date))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $date))
            ->exists();
        if (! $showing) {
            return false;
        }
        // 同じ日の行に加算する（同時に届いても一意キーで1行にまとめる）
        DB::table('promo_popup_stats')->upsert(
            [['project_id' => $projectId, 'promo_popup_id' => $popupId, 'date' => $date->format('Y-m-d'), $column => 1, 'created_at' => now(), 'updated_at' => now()]],
            ['promo_popup_id', 'date'],
            [$column => DB::raw("{$column} + 1"), 'updated_at' => now()],
        );

        return true;
    }
}
