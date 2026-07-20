<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LiffConnectionSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'line_liff_id' => 'required|string|max:128',
            'line_liff_url' => 'required|url|max:2048',
            'line_friend_add_url' => 'nullable|url|max:2048',
            'line_channel_access_token' => 'nullable|string|max:1024',
            'line_channel_secret' => 'nullable|string|max:255',
        ];
    }

    /**
     * @return array{
     *     line_liff_id: string,
     *     line_liff_url: string,
     *     line_friend_add_url: string|null,
     *     line_channel_access_token: string|null,
     *     line_channel_secret: string|null,
     * }
     */
    public function toPayload(): array
    {
        return [
            'line_liff_id' => trim((string) $this->validated('line_liff_id')),
            'line_liff_url' => trim((string) $this->validated('line_liff_url')),
            'line_friend_add_url' => $this->validated('line_friend_add_url'),
            'line_channel_access_token' => $this->validated('line_channel_access_token'),
            'line_channel_secret' => $this->validated('line_channel_secret'),
        ];
    }
}
