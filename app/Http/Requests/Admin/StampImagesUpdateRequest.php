<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StampImagesUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stamp_images_data' => 'nullable|string',
            'stamp_empty_images_data' => 'nullable|string',
        ];
    }
}
