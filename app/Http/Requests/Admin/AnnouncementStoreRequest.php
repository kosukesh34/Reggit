<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'body' => 'required|string|max:2000',
            'is_active' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
        ];
    }
}
