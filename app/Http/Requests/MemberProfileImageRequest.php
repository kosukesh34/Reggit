<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class MemberProfileImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => '画像を選択してください',
            'image.image' => '画像ファイルを選択してください',
            'image.mimes' => 'JPEG・PNG・WebPのいずれかを選択してください',
            'image.max' => '画像は5MB以下にしてください',
        ];
    }

    public function imageFile(): UploadedFile
    {
        $image = $this->file('image');
        if (! $image instanceof UploadedFile) {
            throw ValidationException::withMessages(['image' => '画像を選択してください']);
        }

        return $image;
    }
}
