<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProjectAppLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->isSystemAdmin() === true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'download_url' => 'nullable|url:https|max:2048',
        ];
    }

    /** @return array{download_url: string|null} */
    public function urls(): array
    {
        return ['download_url' => $this->validated('download_url')];
    }
}
