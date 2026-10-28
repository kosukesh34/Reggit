<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;

/** ポップアップの日ごとの表示・タップの件数。 */
class PromoPopupStat extends Model
{
    use BelongsToProject;

    protected $fillable = ['project_id', 'promo_popup_id', 'date', 'views', 'clicks', 'secondary_clicks', 'closes'];

    /** @var array<string, string> */
    protected $casts = ['date' => 'date', 'views' => 'integer', 'clicks' => 'integer', 'secondary_clicks' => 'integer', 'closes' => 'integer'];
}
