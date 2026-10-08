<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementUpdateRequest extends FormRequest
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
            'image_url' => 'nullable|url|max:500',
            'image_files' => ['nullable', 'array', 'max:10'],
            'image_files.*' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'clear_images' => ['nullable', 'boolean'],
            'image_file' => 'nullable|image|max:2048',
            'is_pinned' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
        ];
    }
}
