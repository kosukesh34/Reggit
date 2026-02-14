<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function setPayloadAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['payload'] = json_encode($value, JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->attributes['payload'] = $value;
    }
}
