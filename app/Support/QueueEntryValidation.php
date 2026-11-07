<?php

namespace App\Support;

final class QueueEntryValidation
{
    /**
     * 会員アプリ（LIFF / Api）の順番待ち登録で共通のバリデーション規則。
     *
     * @return array<string, string>
     */
    public static function storeRules(): array
    {
        return [
            'party_size' => 'required|integer|min:1|max:20',
            'guest_name' => 'nullable|string|max:255',
            'guest_phone' => 'nullable|string|max:50',
        ];
    }
}
