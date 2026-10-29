<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;

/** LINE の友だち追加・ブロック解除（follow）とブロック（unfollow）の記録 */
class LineFollowEvent extends Model
{
    use BelongsToProject;

    public const TYPE_FOLLOW = 'follow';

    public const TYPE_UNFOLLOW = 'unfollow';

    public const UPDATED_AT = null;

    protected $fillable = ['project_id', 'user_id', 'type', 'webhook_event_id'];
}
