<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 会員登録の流入経路（店頭POP・チラシなど）。経路ごとのQRに code を付けて登録元を記録する。 */
class AcquisitionSource extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'name',
        'code',
        'landing_tab',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /** 登録した会員に付けるタグ名。タグでセグメント配信できるようにする */
    public function tagName(): string
    {
        return '流入:'.$this->name;
    }
}
