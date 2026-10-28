<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** アプリ起動時に表示する宣伝用ポップアップ。 */
class PromoPopup extends Model
{
    use BelongsToProject;

    public const DISPLAY_TYPES = [
        'fullscreen' => '全画面',
        'modal' => '中央モーダル',
        'bottom_sheet' => '下からシート',
        'banner' => '下部バナー',
    ];

    public const FREQUENCIES = [
        'once' => '1人1回だけ',
        'daily' => '1日1回',
        'every_open' => '起動のたび',
    ];

    protected $fillable = [
        'project_id',
        'title',
        'body',
        'image_url',
        'display_type',
        'button_label',
        'link_url',
        'secondary_button_label',
        'secondary_link_url',
        'frequency',
        'audience',
        'audience_tag_ids',
        'audience_ranks',
        'store_ids',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'audience_tag_ids' => 'array',
        'audience_ranks' => 'array',
        'store_ids' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function stats(): HasMany
    {
        return $this->hasMany(PromoPopupStat::class);
    }

    /** ボタン（1つ目・2つ目）が押された割合（%）。表示がなければ NULL */
    public function tapRate(): ?int
    {
        $views = (int) $this->getAttribute('views_total');

        return $views === 0 ? null : (int) round(((int) $this->getAttribute('clicks_total') + (int) $this->getAttribute('secondary_clicks_total')) * 100 / $views);
    }
}
