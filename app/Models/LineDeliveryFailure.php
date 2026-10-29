<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** LINE で送れなかった会員の履歴（ブロック中・送信エラー） */
class LineDeliveryFailure extends Model
{
    use BelongsToProject;

    public const REASON_BLOCKED = 'blocked';

    public const REASON_ERROR = 'error';

    public const REASON_LABELS = [
        self::REASON_BLOCKED => 'ブロック中',
        self::REASON_ERROR => '送信エラー',
    ];

    public const UPDATED_AT = null;

    protected $fillable = ['project_id', 'member_id', 'announcement_id', 'reason', 'error_message'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}
