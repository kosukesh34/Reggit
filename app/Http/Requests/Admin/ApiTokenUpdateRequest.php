<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * API トークン更新リクエスト（バリデーション・ペイロード取得）
 *
 * クリーンアーキテクチャの Controller 層に属する。
 * バリデーション後、toPayload() で UseCase へ渡す入力データを返す。
 *
 * @see docs/CLEAN_ARCHITECTURE.md
 */
class ApiTokenUpdateRequest extends FormRequest
{
    private const NAME_MAX_LENGTH = 100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, string|array<int, string>> フィールド名 => ルール（文字列またはルール配列）
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:'.self::NAME_MAX_LENGTH,
            'expires_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * 検証済みデータを UseCase 用のペイロードに変換する
     *
     * @return array{name: string, expires_at: string|null, is_active: bool}
     */
    public function toPayload(): array
    {
        return [
            'name' => $this->validated('name'),
            'expires_at' => $this->validated('expires_at'),
            'is_active' => (bool) ($this->validated('is_active') ?? false),
        ];
    }
}
