<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MemberTag extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'name',
        'color',
    ];

    /** @return BelongsToMany<Member> */
    public function members()
    {
        return $this->belongsToMany(Member::class, 'member_tag_members');
    }
}
