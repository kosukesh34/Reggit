<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SurveyAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // 各回答の形式は質問ごとに異なるため、Domain の SurveyAnswerPolicy で検証する
        return ['answers' => ['required', 'array', 'max:50']];
    }

    /** @return array<array-key, mixed> */
    public function answers(): array
    {
        return (array) $this->validated('answers');
    }
}
