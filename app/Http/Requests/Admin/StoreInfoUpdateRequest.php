<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInfoUpdateRequest extends FormRequest
{
    /** Google マップでコピーした「緯度, 経度」（括弧付きも可） */
    private const COORDINATES = '/^\(?\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*\)?$/';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address' => 'nullable|string|max:255',
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-() ]+$/'],
            'coordinates' => ['nullable', 'string', 'regex:'.self::COORDINATES],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => '電話番号は数字とハイフンで入力してください。',
            'coordinates.regex' => '座標は「緯度, 経度」の形式で入力してください（例：33.5494, 130.4242）。',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            [$latitude, $longitude] = $this->coordinates();
            if ($latitude !== null && (abs($latitude) > 90 || abs($longitude) > 180)) {
                $validator->errors()->add('coordinates', '緯度は-90〜90、経度は-180〜180の範囲で入力してください。');
            }
        }];
    }

    /** @return array{address: ?string, phone: ?string, latitude: ?float, longitude: ?float} */
    public function info(): array
    {
        [$latitude, $longitude] = $this->coordinates();

        return [
            'address' => $this->validated('address'),
            'phone' => $this->validated('phone'),
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    /** @return array{0: ?float, 1: ?float} */
    private function coordinates(): array
    {
        if (preg_match(self::COORDINATES, (string) $this->input('coordinates'), $matches) !== 1) {
            return [null, null];
        }

        return [(float) $matches[1], (float) $matches[2]];
    }
}
