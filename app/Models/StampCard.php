<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StampCard extends Model
{
    use BelongsToProject, HasFactory;

    public const LAYOUT_GRID = 'grid';

    public const LAYOUT_ROW = 'row';

    public const LAYOUT_CIRCLE = 'circle';

    public const LAYOUT_MINIMAL = 'minimal';

    public const LAYOUT_CARD = 'card';

    /** @var array<string> */
    public static function getLayoutOptions(): array
    {
        return [
            self::LAYOUT_GRID => 'グリッド（2列）',
            self::LAYOUT_ROW => '横一列',
            self::LAYOUT_CIRCLE => '円形',
            self::LAYOUT_MINIMAL => 'ミニマル（コンパクト）',
            self::LAYOUT_CARD => 'カード（枠付き）',
        ];
    }

    protected $fillable = [
        'project_id', 'name', 'required_stamps', 'reward_description', 'image_url',
        'stamp_images', 'layout_style', 'valid_days', 'valid_until', 'reward_milestones', 'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'required_stamps' => 'integer',
        'valid_days' => 'integer',
        'valid_until' => 'date',
        'reward_milestones' => 'array',
        'stamp_images' => 'array',
        'is_active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function memberStamps(): HasMany
    {
        return $this->hasMany(MemberStamp::class);
    }
}
